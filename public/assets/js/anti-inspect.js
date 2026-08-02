// Dissuasion basique de la copie de contenu sur la vitrine publique.
// Ne bloque PAS l'accès aux outils de développement : n'importe qui peut
// désactiver JS, ouvrir la vue source (Ctrl+U côté navigateur, pas
// interceptable), ou passer par le menu Chrome > Plus d'outils > Outils de
// développement. C'est une friction, pas une protection réelle.
(function () {
  document.addEventListener("contextmenu", function (e) {
    e.preventDefault();
  });

  document.addEventListener("dragstart", function (e) {
    e.preventDefault();
  });

  document.addEventListener("selectstart", function (e) {
    var tag = e.target.tagName;
    if (tag === "INPUT" || tag === "TEXTAREA") return;
    e.preventDefault();
  });

  document.addEventListener("keydown", function (e) {
    var key = e.key;
    if (
      key === "F12" ||
      (e.ctrlKey &&
        e.shiftKey &&
        ["I", "i", "J", "j", "C", "c"].includes(key)) ||
      (e.metaKey && e.altKey && ["I", "i", "J", "j", "C", "c"].includes(key)) ||
      (e.ctrlKey && ["U", "u"].includes(key)) ||
      (e.metaKey && ["U", "u"].includes(key))
    ) {
      e.preventDefault();
    }
  });
})();
