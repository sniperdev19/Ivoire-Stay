/* ============================================================
   Ivoire Stay — Page d'accueil (vitrine/home.php)
   Carrousel "Destinations phares".
   Les données (items) sont injectées depuis PHP via x-data.
   ============================================================ */

/**
 * Composant Alpine du carrousel de destinations.
 * @param {Array} items  Liste des destinations (construite côté PHP).
 * Utilisé via x-data="homeCarousel(<?= json_encode($destinations) ?>)".
 */
function homeCarousel(items) {
  return {
    items: Array.isArray(items) ? items : [],
    current: 0,
    paused: false,
    timer: null,
    screenW: window.innerWidth, // réactif : recalcul des tailles au redimensionnement

    get total() { return this.items.length; },
    idx(offset) { return (this.current + offset + this.total) % this.total; },
    goTo(i) { this.current = i; },
    goNext() { this.current = this.idx(1); },
    goPrev() { this.current = this.idx(-1); },
    startAuto() {
      // Suivi de la largeur d'écran pour un carrousel responsive
      window.addEventListener('resize', () => { this.screenW = window.innerWidth; });
      this.timer = setInterval(() => { if (!this.paused) this.goNext(); }, 3800);
    },
    stopAuto() { this.paused = true; },
    resumeAuto() { this.paused = false; },

    styleFor(pos) {
      const w = this.screenW || window.innerWidth;
      let styles;
      if (w < 640) {
        // Mobile : seule la carte centrale, en pleine largeur ; côtés masqués
        styles = {
          center: 'width:86vw;max-width:340px;height:400px;z-index:10;opacity:1;transform:scale(1) translateY(0);',
          r1: 'width:0;height:400px;z-index:7;opacity:0;transform:scale(0.9);pointer-events:none;',
          l1: 'width:0;height:400px;z-index:7;opacity:0;transform:scale(0.9);pointer-events:none;',
          r2: 'width:0;opacity:0;pointer-events:none;',
          l2: 'width:0;opacity:0;pointer-events:none;',
        };
      } else if (w < 1024) {
        // Tablette : centrale + 1 carte de chaque côté
        styles = {
          center: 'width:300px;height:440px;z-index:10;opacity:1;transform:scale(1) translateY(0);',
          r1: 'width:200px;height:360px;z-index:7;opacity:0.82;transform:scale(0.93) translateY(14px);',
          l1: 'width:200px;height:360px;z-index:7;opacity:0.82;transform:scale(0.93) translateY(14px);',
          r2: 'width:0;opacity:0;pointer-events:none;',
          l2: 'width:0;opacity:0;pointer-events:none;',
        };
      } else {
        // Desktop : effet coverflow complet
        styles = {
          center: 'width:440px;height:520px;z-index:10;opacity:1;transform:scale(1) translateY(0);',
          r1: 'width:320px;height:440px;z-index:7;opacity:0.82;transform:scale(0.93) translateY(18px);',
          l1: 'width:320px;height:440px;z-index:7;opacity:0.82;transform:scale(0.93) translateY(18px);',
          r2: 'width:210px;height:320px;z-index:4;opacity:0.5;transform:scale(0.83) translateY(32px);',
          l2: 'width:210px;height:320px;z-index:4;opacity:0.5;transform:scale(0.83) translateY(32px);',
        };
      }
      return styles[pos] ?? 'width:0;opacity:0;';
    },
    radiusFor(pos) {
      return pos === 'center' ? 'border-radius:28px;' : pos.includes('1') ? 'border-radius:22px;' : 'border-radius:16px;';
    },
    shadowFor(pos) {
      return pos === 'center' ? 'box-shadow:0 40px 80px rgba(0,0,0,0.55);' : pos.includes('1') ? 'box-shadow:0 20px 48px rgba(0,0,0,0.35);' : 'box-shadow:0 8px 24px rgba(0,0,0,0.2);';
    },
  };
}
