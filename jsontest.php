<?php

//
//                   ._______ __________  .______________
//_______   ____   __| _/    |   \      \ |   \__    ___/
//\_  __ \_/ __ \ / __ ||    |   /   |   \|   | |    |
// |  | \/\  ___// /_/ ||    |  /    |    \   | |    |
// |__|    \___  >____ ||______/\____|__  /___| |____|
//            \/     \/                \/

// Written by Gabor de Mooij Copyright (c) 2009
/**
 * RedUNIT (Test Suite)
 * @file 		test.php
 * @description		Series of Unit Tests for RedBean
 * @author			Gabor de Mooij
 * @license			BSD
 */
//require("rb.php");
require("RedBean/redbean.inc.php");

/**
 * A simple print function that works
 * both for CLI and HTML.
 * @param string $text
 */
function printtext( $text ) {
	if ($_SERVER["DOCUMENT_ROOT"]) {
		echo "<BR>".$text;
	}
	else {
		echo "\n".$text;
	}
}

/**
 * Tests whether a === b.
 * @global integer $tests
 * @param mixed $a
 * @param mixed $b
 */
function asrt( $a, $b ) {
	if ($a === $b) {
		global $tests;
		$tests++;
		print( "[".$tests."]" );
	}
	else {
		printtext("FAILED TEST: EXPECTED $b BUT GOT: $a ");
		fail();
	}
}

function pass() {
	global $tests;
	$tests++;
	print( "[".$tests."]" );
}

function fail() {
	printtext("FAILED TEST");
	debug_print_backtrace();
	exit;
}


function testpack($name) {
	printtext("testing: ".$name);
}


//Test the Setup Class
testpack("Test JSON RPC");

function s($data,$params=null,$id="1234") {
	
	$j = array(
		"jsonrpc"=>"2.0",
		"method"=>$data,
		"params"=>$params,
		"id"=>$id
	);
	
	$can = new RedBean_BeanCan;
	$request = json_encode($j);
	$out =  $can->handleJSONRequest( $request );
	
	//echo "\n $out "; //--debugging
	
	return $out;
}

R::setup("mysql:dbname=oodb;host=localhost","root");


testpack("Test BeanMachine");

$beanMachine = RedBean_Plugin_BeanMachine::getInstance( R::$toolbox );

$beanMachine->addGroup("SELECT-CLAUSE"," SELECT @ ", ",")
		->addGroup("FROM-CLAUSE", "  \n FROM @ ", ",")
		->addGroup("WHERE-CLAUSE", " \n  WHERE @ ", " AND ");

$beanMachine->openGroup("WHERE-CLAUSE")
		->addGroup("QUALIFICATIONS", " \n (@) ", " OR ")
		->open()
		->addGroup("AGE-LIMITATION", " \n (@) ", " AND ")
		->addGroup("EXPERIENCE", " \n (@) ", " OR ");

$beanMachine->openGroup("AGE-LIMITATION")
		->add(" age < :too_old ")
		->add(" age > :too_young ");

$beanMachine->openGroup("EXPERIENCE")
		->add(" experience > :experience_level ")
		->add(" experiencerank = 'expert' ");

$beanMachine->openGroup("WHERE-CLAUSE")
		->add(" workstatus = 'currently-working-here' ");

$beanMachine->openGroup("FROM-CLAUSE")
		->add(" person ");

$beanMachine->openGroup("SELECT-CLAUSE")
		->add("name")
		->add("job");

//die($beanMachine);

$output = preg_replace("/\s/","", $beanMachine );


//echo "\n\n".sha1( $output )."\n\n" ; exit;
$expected = "04586a1c347cd1ffa0e4fac3ca3ba9bcdf794371";
asrt(sha1($output), $expected);





R::wipe("book");
R::wipe("book_page");
R::wipe("page");

