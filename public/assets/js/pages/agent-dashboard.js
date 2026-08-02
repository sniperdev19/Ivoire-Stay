/* ============================================================
   Afristay — Tableau de bord agent commercial
   Scan de QR code via getUserMedia + jsQR (vendorisé, pas de dépendance npm).
   ============================================================ */

function agentDashboardPage(baseUrl) {
  return {
    agent: {},
    establishments: [],
    referrals: [],
    payouts: [],
    bonusAwards: [],
    progress: { pro: { count: 0, target: 5, reward: 0 }, business: { count: 0, target: 5, reward: 0 } },
    bonuses: {},

    activeView: 'home', // home | establishments | history | ranking (barre de navigation basse)
    ranking: [],
    rankingLoaded: false,

    showScanner:  false,
    cameraError:  false,
    showManualEntry: false,
    manualToken:  '',
    scanFeedback: '',
    scanOk:       false,
    _stream:      null,
    _scanning:    false,

    init() {
      const token = localStorage.getItem('agent_token');
      if (!token) {
        window.location.href = baseUrl + '/agent/login';
        return;
      }
      const cached = localStorage.getItem('agent');
      if (cached) { try { this.agent = JSON.parse(cached); } catch (_) {} }
      this.load();
    },

    get initials() {
      const words = (this.agent.nom || '').trim().split(/\s+/).filter(Boolean);
      if (!words.length) return 'AS';
      return words.slice(0, 2).map(w => w[0].toUpperCase()).join('');
    },

    formatFcfa(v) {
      return new Intl.NumberFormat('fr-FR').format(v || 0) + ' FCFA';
    },

    formatDate(v) {
      if (!v) return '';
      const d = new Date(String(v).replace(' ', 'T'));
      if (isNaN(d)) return '';
      return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
    },

    setView(view) {
      this.activeView = view;
      if (view === 'ranking' && !this.rankingLoaded) this.loadRanking();
    },

    // Historique = fusion des 3 flux (rattachements, versements, primes gagnées),
    // triée par date décroissante — plutôt que 3 sections séparées, pour donner
    // une vue chronologique unique de l'activité de l'agent.
    get history() {
      const bonusLabels = {
        first_to_5:      { icon: '🏁', label: 'Prime "Premier arrivé"' },
        first_business:  { icon: '💎', label: 'Prime "Premier client Business"' },
        fast_conversion: { icon: '⚡', label: 'Prime "Conversion rapide"' },
        monthly_top:     { icon: '🏆', label: 'Prime "Top agent du mois"' },
      };

      const items = [
        ...this.referrals.map(r => ({
          kind: 'referral',
          icon: '🔗',
          date: r.created_at,
          title: 'Établissement rattaché',
          desc: r.establishment_name,
          amount: null,
        })),
        ...this.payouts.map(p => ({
          kind: 'payout',
          icon: '💳',
          date: p.created_at,
          title: (p.label || (p.plan === 'pro' ? 'Versement Pro' : 'Versement Business')),
          desc: p.status === 'pending' ? 'En attente' : (p.status === 'paid' ? 'Payé' : 'Rejeté'),
          amount: p.amount,
        })),
        ...this.bonusAwards.map(b => ({
          kind: 'bonus',
          icon: (bonusLabels[b.type]?.icon) || '🎁',
          date: b.awarded_at,
          title: (bonusLabels[b.type]?.label) || 'Prime',
          desc: 'Prime décernée',
          amount: b.amount,
        })),
      ];

      return items.sort((a, b) => new Date(String(b.date).replace(' ', 'T')) - new Date(String(a.date).replace(' ', 'T')));
    },

    async loadRanking() {
      try {
        const res = await fetch(baseUrl + '/api/agent/ranking', { headers: this.authHeaders() });
        const data = await res.json();
        if (data.success) {
          this.ranking = data.data.ranking;
          this.rankingLoaded = true;
        }
      } catch (_) { /* réseau indisponible — on retentera à la prochaine ouverture de l'onglet */ }
    },

    authHeaders() {
      return {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + localStorage.getItem('agent_token'),
      };
    },

    async load() {
      try {
        const res = await fetch(baseUrl + '/api/agent/me', { headers: this.authHeaders() });
        const data = await res.json();
        if (res.status === 401) { this.logout(); return; }
        if (data.success) {
          this.agent          = data.data.agent;
          this.establishments = data.data.establishments;
          this.referrals      = data.data.referrals;
          this.payouts        = data.data.payouts;
          this.bonusAwards    = data.data.bonusAwards || [];
          this.progress       = data.data.progress;
          this.bonuses        = data.data.bonuses || {};
          localStorage.setItem('agent', JSON.stringify(this.agent));
        }
      } catch (_) { /* réseau indisponible — on garde les données déjà chargées */ }
    },

    logout() {
      fetch(baseUrl + '/api/agent/logout', { method: 'POST', headers: this.authHeaders() }).catch(() => {});
      localStorage.removeItem('agent_token');
      localStorage.removeItem('agent');
      window.location.href = baseUrl + '/agent/login';
    },

    async openScanner() {
      this.showScanner  = true;
      this.cameraError  = false;
      this.showManualEntry = false;
      this.scanFeedback = '';
      this.manualToken  = '';

      await this.$nextTick();

      try {
        this._stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        this.$refs.video.srcObject = this._stream;
        this._scanning = true;
        requestAnimationFrame(() => this._scanLoop());
      } catch (_) {
        this.cameraError = true;
      }
    },

    _scanLoop() {
      if (!this._scanning) return;
      const video = this.$refs.video;

      if (video && video.readyState === video.HAVE_ENOUGH_DATA) {
        const canvas = document.createElement('canvas');
        canvas.width  = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = window.jsQR ? window.jsQR(imageData.data, imageData.width, imageData.height) : null;

        if (code && code.data) {
          this.submitScan(code.data);
          return;
        }
      }
      requestAnimationFrame(() => this._scanLoop());
    },

    closeScanner() {
      this._scanning = false;
      if (this._stream) {
        this._stream.getTracks().forEach(t => t.stop());
        this._stream = null;
      }
      this.showScanner = false;
    },

    async submitScan(qrToken) {
      qrToken = (qrToken || '').trim();
      if (!qrToken) return;
      this._scanning = false;

      try {
        const res = await fetch(baseUrl + '/api/agent/scan', {
          method:  'POST',
          headers: this.authHeaders(),
          body:    JSON.stringify({ qr_token: qrToken }),
        });
        const data = await res.json();
        this.scanOk = !!data.success;
        this.scanFeedback = data.message || (data.success ? 'Établissement rattaché.' : 'QR code invalide.');

        if (data.success) {
          await this.load();
          setTimeout(() => this.closeScanner(), 1500);
        } else {
          setTimeout(() => { this._scanning = true; requestAnimationFrame(() => this._scanLoop()); }, 1500);
        }
      } catch (_) {
        this.scanOk = false;
        this.scanFeedback = 'Erreur réseau. Réessayez.';
      }
    },
  };
}
