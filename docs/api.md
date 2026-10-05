# Developer API

Public methods and filters for Viget Primary Term. Everything lives under the `Viget\PrimaryTerm` namespace, and `vgpt()` returns the shared `Core` instance.

## `Core`

### `get_primary_term( int $post_id, string $taxonomy, bool $fallback = true ): ?WP_Term`

Returns the post's primary term while it's still assigned. Otherwise, with `$fallback`, the deepest assigned term (hierarchical, first by name on a tie) or the first by name (flat), through the `vgpt_default_term` filter. `null` with no terms, or with no primary term and `$fallback` off.

```php
$term = vgpt()->get_primary_term( $post_id, 'category' );
```

### `get_taxonomies(): string[]`

Taxonomies with a primary term: registered in code first, then saved on the settings page. Taxonomies that don't exist are left out. Passes through `vgpt_taxonomies`.

### `get_registered_taxonomies(): string[]`

The sanitized, de-duplicated taxonomies from `vgpt_registered_taxonomies`.

### `get_saved_taxonomies(): string[]`

The taxonomies saved on the settings page.

### `get_taxonomies_for_post_type( string $post_type ): string[]`

The enabled taxonomies a post type uses.

### `get_meta_key( string $taxonomy ): string`

The post meta key that stores a taxonomy's primary term ID: `_vgpt_primary_{taxonomy}`.

### Constants

| Constant | Value |
| --- | --- |
| `Core::OPTION_NAME` | `vgpt_settings` |
| `Core::META_KEY_PREFIX` | `_vgpt_primary_` |

## Filters

### `vgpt_registered_taxonomies`

Registers taxonomies in code. They show on **Settings → Primary Terms** as locked rows that can't be turned off. A registered taxonomy that doesn't exist or isn't in the block editor shows why it's off.

```php
add_filter(
	'vgpt_registered_taxonomies',
	function ( array $taxonomies ): array {
		$taxonomies[] = 'product_category';
		return $taxonomies;
	}
);
```

### `vgpt_taxonomies`

Filters the resolved taxonomies from `get_taxonomies()`.

### `vgpt_default_term`

Filters the term `get_primary_term()` falls back to. The editor's "Default" option always shows the deepest or first term, so it won't reflect this filter.

```php
add_filter(
	'vgpt_default_term',
	function ( ?WP_Term $term, int $post_id, string $taxonomy ): ?WP_Term {
		return $term;
	},
	10,
	3
);
```
