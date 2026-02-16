<?php
/**
 * Admin settings.
 *
 * @package WBCOM\WBWAN
 */

namespace WBCOM\WBWAN;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {
	/**
	 * Option key.
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'wbwan_settings';

	/**
	 * Setup hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_wbwan_export_settings', array( $this, 'handle_export_settings' ) );
		add_action( 'admin_post_wbwan_import_settings', array( $this, 'handle_import_settings' ) );
	}

	/**
	 * Enqueue settings page assets.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'woocommerce_page_wbwan-settings' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'wbwan-admin',
			WBWAN_URL . 'assets/css/admin.css',
			array(),
			WBWAN_VERSION
		);

		wp_enqueue_script(
			'wbwan-admin',
			WBWAN_URL . 'assets/js/admin.js',
			array(),
			WBWAN_VERSION,
			true
		);
	}

	/**
	 * Default settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		$defaults = array(
			'taxonomies'          => array( 'product_cat' ),
			'collections'         => array( 'best_sellers', 'on_sale' ),
			'default_title'       => __( 'Shop Navigation', 'wb-accordion-navigation-for-woocommerce' ),
			'style_preset'        => 'minimal',
			'show_counts'         => 1,
			'hide_empty'          => 1,
			'auto_expand_current' => 1,
			'enable_search'       => 1,
			'remember_state'      => 1,
			'mobile_offcanvas'    => 1,
			'enable_ajax_filtering' => 0,
			'enable_ajax_sorting' => 1,
			'filter_logic'        => 'and',
			'enable_filter_analytics' => 1,
			'inject_shop_sidebar' => 0,
			'cache_ttl'           => 15,
			'menu_id'             => 0,
		);

		return apply_filters( 'wbwan_default_settings', $defaults );
	}

	/**
	 * Get merged settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function get(): array {
		$settings = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( is_array( $settings ) ? $settings : array(), self::defaults() );
	}

	/**
	 * Register submenu.
	 */
	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			esc_html__( 'Accordion Navigation', 'wb-accordion-navigation-for-woocommerce' ),
			esc_html__( 'Accordion Navigation', 'wb-accordion-navigation-for-woocommerce' ),
			'manage_woocommerce',
			'wbwan-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register fields.
	 */
	public function register_settings(): void {
		register_setting(
			'wbwan_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);

		add_settings_section(
			'wbwan_section_main',
			esc_html__( 'Navigation Sources', 'wb-accordion-navigation-for-woocommerce' ),
			'__return_empty_string',
			'wbwan-settings'
		);

		add_settings_field(
			'taxonomies',
			esc_html__( 'Taxonomies', 'wb-accordion-navigation-for-woocommerce' ),
			array( $this, 'render_taxonomies_field' ),
			'wbwan-settings',
			'wbwan_section_main'
		);

		add_settings_field(
			'collections',
			esc_html__( 'Dynamic Collections', 'wb-accordion-navigation-for-woocommerce' ),
			array( $this, 'render_collections_field' ),
			'wbwan-settings',
			'wbwan_section_main'
		);

		add_settings_field(
			'menu_id',
			esc_html__( 'WordPress Menu', 'wb-accordion-navigation-for-woocommerce' ),
			array( $this, 'render_menu_field' ),
			'wbwan-settings',
			'wbwan_section_main'
		);

		add_settings_field(
			'default_title',
			esc_html__( 'Default accordion title', 'wb-accordion-navigation-for-woocommerce' ),
			array( $this, 'render_default_title_field' ),
			'wbwan-settings',
			'wbwan_section_main'
		);

		add_settings_section(
			'wbwan_section_ux',
			esc_html__( 'UX & Display', 'wb-accordion-navigation-for-woocommerce' ),
			'__return_empty_string',
			'wbwan-settings'
		);

		$this->register_toggle_field( 'show_counts', esc_html__( 'Show product counts', 'wb-accordion-navigation-for-woocommerce' ) );
		$this->register_toggle_field( 'hide_empty', esc_html__( 'Hide empty terms', 'wb-accordion-navigation-for-woocommerce' ) );
		$this->register_toggle_field( 'auto_expand_current', esc_html__( 'Auto-expand current term path', 'wb-accordion-navigation-for-woocommerce' ) );
		$this->register_toggle_field( 'enable_search', esc_html__( 'Enable search within accordion', 'wb-accordion-navigation-for-woocommerce' ) );
		$this->register_toggle_field( 'remember_state', esc_html__( 'Remember open accordion state', 'wb-accordion-navigation-for-woocommerce' ) );
		$this->register_toggle_field( 'mobile_offcanvas', esc_html__( 'Enable mobile off-canvas mode', 'wb-accordion-navigation-for-woocommerce' ) );
		$this->register_toggle_field( 'inject_shop_sidebar', esc_html__( 'Auto-inject into WooCommerce sidebar', 'wb-accordion-navigation-for-woocommerce' ) );

		add_settings_field(
			'cache_ttl',
			esc_html__( 'Taxonomy cache TTL (minutes)', 'wb-accordion-navigation-for-woocommerce' ),
			array( $this, 'render_cache_ttl_field' ),
			'wbwan-settings',
			'wbwan_section_ux'
		);
	}

	/**
	 * Register reusable toggle.
	 *
	 * @param string $key Field key.
	 * @param string $label Field label.
	 */
	private function register_toggle_field( string $key, string $label ): void {
		add_settings_field(
			$key,
			$label,
			array( $this, 'render_toggle_field' ),
			'wbwan-settings',
			'wbwan_section_ux',
			array( 'key' => $key )
		);
	}

	/**
	 * Sanitize option payload.
	 *
	 * @param array<string,mixed> $input Raw data.
	 * @return array<string,mixed>
	 */
	public function sanitize( array $input ): array {
		$defaults = self::defaults();
		$clean    = $defaults;

		$available_taxonomies = array_keys( self::available_taxonomies() );
		$input_taxonomies     = isset( $input['taxonomies'] ) && is_array( $input['taxonomies'] ) ? wp_unslash( $input['taxonomies'] ) : array();
		$clean['taxonomies']  = array_values( array_intersect( $available_taxonomies, array_map( 'sanitize_key', $input_taxonomies ) ) );

		$available_collections = array_keys( self::available_collections() );
		$input_collections     = isset( $input['collections'] ) && is_array( $input['collections'] ) ? wp_unslash( $input['collections'] ) : array();
		$clean['collections']  = array_values( array_intersect( $available_collections, array_map( 'sanitize_key', $input_collections ) ) );

		$clean['menu_id'] = isset( $input['menu_id'] ) ? absint( $input['menu_id'] ) : 0;
		$clean['default_title'] = isset( $input['default_title'] ) ? sanitize_text_field( wp_unslash( (string) $input['default_title'] ) ) : (string) $defaults['default_title'];
		$clean['cache_ttl'] = isset( $input['cache_ttl'] ) ? max( 1, min( 1440, absint( $input['cache_ttl'] ) ) ) : (int) $defaults['cache_ttl'];
		$clean['style_preset'] = isset( $input['style_preset'] ) ? sanitize_key( $input['style_preset'] ) : (string) $defaults['style_preset'];
		if ( ! in_array( $clean['style_preset'], array( 'minimal', 'bold', 'glass' ), true ) ) {
			$clean['style_preset'] = (string) $defaults['style_preset'];
		}

		foreach ( array( 'show_counts', 'hide_empty', 'auto_expand_current', 'enable_search', 'remember_state', 'mobile_offcanvas', 'enable_ajax_filtering', 'enable_ajax_sorting', 'enable_filter_analytics', 'inject_shop_sidebar' ) as $bool_key ) {
			$clean[ $bool_key ] = isset( $input[ $bool_key ] ) ? 1 : 0;
		}

		$clean['filter_logic'] = isset( $input['filter_logic'] ) ? sanitize_key( (string) $input['filter_logic'] ) : (string) $defaults['filter_logic'];
		if ( ! in_array( $clean['filter_logic'], array( 'and', 'or' ), true ) ) {
			$clean['filter_logic'] = (string) $defaults['filter_logic'];
		}

		return $clean;
	}

	/**
	 * Render settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$settings    = self::get();
		$taxonomies  = self::available_taxonomies();
		$collections = self::available_collections();
		$menus       = wp_get_nav_menus();
		$notice      = isset( $_GET['wbwan_notice'] ) ? sanitize_key( wp_unslash( (string) $_GET['wbwan_notice'] ) ) : '';
		?>
		<div class="wrap wbwan-admin-wrap">
			<?php if ( 'import_success' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings imported successfully.', 'wb-accordion-navigation-for-woocommerce' ); ?></p></div>
			<?php elseif ( 'import_failed' === $notice ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Import failed. Please upload a valid settings JSON file.', 'wb-accordion-navigation-for-woocommerce' ); ?></p></div>
			<?php endif; ?>

			<div class="wbwan-admin-hero">
				<div>
					<h1><?php esc_html_e( 'WB Accordion Navigation for WooCommerce', 'wb-accordion-navigation-for-woocommerce' ); ?></h1>
					<p><?php esc_html_e( 'Design powerful storefront navigation with a premium accordion experience.', 'wb-accordion-navigation-for-woocommerce' ); ?></p>
				</div>
				<code>[wb_wc_accordion_navigation]</code>
			</div>

			<form method="post" action="options.php" class="wbwan-admin-form" data-wbwan-settings-form>
				<?php settings_fields( 'wbwan_settings_group' ); ?>

				<div class="wbwan-admin-grid">
					<div class="wbwan-card wbwan-card-span-2">
						<h2><?php esc_html_e( 'Navigation Sources', 'wb-accordion-navigation-for-woocommerce' ); ?></h2>
						<p class="wbwan-card-desc"><?php esc_html_e( 'Choose what appears in your accordion navigation.', 'wb-accordion-navigation-for-woocommerce' ); ?></p>

						<h3><?php esc_html_e( 'Taxonomies', 'wb-accordion-navigation-for-woocommerce' ); ?></h3>
						<div class="wbwan-choice-grid">
							<?php foreach ( $taxonomies as $slug => $label ) : ?>
								<label class="wbwan-choice">
									<input
										type="checkbox"
										name="<?php echo esc_attr( self::OPTION_KEY ); ?>[taxonomies][]"
										value="<?php echo esc_attr( $slug ); ?>"
										<?php checked( in_array( $slug, (array) $settings['taxonomies'], true ) ); ?>
									/>
									<span><?php echo esc_html( $label ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>

						<h3><?php esc_html_e( 'Dynamic Collections', 'wb-accordion-navigation-for-woocommerce' ); ?></h3>
						<div class="wbwan-choice-grid">
							<?php foreach ( $collections as $slug => $label ) : ?>
								<label class="wbwan-choice">
									<input
										type="checkbox"
										name="<?php echo esc_attr( self::OPTION_KEY ); ?>[collections][]"
										value="<?php echo esc_attr( $slug ); ?>"
										<?php checked( in_array( $slug, (array) $settings['collections'], true ) ); ?>
									/>
									<span><?php echo esc_html( $label ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>

						<h3><?php esc_html_e( 'WordPress Menu', 'wb-accordion-navigation-for-woocommerce' ); ?></h3>
						<select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[menu_id]" class="wbwan-input">
							<option value="0"><?php esc_html_e( 'None', 'wb-accordion-navigation-for-woocommerce' ); ?></option>
							<?php foreach ( $menus as $menu ) : ?>
								<option value="<?php echo esc_attr( (string) $menu->term_id ); ?>" <?php selected( absint( $settings['menu_id'] ), (int) $menu->term_id ); ?>>
									<?php echo esc_html( $menu->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="wbwan-card">
						<h2><?php esc_html_e( 'Display & UX', 'wb-accordion-navigation-for-woocommerce' ); ?></h2>
						<p class="wbwan-card-desc"><?php esc_html_e( 'Control how users interact with your accordion.', 'wb-accordion-navigation-for-woocommerce' ); ?></p>
						<?php
						$this->render_modern_toggle( 'show_counts', __( 'Show product counts', 'wb-accordion-navigation-for-woocommerce' ), $settings );
						$this->render_modern_toggle( 'hide_empty', __( 'Hide empty terms', 'wb-accordion-navigation-for-woocommerce' ), $settings );
						$this->render_modern_toggle( 'auto_expand_current', __( 'Auto-expand current term path', 'wb-accordion-navigation-for-woocommerce' ), $settings );
							$this->render_modern_toggle( 'enable_search', __( 'Enable search within accordion', 'wb-accordion-navigation-for-woocommerce' ), $settings );
							$this->render_modern_toggle( 'remember_state', __( 'Remember open accordion state', 'wb-accordion-navigation-for-woocommerce' ), $settings );
							$this->render_modern_toggle( 'mobile_offcanvas', __( 'Enable mobile off-canvas mode', 'wb-accordion-navigation-for-woocommerce' ), $settings );
							$this->render_modern_toggle( 'enable_ajax_filtering', __( 'Enable AJAX filtering mode (shop/product archives)', 'wb-accordion-navigation-for-woocommerce' ), $settings );
							$this->render_modern_toggle( 'enable_ajax_sorting', __( 'Enable AJAX sorting', 'wb-accordion-navigation-for-woocommerce' ), $settings );
							$this->render_modern_toggle( 'enable_filter_analytics', __( 'Enable filter analytics tracking', 'wb-accordion-navigation-for-woocommerce' ), $settings );
							?>
					</div>

						<div class="wbwan-card">
							<h2><?php esc_html_e( 'Placement', 'wb-accordion-navigation-for-woocommerce' ); ?></h2>
						<p class="wbwan-card-desc"><?php esc_html_e( 'Choose where navigation appears on shop pages.', 'wb-accordion-navigation-for-woocommerce' ); ?></p>
						<?php $this->render_modern_toggle( 'inject_shop_sidebar', __( 'Auto-inject into WooCommerce sidebar', 'wb-accordion-navigation-for-woocommerce' ), $settings ); ?>

							<h2><?php esc_html_e( 'Branding & Performance', 'wb-accordion-navigation-for-woocommerce' ); ?></h2>
							<label class="wbwan-label" for="wbwan-style-preset"><?php esc_html_e( 'Style preset', 'wb-accordion-navigation-for-woocommerce' ); ?></label>
							<select
								id="wbwan-style-preset"
								class="wbwan-input"
								name="<?php echo esc_attr( self::OPTION_KEY ); ?>[style_preset]"
								data-wbwan-preview="style"
							>
								<option value="minimal" <?php selected( (string) $settings['style_preset'], 'minimal' ); ?>><?php esc_html_e( 'Minimal', 'wb-accordion-navigation-for-woocommerce' ); ?></option>
								<option value="bold" <?php selected( (string) $settings['style_preset'], 'bold' ); ?>><?php esc_html_e( 'Bold', 'wb-accordion-navigation-for-woocommerce' ); ?></option>
								<option value="glass" <?php selected( (string) $settings['style_preset'], 'glass' ); ?>><?php esc_html_e( 'Glass', 'wb-accordion-navigation-for-woocommerce' ); ?></option>
							</select>

							<label class="wbwan-label" for="wbwan-default-title"><?php esc_html_e( 'Default accordion title', 'wb-accordion-navigation-for-woocommerce' ); ?></label>
							<input
								id="wbwan-default-title"
								type="text"
								class="wbwan-input"
								name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_title]"
								value="<?php echo esc_attr( (string) $settings['default_title'] ); ?>"
								data-wbwan-preview="title"
							/>

						<label class="wbwan-label" for="wbwan-cache-ttl"><?php esc_html_e( 'Taxonomy cache TTL (minutes)', 'wb-accordion-navigation-for-woocommerce' ); ?></label>
							<input
								id="wbwan-cache-ttl"
							type="number"
							min="1"
							max="1440"
							class="wbwan-input wbwan-input-small"
							name="<?php echo esc_attr( self::OPTION_KEY ); ?>[cache_ttl]"
							value="<?php echo esc_attr( (string) max( 1, absint( $settings['cache_ttl'] ) ) ); ?>"
							/>

							<label class="wbwan-label" for="wbwan-filter-logic"><?php esc_html_e( 'Taxonomy filter logic', 'wb-accordion-navigation-for-woocommerce' ); ?></label>
							<select id="wbwan-filter-logic" class="wbwan-input" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[filter_logic]">
								<option value="and" <?php selected( (string) $settings['filter_logic'], 'and' ); ?>><?php esc_html_e( 'AND (match all selected terms)', 'wb-accordion-navigation-for-woocommerce' ); ?></option>
								<option value="or" <?php selected( (string) $settings['filter_logic'], 'or' ); ?>><?php esc_html_e( 'OR (match any selected term)', 'wb-accordion-navigation-for-woocommerce' ); ?></option>
							</select>
						</div>

						<div class="wbwan-card wbwan-preview-card">
							<h2><?php esc_html_e( 'Live Preview', 'wb-accordion-navigation-for-woocommerce' ); ?></h2>
							<p class="wbwan-card-desc"><?php esc_html_e( 'Quickly preview title, counts, search, and style changes.', 'wb-accordion-navigation-for-woocommerce' ); ?></p>
							<div class="wbwan-preview-wrap">
								<div class="wbwan-accordion wbwan-preview-accordion wbwan-style-<?php echo esc_attr( (string) $settings['style_preset'] ); ?>" data-wbwan-preview-root>
									<div class="wbwan-panel">
										<div class="wbwan-head">
											<h3 class="wbwan-title" data-wbwan-preview-title><?php echo esc_html( (string) $settings['default_title'] ); ?></h3>
										</div>
										<input
											type="search"
											class="wbwan-search<?php echo empty( $settings['enable_search'] ) ? ' wbwan-preview-hidden' : ''; ?>"
											placeholder="<?php esc_attr_e( 'Search navigation...', 'wb-accordion-navigation-for-woocommerce' ); ?>"
											data-wbwan-preview-search
										/>
										<div class="wbwan-section">
											<h4 class="wbwan-section-title"><?php esc_html_e( 'Product Categories', 'wb-accordion-navigation-for-woocommerce' ); ?></h4>
											<ul class="wbwan-list">
												<li class="wbwan-item is-open">
													<div class="wbwan-row">
														<button type="button" class="wbwan-toggle" aria-expanded="true"></button>
														<a href="#" onclick="return false;">Electronics <span class="wbwan-count<?php echo empty( $settings['show_counts'] ) ? ' wbwan-preview-hidden' : ''; ?>" data-wbwan-preview-count>(12)</span></a>
													</div>
													<ul class="wbwan-list">
														<li class="wbwan-item">
															<div class="wbwan-row">
																<span class="wbwan-toggle-spacer" aria-hidden="true"></span>
																<a href="#" onclick="return false;">Laptops <span class="wbwan-count<?php echo empty( $settings['show_counts'] ) ? ' wbwan-preview-hidden' : ''; ?>" data-wbwan-preview-count>(5)</span></a>
															</div>
														</li>
													</ul>
												</li>
											</ul>
										</div>
									</div>
								</div>
							</div>
						</div>

					</div>

					<div class="wbwan-admin-actions">
						<?php submit_button( __( 'Save Settings', 'wb-accordion-navigation-for-woocommerce' ), 'primary', 'submit', false ); ?>
					</div>
				</form>

				<div class="wbwan-admin-grid wbwan-tools-grid">
					<div class="wbwan-card wbwan-card-span-2">
						<h2><?php esc_html_e( 'Import / Export', 'wb-accordion-navigation-for-woocommerce' ); ?></h2>
						<p class="wbwan-card-desc"><?php esc_html_e( 'Move settings between sites quickly with JSON.', 'wb-accordion-navigation-for-woocommerce' ); ?></p>
						<div class="wbwan-inline-actions">
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="wbwan_export_settings" />
								<?php wp_nonce_field( 'wbwan_export_settings', 'wbwan_export_nonce' ); ?>
								<?php submit_button( __( 'Export Settings', 'wb-accordion-navigation-for-woocommerce' ), 'secondary', 'submit', false ); ?>
							</form>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
								<input type="hidden" name="action" value="wbwan_import_settings" />
								<?php wp_nonce_field( 'wbwan_import_settings', 'wbwan_import_nonce' ); ?>
								<input type="file" name="wbwan_import_file" accept="application/json" required />
								<?php submit_button( __( 'Import Settings', 'wb-accordion-navigation-for-woocommerce' ), 'secondary', 'submit', false ); ?>
							</form>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

	/**
	 * Render modern toggle row.
	 *
	 * @param string              $key Toggle key.
	 * @param string              $label Toggle label.
	 * @param array<string,mixed> $settings Current settings.
	 */
	private function render_modern_toggle( string $key, string $label, array $settings ): void {
		?>
		<label class="wbwan-toggle-row">
			<span><?php echo esc_html( $label ); ?></span>
			<span class="wbwan-switch">
				<input
					type="checkbox"
					name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"
					value="1"
					<?php checked( ! empty( $settings[ $key ] ) ); ?>
				/>
				<span class="wbwan-slider" aria-hidden="true"></span>
			</span>
		</label>
		<?php
	}

	/**
	 * Render taxonomy selection field.
	 */
	public function render_taxonomies_field(): void {
		$settings   = self::get();
		$selected   = is_array( $settings['taxonomies'] ) ? $settings['taxonomies'] : array();
		$taxonomies = self::available_taxonomies();

		foreach ( $taxonomies as $slug => $label ) {
			printf(
				'<label><input type="checkbox" name="%1$s[taxonomies][]" value="%2$s" %3$s /> %4$s</label><br/>',
				esc_attr( self::OPTION_KEY ),
				esc_attr( $slug ),
				checked( in_array( $slug, $selected, true ), true, false ),
				esc_html( $label )
			);
		}
	}

	/**
	 * Render collection field.
	 */
	public function render_collections_field(): void {
		$settings    = self::get();
		$selected    = is_array( $settings['collections'] ) ? $settings['collections'] : array();
		$collections = self::available_collections();

		foreach ( $collections as $slug => $label ) {
			printf(
				'<label><input type="checkbox" name="%1$s[collections][]" value="%2$s" %3$s /> %4$s</label><br/>',
				esc_attr( self::OPTION_KEY ),
				esc_attr( $slug ),
				checked( in_array( $slug, $selected, true ), true, false ),
				esc_html( $label )
			);
		}
	}

	/**
	 * Render menu chooser.
	 */
	public function render_menu_field(): void {
		$settings = self::get();
		$menu_id  = absint( $settings['menu_id'] );
		$menus    = wp_get_nav_menus();

		echo '<select name="' . esc_attr( self::OPTION_KEY ) . '[menu_id]">';
		echo '<option value="0">' . esc_html__( 'None', 'wb-accordion-navigation-for-woocommerce' ) . '</option>';
		foreach ( $menus as $menu ) {
			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				absint( $menu->term_id ),
				selected( $menu_id, (int) $menu->term_id, false ),
				esc_html( $menu->name )
			);
		}
		echo '</select>';
	}

