<?php
/**
 * Editor class
 *
 * @package Viget\PrimaryTerm
 */

namespace Viget\PrimaryTerm;

/**
 * Registers the primary term meta and adds its select to the block editor's taxonomy panels.
 *
 * @package Viget\PrimaryTerm
 */
class Editor {

	/**
	 * Script handle.
	 */
	const SCRIPT_HANDLE = 'vgpt-editor';

	/**
	 * Instance of this class.
	 *
	 * @var Editor|null
	 */
	private static ?Editor $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Editor
	 */
	public static function get_instance(): Editor {
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
	 * Initialize the hooks.
	 *
	 * @return void
	 */
	private function init(): void {
		// Late, so taxonomies registered on init are in place.
		add_action( 'init', [ $this, 'register_meta' ], 99 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Registers the primary term meta for every post type an enabled taxonomy is attached to.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		foreach ( vgpt()->get_taxonomies() as $taxonomy ) {
			foreach ( get_taxonomy( $taxonomy )->object_type as $post_type ) {
				// The REST API only includes meta for post types that support custom fields.
				add_post_type_support( $post_type, 'custom-fields' );

				register_post_meta(
					$post_type,
					vgpt()->get_meta_key( $taxonomy ),
					[
						'type'              => 'integer',
						'single'            => true,
						'default'           => 0,
						'show_in_rest'      => true,
						'sanitize_callback' => 'absint',
						'auth_callback'     => [ $this, 'can_edit_meta' ],
					]
				);
			}
		}
	}

	/**
	 * Lets anyone who can edit the post change its primary terms.
	 *
	 * @param bool   $allowed  Whether the user can edit the meta.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 *
	 * @return bool
	 */
	public function can_edit_meta( bool $allowed, string $meta_key, int $post_id ): bool {
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Enqueues the primary term select on screens whose post type has an enabled taxonomy.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		$screen     = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$taxonomies = $screen && $screen->post_type ? vgpt()->get_taxonomies_for_post_type( $screen->post_type ) : [];

		if ( ! $taxonomies ) {
			return;
		}

		$asset_file = VGPT_PLUGIN_PATH . 'build/editor.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: [
				'dependencies' => [],
				'version'      => VGPT_PLUGIN_VERSION,
			];

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			VGPT_PLUGIN_URL . 'build/editor.js',
			$asset['dependencies'],
			$asset['version'],
			[ 'in_footer' => true ]
		);

		$data = [];

		foreach ( $taxonomies as $taxonomy ) {
			$data[ $taxonomy ] = [
				'singular' => get_taxonomy( $taxonomy )->labels->singular_name,
				'metaKey'  => vgpt()->get_meta_key( $taxonomy ),
			];
		}

		wp_add_inline_script( self::SCRIPT_HANDLE, 'window.vgptTaxonomies = ' . wp_json_encode( $data ) . ';', 'before' );
		wp_set_script_translations( self::SCRIPT_HANDLE, 'viget-primary-term', VGPT_PLUGIN_PATH . 'languages' );
	}
}
