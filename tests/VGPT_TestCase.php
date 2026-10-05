<?php
/**
 * Shared base test case.
 *
 * @package Viget\PrimaryTerm
 */

use Viget\PrimaryTerm\Core;
use Viget\PrimaryTerm\Editor;

/**
 * Shared base test case with helpers for enabling taxonomies.
 */
abstract class VGPT_TestCase extends WP_UnitTestCase {

	/**
	 * Registered taxonomies returned by the vgpt_registered_taxonomies filter.
	 *
	 * @var string[]
	 */
	protected array $registered = [];

	/**
	 * Registers the filter that returns $this->registered.
	 */
	public function set_up() {
		parent::set_up();

		$this->registered = [];
		add_filter( 'vgpt_registered_taxonomies', [ $this, 'filter_registered' ] );
	}

	/**
	 * Returns the taxonomies registered by the test.
	 *
	 * @param array $taxonomies Taxonomy slugs.
	 *
	 * @return array
	 */
	public function filter_registered( array $taxonomies ): array {
		return array_merge( $taxonomies, $this->registered );
	}

	/**
	 * Saves taxonomies as if checked on the settings page.
	 *
	 * @param string[] $taxonomies Taxonomy slugs.
	 *
	 * @return void
	 */
	protected function save_taxonomies( array $taxonomies ): void {
		update_option( Core::OPTION_NAME, [ 'taxonomies' => $taxonomies ] );
	}

	/**
	 * Registers the primary term meta again. The suite unregisters every meta key after each test.
	 *
	 * @return void
	 */
	protected function register_meta(): void {
		Editor::get_instance()->register_meta();
	}
}