$book1 = R::dispense("book");
$book1->title = "book1";
R::store($book1);
$book2 = R::dispense("book");
$book2->title = "book2";
R::store($book2);
$page1 = R::dispense("page");
$page1->text = "lorem ipsum";
$page2 = R::dispense("page");
$page2->text = "lorem ipsum2";
R::associate($book1,$page1);
R::associate($book1,$page2);
$page3 = R::dispense("page");
$page3->text = "lorem ipsum";
R::associate($book2,$page3);

//exit;

require("RedBean/Plugin/BeanMachine/Summary.php");


$machine = $beanMachine->getQueryByName("Summary");
$machine->summarize("book", "page", "book_page");
$beanCollection = $beanMachine->getBeans( "book", $machine );

asrt(count($beanCollection),2);
asrt($beanCollection[0]->title, "book1");
asrt($beanCollection[1]->title, "book2");
asrt($beanCollection[0]->getMeta("_count"), "2");
asrt($beanCollection[1]->getMeta("_count"), "1");



class Model_CandyBar extends RedBean_SimpleModel {
	
	public function customMethod($custom) {
		return $custom."!";
	}	

}

$rs = ( s("candybar:store",array( array("brand"=>"funcandy","taste"=>"sweet") ) ) );
testpack("Test create");
asrt(is_string($rs),true);
$rs = json_decode($rs,true);
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),true);
asrt(($rs["id"]),"1234");
asrt(isset($rs["result"]),true);
asrt(($rs["result"]>0),true);
asrt(isset($rs["error"]),false);
asrt(count($rs),3);
$oldid = $rs["result"];
testpack("Test retrieve");
$rs = json_decode( s("candybar:load",array( $oldid ) ),true );
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),3);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),true);
asrt(($rs["id"]),"1234");
asrt(isset($rs["result"]),true);
asrt(isset($rs["error"]),false);
asrt(is_array($rs["result"]),true);
asrt(count($rs["result"]),3);
asrt($rs["result"]["id"],(string)$oldid);
asrt($rs["result"]["brand"],"funcandy");
asrt($rs["result"]["taste"],"sweet");
testpack("Test update");
$rs = json_decode( s("candybar:store",array( array( "id"=>$oldid, "taste"=>"salty" ) ),"42" ),true );
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),3);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),true);
asrt(($rs["id"]),"42");
asrt(isset($rs["result"]),true);
asrt(isset($rs["error"]),false);
$rs = json_decode( s("candybar:load",array( $oldid ) ),true );
asrt($rs["result"]["taste"],"salty");
$rs = json_decode( s("candybar:store",array( array("brand"=>"darkchoco","taste"=>"bitter") ) ), true );
$id2 = $rs["result"];
$rs = json_decode( s("candybar:load",array( $oldid ) ),true );
asrt($rs["result"]["brand"],"funcandy");
asrt($rs["result"]["taste"],"salty");
$rs = json_decode( s("candybar:load",array( $id2 ) ),true );
asrt($rs["result"]["brand"],"darkchoco");
asrt($rs["result"]["taste"],"bitter");
testpack("Test delete");
$rs = json_decode( s("candybar:trash",array( $oldid )), true );
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),3);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),true);
asrt(($rs["id"]),"1234");
asrt(isset($rs["result"]),true);
asrt(isset($rs["error"]),false);
asrt($rs["result"],"OK");
$rs = json_decode( s("candybar:load",array( $oldid ) ),true );
asrt(isset($rs["result"]),true);
asrt(isset($rs["error"]),false);
asrt($rs["result"]["id"],0);
$rs = json_decode( s("candybar:load",array( $id2 ) ),true );
asrt($rs["result"]["brand"],"darkchoco");
asrt($rs["result"]["taste"],"bitter");
testpack("Test Custom Method");
$rs = json_decode( s("candybar:customMethod",array( "test" )), true );
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),3);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),true);
asrt(($rs["id"]),"1234");
asrt(isset($rs["result"]),true);
asrt(isset($rs["error"]),false);
asrt($rs["result"],"test!");

