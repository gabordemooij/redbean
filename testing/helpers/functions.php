<?php

/**
 * Convenience function for test hook.
 * If you added the proper Writer class, the facade should be able
 * to automatically load it, i.e. \RedBeanPHP\QueryWriter\MyWriter
 * 
 * @global array $ini
 * 
 * @param string $name name of the connection (key)
 * @param string $dsn  DSN to connect
 * @param string $user username
 * @param string $pass passwords
 */
function add_writer_to_tests( $name, $dsn, $user, $pass )
{

	global $ini;
	
	\RedUNIT\Base::addToDriverList( $name );
	R::addDatabase( $name, $dsn, $user, $pass );

	$ini[ $name ] = true;
}

/**
 * A simple print function that works
 * both for CLI and HTML.
 *
 * @param string $text
 */
function printtext( $text )
{
	if ( $_SERVER["DOCUMENT_ROOT"] ) {
		echo "<BR>" . $text;
	} else {
		echo "\n" . $text;
	}
}

/**
 * Tests whether a === b. The minimalistic core of this little
 * unit test framework.
 *
 * @global integer $tests
 *
 * @param mixed    $a value for A
 * @param mixed    $b value for B
 */
function asrt( $a, $b )
{
	if ( $a === $b ) {
		global $tests;

		$tests++;

		print( "[" . $tests . "]" );
	} else {
		printtext( "FAILED TEST: EXPECTED $b BUT GOT: $a " );

		fail();
	}
}

/**
 * called when a test is passed. prints the test number to the screen.
 */
function pass()
{
	global $tests;

	$tests++;

	print( "[" . $tests . "]" );
}

/**
 * called when a test fails. shows debug info and exits.
 */
function fail()
{
	printtext( "FAILED TEST" );

	debug_print_backtrace();

	exit( 1 );
}

/**
 * prints out the name of the current test pack.
 *
 * @param string $name name of the test pack
 */
function testpack( $name )
{
	printtext( "\n\tSub testpack: " . $name . " \n\t" );
}

/**
 * prints out the name of the current test pack.
 *
 * @param string $name name of the test pack
 */
function maintestpack( $name )
{
	printtext( "\n\nTestpack: " . $name . " \n\t" );
}

/**
 * Quickly resolves the formatted table name
 */
function tbl( $table )
{
	return R::$writer->getFormattedTableName( $table );
}

/**
 * Quickly resolves the formatted ID
 */
function ID( $table )
{
	return R::$writer->getIDField( $table );
}

/**
 * Emulates legacy function for use with older tests.
 */
function set1toNAssoc( $a, \RedBeanPHP\OODBBean $bean1, \RedBeanPHP\OODBBean $bean2 )
{
	$type = $bean1->getMeta( "type" );

	$a->clearRelations( $bean2, $type );
	$a->associate( $bean1, $bean2 );

	if ( count( $a->related( $bean2, $type ) ) === 1 ) {
		// return $this;
	} else {
		throw new \RedBeanPHP\RedException\SQL( "Failed to enforce 1-N Relation for $type " );
	}
}

/**
 * Returns all property values of beans as a
 * comma separated string sorted.
 *
 * @param array  $beans    beans
 * @param string $property name of the property
 *
 * @return string $values  values
 */
function getList( $beans, $property )
{
	$items = array();

	foreach ( $beans as $bean ) {
		$items[] = $bean->$property;
	}

	sort( $items );

	return implode( ",", $items );
}

/**
 * Helper function to test IDs
 *
 * @param array $array array
 */
function testids( $array )
{
	foreach ( $array as $key => $bean ) {
		asrt( intval( $key ), intval( $bean->getID() ) );
	}
}

/**
 * Group modifier function. Tests random modifications
 * of groups of beans. For use with tests that test N:1 relation mapping
 * features.
 *
 * @param mixed $book3    book
 * @param mixed $quotes   quotes
 * @param mixed $pictures pictures
 * @param mixed $topics   topics
 */
