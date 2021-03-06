<?php

class CategorySynonymsTest extends WP_UnitTestCase {

	private $categorySynonyms;
	private $post_type;


	function __construct()
	{
		global $categorySynonyms_instance;
		$this->categorySynonyms = $categorySynonyms_instance;
		$this->post_type = $categorySynonyms_instance->post_type;
	}


	//for my training
	function test_if_module_Ginq_loaded()
	{
		$result = Ginq::from( array( 1,2,3,4,5,6,7,8,9 ) )
			->where( function( $x ){return  $x % 3 === 0; } )
			->select( function( $x ){return $x; } )
			->toList();

		$this->assertEquals( $result, array( 3, 6, 9 ) );
	}


	function test_custom_post_type_registration()
	{
		// [appearance] it should be that the singleton instance exists.
		$this->assertNotNull( $this->categorySynonyms );

		// [behavior] it should be that the custom post type have been registered.
		$this->assertTrue( post_type_exists( $this->post_type ) );
	}


	function test_if_custom_taxonomy_registered_for_custom_post_type()
	{
		// [behavior] it should be that the custop post type have been linked with all the default taxonomies.
		$this->assertEquals(
			array_keys( get_taxonomies() ),
			get_object_taxonomies( $this->post_type )
		);

	}


	function test_for_synonyms_registration_and_unregistration()
	{
		//provisioning
		$cs = $this->categorySynonyms;

		$synonymous_terms = array( 'white', 'bianco', 'weiss', 'abyad', '白' );
		$tax_name = 'category';

		// `synonyms_id` is `post_id`
		$registration_info = $cs->register( array(
			'label'    => 'synonym label in test to identifiy the synonym group',
			'terms'    => $synonymous_terms,
			'taxonomy' => $tax_name,
		) );


		// describe registration test

		// [appearance] it should be that register returns 3element array of synonyms_definition_id, concerned taxonomy name and array of registerd term_taxonomy_ids.;
		$this->assertEquals(
			array_keys( $registration_info ),
			array( 'synonyms_definition_id', 'taxonomy', 'term_taxonomy_ids' )
		);

		// [appearance] it should be that syonnyms_id is integer if success
		$this->assertInternalType ('integer', $registration_info['synonyms_definition_id'] );
		$this->assertNotEquals( 0, $registration_info['synonyms_definition_id'] );

		// [appearance] taxonomy name should be returened directly.
		$this->assertEquals($tax_name, $registration_info['taxonomy']);
		// [appearance] also the taxnomy name should be set as custom field.
		$this->assertEquals($tax_name, get_post_meta(
			$registration_info['synonyms_definition_id'],
			CATEGORY_SYNONYMS_TAXONOMY_FIELD_KEY,
			true
		) );

		// check if same amount of ids has been returened.
		foreach ( $registration_info['term_taxonomy_ids'] as $index => $combind_term_taxonomy_id ) {

			$this->assertInternalType( 'integer', $combind_term_taxonomy_id['term_id'] );
			$this->assertInternalType( 'integer', $combind_term_taxonomy_id['term_taxonomy_id'] );

			$this->assertEquals(
				$synonymous_terms[$index],
				get_term_by( 'id', $combind_term_taxonomy_id['term_taxonomy_id'], $tax_name )->name
			);

		}

		// [behavior] it should be that terms have been generated after registration
		foreach ( $synonymous_terms as $term ){

			// negative result of `term_exists` is 0 or null
			$this->assertNotFalse ( term_exists( $term, $tax_name ) );

			$this->assertNotNull ( term_exists( $term, $tax_name ) );

		}

		// [behavior] it should be that a custom post have been generated.
		$this->assertNotFalse( get_post_status( $registration_info['synonyms_definition_id'] ) );

		$this->assertEquals( get_post_type( $registration_info['synonyms_definition_id'] ), $this->post_type );


		// [behavior] it should be that the post have been attached all the terms
		foreach ( $synonymous_terms as $term ) {

			$this->assertTrue ( has_term( $term, $tax_name, $registration_info['synonyms_definition_id'] ) );

		}


		// describe unregister test
		$unregistration_info = $cs->unregister( $registration_info['synonyms_definition_id'] );

		// [appearance] it should be that the function returns copied deleted post object.
		$this->assertNotFalse( $unregistration_info );

		// [behavior] it should be that the post have been deleted.
		$this->assertFalse( get_post_status( $unregistration_info->ID ) );

	}


	function test_for_synonym_definition_update()
	{
		//provisioning
		$cs = $this->categorySynonyms;

		$synonymous_terms = array( 'white', 'bianco', 'weiss' );
		$tax_name = 'category';

		// `synonyms_id` is `post_id`
		$info_before = $cs->register( array(
			'label'    => 'synonym label in test to identifiy the synonym group',
			'terms'    => $synonymous_terms,
			'taxonomy' => $tax_name,
		) );

		$info_after = $cs->update( $info_before['synonyms_definition_id'], array(
			'label' => 'label updated.',
			'terms' => array( 'white', 'abyad', 'weiss' ),
			'taxonomy' => 'post_tag'
		) );

		$actual = array();
		$def = $cs->get_defined_synonyms_by_id( $info_before['synonyms_definition_id'] );

		$actual['terms'] = array();
		foreach ( $def['term_taxonomy_ids'] as $tt_id ) {
			array_push( $actual['terms'], get_term_by( 'id', $tt_id, $def['taxonomy'] )->name );
		}
		;
		$actual['taxonomy'] = $def['taxonomy'];
		$actual['label'] = get_the_title( $info_before['synonyms_definition_id'] );

		$this->assertEquals( 'label updated.', $actual['label'] );
		$this->assertEquals( 'post_tag', $actual['taxonomy'] );
		$this->assertEquals( array( 'white', 'abyad', 'weiss' ), $actual['terms'] );
	}



