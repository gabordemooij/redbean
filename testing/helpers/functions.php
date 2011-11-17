<?php

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
 * Tests whether a === b. The minimalistic core of this little
 * unit test framework.
 * @global integer $tests
 * @param mixed $a value for A
 * @param mixed $b value for B
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

/**
 * called when a test is passed. prints the test number to the screen.
 */
function pass() {
	global $tests;
	$tests++;
	print( "[".$tests."]" );
}

/**
 * called when a test fails. shows debug info and exits.
 */
function fail() {
	printtext("FAILED TEST");
	debug_print_backtrace();
	exit;
}


/**
 * prints out the name of the current test pack.
 */
function testpack($name) {
	printtext("testing: ".$name);
}


/**
 * drops all tables in the database.
 */
$nukepass=0;
function droptables() {
	global $nukepass;
	R::nuke();
	if (!count($t=R::$writer->getTables())) $nukepass++; else {
	echo "\nFailed to clean up database: ".print_r($t,1);
	fail();
	}
}

/**
 * Quickly resolves the formatted table name
 */
function tbl($table) {
	return R::$writer->getFormattedTableName($table);
}

/**
 * Quickly resolves the formatted ID
 */
function ID($id) {
	return R::$writer->getIDField($table);
}





function set1toNAssoc($a, RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		$type = $bean1->getMeta("type");
        $a->clearRelations($bean2, $type);
        $a->associate($bean1, $bean2);
        if (count( $a->related($bean2, $type) )===1) {
               // return $this;
        }
        else {
                throw new RedBean_Exception_SQL("Failed to enforce 1toN Relation for $type ");
        }
}


function getList($beans,$property) {
	$items = array();
	foreach($beans as $bean) {
		$items[] = $bean->$property;
	}
	sort($items);
	return implode(",",$items);
}



function testids($array) {
	foreach($array as $key=>$bean) {
		asrt(intval($key),intval($bean->getID()));
	}
}

function modgr($book3,$quotes,$pictures,$topics) {
		
			//global ;
		
			$key = array_rand($quotes);
			$quote = $quotes[$key];
			$keyPic = array_rand($pictures);
			$picture = $pictures[$keyPic];
			$keyTop = array_rand($topics);
			$topic = $topics[$keyTop];
		
		
		
		
			if (rand(0,1)) {
				$f=0;
				foreach($book3->ownQuote as $z) {
					if ($z->note == $quote->note) { $f = 1; break; }
				}
				if (!$f) {
				//echo "\n add a quote ";
				$book3->ownQuote[] = $quote;
				}
			}
			if (rand(0,1)){
				$f=0;
				foreach($book3->ownPicture as $z) {
					if ($z->note == $picture->note) { $f = 1; break; }
				}
				if (!$f) {
				//	echo "\n add a picture ";
					$book3->ownPicture[] = $picture;
				}
			}
			if (rand(0,1)) {
				$f=0;
				foreach($book3->sharedTopic as $z) {
					if ($z->note == $topic->note) { $f = 1; break; }
				}
				if (!$f) {
				//	echo "\n add a shared topic ";
					$book3->sharedTopic[] = $topic;
				}
			}
			if (rand(0,1) && count($book3->ownQuote)>0) {
				$key = array_rand($book3->ownQuote);
				unset($book3->ownQuote[ $key ]);
			//	echo "\n delete quote with key $key ";
			}
			if (rand(0,1) && count($book3->ownPicture)>0) {
				$key = array_rand($book3->ownPicture);
				unset($book3->ownPicture[ $key ]);
			//	echo "\n delete picture with key $key ";
			}
			if (rand(0,1) && count($book3->sharedTopic)>0) {
				$key = array_rand($book3->sharedTopic);
				unset($book3->sharedTopic[ $key ]);
			//	echo "\n delete sh topic  with key $key ";
			}
		
			if (rand(0,1) && count($book3->ownPicture)>0) {
				$key = array_rand($book3->ownPicture);
				$book3->ownPicture[ $key ]->change = rand(0,100);
			//	echo "\n changed picture with key $key ";
			}
			if (rand(0,1) && count($book3->ownQuote)>0) {
				$key = array_rand($book3->ownQuote);
				$book3->ownQuote[ $key ]->change = 'note ch '.rand(0,100);
			//	echo "\n changed quote with key $key ";
			}
			if (rand(0,1) && count($book3->sharedTopic)>0) {
				$key = array_rand($book3->sharedTopic);
				$book3->sharedTopic[ $key ]->change = rand(0,100);
			//	echo "\n changed sharedTopic with key $key ";
			}
		}
		

function setget($val) {
	R::nuke();
	$bean = R::dispense("page");
	$bean->prop = $val;
	$id = R::store($bean);
	$bean = R::load("page",$id);
	//asrt((is_string($bean->prop) || is_null($bean->prop)),true);
	return $bean->prop;
}



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