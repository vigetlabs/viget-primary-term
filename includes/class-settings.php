<?php
/**
 * Settings class for Viget Primary Term
 *
 * @package Viget\PrimaryTerm
 */

namespace Viget\PrimaryTerm;

/**
 * Settings class
 *
 * @package Viget\PrimaryTerm
 */
class Settings {

	/**
	 * Page slug for plugin settings.
	 */
	const PAGE_SLUG = 'vgpt-settings';

	/**
	 * Option name for plugin settings.
	 */
	const OPTION_NAME = Core::OPTION_NAME;

	/**
	 * Instance of this class.
	 *
	 * @var Settings|null
	 */
	private static ?Settings $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Settings
	 */
	public static function get_instance(): Settings {
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
	 * Initialize the settings.
	 *
	 * @return void
	 */
	private function init(): void {
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_assets' ] );
	}

	/**
	 * Registers the admin assets.
	 *
	 * @return void
	 */
	public function register_admin_assets(): void {
		$style_path = VGPT_PLUGIN_PATH . 'build/admin-style.css';

		wp_register_style(
			'vgpt-admin-styles',
			VGPT_PLUGIN_URL . 'build/admin-style.css',
			[],
			file_exists( $style_path ) ? filemtime( $style_path ) : VGPT_PLUGIN_VERSION
		);
		wp_style_add_data( 'vgpt-admin-styles', 'rtl', 'replace' );
	}

	/**
	 * Registers the plugin settings using the Settings API.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_NAME,
			self::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => [],
			]
		);

		add_settings_section(
			'vgpt_main_section',
			esc_html__( 'Taxonomies', 'viget-primary-term' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'vgpt_taxonomies_field',
			esc_html__( 'Primary term for', 'viget-primary-term' ),
			[ $this, 'render_taxonomies_field' ],
			self::PAGE_SLUG,
			'vgpt_main_section'
		);
	}

	/**
	 * Sanitizes plugin settings before saving.
	 *
	 * Taxonomies registered in code aren't saved, since they're always on.
	 *
	 * @param mixed $input Raw input.
	 *
	 * @return array
	 */
	public static function sanitize_settings( $input ): array {
		$taxonomies = \is_array( $input ) && \is_array( $input['taxonomies'] ?? null ) ? $input['taxonomies'] : [];
		$registered = vgpt()->get_registered_taxonomies();
		$sanitized  = [];

		foreach ( $taxonomies as $taxonomy ) {
			$taxonomy = sanitize_key( (string) $taxonomy );

			if ( taxonomy_exists( $taxonomy ) && ! \in_array( $taxonomy, $registered, true ) ) {
				$sanitized[] = $taxonomy;
			}
		}

		return [ 'taxonomies' => array_values( array_unique( $sanitized ) ) ];
	}

	/**
	 * Registers the settings page.
	 *
	 * @return void
	 */
	public function register_settings_page(): void {
		add_options_page(
			esc_html__( 'Primary Terms', 'viget-primary-term' ),
			esc_html__( 'Primary Terms', 'viget-primary-term' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_style( 'vgpt-admin-styles' );

		// Core tooltip for registered taxonomies (WordPress 7.1+).
		if ( wp_script_is( 'wp-tooltip', 'registered' ) ) {
			wp_enqueue_script( 'wp-tooltip' );
			wp_enqueue_style( 'wp-tooltip' );
		}

		require VGPT_PLUGIN_PATH . 'views/admin/settings.php';
	}

	/**
	 * Renders the taxonomies field.
	 *
	 * @return void
	 */
	public function render_taxonomies_field(): void {
		$rows = $this->get_rows();

		require VGPT_PLUGIN_PATH . 'views/admin/taxonomies-field.php';
	}

	/**
	 * Gets the settings page rows: registered taxonomies first, then every other block editor taxonomy.
	 *
	 * Each row has `taxonomy`, `label`, `post_types`, `registered`, `checked`, and `note`, why a registered taxonomy is off, or null.
	 *
	 * @return array
	 */
	public function get_rows(): array {
		$registered = vgpt()->get_registered_taxonomies();
		$saved      = vgpt()->get_saved_taxonomies();
		$rows       = [];

		foreach ( $registered as $taxonomy ) {
			$rows[ $taxonomy ] = $this->get_row( $taxonomy, true, true );
		}

		foreach ( get_taxonomies( [ 'show_ui' => true ], 'objects' ) as $taxonomy => $object ) {
			if ( ! isset( $rows[ $taxonomy ] ) && $object->show_in_rest ) {
				$rows[ $taxonomy ] = $this->get_row( $taxonomy, false, \in_array( $taxonomy, $saved, true ) );
			}
		}

		return array_values( $rows );
	}

	/**
	 * Gets one settings page row.
	 *
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $registered Whether it's registered in code.
	 * @param bool   $checked    Whether it's enabled.
	 *
	 * @return array
	 */
	private function get_row( string $taxonomy, bool $registered, bool $checked ): array {
		$object     = get_taxonomy( $taxonomy );
		$post_types = [];

		foreach ( $object ? $object->object_type : [] as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			$post_types[]     = $post_type_object ? $post_type_object->labels->name : $post_type;
		}

		$note = null;

		if ( $registered && ! $object ) {
			/* translators: %s: taxonomy slug */
			$note = sprintf( __( 'Off: the "%s" taxonomy is not registered.', 'viget-primary-term' ), $taxonomy );
		} elseif ( $registered && ! $object->show_in_rest ) {
			$note = __( 'Off: this taxonomy is not shown in the block editor.', 'viget-primary-term' );
		}

		return [
			'taxonomy'   => $taxonomy,
			'label'      => $object ? $object->labels->name : $taxonomy,
			'post_types' => $post_types,
			'registered' => $registered,
			'checked'    => $checked,
			'note'       => $note,
		];
	}
}