	/**
	 * Render default title field.
	 */
	public function render_default_title_field(): void {
		$settings = self::get();
		$value    = isset( $settings['default_title'] ) ? (string) $settings['default_title'] : '';
		printf(
			'<input type="text" name="%1$s[default_title]" value="%2$s" class="regular-text" /> <p class="description">%3$s</p>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $value ),
			esc_html__( 'Used when shortcode title attribute is not provided.', 'wb-accordion-navigation-for-woocommerce' )
		);
	}

	/**
	 * Render cache TTL field.
	 */
	public function render_cache_ttl_field(): void {
		$settings = self::get();
		$value    = isset( $settings['cache_ttl'] ) ? max( 1, absint( $settings['cache_ttl'] ) ) : 15;
		printf(
			'<input type="number" min="1" max="1440" name="%1$s[cache_ttl]" value="%2$d" class="small-text" /> <p class="description">%3$s</p>',
			esc_attr( self::OPTION_KEY ),
			(int) $value,
			esc_html__( 'Controls cached taxonomy tree lifetime (1-1440 minutes).', 'wb-accordion-navigation-for-woocommerce' )
		);
	}

	/**
	 * Render toggle field.
	 *
	 * @param array<string,string> $args Field args.
	 */
	public function render_toggle_field( array $args ): void {
		$settings = self::get();
		$key      = $args['key'];
		$checked  = ! empty( $settings[ $key ] );

		printf(
			'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s /></label>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			checked( $checked, true, false )
		);
	}