function modgr( $book3, $quotes, $pictures, $topics )
{
	$key = array_rand( $quotes );

	$quote = $quotes[$key];

	$keyPic = array_rand( $pictures );

	$picture = $pictures[$keyPic];

	$keyTop = array_rand( $topics );

	$topic = $topics[$keyTop];

	if ( rand( 0, 1 ) ) {
		$f = 0;

		foreach ( $book3->ownQuote as $z ) {
			if ( $z->note == $quote->note ) {
				$f = 1;

				break;
			}
		}
		if ( !$f ) {
			//echo "\n add a quote ";

			$book3->ownQuote[] = $quote;
		}
	}

	if ( rand( 0, 1 ) ) {
		$f = 0;

		foreach ( $book3->ownPicture as $z ) {
			if ( $z->note == $picture->note ) {
				$f = 1;

				break;
			}
		}
		if ( !$f ) {
			//	echo "\n add a picture ";

			$book3->ownPicture[] = $picture;
		}
	}

	if ( rand( 0, 1 ) ) {
		$f = 0;

		foreach ( $book3->sharedTopic as $z ) {
			if ( $z->note == $topic->note ) {
				$f = 1;

				break;
			}
		}

		if ( !$f ) {
			$book3->sharedTopic[] = $topic;
		}
	}

	if ( rand( 0, 1 ) && count( $book3->ownQuote ) > 0 ) {
		$key = array_rand( $book3->ownQuote );

		unset( $book3->ownQuote[$key] );
	}

	if ( rand( 0, 1 ) && count( $book3->ownPicture ) > 0 ) {
		$key = array_rand( $book3->ownPicture );

		unset( $book3->ownPicture[$key] );
	}

	if ( rand( 0, 1 ) && count( $book3->sharedTopic ) > 0 ) {
		$key = array_rand( $book3->sharedTopic );

		unset( $book3->sharedTopic[$key] );
	}

	if ( rand( 0, 1 ) && count( $book3->ownPicture ) > 0 ) {
		$key = array_rand( $book3->ownPicture );

		$book3->ownPicture[$key]->change = rand( 0, 100 );
	}

	if ( rand( 0, 1 ) && count( $book3->ownQuote ) > 0 ) {
		$key = array_rand( $book3->ownQuote );

		$book3->ownQuote[$key]->change = 'note ch ' . rand( 0, 100 );
	}

	if ( rand( 0, 1 ) && count( $book3->sharedTopic ) > 0 ) {
		$key = array_rand( $book3->sharedTopic );

		$book3->sharedTopic[$key]->change = rand( 0, 100 );
	}
}

/**
 * SetGet function, sets a value in a bean and retrieves it again
 * after storage, useful for tests that want to make sure the same value
 * that gets in, comes out again.
 *
 * @param mixed $val the value that needs to be preserved
 *
 * @return mixed $val the value as returned after storage-retrieval cycle.
 */
function setget( $val )
{
	R::nuke();

	$bean = R::dispense( "page" );

	$bean->prop = $val;

	$id = R::store( $bean );

	$bean = R::load( "page", $id );

	return $bean->prop;
}

/**
 * Wrapper function to test BeanCan Server, does the boring
 * plumming work.
 *
 * @param mixed  $data   Data for JSON-RPC request object
 * @param mixed  $params Parameters for JSON-RPC request object
 * @param string $id     Identification of JSON-RPC request to connect to response
 *
 * @return string $out Output JSON from BeanCan server.
 */
function fakeBeanCanServerRequest( $data, $params = NULL, $id = "1234", $whiteList = 'all' )
{
	$j = array(
		"jsonrpc" => "2.0",
		"method"  => $data,
		"params"  => $params,
		"id"      => $id
	);

	$can = new \RedBeanPHP\Plugin\BeanCan;

	$request = json_encode( $j );

	$can->setWhitelist( $whiteList );

	$out = $can->handleJSONRequest( $request );

	return $out;
}

/**
 * Candy Cane Factory. Produces lots of candy canes.
 *
 * @return array $canes canes
 */
function candy_canes()
{
	$canes = R::dispense( 'cane', 10 );

	$i = 0;

	foreach ( $canes as $k => $cane ) {
		$canes[$k]->label = 'Cane No. ' . ( $i++ );
	}

	$canes[0]->cane = $canes[1];
	$canes[1]->cane = $canes[4];
	$canes[9]->cane = $canes[4];
	$canes[6]->cane = $canes[4];
	$canes[4]->cane = $canes[7];
	$canes[8]->cane = $canes[7];

	return $canes;
}
