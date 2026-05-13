/* =========================================================
   app.js — JS global : confirmation avant suppression/désactivation,
             fermeture automatique des alertes flash
   Wacdo Back-office — Bloc 2
   ========================================================= */

'use strict';

document.addEventListener('DOMContentLoaded', function () {

    /* ------------------------------------------------------------------
       Confirmation avant suppression ou désactivation
       Usage : ajouter data-confirm="Votre message" sur tout lien ou bouton
    ------------------------------------------------------------------ */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            var message = el.dataset.confirm || 'Confirmer cette action ?';
            if (!window.confirm(message)) {
                e.preventDefault();
            }
        });
    });

    /* ------------------------------------------------------------------
       Fermeture des alertes flash
       Usage : ajouter un <button class="alert-close" aria-label="Fermer">×</button>
               à l'intérieur de chaque .alert
    ------------------------------------------------------------------ */
    document.querySelectorAll('.alert-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var alert = btn.closest('.alert');
            if (alert) {
                alert.remove();
            }
        });
    });

});