	/**
	 * Export plugin settings as JSON.
	 */
	public function handle_export_settings(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to export settings.', 'wb-accordion-navigation-for-woocommerce' ) );
		}

		check_admin_referer( 'wbwan_export_settings', 'wbwan_export_nonce' );

		$settings = self::get();
		$payload  = wp_json_encode(
			array(
				'plugin'   => 'wb-accordion-navigation-for-woocommerce',
				'version'  => WBWAN_VERSION,
				'exported' => gmdate( 'c' ),
				'settings' => $settings,
			),
			JSON_PRETTY_PRINT
		);

		if ( false === $payload ) {
			$payload = '{}';
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wbwan-settings-' . gmdate( 'Y-m-d' ) . '.json' );
		echo $payload; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Import plugin settings from JSON.
	 */
	public function handle_import_settings(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to import settings.', 'wb-accordion-navigation-for-woocommerce' ) );
		}

		check_admin_referer( 'wbwan_import_settings', 'wbwan_import_nonce' );

		$file = isset( $_FILES['wbwan_import_file'] ) ? $_FILES['wbwan_import_file'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! is_array( $file ) || empty( $file['tmp_name'] ) ) {
			$this->redirect_with_notice( 'import_failed' );
		}

		$content = file_get_contents( (string) $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $content || '' === $content ) {
			$this->redirect_with_notice( 'import_failed' );
		}

		$decoded = json_decode( (string) $content, true );
		if ( ! is_array( $decoded ) ) {
			$this->redirect_with_notice( 'import_failed' );
		}

		$raw_settings = isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ? $decoded['settings'] : $decoded;
		if ( ! is_array( $raw_settings ) ) {
			$this->redirect_with_notice( 'import_failed' );
		}

		$clean = $this->sanitize( $raw_settings );
		update_option( self::OPTION_KEY, $clean );
		$this->redirect_with_notice( 'import_success' );
	}

