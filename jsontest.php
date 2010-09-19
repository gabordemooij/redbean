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
require("rb.php");

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

R::setup();


class Model_CandyBar {
	
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




