<?php

defined( 'ABSPATH' ) or die();

class Text_Replace_Test extends WP_UnitTestCase {

	protected static $text_to_link = array(
		':wp:'           => 'WordPress',
		":coffee2code:"  => "<a href='http://coffee2code.com' title='coffee2code'>coffee2code</a>",
		'Matt Mullenweg' => '<span title="Founder of WordPress">Matt Mullenweg</span>',
		'<strong>to be linked</strong>' => '<a href="http://example.org/link">to be linked</a>',
		'comma, here'    => 'Yes, a comma',
		'"quoted text"'  => 'quoted "text"',
		'blank'          => '',
		':WP:'           => "<a href='https://w.org'>WP</a> <!-- Replacement by <contact>person</contact> -->",
		'example.com/wp-content/uploads' => 'example.org/wp-content/uploads',
		':A&A:'          => 'Axis & Allies',
		'は'             => 'Foo',
		'@macnfoco'      => "Mac'N",
		'Cocktail glacé' => 'http://www.domain.com/cocktail-glace.html',
		'ユニコード漢字'   => 'http://php.net/manual/en/ref.mbstring.php',
		'ユニコード漢字 は' => 'replacment text',
		'Apple iPhone 6' => 'http://example.com/apple1',
		'iPhone 6'       => 'http://example.com/aople2',
		'test'           => 'http://example.com/txst1',
		'test place'     => 'http://example.com/txst2',
	);

	public static function setUpBeforeClass() {
		c2c_TextReplace::get_instance()->install();
	}

	public function setUp() {
		parent::setUp();
		c2c_TextReplace::get_instance()->reset_options();
		$this->set_option();
	}

	public function tearDown() {
		parent::tearDown();

		// Reset options
		c2c_TextReplace::get_instance()->reset_options();

		remove_filter( 'c2c_text_replace',                array( $this, 'add_text_to_replace' ) );
		remove_filter( 'c2c_text_replace_once',           '__return_true' );
		remove_filter( 'c2c_text_replace_case_sensitive', '__return_false' );
		remove_filter( 'c2c_text_replace_comments',       '__return_true' );
		remove_filter( 'c2c_text_replace_filters',        array( $this, 'add_custom_filter' ) );
	}


	/*
	 *
	 * DATA PROVIDERS
	 *
	 */


	public static function get_default_filters() {
		return array(
			array( 'the_content' ),
			array( 'the_excerpt' ),
			array( 'widget_text' ),
		);
	}

	public static function get_comment_filters() {
		return array(
			array( 'get_comment_text' ),
			array( 'get_comment_excerpt' ),
		);
	}

	public static function get_text_to_link() {
		return array_map( function($v) { return array( $v ); }, array_keys( self::$text_to_link ) );
	}


	//
	//
	// HELPER FUNCTIONS
	//
	//


	protected function text_replacements( $term = '' ) {
		$text_to_link = self::$text_to_link;

		if ( ! empty( $term ) ) {
			$text_to_link = isset( $text_to_link[ $term ] ) ? $text_to_link[ $term ] : '';
		}

		return $text_to_link;
	}

	protected function set_option( $settings = array() ) {
		$defaults = array(
			'text_to_replace' => $this->text_replacements(),
			'case_sensitive'  => true,
		);
		$settings = wp_parse_args( $settings, $defaults );
		c2c_TextReplace::get_instance()->update_option( $settings, true );
	}

	protected function text_replace( $text ) {
		return c2c_TextReplace::get_instance()->text_replace( $text );
	}

	protected function expected_text( $term ) {
		return $this->text_replacements( $term );
	}

	public function add_text_to_replace( $text_to_replace ) {
		$text_to_replace = (array) $text_to_replace;
		$text_to_replace['bbPress'] = '<a href="https://bbpress.org">bbPress - Forum Software</a>';
		return $text_to_replace;
	}

	public function add_custom_filter( $filters ) {
		$filters[] = 'custom_filter';
		return $filters;
	}


	//
	//
	// TESTS
	//
	//


	public function test_class_exists() {
		$this->assertTrue( class_exists( 'c2c_TextReplace' ) );
	}

	public function test_plugin_framework_class_name() {
		$this->assertTrue( class_exists( 'c2c_TextReplace_Plugin_048' ) );
	}

	public function test_plugin_framework_version() {
		$this->assertEquals( '048', c2c_TextReplace::get_instance()->c2c_plugin_version() );
	}

	public function test_version() {
		$this->assertEquals( '3.8', c2c_TextReplace::get_instance()->version() );
	}

	public function test_instance_object_is_returned() {
		$this->assertTrue( is_a( c2c_TextReplace::get_instance(), 'c2c_TextReplace' ) );
	}

