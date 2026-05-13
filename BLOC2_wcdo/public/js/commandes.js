/* =========================================================
   commandes.js — JS spécifique à la liste des commandes :
                  rafraîchissement automatique toutes les 30 secondes
                  (UI uniquement — aucune logique métier)
   Wacdo Back-office — Bloc 2

   Activation : inclure ce script uniquement sur la vue des commandes.
   Le rafraîchissement ne s'active que si #commandes-list est présent.
   ========================================================= */

'use strict';

(function () {

    var REFRESH_INTERVAL = 30 * 1000; // 30 000 ms = 30 secondes

    function initAutoRefresh() {
        var liste = document.getElementById('commandes-list');
        if (!liste) {
            return; // pas sur la page commandes, ne rien faire
        }

        setInterval(function () {
            window.location.reload();
        }, REFRESH_INTERVAL);
    }

    document.addEventListener('DOMContentLoaded', initAutoRefresh);

}());
