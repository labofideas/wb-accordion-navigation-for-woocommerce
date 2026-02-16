(function () {
  'use strict';

  function initAccordion(root) {
    var storageKey = 'wbwan-open-state';
    var searchInput = root.querySelector('[data-wbwan="search"]');
    var clearFiltersButton = root.querySelector('[data-wbwan="clear-filters"]');
    var toggles = root.querySelectorAll('[data-wbwan="toggle"]');
    var filterLinks = root.querySelectorAll('[data-wbwan-filter-link="1"]');
    var collectionLinks = root.querySelectorAll('[data-wbwan-collection-link="1"]');
    var minPriceInput = root.querySelector('[data-wbwan="min-price"]');
    var maxPriceInput = root.querySelector('[data-wbwan="max-price"]');
    var inStockInput = root.querySelector('[data-wbwan="in-stock"]');
    var sortInput = root.querySelector('[data-wbwan="sort"]');
    var productsContainer = document.querySelector('.woocommerce ul.products, ul.products');
    var config = (typeof window !== 'undefined' && window.wbwanSettings) ? window.wbwanSettings : {};
    var rememberState = !!config.rememberState;
    var ajaxEnabled = root.getAttribute('data-wbwan-ajax') === '1';
    var restUrl = config.restUrl || (window.location.origin + '/wp-json/wbwan/v1/filter');
    var analyticsEnabled = !!config.analyticsEnabled;
    var analyticsUrl = config.analyticsUrl || (window.location.origin + '/wp-json/wbwan/v1/analytics');
    var activeTaxFilters = {};
    var activeCollection = '';
    var activePage = 1;
    var minPrice = '';
    var maxPrice = '';
    var inStockOnly = false;
    var activeSort = 'default';
    var debounceTimer = null;

    function parseHtml(html) {
      var container = document.createElement('div');
      container.innerHTML = html;
      return container.firstElementChild;
    }

    function hasAnyFilterState() {
      var hasTaxFilter = Object.keys(activeTaxFilters).some(function (taxonomy) {
        return Array.isArray(activeTaxFilters[taxonomy]) && activeTaxFilters[taxonomy].length > 0;
      });
      return hasTaxFilter || !!activeCollection || !!minPrice || !!maxPrice || inStockOnly || activePage > 1;
    }

    function parseStateFromUrl() {
      var params = new URLSearchParams(window.location.search);
      activeTaxFilters = {};
      activeCollection = params.get('wbf_collection') || '';
      activePage = Math.max(1, parseInt(params.get('wbf_page') || '1', 10) || 1);
      minPrice = params.get('wbf_min_price') || '';
      maxPrice = params.get('wbf_max_price') || '';
      inStockOnly = params.get('wbf_in_stock') === '1';
      activeSort = params.get('wbf_sort') || 'default';

      params.forEach(function (value, key) {
        if (key.indexOf('wbf_tax_') !== 0) {
          return;
        }
        var taxonomy = key.replace('wbf_tax_', '');
        var ids = value
          .split(',')
          .map(function (v) { return parseInt(v, 10); })
          .filter(function (v) { return !Number.isNaN(v) && v > 0; });
        if (ids.length) {
          activeTaxFilters[taxonomy] = ids;
        }
      });
    }

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
      if (activePage > 1) {
        params.set('paged', String(activePage));
      }
      if (minPrice) {
        params.set('min_price', String(minPrice));
      }
      if (maxPrice) {
        params.set('max_price', String(maxPrice));
      }
      if (inStockOnly) {
        params.set('in_stock', '1');
      }
      if (activeSort && activeSort !== 'default') {
        params.set('sort', activeSort);
      }
      return params.toString();
    }

    function syncUrl(pushState) {
      var params = new URLSearchParams(window.location.search);

      Array.from(params.keys()).forEach(function (key) {
        if (key.indexOf('wbf_') === 0) {
          params.delete(key);
        }
      });

      Object.keys(activeTaxFilters).forEach(function (taxonomy) {
        if (!Array.isArray(activeTaxFilters[taxonomy]) || !activeTaxFilters[taxonomy].length) {
          return;
        }
        params.set('wbf_tax_' + taxonomy, activeTaxFilters[taxonomy].join(','));
      });

      if (activeCollection) {
        params.set('wbf_collection', activeCollection);
      }
      if (activePage > 1) {
        params.set('wbf_page', String(activePage));
      }
      if (minPrice) {
        params.set('wbf_min_price', String(minPrice));
      }
      if (maxPrice) {
        params.set('wbf_max_price', String(maxPrice));
      }
      if (inStockOnly) {
        params.set('wbf_in_stock', '1');
      }
      if (activeSort && activeSort !== 'default') {
        params.set('wbf_sort', activeSort);
      }

      var nextUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
      if (pushState) {
        window.history.pushState({ wbwan: true }, '', nextUrl);
      } else {
        window.history.replaceState({ wbwan: true }, '', nextUrl);
      }
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
        clearFiltersButton.classList.toggle('is-hidden', !hasAnyFilterState());
      }

      if (minPriceInput) {
        minPriceInput.value = minPrice;
      }
      if (maxPriceInput) {
        maxPriceInput.value = maxPrice;
      }
      if (inStockInput) {
        inStockInput.checked = inStockOnly;
      }
      if (sortInput) {
        sortInput.value = activeSort || 'default';
      }
    }

    function updateResultFragments(payload) {
      var countNode = document.querySelector('.woocommerce-result-count');
      var paginationNode = document.querySelector('.woocommerce-pagination');
      var productsNode = document.querySelector('.woocommerce ul.products, ul.products, .woocommerce-info');

      if (payload && typeof payload.countHtml === 'string') {
        if (payload.countHtml) {
          var newCount = parseHtml(payload.countHtml);
          if (newCount) {
            if (countNode) {
              countNode.replaceWith(newCount);
            } else if (productsNode && productsNode.parentNode) {
              productsNode.parentNode.insertBefore(newCount, productsNode);
            }
          }
        } else if (countNode) {
          countNode.remove();
        }
      }

      if (payload && typeof payload.paginationHtml === 'string') {
        if (payload.paginationHtml) {
          var newPagination = parseHtml(payload.paginationHtml);
          if (newPagination) {
            if (paginationNode) {
              paginationNode.replaceWith(newPagination);
            } else if (productsNode && productsNode.parentNode) {
              productsNode.parentNode.appendChild(newPagination);
            }
          }
        } else if (paginationNode) {
          paginationNode.remove();
        }
      }
    }

    function trackAnalytics(foundPosts) {
      if (!analyticsEnabled || !analyticsUrl) {
        return;
      }

      window.fetch(analyticsUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          taxFilters: activeTaxFilters,
          collection: activeCollection,
          sort: activeSort,
          foundPosts: Number(foundPosts || 0),
          inStockOnly: inStockOnly
        })
      }).catch(function () {
        // Analytics is best-effort only.
      });
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
          updateResultFragments(payload);
          trackAnalytics(payload.foundPosts);
        })
        .catch(function () {
          // Silently ignore AJAX errors and keep current product list.
        })
        .finally(function () {
          root.classList.remove('is-loading');
        });
    }

    function scheduleFetch() {
      window.clearTimeout(debounceTimer);
      debounceTimer = window.setTimeout(function () {
        syncUrl(true);
        fetchFilteredProducts();
      }, 320);
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
      parseStateFromUrl();
      updateActiveUi();

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

          activePage = 1;
          updateActiveUi();
          scheduleFetch();
        });
      });

      collectionLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
          event.preventDefault();
          var collection = link.getAttribute('data-wbwan-collection') || '';
          activeCollection = activeCollection === collection ? '' : collection;
          activePage = 1;
          updateActiveUi();
          scheduleFetch();
        });
      });

      if (clearFiltersButton) {
        clearFiltersButton.addEventListener('click', function () {
          activeTaxFilters = {};
          activeCollection = '';
          minPrice = '';
          maxPrice = '';
          inStockOnly = false;
          activeSort = 'default';
          activePage = 1;
          updateActiveUi();
          scheduleFetch();
        });
      }

      if (minPriceInput) {
        minPriceInput.addEventListener('input', function () {
          minPrice = minPriceInput.value.trim();
          activePage = 1;
          scheduleFetch();
        });
      }

      if (maxPriceInput) {
        maxPriceInput.addEventListener('input', function () {
          maxPrice = maxPriceInput.value.trim();
          activePage = 1;
          scheduleFetch();
        });
      }

      if (inStockInput) {
        inStockInput.addEventListener('change', function () {
          inStockOnly = !!inStockInput.checked;
          activePage = 1;
          scheduleFetch();
        });
      }

      if (sortInput) {
        sortInput.addEventListener('change', function () {
          activeSort = (sortInput.value || 'default').trim();
          activePage = 1;
          scheduleFetch();
        });
      }

      document.addEventListener('click', function (event) {
        var pageLink = event.target.closest('.woocommerce-pagination [data-wbwan-page]');
        if (!pageLink) {
          return;
        }
        event.preventDefault();
        var nextPage = parseInt(pageLink.getAttribute('data-wbwan-page') || '1', 10);
        if (Number.isNaN(nextPage) || nextPage < 1) {
          return;
        }
        activePage = nextPage;
        scheduleFetch();
      });

      window.addEventListener('popstate', function () {
        parseStateFromUrl();
        updateActiveUi();
        fetchFilteredProducts();
      });

      if (hasAnyFilterState()) {
        syncUrl(false);
        fetchFilteredProducts();
      }
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-wbwan="accordion"]').forEach(initAccordion);
  });
})();
