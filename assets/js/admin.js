(function () {
  const form = document.querySelector('[data-wbwan-settings-form]');
  if (!form) {
    return;
  }

  const previewRoot = document.querySelector('[data-wbwan-preview-root]');
  if (!previewRoot) {
    return;
  }

  const titleInput = form.querySelector('[data-wbwan-preview="title"]');
  const styleSelect = form.querySelector('[data-wbwan-preview="style"]');
  const titleEl = previewRoot.querySelector('[data-wbwan-preview-title]');
  const searchEl = previewRoot.querySelector('[data-wbwan-preview-search]');
  const countEls = previewRoot.querySelectorAll('[data-wbwan-preview-count]');

  const updatePreview = () => {
    if (titleInput && titleEl) {
      const value = titleInput.value.trim();
      titleEl.textContent = value || 'Shop Navigation';
    }

    const showSearch = !!form.querySelector('input[name="wbwan_settings[enable_search]"]')?.checked;
    if (searchEl) {
      searchEl.classList.toggle('wbwan-preview-hidden', !showSearch);
    }

    const showCounts = !!form.querySelector('input[name="wbwan_settings[show_counts]"]')?.checked;
    countEls.forEach((el) => {
      el.classList.toggle('wbwan-preview-hidden', !showCounts);
    });

    if (styleSelect) {
      previewRoot.classList.remove('wbwan-style-minimal', 'wbwan-style-bold', 'wbwan-style-glass');
      previewRoot.classList.add(`wbwan-style-${styleSelect.value || 'minimal'}`);
    }
  };

  form.addEventListener('input', updatePreview);
  form.addEventListener('change', updatePreview);
  updatePreview();
})();
