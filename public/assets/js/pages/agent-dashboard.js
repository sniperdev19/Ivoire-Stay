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
    progress: { pro: { count: 0, target: 5, reward: 0 }, business: { count: 0, target: 5, reward: 0 } },

    showScanner:  false,
    cameraError:  false,
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

    formatFcfa(v) {
      return new Intl.NumberFormat('fr-FR').format(v || 0) + ' FCFA';
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
          this.progress       = data.data.progress;
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
