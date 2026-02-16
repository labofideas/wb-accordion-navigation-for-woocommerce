(function () {
  'use strict';

  function initAccordion(root) {
    var storageKey = 'wbwan-open-state';
    var searchInput = root.querySelector('[data-wbwan="search"]');
    var clearFiltersButton = root.querySelector('[data-wbwan="clear-filters"]');
    var toggles = root.querySelectorAll('[data-wbwan="toggle"]');
    var filterLinks = root.querySelectorAll('[data-wbwan-filter-link="1"]');
    var collectionLinks = root.querySelectorAll('[data-wbwan-collection-link="1"]');
    var productsContainer = document.querySelector('.woocommerce ul.products, ul.products');
    var rememberState = typeof wbwanSettings !== 'undefined' && !!wbwanSettings.rememberState;
    var ajaxEnabled = typeof wbwanSettings !== 'undefined' && !!wbwanSettings.ajaxFiltering && root.getAttribute('data-wbwan-ajax') === '1';
    var restUrl = typeof wbwanSettings !== 'undefined' ? wbwanSettings.restUrl : '';
    var activeTaxFilters = {};
    var activeCollection = '';

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

    function buildQueryString() {
      var params = new URLSearchParams();
      Object.keys(activeTaxFilters).forEach(function (taxonomy) {
        if (!Array.isArray(activeTaxFilters[taxonomy])) {
          return;
        }
        activeTaxFilters[taxonomy].forEach(function (termId) {
          params.append('tax[' + taxonomy + '][]', String(termId));
        });
      });
      if (activeCollection) {
        params.set('collection', activeCollection);
      }
      return params.toString();
    }

    function updateActiveUi() {
      filterLinks.forEach(function (link) {
        var taxonomy = link.getAttribute('data-wbwan-taxonomy');
        var termId = parseInt(link.getAttribute('data-wbwan-term-id') || '0', 10);
        var isActive = taxonomy && Array.isArray(activeTaxFilters[taxonomy]) && activeTaxFilters[taxonomy].indexOf(termId) !== -1;
        link.classList.toggle('is-active', !!isActive);
      });

      collectionLinks.forEach(function (link) {
        var collection = link.getAttribute('data-wbwan-collection');
        link.classList.toggle('is-active', !!collection && collection === activeCollection);
      });

      if (clearFiltersButton) {
        var hasTaxFilter = Object.keys(activeTaxFilters).some(function (taxonomy) {
          return Array.isArray(activeTaxFilters[taxonomy]) && activeTaxFilters[taxonomy].length > 0;
        });
        clearFiltersButton.classList.toggle('is-hidden', !hasTaxFilter && !activeCollection);
      }
    }

    function fetchFilteredProducts() {
      if (!ajaxEnabled || !restUrl || !productsContainer) {
        return;
      }

      var query = buildQueryString();
      var url = restUrl + (query ? '?' + query : '');
      root.classList.add('is-loading');

      window.fetch(url, { credentials: 'same-origin' })
        .then(function (response) {
          return response.json();
        })
        .then(function (payload) {
          if (!payload || typeof payload.html !== 'string') {
            return;
          }
          productsContainer.outerHTML = payload.html;
          productsContainer = document.querySelector('.woocommerce ul.products, ul.products');
        })
        .catch(function () {
          // Silently ignore AJAX errors and keep current product list.
        })
        .finally(function () {
          root.classList.remove('is-loading');
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

    if (ajaxEnabled) {
      filterLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
          event.preventDefault();
          var taxonomy = link.getAttribute('data-wbwan-taxonomy');
          var termId = parseInt(link.getAttribute('data-wbwan-term-id') || '0', 10);
          if (!taxonomy || !termId) {
            return;
          }

          if (!Array.isArray(activeTaxFilters[taxonomy])) {
            activeTaxFilters[taxonomy] = [];
          }

          var index = activeTaxFilters[taxonomy].indexOf(termId);
          if (index === -1) {
            activeTaxFilters[taxonomy].push(termId);
          } else {
            activeTaxFilters[taxonomy].splice(index, 1);
            if (activeTaxFilters[taxonomy].length === 0) {
              delete activeTaxFilters[taxonomy];
            }
          }

          updateActiveUi();
          fetchFilteredProducts();
        });
      });

      collectionLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
          event.preventDefault();
          var collection = link.getAttribute('data-wbwan-collection') || '';
          activeCollection = activeCollection === collection ? '' : collection;
          updateActiveUi();
          fetchFilteredProducts();
        });
      });

      if (clearFiltersButton) {
        clearFiltersButton.addEventListener('click', function () {
          activeTaxFilters = {};
          activeCollection = '';
          updateActiveUi();
          fetchFilteredProducts();
        });
      }

      updateActiveUi();
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-wbwan="accordion"]').forEach(initAccordion);
  });
})();
