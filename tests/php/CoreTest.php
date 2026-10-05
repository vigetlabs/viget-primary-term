<?php
/**
 * Tests for the Core class.
 *
 * @package Viget\PrimaryTerm
 */

/**
 * Tests for the Core class.
 */
class CoreTest extends VGPT_TestCase {

	/**
	 * Creates a category.
	 *
	 * @param string $name   Name.
	 * @param int    $parent Parent term ID.
	 *
	 * @return int
	 */
	protected function category( string $name, int $parent = 0 ): int {
		return self::factory()->category->create(
			[
				'name'   => $name,
				'parent' => $parent,
			]
		);
	}

	/**
	 * get_taxonomies() merges registered and saved taxonomies, registered first, without duplicates or missing taxonomies.
	 */
	public function test_get_taxonomies() {
		$this->registered = [ 'post_tag', 'missing_taxonomy' ];
		$this->save_taxonomies( [ 'category', 'post_tag' ] );

		$this->assertSame( [ 'post_tag', 'category' ], vgpt()->get_taxonomies() );
	}

	/**
	 * get_registered_taxonomies() sanitizes and de-duplicates the filter's slugs.
	 */
	public function test_get_registered_taxonomies_sanitizes() {
		$this->registered = [ 'Category', 'category', '', 5 ];

		$this->assertSame( [ 'category' ], vgpt()->get_registered_taxonomies() );
	}

	/**
	 * get_taxonomies_for_post_type() returns only enabled taxonomies the post type uses.
	 */
	public function test_get_taxonomies_for_post_type() {
		$this->save_taxonomies( [ 'category' ] );

		$this->assertSame( [ 'category' ], vgpt()->get_taxonomies_for_post_type( 'post' ) );
		$this->assertSame( [], vgpt()->get_taxonomies_for_post_type( 'page' ) );
	}

	/**
	 * get_meta_key() prefixes the taxonomy.
	 */
	public function test_get_meta_key() {
		$this->assertSame( '_vgpt_primary_category', vgpt()->get_meta_key( 'category' ) );
	}

	/**
	 * get_primary_term() returns the saved primary term.
	 */
	public function test_get_primary_term_uses_saved_term() {
		$chains = $this->category( 'Chains' );
		$roller = $this->category( 'Roller', $chains );
		$post   = self::factory()->post->create();

		wp_set_object_terms( $post, [ $chains, $roller ], 'category' );
		update_post_meta( $post, vgpt()->get_meta_key( 'category' ), $chains );

		$this->assertSame( $chains, vgpt()->get_primary_term( $post, 'category' )->term_id );
	}

	/**
	 * get_primary_term() ignores a saved term that isn't assigned anymore.
	 */
	public function test_get_primary_term_ignores_unassigned_term() {
		$chains = $this->category( 'Chains' );
		$other  = $this->category( 'Other' );
		$post   = self::factory()->post->create();

		wp_set_object_terms( $post, [ $chains ], 'category' );
		update_post_meta( $post, vgpt()->get_meta_key( 'category' ), $other );

		$this->assertSame( $chains, vgpt()->get_primary_term( $post, 'category' )->term_id );
		$this->assertNull( vgpt()->get_primary_term( $post, 'category', false ) );
	}

	/**
	 * get_primary_term() falls back to the deepest term in a hierarchical taxonomy.
	 */
	public function test_get_primary_term_falls_back_to_deepest() {
		$chains = $this->category( 'Chains' );
		$roller = $this->category( 'Roller', $chains );
		$ansi   = $this->category( 'ANSI', $roller );
		$post   = self::factory()->post->create();

		wp_set_object_terms( $post, [ $chains, $ansi, $roller ], 'category' );

		$this->assertSame( $ansi, vgpt()->get_primary_term( $post, 'category' )->term_id );
	}

	/**
	 * get_primary_term() falls back to the first term, by name, in a flat taxonomy.
	 */
	public function test_get_primary_term_falls_back_to_first_flat_term() {
		$post = self::factory()->post->create();

		wp_set_object_terms( $post, [ 'Zinc', 'Alloy' ], 'post_tag' );

		$this->assertSame( 'Alloy', vgpt()->get_primary_term( $post, 'post_tag' )->name );
	}

	/**
	 * get_primary_term() returns null without a fallback, or without terms.
	 */
	public function test_get_primary_term_null_cases() {
		$post = self::factory()->post->create();

		$this->assertNull( vgpt()->get_primary_term( $post, 'post_tag' ) );

		wp_set_object_terms( $post, [ 'Alloy' ], 'post_tag' );

		$this->assertNull( vgpt()->get_primary_term( $post, 'post_tag', false ) );
	}

	/**
	 * vgpt_default_term filters the fallback.
	 */
	public function test_default_term_filter() {
		$chains = $this->category( 'Chains' );
		$other  = get_term( $this->category( 'Other' ) );
		$post   = self::factory()->post->create();

		wp_set_object_terms( $post, [ $chains ], 'category' );

		$filter = static function ( $term, $post_id, $taxonomy ) use ( $other, $post ) {
			return $post === $post_id && 'category' === $taxonomy ? $other : $term;
		};
		add_filter( 'vgpt_default_term', $filter, 10, 3 );

		$this->assertSame( $other->term_id, vgpt()->get_primary_term( $post, 'category' )->term_id );

		update_post_meta( $post, vgpt()->get_meta_key( 'category' ), $chains );

		$this->assertSame( $chains, vgpt()->get_primary_term( $post, 'category' )->term_id, 'A saved primary term skips the filter.' );
	}
}
