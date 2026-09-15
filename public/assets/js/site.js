/* ---------------------------------------------------------------------------
   Public site behaviour.

   Deliberately small and dependency-free: the mobile menu, the gallery
   thumbnails, and a guard that stops a double-click sending an enquiry twice.
   Everything here is an enhancement — the site works with JavaScript off.
   --------------------------------------------------------------------------- */

(function () {
  'use strict';

  // --- Mobile navigation ---------------------------------------------------
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('site-nav');

  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    // Close the menu when the viewport grows past the mobile breakpoint,
    // otherwise it stays stuck open in the desktop layout.
    window.addEventListener('resize', function () {
      if (window.innerWidth > 800 && nav.classList.contains('is-open')) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // --- Property gallery ----------------------------------------------------
  var mainImage = document.querySelector('[data-gallery-main]');
  if (mainImage) {
    document.querySelectorAll('[data-gallery-thumb]').forEach(function (thumb) {
      thumb.addEventListener('click', function (event) {
        event.preventDefault();
        mainImage.src = thumb.getAttribute('data-full') || thumb.src;
        mainImage.alt = thumb.alt;
      });
    });
  }

  // --- Double-submit guard -------------------------------------------------
  document.querySelectorAll('form[data-guard]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var button = form.querySelector('button[type="submit"], input[type="submit"]');
      if (!button || button.dataset.busy) return;

      // Re-enable after a moment: if the browser restores the page from cache
      // (back button), a permanently disabled button would strand the user.
      button.dataset.busy = '1';
      button.disabled = true;
      var label = button.textContent;
      button.textContent = 'Sending…';

      setTimeout(function () {
        button.disabled = false;
        button.textContent = label;
        delete button.dataset.busy;
      }, 8000);
    });
  });
})();
