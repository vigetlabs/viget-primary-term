<?php
/**
 * Core class
 *
 * @package Viget\PrimaryTerm
 */

namespace Viget\PrimaryTerm;

/**
 * Core class
 *
 * @package Viget\PrimaryTerm
 */
class Core {

	/**
	 * Option name for plugin settings.
	 */
	const OPTION_NAME = 'vgpt_settings';

	/**
	 * Prefix for the post meta key that stores a taxonomy's primary term ID.
	 */
	const META_KEY_PREFIX = '_vgpt_primary_';

	/**
	 * Instance of this class.
	 *
	 * @var Core|null
	 */
	private static ?Core $instance = null;

	/**
	 * Settings instance.
	 *
	 * @var Settings|null
	 */
	public ?Settings $settings = null;

	/**
	 * Editor instance.
	 *
	 * @var Editor|null
	 */
	private ?Editor $editor = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Core
	 */
	public static function get_instance(): Core {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	private function init(): void {
		// Load dependencies.
		require_once VGPT_PLUGIN_PATH . 'includes/class-settings.php';
		require_once VGPT_PLUGIN_PATH . 'includes/class-editor.php';

		// Initialize dependencies.
		$this->settings = Settings::get_instance();
		$this->editor   = Editor::get_instance();

		add_action( 'deleted_term_relationships', [ $this, 'clear_removed_primary_term' ], 10, 3 );

		// Check for plugin updates from GitHub releases. Cron and WP-CLI check without admin_init.
		add_action( wp_doing_cron() || ( \defined( 'WP_CLI' ) && WP_CLI ) ? 'init' : 'admin_init', [ $this, 'init_updater' ] );
	}

	/**
	 * Loads the GitHub updater, only where update checks run.
	 *
	 * @return void
	 */
	public function init_updater(): void {
		require_once VGPT_PLUGIN_PATH . 'includes/class-github-plugin-updater.php';

		new GitHub_Plugin_Updater( VGPT_PLUGIN_FILE, 'vigetlabs', 'viget-primary-term' );
	}

	/**
	 * Clears a post's primary term when that term is removed from the post.
	 *
	 * @param int    $object_id Object ID.
	 * @param int[]  $tt_ids    Removed term taxonomy IDs.
	 * @param string $taxonomy  Taxonomy slug.
	 *
	 * @return void
	 */
	public function clear_removed_primary_term( int $object_id, array $tt_ids, string $taxonomy ): void {
		if ( ! \in_array( $taxonomy, $this->get_taxonomies(), true ) ) {
			return;
		}

		$meta_key = $this->get_meta_key( $taxonomy );
		$primary  = get_term( (int) get_post_meta( $object_id, $meta_key, true ), $taxonomy );

		if ( $primary instanceof \WP_Term && \in_array( $primary->term_taxonomy_id, array_map( 'intval', $tt_ids ), true ) ) {
			delete_post_meta( $object_id, $meta_key );
		}
	}

	/**
	 * Gets every taxonomy with a primary term: registered in code first, then saved on the settings page.
	 *
	 * Taxonomies that don't exist are left out, including any added by the `vgpt_taxonomies` filter.
	 *
	 * @return string[]
	 */
	public function get_taxonomies(): array {
		$taxonomies = array_values(
			array_filter(
				array_unique( array_merge( $this->get_registered_taxonomies(), $this->get_saved_taxonomies() ) ),
				'taxonomy_exists'
			)
		);

		/**
		 * Filters the taxonomies with a primary term.
		 *
		 * @param string[] $taxonomies Taxonomy slugs.
		 */
		$taxonomies = (array) apply_filters( 'vgpt_taxonomies', $taxonomies );

		return array_values( array_filter( $taxonomies, fn( $taxonomy ) => \is_string( $taxonomy ) && taxonomy_exists( $taxonomy ) ) );
	}

	/**
	 * Gets the taxonomies registered in code through the `vgpt_registered_taxonomies` filter.
	 *
	 * @return string[]
	 */
	public function get_registered_taxonomies(): array {
		/**
		 * Filters the taxonomies registered in code. They show on the settings page as locked rows.
		 *
		 * @param string[] $taxonomies Taxonomy slugs.
		 */
		return $this->sanitize_taxonomies( (array) apply_filters( 'vgpt_registered_taxonomies', [] ) );
	}

	/**
	 * Gets the taxonomies saved on the settings page.
	 *
	 * @return string[]
	 */
	public function get_saved_taxonomies(): array {
		$settings = get_option( self::OPTION_NAME, [] );

		return $this->sanitize_taxonomies( \is_array( $settings ) ? (array) ( $settings['taxonomies'] ?? [] ) : [] );
	}

	/**
	 * Gets the enabled taxonomies a post type uses.
	 *
	 * @param string $post_type Post type slug.
	 *
	 * @return string[]
	 */
	public function get_taxonomies_for_post_type( string $post_type ): array {
		return array_values( array_intersect( $this->get_taxonomies(), get_object_taxonomies( $post_type ) ) );
	}

	/**
	 * Gets the post meta key that stores a taxonomy's primary term ID.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return string
	 */
	public function get_meta_key( string $taxonomy ): string {
		return self::META_KEY_PREFIX . $taxonomy;
	}

	/**
	 * Gets a post's primary term.
	 *
	 * Returns the saved primary term while it's still assigned. Otherwise, with
	 * `$fallback`, the deepest assigned term (hierarchical) or the first (flat).
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @param bool   $fallback Whether to fall back to a default term.
	 *
	 * @return \WP_Term|null
	 */
	public function get_primary_term( int $post_id, string $taxonomy, bool $fallback = true ): ?\WP_Term {
		$terms = get_the_terms( $post_id, $taxonomy );
		$terms = $terms && ! is_wp_error( $terms ) ? $terms : [];

		$primary_id = (int) get_post_meta( $post_id, $this->get_meta_key( $taxonomy ), true );

		foreach ( $terms as $term ) {
			if ( $term->term_id === $primary_id ) {
				return $term;
			}
		}

		if ( ! $fallback ) {
			return null;
		}

		/**
		 * Filters the term used when a post has no primary term.
		 *
		 * @param \WP_Term|null $term     The deepest assigned term (hierarchical) or the first (flat).
		 * @param int           $post_id  Post ID.
		 * @param string        $taxonomy Taxonomy slug.
		 */
		return apply_filters( 'vgpt_default_term', $this->get_default_term( $terms, $taxonomy ), $post_id, $taxonomy );
	}

	/**
	 * Gets the deepest of a post's terms (hierarchical), or the first (flat).
	 *
	 * @param \WP_Term[] $terms    Assigned terms, in order.
	 * @param string     $taxonomy Taxonomy slug.
	 *
	 * @return \WP_Term|null
	 */
	private function get_default_term( array $terms, string $taxonomy ): ?\WP_Term {
		if ( ! $terms || ! is_taxonomy_hierarchical( $taxonomy ) ) {
			return $terms[0] ?? null;
		}

		$deepest   = null;
		$max_depth = -1;

		foreach ( $terms as $term ) {
			$depth = \count( get_ancestors( $term->term_id, $taxonomy, 'taxonomy' ) );

			if ( $depth > $max_depth ) {
				$deepest   = $term;
				$max_depth = $depth;
			}
		}

		return $deepest;
	}

	/**
	 * Sanitizes and de-duplicates a list of taxonomy slugs.
	 *
	 * @param array $taxonomies Taxonomy slugs.
	 *
	 * @return string[]
	 */
	private function sanitize_taxonomies( array $taxonomies ): array {
		return array_values( array_unique( array_filter( array_map( 'sanitize_key', array_filter( $taxonomies, 'is_string' ) ) ) ) );
	}
}
