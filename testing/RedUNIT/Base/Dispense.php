<?php
/**
 * RedUNIT_Base_Dispense
 *
 * @file    RedUNIT/Base/Dispense.php
 * @desc    Tests bean dispensing functionality.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Dispense extends RedUNIT_Base
{
	/**
	 * Test dispense.
	 * 
	 * @return void
	 */
	public function testBasicsDispense()
	{
		$redbean = R::$redbean;

		// Can we dispense a bean?
		$page = $redbean->dispense( "page" );

		// Does it have a meta type?
		asrt( ( (bool) $page->getMeta( "type" ) ), TRUE );

		// Does it have an ID?
		asrt( isset( $page->id ), TRUE );

		// Type should be 'page'
		asrt( ( $page->getMeta( "type" ) ), "page" );

		// ID should be 0 because bean does not exist in database yet.
		asrt( ( $page->id ), 0 );

		// Try some faulty dispense actions.
		foreach ( array( "", ".", "-") as $value ) {
			try {
				$redbean->dispense( $value );

				fail();
			} catch ( RedBean_Exception_Security $e ) {
				pass();
			}
		}

		$bean = $redbean->dispense( "testbean" );

		$bean["property"] = 123;
		$bean["abc"]      = "def";

		asrt( $bean["property"], 123 );
		asrt( $bean["abc"], "def" );
		asrt( $bean->abc, "def" );

		asrt( isset( $bean["abd"] ), FALSE );
		asrt( isset( $bean["abc"] ), TRUE );
	}
	
	
	/**
	 * Tests the facade-only dispenseAll method.
	 * 
	 * @return void
	 */
	public function testDispenseAll() 
	{
		list( $book, $page ) = RedBean_Facade::dispenseAll( 'book,page' );
		asrt( ( $book instanceof RedBean_OODBBean ), true );
		asrt( ( $page instanceof RedBean_OODBBean ), true );
		asrt( $book->getMeta( 'type' ), 'book');
		asrt( $page->getMeta( 'type' ), 'page');

		list( $book, $page, $texts, $mark ) = R::dispenseAll( 'book,page,text*2,mark' );		
		asrt( ( $book instanceof RedBean_OODBBean ), true );
		asrt( ( $page instanceof RedBean_OODBBean ), true );
		asrt( is_array( $texts ), true );
		asrt( ( $mark instanceof RedBean_OODBBean ), true );
		asrt( $book->getMeta( 'type'), 'book' );
		asrt( $page->getMeta( 'type'), 'page' );
		asrt( $mark->getMeta( 'type'), 'mark' );
		asrt( $texts[0]->getMeta( 'type'), 'text' );
		asrt( $texts[1]->getMeta( 'type'), 'text' );

		list( $eggs, $milk, $butter ) = R::dispenseAll( 'eggs*3,milk*1,butter*9' );
		asrt( count( $eggs ), 3 );
		asrt( ( $milk instanceof RedBean_OODBBean ), true );
		asrt( count( $butter ), 9 );

		list( $beer ) = R::dispenseAll( 'beer*0' );
		asrt( is_array( $beer ), true );
		asrt( count( $beer ), 0 );
	}
}
