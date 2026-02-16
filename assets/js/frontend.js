(function () {
  'use strict';

  function initAccordion(root) {
    var storageKey = 'wbwan-open-state';
    var searchInput = root.querySelector('[data-wbwan="search"]');
    var toggles = root.querySelectorAll('[data-wbwan="toggle"]');
    var rememberState = typeof wbwanSettings !== 'undefined' && !!wbwanSettings.rememberState;

    function persistState() {
      if (!rememberState || !window.localStorage) {
        return;
      }

      var openTerms = [];
      root.querySelectorAll('.wbwan-item.is-open').forEach(function (item) {
        var id = item.getAttribute('data-wbwan-term');
        if (id) {
          openTerms.push(id);
        }
      });

      window.localStorage.setItem(storageKey, JSON.stringify(openTerms));
    }

    function restoreState() {
      if (!rememberState || !window.localStorage) {
        return;
      }

      var value = window.localStorage.getItem(storageKey);
      if (!value) {
        return;
      }

      try {
        var openTerms = JSON.parse(value);
        if (!Array.isArray(openTerms)) {
          return;
        }

        openTerms.forEach(function (id) {
          var item = root.querySelector('.wbwan-item[data-wbwan-term="' + id + '"]');
          if (item) {
            item.classList.add('is-open');
            var btn = item.querySelector('[data-wbwan="toggle"]');
            if (btn) {
              btn.setAttribute('aria-expanded', 'true');
            }
          }
        });
      } catch (e) {
        // Ignore malformed localStorage payload.
      }
    }

    restoreState();

    toggles.forEach(function (button) {
      button.addEventListener('click', function () {
        var item = button.closest('.wbwan-item');
        if (!item) {
          return;
        }

        var isOpen = item.classList.toggle('is-open');
        button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        persistState();
      });
    });

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        var query = searchInput.value.toLowerCase().trim();
        root.querySelectorAll('.wbwan-item').forEach(function (item) {
          var text = item.textContent.toLowerCase();
          item.classList.toggle('is-hidden', query.length > 0 && text.indexOf(query) === -1);
        });
      });
    }

    var mobileToggle = root.querySelector('[data-wbwan="mobile-toggle"]');
    var mobileClose = root.querySelector('[data-wbwan="mobile-close"]');

    if (mobileToggle) {
      mobileToggle.addEventListener('click', function () {
        root.classList.add('is-open');
      });
    }

    if (mobileClose) {
      mobileClose.addEventListener('click', function () {
        root.classList.remove('is-open');
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-wbwan="accordion"]').forEach(initAccordion);
  });
})();
