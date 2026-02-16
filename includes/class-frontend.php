<?php
/**
 * Frontend rendering and query integration.
 *
 * @package WBCOM\WBWAN
 */

namespace WBCOM\WBWAN;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontend {
	/**
	 * Local cache for terms.
	 *
	 * @var array<string,array<int,\WP_Term>>
	 */
	private array $terms_cache = array();
	/**
	 * Term cache lifetime in minutes.
	 *
	 * @var int
	 */
	private int $cache_ttl_minutes = 15;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_shortcode( 'wb_wc_accordion_navigation', array( $this, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_collection_query' ) );
		add_action( 'woocommerce_sidebar', array( $this, 'maybe_inject_into_sidebar' ), 5 );
	}

	/**
	 * Register assets.
	 */
	public function register_assets(): void {
		wp_register_style( 'wbwan-frontend', WBWAN_URL . 'assets/css/frontend.css', array(), WBWAN_VERSION );
		wp_register_script( 'wbwan-frontend', WBWAN_URL . 'assets/js/frontend.js', array(), WBWAN_VERSION, true );
	}

	/**
	 * Add custom query var.
	 *
	 * @param array<int,string> $vars Existing query vars.
	 * @return array<int,string>
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = 'wban_collection';
		return $vars;
	}

	/**
	 * Inject into Woo sidebar if enabled.
	 */
	public function maybe_inject_into_sidebar(): void {
		$settings = Settings::get();
		if ( empty( $settings['inject_shop_sidebar'] ) ) {
			return;
		}

		echo $this->render_accordion( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( array $atts ): string {
		$settings = Settings::get();
		$default_title = isset( $settings['default_title'] ) && is_string( $settings['default_title'] ) && '' !== $settings['default_title']
			? $settings['default_title']
			: __( 'Shop Navigation', 'wb-accordion-navigation-for-woocommerce' );

		$atts = shortcode_atts(
			array(
				'title'       => $default_title,
				'taxonomies'  => '',
				'collections' => '',
			),
			$atts,
			'wb_wc_accordion_navigation'
		);

		if ( ! empty( $atts['taxonomies'] ) ) {
			$settings['taxonomies'] = array_map( 'sanitize_key', array_map( 'trim', explode( ',', $atts['taxonomies'] ) ) );
		}

		if ( ! empty( $atts['collections'] ) ) {
			$settings['collections'] = array_map( 'sanitize_key', array_map( 'trim', explode( ',', $atts['collections'] ) ) );
		}

		return $this->render_accordion( $settings, sanitize_text_field( $atts['title'] ) );
	}

	/**
	 * Apply collection query filters.
	 *
	 * @param \WP_Query $query Query object.
	 */
	public function apply_collection_query( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! ( is_shop() || is_product_taxonomy() ) ) {
			return;
		}

		$collection = sanitize_key( get_query_var( 'wban_collection', '' ) );
		if ( empty( $collection ) ) {
			return;
		}

		$collection_args = array();

		switch ( $collection ) {
			case 'best_sellers':
				$collection_args = array(
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Intentional WooCommerce catalog ordering by sales meta.
					'meta_key' => 'total_sales',
					'orderby'  => 'meta_value_num',
					'order'    => 'DESC',
				);
				break;

			case 'on_sale':
				$on_sale_ids = function_exists( 'wc_get_product_ids_on_sale' ) ? wc_get_product_ids_on_sale() : array();
				$collection_args = array(
					'post__in' => ! empty( $on_sale_ids ) ? $on_sale_ids : array( 0 ),
				);
				break;

			case 'top_rated':
				$collection_args = array(
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Intentional WooCommerce catalog ordering by rating meta.
					'meta_key' => '_wc_average_rating',
					'orderby'  => 'meta_value_num',
					'order'    => 'DESC',
				);
				break;

			case 'new_arrivals':
				$collection_args = array(
					'orderby' => 'date',
					'order'   => 'DESC',
				);
				break;
		}

		$collection_args = apply_filters( 'wbwan_collection_query_args', $collection_args, $collection, $query );
		if ( ! is_array( $collection_args ) ) {
			return;
		}

		foreach ( $collection_args as $key => $value ) {
			$query->set( (string) $key, $value );
		}
	}

	/**
	 * Render full accordion output.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @param string $title Display title.
	 * @return string
	 */
	private function render_accordion( array $settings, string $title = '' ): string {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return '';
		}

		$this->cache_ttl_minutes = isset( $settings['cache_ttl'] ) ? max( 1, min( 1440, absint( $settings['cache_ttl'] ) ) ) : 15;
		$settings_title = isset( $settings['default_title'] ) && is_string( $settings['default_title'] ) && '' !== $settings['default_title']
			? $settings['default_title']
			: __( 'Shop Navigation', 'wb-accordion-navigation-for-woocommerce' );
		$title = '' !== $title ? $title : $settings_title;

		wp_enqueue_style( 'wbwan-frontend' );
		wp_enqueue_script( 'wbwan-frontend' );

		wp_localize_script(
			'wbwan-frontend',
			'wbwanSettings',
			array(
				'rememberState' => ! empty( $settings['remember_state'] ),
			)
		);

		$wrapper_classes = array( 'wbwan-accordion' );
		$style_preset = isset( $settings['style_preset'] ) ? sanitize_key( (string) $settings['style_preset'] ) : 'minimal';
		if ( ! in_array( $style_preset, array( 'minimal', 'bold', 'glass' ), true ) ) {
			$style_preset = 'minimal';
		}
		$wrapper_classes[] = 'wbwan-style-' . $style_preset;
		if ( ! empty( $settings['mobile_offcanvas'] ) ) {
			$wrapper_classes[] = 'is-mobile-offcanvas';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" data-wbwan="accordion">
			<?php do_action( 'wbwan_before_render', $settings ); ?>
			<?php if ( ! empty( $settings['mobile_offcanvas'] ) ) : ?>
				<button class="wbwan-mobile-toggle" type="button" data-wbwan="mobile-toggle"><?php esc_html_e( 'Filters', 'wb-accordion-navigation-for-woocommerce' ); ?></button>
			<?php endif; ?>

			<div class="wbwan-panel" data-wbwan="panel">
				<div class="wbwan-head">
					<h3 class="wbwan-title"><?php echo esc_html( $title ); ?></h3>
					<?php if ( ! empty( $settings['mobile_offcanvas'] ) ) : ?>
						<button class="wbwan-close" type="button" data-wbwan="mobile-close" aria-label="<?php esc_attr_e( 'Close', 'wb-accordion-navigation-for-woocommerce' ); ?>">&times;</button>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $settings['enable_search'] ) ) : ?>
					<input type="search" class="wbwan-search" data-wbwan="search" placeholder="<?php esc_attr_e( 'Search navigation...', 'wb-accordion-navigation-for-woocommerce' ); ?>" />
				<?php endif; ?>

				<?php
				$sections = array();
				$taxonomies = isset( $settings['taxonomies'] ) && is_array( $settings['taxonomies'] ) ? $settings['taxonomies'] : array();
				foreach ( $taxonomies as $taxonomy ) {
					$sections[] = $this->render_taxonomy_section( sanitize_key( $taxonomy ), $settings );
				}

				$sections[] = $this->render_collections_section( $settings );
				$sections[] = $this->render_menu_section( $settings );

				$sections = apply_filters( 'wbwan_render_sections', $sections, $settings );
				if ( is_array( $sections ) ) {
					foreach ( $sections as $section_markup ) {
						if ( is_string( $section_markup ) && '' !== $section_markup ) {
							echo $section_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
					}
				}
				?>
			</div>
			<?php do_action( 'wbwan_after_render', $settings ); ?>
		</div>
		<?php
		$output = (string) ob_get_clean();
		return (string) apply_filters( 'wbwan_render_output', $output, $settings, $title );
	}

	/**
	 * Render taxonomy section.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param array<string,mixed> $settings Plugin settings.
	 * @return string
	 */
	private function render_taxonomy_section( string $taxonomy, array $settings ): string {
		$taxonomy_obj = get_taxonomy( $taxonomy );
		if ( ! $taxonomy_obj ) {
			return '';
		}

		$terms = $this->get_terms_cached( $taxonomy, ! empty( $settings['hide_empty'] ) );
		if ( empty( $terms ) ) {
			return '';
		}

		$current_path = $this->get_current_term_path( $taxonomy );

		ob_start();
		?>
		<div class="wbwan-section" data-wbwan="section">
			<h4 class="wbwan-section-title"><?php echo esc_html( $taxonomy_obj->labels->name ); ?></h4>
			<?php
			if ( is_taxonomy_hierarchical( $taxonomy ) ) {
				echo $this->render_hierarchical_terms( $taxonomy, $terms, 0, $settings, $current_path ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo $this->render_flat_terms( $terms, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render hierarchical tree.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param array<int,\WP_Term> $terms Terms list.
	 * @param int $parent Parent term id.
	 * @param array<string,mixed> $settings Settings.
	 * @param array<int,int> $current_path Current path term IDs.
	 * @return string
	 */
	private function render_hierarchical_terms( string $taxonomy, array $terms, int $parent, array $settings, array $current_path ): string {
		$children = array_values(
			array_filter(
				$terms,
				static fn( \WP_Term $term ): bool => (int) $term->parent === $parent
			)
		);

		if ( empty( $children ) ) {
			return '';
		}

		ob_start();
		echo '<ul class="wbwan-list" data-wbwan="list">';
		foreach ( $children as $term ) {
			$term_children = array_values(
				array_filter(
					$terms,
					static fn( \WP_Term $child ): bool => (int) $child->parent === (int) $term->term_id
				)
			);
			$has_children  = ! empty( $term_children );
			$is_open       = $has_children && ! empty( $settings['auto_expand_current'] ) && in_array( (int) $term->term_id, $current_path, true );
			$term_link     = get_term_link( $term, $taxonomy );
			if ( is_wp_error( $term_link ) ) {
				continue;
			}

			echo '<li class="wbwan-item' . ( $has_children ? ' has-children' : '' ) . ( $is_open ? ' is-open' : '' ) . '" data-wbwan-term="' . esc_attr( (string) $term->term_id ) . '">';
			echo '<div class="wbwan-row">';
			if ( $has_children ) {
				echo '<button type="button" class="wbwan-toggle" data-wbwan="toggle" aria-expanded="' . ( $is_open ? 'true' : 'false' ) . '" aria-label="' . esc_attr__( 'Toggle children', 'wb-accordion-navigation-for-woocommerce' ) . '"></button>';
			} else {
				echo '<span class="wbwan-toggle-spacer" aria-hidden="true"></span>';
			}

			echo '<a href="' . esc_url( $term_link ) . '">' . esc_html( $term->name );
			if ( ! empty( $settings['show_counts'] ) ) {
				echo ' <span class="wbwan-count">(' . esc_html( (string) $term->count ) . ')</span>';
			}
			echo '</a>';
			echo '</div>';

			if ( $has_children ) {
				echo $this->render_hierarchical_terms( $taxonomy, $terms, (int) $term->term_id, $settings, $current_path ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '</li>';
		}
		echo '</ul>';
		return (string) ob_get_clean();
	}

	/**
	 * Render flat term list.
	 *
	 * @param array<int,\WP_Term> $terms Terms.
	 * @param array<string,mixed> $settings Settings.
	 * @return string
	 */
	private function render_flat_terms( array $terms, array $settings ): string {
		ob_start();
		echo '<ul class="wbwan-list" data-wbwan="list">';
		foreach ( $terms as $term ) {
			$term_link = get_term_link( $term );
			if ( is_wp_error( $term_link ) ) {
				continue;
			}

			echo '<li class="wbwan-item"><div class="wbwan-row"><span class="wbwan-toggle-spacer" aria-hidden="true"></span><a href="' . esc_url( $term_link ) . '">' . esc_html( $term->name );
			if ( ! empty( $settings['show_counts'] ) ) {
				echo ' <span class="wbwan-count">(' . esc_html( (string) $term->count ) . ')</span>';
			}
			echo '</a></div></li>';
		}
		echo '</ul>';
		return (string) ob_get_clean();
	}

	/**
	 * Render collection links section.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return string
	 */
	private function render_collections_section( array $settings ): string {
		$collections_map = Settings::available_collections();

		$enabled = isset( $settings['collections'] ) && is_array( $settings['collections'] ) ? $settings['collections'] : array();
		if ( empty( $enabled ) ) {
			return '';
		}

		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

		ob_start();
		?>
		<div class="wbwan-section" data-wbwan="section">
			<h4 class="wbwan-section-title"><?php esc_html_e( 'Collections', 'wb-accordion-navigation-for-woocommerce' ); ?></h4>
			<ul class="wbwan-list" data-wbwan="list">
				<?php foreach ( $enabled as $collection ) : ?>
					<?php if ( ! isset( $collections_map[ $collection ] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<?php $link = add_query_arg( 'wban_collection', $collection, $shop_url ); ?>
					<li class="wbwan-item">
						<div class="wbwan-row">
							<span class="wbwan-toggle-spacer" aria-hidden="true"></span>
							<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $collections_map[ $collection ] ); ?></a>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render optional menu section.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return string
	 */
	private function render_menu_section( array $settings ): string {
		$menu_id = isset( $settings['menu_id'] ) ? absint( $settings['menu_id'] ) : 0;
		if ( $menu_id < 1 ) {
			return '';
		}

		$items = wp_get_nav_menu_items( $menu_id, array( 'update_post_term_cache' => false ) );
		if ( empty( $items ) || is_wp_error( $items ) ) {
			return '';
		}

		$by_parent = array();
		foreach ( $items as $item ) {
			$by_parent[ (int) $item->menu_item_parent ][] = $item;
		}

		ob_start();
		?>
		<div class="wbwan-section" data-wbwan="section">
			<h4 class="wbwan-section-title"><?php esc_html_e( 'Menu', 'wb-accordion-navigation-for-woocommerce' ); ?></h4>
			<?php echo $this->render_menu_branch( $by_parent, 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Recursive menu branch render.
	 *
	 * @param array<int,array<int,\WP_Post>> $by_parent Parent map.
	 * @param int $parent Parent item id.
	 * @return string
	 */
	private function render_menu_branch( array $by_parent, int $parent ): string {
		if ( empty( $by_parent[ $parent ] ) ) {
			return '';
		}

		ob_start();
		echo '<ul class="wbwan-list" data-wbwan="list">';
		foreach ( $by_parent[ $parent ] as $item ) {
			$item_id       = (int) $item->ID;
			$has_children  = ! empty( $by_parent[ $item_id ] );
			echo '<li class="wbwan-item' . ( $has_children ? ' has-children' : '' ) . '">';
			echo '<div class="wbwan-row">';
			if ( $has_children ) {
				echo '<button type="button" class="wbwan-toggle" data-wbwan="toggle" aria-expanded="false" aria-label="' . esc_attr__( 'Toggle children', 'wb-accordion-navigation-for-woocommerce' ) . '"></button>';
			} else {
				echo '<span class="wbwan-toggle-spacer" aria-hidden="true"></span>';
			}
			echo '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
			echo '</div>';
			if ( $has_children ) {
				echo $this->render_menu_branch( $by_parent, $item_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</li>';
		}
		echo '</ul>';
		return (string) ob_get_clean();
	}

	/**
	 * Cached term fetch.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param bool $hide_empty Hide empty.
	 * @return array<int,\WP_Term>
	 */
	private function get_terms_cached( string $taxonomy, bool $hide_empty ): array {
		$cache_key = sprintf( '%s:%d', $taxonomy, (int) $hide_empty );
		if ( isset( $this->terms_cache[ $cache_key ] ) ) {
			return $this->terms_cache[ $cache_key ];
		}

		$transient_key = 'wbwan_terms_' . md5( $cache_key );
		$terms         = get_transient( $transient_key );

		if ( false === $terms ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => $hide_empty,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);
			$terms = is_wp_error( $terms ) ? array() : $terms;
			set_transient( $transient_key, $terms, MINUTE_IN_SECONDS * $this->cache_ttl_minutes );
		}

		$this->terms_cache[ $cache_key ] = is_array( $terms ) ? $terms : array();
		return $this->terms_cache[ $cache_key ];
	}

	/**
	 * Resolve current term path for auto-expansion.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int,int>
	 */
	private function get_current_term_path( string $taxonomy ): array {
		if ( ! is_tax( $taxonomy ) ) {
			return array();
		}

		$current = get_queried_object();
		if ( ! ( $current instanceof \WP_Term ) ) {
			return array();
		}

		$path   = array( (int) $current->term_id );
		$parent = (int) $current->parent;

		while ( $parent > 0 ) {
			$path[] = $parent;
			$next   = get_term( $parent, $taxonomy );
			if ( ! $next || is_wp_error( $next ) ) {
				break;
			}
			$parent = (int) $next->parent;
		}

		return $path;
	}
}
