# WB Accordion Navigation for WooCommerce Hooks

This document lists the plugin's public `wbwan_*` actions and filters.

Use these hooks to customize behavior instead of editing plugin core files.

## Filters

### `wbwan_default_settings`
- Purpose: Modify default plugin settings.
- Args:
1. `$defaults` (`array<string,mixed>`)
- Return: settings array.

```php
add_filter( 'wbwan_default_settings', function ( array $defaults ): array {
	$defaults['enable_ajax_filtering'] = 1;
	$defaults['style_preset'] = 'bold';
	return $defaults;
} );
```

### `wbwan_available_taxonomies`
- Purpose: Add/remove taxonomies shown in settings and accordion.
- Args:
1. `$taxonomies` (`array<string,string>`) slug => label.
- Return: taxonomy map.

```php
add_filter( 'wbwan_available_taxonomies', function ( array $taxonomies ): array {
	$taxonomies['pa_material'] = __( 'Attribute: Material', 'my-textdomain' );
	return $taxonomies;
} );
```

### `wbwan_available_collections`
- Purpose: Add/remove collection options.
- Args:
1. `$collections` (`array<string,string>`) key => label.
- Return: collections map.

```php
add_filter( 'wbwan_available_collections', function ( array $collections ): array {
	$collections['featured'] = __( 'Featured', 'my-textdomain' );
	return $collections;
} );
```

### `wbwan_enabled_addons`
- Purpose: Control which registered addons boot.
- Args:
1. `$enabled` (`array<int,string>`) addon IDs.
- Return: addon IDs list.

```php
add_filter( 'wbwan_enabled_addons', function ( array $enabled ): array {
	return array_diff( $enabled, array( 'example-addon' ) );
} );
```

### `wbwan_rest_namespace`
- Purpose: Customize REST namespace.
- Args:
1. `$namespace` (`string`) default `wbwan/v1`.
- Return: namespace string (without leading/trailing slash).

```php
add_filter( 'wbwan_rest_namespace', fn() => 'wbwan/v2' );
```

### `wbwan_filter_route_path`
- Purpose: Customize filter endpoint path.
- Args:
1. `$route` (`string`) default `/filter`.
- Return: route path.

### `wbwan_analytics_route_path`
- Purpose: Customize analytics endpoint path.
- Args:
1. `$route` (`string`) default `/analytics`.
- Return: route path.

```php
add_filter( 'wbwan_filter_route_path', fn() => '/products/filter' );
add_filter( 'wbwan_analytics_route_path', fn() => '/products/analytics' );
```

### `wbwan_rest_filter_permission`
- Purpose: Control filter endpoint access.
- Args:
1. `$allowed` (`bool|\WP_Error`) default `true`.
2. `$request` (`\WP_REST_Request`)
- Return: `true` to allow, or `\WP_Error`/`false` to deny.

```php
add_filter( 'wbwan_rest_filter_permission', function ( $allowed, \WP_REST_Request $request ) {
	if ( is_user_logged_in() ) {
		return true;
	}
	return new \WP_Error( 'forbidden', __( 'Login required', 'my-textdomain' ), array( 'status' => 401 ) );
}, 10, 2 );
```

### `wbwan_allow_public_analytics`
- Purpose: Allow analytics endpoint without auth/nonce.
- Args:
1. `$allow_public` (`bool`) default `false`.
2. `$request` (`\WP_REST_Request`)
- Return: bool.

```php
add_filter( 'wbwan_allow_public_analytics', '__return_true', 10, 2 );
```

### `wbwan_allowed_sort_keys`
- Purpose: Extend/limit accepted sort keys for REST validation.
- Args:
1. `$keys` (`array<int,string>`)
- Return: sort key list.

```php
add_filter( 'wbwan_allowed_sort_keys', function ( array $keys ): array {
	$keys[] = 'menu_order';
	return array_values( array_unique( $keys ) );
} );
```