	/**
	 * Redirect to settings page with notice key.
	 *
	 * @param string $notice Notice key.
	 */
	private function redirect_with_notice( string $notice ): void {
		$url = add_query_arg(
			array(
				'page'         => 'wbwan-settings',
				'wbwan_notice' => sanitize_key( $notice ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * List supported taxonomies.
	 *
	 * @return array<string,string>
	 */
	public static function available_taxonomies(): array {
		$taxonomies = array(
			'product_cat' => __( 'Product Categories', 'wb-accordion-navigation-for-woocommerce' ),
			'product_tag' => __( 'Product Tags', 'wb-accordion-navigation-for-woocommerce' ),
		);

		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			$attributes = wc_get_attribute_taxonomies();
			foreach ( $attributes as $attribute ) {
				$taxonomy               = wc_attribute_taxonomy_name( $attribute->attribute_name );
				$taxonomies[ $taxonomy ] = sprintf(
					/* translators: %s attribute label */
					__( 'Attribute: %s', 'wb-accordion-navigation-for-woocommerce' ),
					$attribute->attribute_label
				);
			}
		}

		return apply_filters( 'wbwan_available_taxonomies', $taxonomies );
	}

	/**
	 * List supported collections.
	 *
	 * @return array<string,string>
	 */
	public static function available_collections(): array {
		$collections = array(
			'best_sellers' => __( 'Best Sellers', 'wb-accordion-navigation-for-woocommerce' ),
			'on_sale'      => __( 'On Sale', 'wb-accordion-navigation-for-woocommerce' ),
			'top_rated'    => __( 'Top Rated', 'wb-accordion-navigation-for-woocommerce' ),
			'new_arrivals' => __( 'New Arrivals', 'wb-accordion-navigation-for-woocommerce' ),
		);

		return apply_filters( 'wbwan_available_collections', $collections );
	}
}