	public function test_replaces_text() {
		$expected = $this->expected_text( ':coffee2code:' );

		$this->assertEquals( $expected, $this->text_replace( ':coffee2code:' ) );
		$this->assertEquals( "ends with $expected", $this->text_replace( 'ends with :coffee2code:' ) );
		$this->assertEquals( "ends with period $expected.", $this->text_replace( 'ends with period :coffee2code:.' ) );
		$this->assertEquals( "$expected starts", $this->text_replace( ':coffee2code: starts' ) );

		$this->assertEquals( $this->expected_text( 'Matt Mullenweg' ), $this->text_replace( 'Matt Mullenweg' ) );
	}

	/**
	 * @dataProvider get_text_to_link
	 */
	public function test_replaces_text_as_defined_in_setting( $text ) {
		$this->assertEquals( $this->expected_text( $text ), $this->text_replace( $text ) );
	}

	public function test_replaces_text_with_html_encoded_amp_ampersand() {
		$this->assertEquals( $this->expected_text( ':A&A:' ), $this->text_replace( ':A&amp;A:' ) );
	}

	public function test_replaces_text_with_html_encoded_038_ampersand() {
		$this->assertEquals( $this->expected_text( ':A&A:' ), $this->text_replace( ':A&#038;A:' ) );
	}

	public function test_replaces_multibyte_text() {
		$this->assertEquals( '漢字Fooユニコード', $this->text_replace( '漢字はユニコード' ) );
	}

	public function test_replaces_single_term_multiple_times() {
		$expected = $this->expected_text( ':coffee2code:' );

		$this->assertEquals( "$expected $expected $expected", $this->text_replace( ':coffee2code: :coffee2code: :coffee2code:' ) );
	}

	public function test_replaces_html_multiple_times() {
		$orig = '<strong>to be linked</strong>';
		$expected = $this->expected_text( $orig );

		$this->assertEquals( "$expected $expected $expected", $this->text_replace( "$orig $orig $orig" ) );
	}

	public function test_replaces_substrings() {
		$expected = $this->expected_text( ':coffee2code:' );

		$this->assertEquals( 'x' . $expected,       $this->text_replace( 'x:coffee2code:' ) );
		$this->assertEquals( 'y' . $expected . 'y', $this->text_replace( 'y:coffee2code:y' ) );
		$this->assertEquals( $expected . 'z',       $this->text_replace( ':coffee2code:z' ) );
	}

	public function test_replaces_html() {
		$this->assertEquals( $this->expected_text( '<strong>to be linked</strong>' ), $this->text_replace( '<strong>to be linked</strong>' ) );
	}

	public function test_replace_with_html_comment() {
		$expected = $this->expected_text( ':WP:' );

		$this->assertEquals( $expected, $this->text_replace( ':WP:' ) );
	}

	public function test_empty_replacement_removes_term() {
		$this->assertEquals( '', $this->text_replace( 'blank' ) );
	}

	public function test_does_not_replace_within_markup_attributes() {
		$format = '<a href="http://%s/file.png">http://%s/file.png</a>';
		$old    = 'example.com/wp-content/uploads';
		$new    = $this->expected_text( $old );

		$this->assertEquals(
			sprintf(
				$format,
				$old,
				$new
			),
			$this->text_replace( sprintf(
				$format,
				$old,
				$old
			) )
		);
	}

	public function test_replaces_with_case_sensitivity_by_default() {
		$expected = $this->expected_text( ':coffee2code:' );

		$this->assertEquals( $expected,       $this->text_replace( ':coffee2code:' ) );
		$this->assertEquals( ':Coffee2code:', $this->text_replace( ':Coffee2code:' ) );
		$this->assertEquals( ':COFFEE2CODE:', $this->text_replace( ':COFFEE2CODE:' ) );
	}

	/*
	 * With 'Apple iPhone 6' followed by 'iPhone 6' as link defines, the string
	 * 'Apple iPhone 6' should not have the 'iPhone 6' linkification applied to it.
	 */
	public function test_does_not_linkify_a_general_term_that_is_included_in_earlier_listed_term() {
		$string = 'Apple iPhone 6';

		$this->assertEquals( $this->expected_text( $string ), $this->text_replace( $string ) );
	}

	/**
	 * Ensure a more specific string matches with priority over a less specific
	 * string, regardless of what order they were defined.
	 *
	 *  MAYBE! Not sure if this is desired. But the theory is if both
	 * "test" and "test place" are defined, then the text "test place" should get
	 * linked, even though "test" was defined first.
	 */
	public function test_does_not_replace_a_more_general_term_when_general_is_first() {
		$expected = $this->expected_text( 'test place' );

		$this->assertEquals( "This $expected is true", $this->text_replace( 'This test place is true' ) );
	}

