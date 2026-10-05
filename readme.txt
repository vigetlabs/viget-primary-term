=== Viget Primary Term ===
Contributors: Viget
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Lets editors pick a primary term for a post from the block editor's taxonomy panels.

== Description ==

When a post has more than one term in a taxonomy, a "Primary {Term}" select appears under that taxonomy's panel in the block editor. It lists the checked terms, and the choice saves with the post.

= Features =

* Turn taxonomies on from **Settings → Primary Terms**, or register them in code with the `vgpt_registered_taxonomies` filter. Registered taxonomies show as locked rows.
* The select only shows with 2+ checked terms, and keeps up with terms added from the panel.
* With no primary term, `vgpt()->get_primary_term()` falls back to the deepest term (hierarchical) or the first (flat).
* Checks for updates from GitHub releases in the WordPress dashboard.

= Installation =

1. Upload the plugin to `/wp-content/plugins/viget-primary-term/`, or install via Composer.
2. Activate the plugin.
3. Turn on taxonomies from **Settings → Primary Terms**.

= Requirements =

* WordPress 6.5+
* PHP 8.2+
* The block editor. The classic editor isn't supported.

== Changelog ==

= 1.0.0 =
* Initial release.