	function test_of_get_defined_synonyms_by_id()
	{
		//provisioning
		$cs = $this->categorySynonyms;
		$synonymous_terms = array( 'wine', 'ワイン' );
		$tax_name = 'category';

		$registration_info = $cs->register( array(
			'label' => 'synonym label in test to read the synonym group',
			'taxonomy' => $tax_name,
			'terms' => $synonymous_terms,
		) );

		$result = $cs->get_defined_synonyms_by_id( $registration_info['synonyms_definition_id'] );

		$this->assertEquals( count( $result['term_taxonomy_ids'] ), count( $synonymous_terms ) );
		$this->assertEquals( $result['taxonomy'], $tax_name );

		// clean up for next test.
		$cs->unregister( $registration_info['synonyms_definition_id'] );
	}


	function test_of_get_synonymous_terms_by()
	{
		//provisioning
		$cs = $this->categorySynonyms;
		$args = array(
			array(
				'taxonomy' => 'category',
				'terms'    => array( 'mocha', 'coffee' ),
			),
			array(
				'taxonomy' => 'category',
				'terms' => array( 'coffee', 'qafa' ),
			),
		);
		$infos = array();
		foreach ( $args as $arg ) {
			array_push( $infos, $cs->register( $arg ) );
		}

		$result = $cs->get_synonymous_terms_by( array(
			'field'    => 'name',
			'value'    => 'coffee',
			'taxonomy' => 'category',
		) );

		$this->assertEquals( count( $result['term_taxonomy_ids'] ), 3 );
		$this->assertEquals( $result['taxonomy'], 'category' );



		//clean up
		foreach ( $infos as $info ) {
			$cs->unregister( $info['synonyms_definition_id'] );
		}
	}


	function test_of_get_all_definitions() {

		//provisioning
		$cs = $this->categorySynonyms;
		$args = array(
			array(
				'taxonomy' => 'category',
				'terms'    => array( 'mocha', 'coffee' ),
			),
			array(
				'taxonomy' => 'category',
				'terms' => array( 'coffee', 'qafa' ),
			),
		);
		$infos = array();
		foreach ( $args as $arg ) {
			array_push( $infos, $cs->register( $arg ) );
		}


		$definitions = $this->categorySynonyms->get_all_definitions();

		$this->assertInternalType( 'array', $definitions );
		$this->assertEquals( count( $definitions ), count($args) );
		foreach ( $definitions as $def ) {
			$this->assertInternalType( 'integer', $def['synonyms_definition_id'] );
			$this->assertInternalType( 'string', $def['label'] );
			$this->assertInternalType( 'string', $def['taxonomy'] );
			$this->assertInternalType( 'array', $def['terms'] );
			foreach ( $def['terms'] as $term ) {
				$this->assertInternalType( 'integer', $term );
			}
		}

		//clean up
		foreach ( $infos as $info ) {
			$cs->unregister( $info['synonyms_definition_id'] );
		}
	}


	function test_of_extend_template_tags() {
		// function(custom tempalate tag) to test
		$this->assertTrue( function_exists( 'get_the_term_list_synonymously' ) );
		$this->assertTrue( function_exists( 'get_term_link_synonymously' ) );


		//provisioning
		$cs = $this->categorySynonyms;
		$args = array(
			array(
				'taxonomy' => 'category',
				'terms'    => array( 'mocha', 'coffee' ),
			),
			array(
				'taxonomy' => 'category',
				'terms' => array( 'coffee', 'qafa', 'mocha' ),
			),
		);
		$infos = array();
		foreach ( $args as $arg ) {
			array_push( $infos, $cs->register( $arg ) );
		}
		//// create dummy post
		$test_post_id = wp_insert_post( array(
			'post_content' => 'test post content',
			'post_type'   => 'post',
			'post_category' => array( get_term_by( 'name', 'coffee', 'category' )->term_id )
		) );
		// calculate expectation
		$the_terms = array( 'mocha', 'coffee', 'qafa' );
		$expected_flagments = array();
		foreach ( $the_terms as $the_term ) {
			array_push( $expected_flagments, '<a href="' . get_term_link( $the_term, 'category'  ) . '">' . $the_term . '</a>' );
		}
		$before = '<h1>this is before</h1>';
		$sep = 'separator';
		$after = '<small>this is after</small>';
		$expectation = $before . implode( $sep, $expected_flagments ) . $after;
		$actual = get_the_term_list_synonymously( $test_post_id, 'category', $before, $sep, $after );
		//assert
		$this->assertEquals( $expectation, $actual );

		//calculate expetation 2
		$expected = get_home_url() . '/category/qafa,mocha,coffee' ;
		$actual = get_term_link_synonymously( 'qafa', 'category' );
		//assert
		$this->assertEquals( $expected, $actual );


		//clean up
		foreach ( $infos as $info ) {
			$cs->unregister( $info['synonyms_definition_id'] );
		}

	}
}