	public function tests_linkifies_term_split_across_multiple_lines() {
		$expected = array(
			"See my " . $this->expected_text( 'test place' ) . " site to read."
				=> $this->text_replace( "See my test\nplace site to read." ),
			"See my " . $this->expected_text( 'test place' ) . " site to read."
				=> $this->text_replace( "See my test   place site to read." ),
			"These are " . $this->expected_text( 'Cocktail glacé' ) . " to read"
				=> $this->text_replace( "These are Cocktail\n\tglacé to read" ),
			"This is interesting " . $this->expected_text( "ユニコード漢字 は" ) . " if I do say so"
				=> $this->text_replace( "This is interesting ユニコード漢字\nは if I do say so" ),
			"This is interesting " . $this->expected_text( "ユニコード漢字 は" ) . " if I do say so"
				=> $this->text_replace( "This is interesting ユニコード漢字\t  は if I do say so" ),
		);

		foreach ( $expected as $expect => $actual ) {
			$this->assertEquals( $expect, $actual );
		}
	}

	public function test_linkifies_multibyte_text_once_via_setting() {
		$linked = $this->expected_text( 'Cocktail glacé' );

		$this->set_option( array( 'replace_once' => true ) );

		$expected = array(
			"$linked Cocktail glacé Cocktail glacé"
				=> $this->text_replace( 'Cocktail glacé Cocktail glacé Cocktail glacé' ),
			'dock ' . $this->expected_text( 'ユニコード漢字' ) . ' cart ユニコード漢字'
				=> $this->text_replace( 'dock ユニコード漢字 cart ユニコード漢字' ),
		);

		foreach ( $expected as $expect => $actual ) {
			$this->assertEquals( $expect, $actual );
		}
	}

	public function test_replaces_once_via_setting() {
		$expected = $this->expected_text( ':coffee2code:' );
		$this->test_replaces_single_term_multiple_times();
		$this->set_option( array( 'replace_once' => true ) );

		$this->assertEquals( "$expected :coffee2code: :coffee2code:", $this->text_replace( ':coffee2code: :coffee2code: :coffee2code:' ) );
	}

	public function test_replaces_once_via_trueish_setting_value() {
		$expected = $this->expected_text( ':coffee2code:' );
		$this->test_replaces_single_term_multiple_times();
		$this->set_option( array( 'replace_once' => '1' ) );

		$this->assertEquals( "$expected :coffee2code: :coffee2code:", $this->text_replace( ':coffee2code: :coffee2code: :coffee2code:' ) );
	}

	public function test_replaces_once_via_filter() {
		$expected = $this->expected_text( ':coffee2code:' );
		$this->test_replaces_single_term_multiple_times();
		add_filter( 'c2c_text_replace_once', '__return_true' );

		$this->assertEquals( "$expected :coffee2code: :coffee2code:", $this->text_replace( ':coffee2code: :coffee2code: :coffee2code:' ) );
	}

	public function test_replaces_html_once_when_replace_once_is_true() {
		$orig = '<strong>to be linked</strong>';
		$expected = $this->expected_text( $orig );
		$this->set_option( array( 'replace_once' => true ) );

		$this->assertEquals( "$expected $orig $orig", $this->text_replace( "$orig $orig $orig" ) );
	}

	public function test_replaces_with_case_insensitivity_via_setting() {
		$expected = $this->expected_text( ':coffee2code:' );
		$this->test_replaces_with_case_sensitivity_by_default();
		$this->set_option( array( 'case_sensitive' => false ) );

		$this->assertEquals( $expected, $this->text_replace( ':coffee2code:' ) );
		$this->assertEquals( $expected, $this->text_replace( ':Coffee2code:' ) );
		$this->assertEquals( $expected, $this->text_replace( ':COFFEE2CODE:' ) );
	}

	public function test_replaces_with_case_insensitivity_via_filter() {
		$expected = $this->expected_text( ':coffee2code:' );
		$this->test_replaces_with_case_sensitivity_by_default();
		add_filter( 'c2c_text_replace_case_sensitive', '__return_false' );

		$this->assertEquals( $expected, $this->text_replace( ':coffee2code:' ) );
		$this->assertEquals( $expected, $this->text_replace( ':Coffee2code:' ) );
		$this->assertEquals( $expected, $this->text_replace( ':COFFEE2CODE:' ) );
	}

	public function test_replaces_html_when_case_insensitive_is_true() {
		$expected = $this->expected_text( '<strong>to be linked</strong>' );
		$this->set_option( array( 'case_sensitive' => false ) );

		$this->assertEquals( $expected, $this->text_replace( '<strong>to be linked</strong>' ) );
		$this->assertEquals( $expected, $this->text_replace( '<strong>To Be Linked</strong>' ) );
	}

