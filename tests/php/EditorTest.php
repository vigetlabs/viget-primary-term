<?php
/**
 * Tests for the Editor class.
 *
 * @package Viget\PrimaryTerm
 */

use Viget\PrimaryTerm\Editor;

/**
 * Tests for the Editor class.
 */
class EditorTest extends VGPT_TestCase {

	/**
	 * Enables categories and registers the meta.
	 */
	public function set_up() {
		parent::set_up();

		$this->registered = [ 'category' ];
		$this->register_meta();
	}

	/**
	 * Resets enqueued scripts.
	 */
	public function tear_down() {
		wp_dequeue_script( Editor::SCRIPT_HANDLE );
		wp_deregister_script( Editor::SCRIPT_HANDLE );
		parent::tear_down();
	}

	/**
	 * Saves a post's primary category through the REST API.
	 *
	 * @param int $post_id Post ID.
	 * @param int $term_id Term ID.
	 *
	 * @return WP_REST_Response
	 */
	protected function save_primary( int $post_id, int $term_id ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', rest_get_route_for_post( $post_id ) );
		$request->set_body_params( [ 'meta' => [ vgpt()->get_meta_key( 'category' ) => $term_id ] ] );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * register_meta() registers the meta for REST on each post type the taxonomy is attached to.
	 */
	public function test_register_meta() {
		$meta = get_registered_meta_keys( 'post', 'post' );
		$key  = vgpt()->get_meta_key( 'category' );

		$this->assertArrayHasKey( $key, $meta );
		$this->assertTrue( $meta[ $key ]['show_in_rest'] );
		$this->assertSame( 'integer', $meta[ $key ]['type'] );
		$this->assertTrue( post_type_supports( 'post', 'custom-fields' ) );
		$this->assertArrayNotHasKey( $key, get_registered_meta_keys( 'post', 'page' ) );
	}

	/**
	 * Editors save the primary term with the post.
	 */
	public function test_editor_saves_primary_term() {
		$term = self::factory()->category->create();
		$post = self::factory()->post->create();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$this->assertSame( 200, $this->save_primary( $post, $term )->get_status() );
		$this->assertSame( $term, (int) get_post_meta( $post, vgpt()->get_meta_key( 'category' ), true ) );
	}

	/**
	 * Users who can't edit the post can't save it.
	 */
	public function test_contributor_cannot_save_primary_term() {
		$post = self::factory()->post->create();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'contributor' ] ) );

		$this->assertGreaterThanOrEqual( 400, $this->save_primary( $post, self::factory()->category->create() )->get_status() );
		$this->assertFalse( metadata_exists( 'post', $post, vgpt()->get_meta_key( 'category' ) ) );
	}

	/**
	 * can_edit_meta() follows edit_post.
	 */
	public function test_can_edit_meta() {
		$post = self::factory()->post->create();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertFalse( Editor::get_instance()->can_edit_meta( true, 'key', $post ) );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
		$this->assertTrue( Editor::get_instance()->can_edit_meta( false, 'key', $post ) );
	}

	/**
	 * The editor script loads only on screens whose post type has an enabled taxonomy.
	 */
	public function test_script_loads_only_for_enabled_post_types() {
		set_current_screen( 'page' );
		Editor::get_instance()->enqueue_editor_assets();
		$this->assertFalse( wp_script_is( Editor::SCRIPT_HANDLE ) );

		set_current_screen( 'post' );
		Editor::get_instance()->enqueue_editor_assets();
		$this->assertTrue( wp_script_is( Editor::SCRIPT_HANDLE ) );

		$data = implode( '', wp_scripts()->get_data( Editor::SCRIPT_HANDLE, 'before' ) );

		$this->assertStringContainsString( '"category":{"singular":"Category","metaKey":"_vgpt_primary_category"}', $data );
	}
}
