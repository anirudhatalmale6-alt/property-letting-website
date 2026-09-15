/* ---------------------------------------------------------------------------
   Admin behaviour. No framework, no build step — three small conveniences.
   --------------------------------------------------------------------------- */

(function () {
  'use strict';

  // --- Mobile sidebar ------------------------------------------------------
  var burger = document.querySelector('.adm-burger');
  var nav = document.getElementById('adm-nav');

  if (burger && nav) {
    burger.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // --- Confirm destructive actions -----------------------------------------
  // Any form carrying data-confirm asks first. Archiving does not use this —
  // it is reversible — but deleting does.
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });

  // --- Warn before leaving a half-finished edit ----------------------------
  var tracked = document.querySelector('form[data-dirty-warn]');
  if (tracked) {
    var dirty = false;

    tracked.addEventListener('input', function () { dirty = true; });
    tracked.addEventListener('change', function () { dirty = true; });
    tracked.addEventListener('submit', function () { dirty = false; });

    window.addEventListener('beforeunload', function (event) {
      if (!dirty) return;
      event.preventDefault();
      // Browsers show their own wording; returning a value is what triggers it.
      event.returnValue = '';
    });
  }
})();