testpack("Test Negatives: parse error");
$can = new RedBean_BeanCan;
$rs =  json_decode( $can->handleJSONRequest( "crap" ), true);
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),2);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),false);
asrt(isset($rs["result"]),false);
asrt(isset($rs["error"]),true);
asrt(isset($rs["error"]["code"]),true);
asrt($rs["error"]["code"],-32700);
testpack("invalid request");
$can = new RedBean_BeanCan;
$rs =  json_decode( $can->handleJSONRequest( '{"aa":"bb"}' ), true);
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),2);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),false);
asrt(isset($rs["result"]),false);
asrt(isset($rs["error"]),true);
asrt(isset($rs["error"]["code"]),true);
asrt($rs["error"]["code"],-32600);
$rs =  json_decode( $can->handleJSONRequest( '{"jsonrpc":"9.1"}' ), true);
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),2);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),false);
asrt(isset($rs["result"]),false);
asrt(isset($rs["error"]),true);
asrt(isset($rs["error"]["code"]),true);
asrt($rs["error"]["code"],-32600);
$rs =  json_decode( $can->handleJSONRequest( '{"id":9876,"jsonrpc":"9.1"}' ), true);
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),2);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),false);
asrt(isset($rs["result"]),false);
asrt(isset($rs["error"]),true);
asrt(isset($rs["error"]["code"]),true);
asrt($rs["error"]["code"],-32600);
$rs = json_decode( s("wrong",array( "test" )), true );
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),3);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),true);
asrt(($rs["id"]),"1234");
asrt(isset($rs["result"]),false);
asrt(isset($rs["error"]),true);
asrt($rs["error"]["code"],-32600);
asrt($rs["error"]["message"],"Invalid method signature. Use: BEAN:ACTION");

$rs = json_decode( s(".;':wrong",array( "test" )), true );
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),3);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),true);
asrt(($rs["id"]),"1234");
asrt(isset($rs["result"]),false);
asrt(isset($rs["error"]),true);
asrt($rs["error"]["code"],-32600);
asrt($rs["error"]["message"],"Invalid Bean Type String");

$rs = json_decode( s("wrong:.;'",array( "test" )), true );
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),3);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),true);
asrt(($rs["id"]),"1234");
asrt(isset($rs["result"]),false);
asrt(isset($rs["error"]),true);
asrt($rs["error"]["code"],-32600);
asrt($rs["error"]["message"],"Invalid Action String");

$rs = json_decode( s("wrong:wrong",array( "test" )), true );
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),3);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),true);
asrt(($rs["id"]),"1234");
asrt(isset($rs["result"]),false);
asrt(isset($rs["error"]),true);
asrt($rs["error"]["code"],-32601);
asrt($rs["error"]["message"],"No such bean in the can!");
$rs = json_decode( s("candybar:beHealthy",array( "test" )), true );
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),3);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),true);
asrt(($rs["id"]),"1234");
asrt(isset($rs["result"]),false);
asrt(isset($rs["error"]),true);
asrt($rs["error"]["code"],-32601);
asrt($rs["error"]["message"],"Method not found in Bean: candybar ");
$rs = json_decode( s("candybar:store"), true );
asrt(is_array($rs),true);
asrt(empty($rs),false);
asrt(count($rs),3);
asrt(isset($rs["jsonrpc"]),true);
asrt($rs["jsonrpc"],"2.0");
asrt(isset($rs["id"]),true);
asrt(($rs["id"]),"1234");
asrt(isset($rs["result"]),false);
asrt(isset($rs["error"]),true);
asrt($rs["error"]["code"],-32602);
$rs = json_decode( s("pdo:connect",array("abc")), true );
asrt($rs["error"]["code"],-32601);
$rs = json_decode( s("stdClass:__toString",array("abc")), true );
asrt($rs["error"]["code"],-32601);
echo "\n\n";
echo "done.";




