(function (blocks, element, i18n, components, blockEditor) {
  const el = element.createElement;
  const __ = i18n.__;
  const InspectorControls = blockEditor.InspectorControls;
  const PanelBody = components.PanelBody;
  const TextControl = components.TextControl;
  const Placeholder = components.Placeholder;

  blocks.registerBlockType('wbcom/wc-accordion-navigation', {
    title: __('Accordion Navigation', 'wb-accordion-navigation-for-woocommerce'),
    description: __('Insert WooCommerce accordion navigation with configurable sources.', 'wb-accordion-navigation-for-woocommerce'),
    icon: 'filter',
    category: 'widgets',
    keywords: ['woocommerce', 'accordion', 'navigation'],
    attributes: {
      title: { type: 'string', default: '' },
      taxonomies: { type: 'string', default: '' },
      collections: { type: 'string', default: '' }
    },
    supports: {
      html: false
    },
    edit: function (props) {
      const attrs = props.attributes;
      const setAttributes = props.setAttributes;
      const title = attrs.title || '';
      const taxonomies = attrs.taxonomies || '';
      const collections = attrs.collections || '';

      return el(
        element.Fragment,
        {},
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            {
              title: __('Accordion Settings', 'wb-accordion-navigation-for-woocommerce'),
              initialOpen: true
            },
            el(TextControl, {
              label: __('Title', 'wb-accordion-navigation-for-woocommerce'),
              value: title,
              onChange: function (value) {
                setAttributes({ title: value });
              }
            }),
            el(TextControl, {
              label: __('Taxonomies (comma separated)', 'wb-accordion-navigation-for-woocommerce'),
              help: __('Example: product_cat,product_tag,pa_brand', 'wb-accordion-navigation-for-woocommerce'),
              value: taxonomies,
              onChange: function (value) {
                setAttributes({ taxonomies: value });
              }
            }),
            el(TextControl, {
              label: __('Collections (comma separated)', 'wb-accordion-navigation-for-woocommerce'),
              help: __('Example: best_sellers,on_sale,top_rated', 'wb-accordion-navigation-for-woocommerce'),
              value: collections,
              onChange: function (value) {
                setAttributes({ collections: value });
              }
            })
          )
        ),
        el(
          Placeholder,
          {
            icon: 'filter',
            label: __('WB Accordion Navigation for WooCommerce', 'wb-accordion-navigation-for-woocommerce'),
            instructions: __('This block renders on the frontend using your plugin settings and optional overrides below.', 'wb-accordion-navigation-for-woocommerce')
          },
          el('p', {}, __('Title:', 'wb-accordion-navigation-for-woocommerce') + ' ' + (title || __('(default from plugin settings)', 'wb-accordion-navigation-for-woocommerce'))),
          el('p', {}, __('Taxonomies:', 'wb-accordion-navigation-for-woocommerce') + ' ' + (taxonomies || __('(plugin defaults)', 'wb-accordion-navigation-for-woocommerce'))),
          el('p', {}, __('Collections:', 'wb-accordion-navigation-for-woocommerce') + ' ' + (collections || __('(plugin defaults)', 'wb-accordion-navigation-for-woocommerce')))
        )
      );
    },
    save: function () {
      return null;
    }
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.components, window.wp.blockEditor);
