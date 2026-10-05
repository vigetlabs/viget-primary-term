<!-- markdownlint-disable MD033 MD041 -->
<p align="left">
  <img src="https://img.shields.io/badge/WordPress-6.5%2B-21759b?logo=wordpress&logoColor=white" alt="WordPress 6.5+">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777bb4?logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/github/v/release/vigetlabs/viget-primary-term" alt="Latest release">
  <img src="https://img.shields.io/github/actions/workflow/status/vigetlabs/viget-primary-term/ci.yaml?branch=main&label=CI" alt="CI status">
  <img src="https://img.shields.io/badge/license-GPL--2.0--or--later-blue" alt="License">
</p>

# Viget Primary Term

Lets editors pick a primary term for a post from the block editor's taxonomy panels, e.g. a primary category for a post with several.

## Features

- Turn taxonomies on from **Settings → Primary Terms**, or register them in code with the `vgpt_registered_taxonomies` filter. Registered taxonomies show on the settings page as locked rows.
- A **Primary {Term}** select appears under the taxonomy's panel once 2+ terms are checked. It lists only the checked terms, including ones just added from the panel.
- The first option shows the fallback, e.g. "Default: Chains › Roller". With no primary term, `get_primary_term()` uses the deepest term (hierarchical) or the first by name (flat).
- Unchecking the primary term clears it.
- Checks for updates from GitHub releases in the WordPress dashboard.

Block editor only. The classic editor isn't supported.

## Installation

### Manual

1. Download the [latest release](https://github.com/vigetlabs/viget-primary-term/releases/latest) zip.
2. Upload it via **Plugins → Add New → Upload Plugin**, or extract it to `wp-content/plugins/viget-primary-term/`.
3. Activate the plugin.

### Composer

```bash
composer require viget/viget-primary-term
```

## Usage

Turn on a taxonomy from **Settings → Primary Terms**, or in code:

```php
add_filter(
	'vgpt_registered_taxonomies',
	function ( array $taxonomies ): array {
		$taxonomies[] = 'product_category';
		return $taxonomies;
	}
);
```

Read a post's primary term:

```php
$term = vgpt()->get_primary_term( $post_id, 'product_category' );
```

The term ID is stored in post meta as `_vgpt_primary_{taxonomy}`, registered for the REST API on every post type the taxonomy is attached to. Those post types get `custom-fields` support, which the REST API needs to include meta.

## Auto-Updates

This plugin checks GitHub releases every 12 hours and surfaces updates in **Plugins**. See [`includes/class-github-plugin-updater.php`](includes/class-github-plugin-updater.php). That's incompatible with the wordpress.org plugin directory, which this plugin isn't listed in.

## Developer API

Public methods and filters are documented in [docs/api.md](docs/api.md).

## Development

```bash
npm install        # JS build tooling
npm run start      # watch mode
npm run build      # production build (build/ is committed)
npm run lint:js    # eslint
npm run lint:css   # stylelint
composer install   # PHPUnit
npm run env:start  # wp-env (Docker), on ports 8898/8899
npm test           # PHPUnit against real WordPress
```