	public function test_replaces_html_when_case_insensitive_and_replace_once_are_true() {
		$expected = $this->expected_text( '<strong>to be linked</strong>' );
		$this->set_option( array( 'case_sensitive' => false, 'replace_once' => true ) );

		$str = '<strong>TO BE linked</strong>';
		$this->assertEquals( "$expected $str", $this->text_replace( "$str $str" ) );
	}

	public function test_replaces_term_added_via_filter() {
		$this->assertEquals( 'bbPress', $this->text_replace( 'bbPress' ) );
		$expected = '<a href="https://bbpress.org">bbPress - Forum Software</a>';
		add_filter( 'c2c_text_replace', array( $this, 'add_text_to_replace' ) );

		$this->assertEquals( $expected, $this->text_replace( 'bbPress' ) );
	}

	public function test_replace_does_not_apply_to_comments_by_default() {
		$this->assertEquals( ':coffee2code:', apply_filters( 'get_comment_text', ':coffee2code:' ) );
		$this->assertEquals( ':coffee2code:', apply_filters( 'get_comment_excerpt', ':coffee2code:' ) );
	}

	public function test_replace_applies_to_comments_via_setting() {
		$expected = $this->expected_text( ':coffee2code:' );
		$this->test_replace_does_not_apply_to_comments_by_default();
		$this->set_option( array( 'text_replace_comments' => true ) );

		$this->assertEquals( $expected, apply_filters( 'get_comment_text', ':coffee2code:' ) );
		$this->assertEquals( $expected, apply_filters( 'get_comment_excerpt', ':coffee2code:' ) );
	}

	public function test_replace_applies_to_comments_via_filter() {
		$expected = $this->expected_text( ':coffee2code:' );
		$this->test_replace_does_not_apply_to_comments_by_default();

		add_filter( 'c2c_text_replace_comments', '__return_true' );

		$this->assertEquals( $expected, apply_filters( 'get_comment_text', ':coffee2code:' ) );
		$this->assertEquals( $expected, apply_filters( 'get_comment_excerpt', ':coffee2code:' ) );
	}

	/**
	 * @dataProvider get_default_filters
	 */
	public function test_replace_applies_to_default_filters( $filter ) {
		$expected = $this->expected_text( ':coffee2code:' );

		$this->assertNotFalse( has_filter( $filter, array( c2c_TextReplace::get_instance(), 'text_replace' ), 12 ) );
		$this->assertGreaterThan( 0, strpos( apply_filters( $filter, 'a :coffee2code:' ), $expected ) );
	}

	/**
	 * @dataProvider get_comment_filters
	 */
	public function test_replace_applies_to_comment_filters( $filter ) {
		$expected = $this->expected_text( ':coffee2code:' );

		add_filter( 'c2c_text_replace_comments', '__return_true' );

		$this->assertNotFalse( has_filter( $filter, array( c2c_TextReplace::get_instance(), 'text_replace_comment_text' ), 11 ) );
		$this->assertGreaterThan( 0, strpos( apply_filters( $filter, 'a :coffee2code:' ), $expected ) );
	}

	public function test_replace_applies_to_custom_filter_via_filter() {
		$this->assertEquals( ':coffee2code:', apply_filters( 'custom_filter', ':coffee2code:' ) );

		add_filter( 'c2c_text_replace_filters', array( $this, 'add_custom_filter' ) );

		c2c_TextReplace::get_instance()->register_filters(); // Plugins would typically register their filter before this originally fires

		$this->assertEquals( $this->expected_text( ':coffee2code:' ), apply_filters( 'custom_filter', ':coffee2code:' ) );
	}

	/*
	 * Setting handling
	 */

	/*
	// This is normally the case, but the unit tests save the setting to db via
	// setUp(), so until the unit tests are restructured somewhat, this test
	// would fail.
	public function test_does_not_immediately_store_default_settings_in_db() {
		$option_name = c2c_TextReplace::SETTING_NAME;
		// Get the options just to see if they may get saved.
		$options     = c2c_TextReplace::get_instance()->get_options();

		$this->assertFalse( get_option( $option_name ) );
	}
	*/

	public function test_uninstall_deletes_option() {
		$option_name = c2c_TextReplace::SETTING_NAME;
		$options     = c2c_TextReplace::get_instance()->get_options();

		// Explicitly set an option to ensure options get saved to the database.
		$this->set_option( array( 'replace_once' => true ) );

		$this->assertNotEmpty( $options );
		$this->assertNotFalse( get_option( $option_name ) );

		c2c_TextReplace::uninstall();

		$this->assertFalse( get_option( $option_name ) );
	}

}