### `wbwan_sort_query_args`
- Purpose: Map a sort key to WP_Query args.
- Args:
1. `$sort_args` (`array<string,mixed>`)
2. `$sort` (`string`)
- Return: WP_Query args array.

```php
add_filter( 'wbwan_sort_query_args', function ( array $sort_args, string $sort ): array {
	if ( 'menu_order' === $sort ) {
		return array(
			'orderby' => 'menu_order',
			'order'   => 'ASC',
		);
	}
	return $sort_args;
}, 10, 2 );
```

### `wbwan_collection_query_args`
- Purpose: Customize query args per collection key.
- Args:
1. `$collection_args` (`array<string,mixed>`)
2. `$collection` (`string`)
3. `$context` (`\WP_Query|\WP_REST_Request`)
- Return: WP_Query args array.

### `wbwan_rest_filter_query_args`
- Purpose: Final mutation of REST filter query args before query runs.
- Args:
1. `$args` (`array<string,mixed>`)
2. `$request` (`\WP_REST_Request`)
3. `$settings` (`array<string,mixed>`)
- Return: WP_Query args array.

### `wbwan_rest_filter_response`
- Purpose: Modify REST filter response payload.
- Args:
1. `$response_data` (`array<string,mixed>`)
2. `$args` (`array<string,mixed>`)
3. `$query` (`\WP_Query`)
4. `$request` (`\WP_REST_Request`)
5. `$settings` (`array<string,mixed>`)
- Return: response payload array.

```php
add_filter( 'wbwan_rest_filter_response', function ( array $data ): array {
	$data['source'] = 'custom';
	return $data;
} );
```

### `wbwan_analytics_payload`
- Purpose: Normalize/trim analytics payload before aggregation.
- Args:
1. `$payload` (`array<string,mixed>`)
2. `$request` (`\WP_REST_Request`)
- Return: payload array.

### `wbwan_wrapper_classes`
- Purpose: Customize root accordion CSS classes.
- Args:
1. `$classes` (`array<int,string>`)
2. `$settings` (`array<string,mixed>`)
3. `$title` (`string`)
- Return: class list.

### `wbwan_frontend_instance_config`
- Purpose: Customize per-instance JS config in `data-wbwan-config`.
- Args:
1. `$config` (`array<string,mixed>`)
2. `$settings` (`array<string,mixed>`)
3. `$title` (`string`)
- Return: config array.

```php
add_filter( 'wbwan_frontend_instance_config', function ( array $config ): array {
	$config['debugMode'] = true;
	return $config;
} );
```

### `wbwan_render_sections`
- Purpose: Add/remove/reorder accordion sections markup.
- Args:
1. `$sections` (`array<int,string>`)
2. `$settings` (`array<string,mixed>`)
- Return: sections markup array.

### `wbwan_render_output`
- Purpose: Final full HTML output filter.
- Args:
1. `$output` (`string`)
2. `$settings` (`array<string,mixed>`)
3. `$title` (`string`)
- Return: HTML string.

## Actions

### `wbwan_register_addons`
- Purpose: Register addon boot callbacks.
- Args:
1. `$manager` (`\WBCOM\WBWAN\Addon_Manager`)

```php
add_action( 'wbwan_register_addons', function ( \WBCOM\WBWAN\Addon_Manager $manager ): void {
	$manager->register( 'my-addon', function (): void {
		add_filter( 'wbwan_wrapper_classes', function ( array $classes ): array {
			$classes[] = 'my-addon-enabled';
			return $classes;
		} );
	} );
} );
```

### `wbwan_before_render`
- Purpose: Run right before accordion markup starts.
- Args:
1. `$settings` (`array<string,mixed>`)

### `wbwan_after_render`
- Purpose: Run after accordion markup ends.
- Args:
1. `$settings` (`array<string,mixed>`)

## Notes

- Hook names are stable and prefixed with `wbwan_`.
- For REST writes, default analytics permissions now require logged-in user + valid REST nonce.
- Prefer extension via hooks over direct plugin edits to keep updates safe.
