<?php
/**
 * Tests for the Settings class.
 *
 * @package Viget\PrimaryTerm
 */

use Viget\PrimaryTerm\Settings;

/**
 * Tests for the Settings class.
 */
class SettingsTest extends VGPT_TestCase {

	/**
	 * Finds a settings page row by taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return array|null
	 */
	protected function row( string $taxonomy ): ?array {
		foreach ( Settings::get_instance()->get_rows() as $row ) {
			if ( $taxonomy === $row['taxonomy'] ) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * sanitize_settings() keeps existing taxonomies and drops missing, duplicate, and registered ones.
	 */
	public function test_sanitize_settings() {
		$this->registered = [ 'post_tag' ];

		$this->assertSame(
			[ 'taxonomies' => [ 'category' ] ],
			Settings::sanitize_settings( [ 'taxonomies' => [ 'Category', 'category', 'post_tag', 'missing_taxonomy' ] ] )
		);
		$this->assertSame( [ 'taxonomies' => [] ], Settings::sanitize_settings( 'nonsense' ) );
	}

	/**
	 * Saving the option runs it through sanitize_settings().
	 */
	public function test_saving_sanitizes() {
		Settings::get_instance()->register_settings();

		update_option( Settings::OPTION_NAME, [ 'taxonomies' => [ 'category', 'missing_taxonomy' ] ] );

		$this->assertSame( [ 'category' ], vgpt()->get_saved_taxonomies() );
	}

	/**
	 * Registered taxonomies show first as locked, checked rows.
	 */
	public function test_registered_rows_are_locked() {
		$this->registered = [ 'post_tag' ];

		$rows = Settings::get_instance()->get_rows();

		$this->assertSame( 'post_tag', $rows[0]['taxonomy'] );
		$this->assertTrue( $rows[0]['registered'] );
		$this->assertTrue( $rows[0]['checked'] );
		$this->assertNull( $rows[0]['note'] );
	}

	/**
	 * Saved taxonomies are checked; the rest aren't.
	 */
	public function test_saved_rows_are_checked() {
		$this->save_taxonomies( [ 'category' ] );

		$this->assertTrue( $this->row( 'category' )['checked'] );
		$this->assertFalse( $this->row( 'category' )['registered'] );
		$this->assertFalse( $this->row( 'post_tag' )['checked'] );
	}

	/**
	 * A registered taxonomy that doesn't exist, or isn't in the block editor, gets a note.
	 */
	public function test_registered_rows_note_why_they_are_off() {
		register_taxonomy( 'vgpt_hidden', 'post', [ 'show_in_rest' => false ] );
		$this->registered = [ 'missing_taxonomy', 'vgpt_hidden' ];

		$this->assertStringContainsString( 'not registered', $this->row( 'missing_taxonomy' )['note'] );
		$this->assertStringContainsString( 'block editor', $this->row( 'vgpt_hidden' )['note'] );
	}

	/**
	 * Only block editor taxonomies are listed unless registered.
	 */
	public function test_rows_skip_taxonomies_outside_the_block_editor() {
		register_taxonomy( 'vgpt_hidden', 'post', [ 'show_in_rest' => false ] );

		$this->assertNull( $this->row( 'vgpt_hidden' ) );
	}
}
