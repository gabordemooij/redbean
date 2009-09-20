<?php 

//Some unittests for RedBean
//Written by G.J.G.T. de Mooij

//I used this file to develop and tweak Redbean
//Redbean has been developed TEST DRIVEN (TDD)

//This file is mostly for me to test RedBean so it might be a bit chaotic because
//I often need to adjust and change this file, however I am now trying to tidy up this file so 
//you can use it as well. Also, test descriptions will be added over time.

function printtext( $text ) {
	if ($_SERVER["DOCUMENT_ROOT"]) {
		echo "<BR>".$text;
	}
	else {
		echo "\n".$text;
	}
}


//Simple class for testing
class SmartTest {
	private static $me = false;
	private $testPack = '';
	public static function instance() {
		if (!self::$me) self::$me = new SmartTest();
		return self::$me;
	}
	public function __set( $canwe, $v ) {
		if ($canwe=="testPack") {
			$this->testPack = $v;
			printtext("processing testpack: ".RedBean_OODB::getEngine()."-".$v." ...now testing: ");
		}
	}
	public function __get( $canwe ) {
		return $this->testPack;
	}
	public function progress() {
		 global $tests;
		 $tests++;
		 print( "[".$tests."]" );
	}
	
	public static function failedTest() {
		printtext("FAILED TEST");
		exit;
	}
	
	public static function test( $value, $expected ) {
		if ($value != $expected) {
			printtext("FAILED TEST");
			exit;
		}
		else {
			self::instance()->progress();
		}
	}
	
}

//New test functions, no objects required here
function asrt( $a, $b ) {
	if ($a === $b) {
		global $tests;
		$tests++;
		print( "[".$tests."]" );
	}
	else {
		printtext("FAILED TEST: EXPECTED $b BUT GOT: $a ");
		exit;
	}
}

function pass() {
	global $tests;
	$tests++;
	print( "[".$tests."]" );
}

function fail() {
	printtext("FAILED TEST");
	exit;
}


function testpack($name) {
	printtext("testing: ".$name);
}

//Use this database for tests
require("allinone.php");

//====== DRIVER SELECTION ======
/**
 * Select the driver we use and show it
 */
if (!isset($_SERVER['argv'][1])) {
	RedBean_Setup::kickstart("mysql:host=localhost;dbname=oodb","root","",false,"innodb",false);
	echo "\n<BR>USING: PDO";
}
else {
	if ($_SERVER['argv'][1]=='embmysql') {
		RedBean_Setup::kickstart("embmysql:host=localhost;dbname=oodb","root","",false,"innodb",false);		
		echo "\n<BR>USING: EMBEDDED MYSQL";
	}
	else {
		echo "\n<BR>Invalid arg\n";
		exit;
	}
}


//====== CLEAN UP ======

$tables = R::$db->getCol("show tables");
foreach($tables as $t){
	if ($t!="dtyp" && $t!="redbeantables" && $t!="locking") {
		R::$db->exec("DROP TABLE `$t`");
	}
};



//====== TEST TRANSACTIONS =====

/*
//Mind though that table will continue to exits, this test is only about the record in Trans
//Unfortunately you have to run this test apart to see the effect. Check manually.
R::gen("Trans");
$t = new Trans;
$t->name = "part of trans";
$t->save();
//throw new Exception("aa"); //trans should not be in database
//R::rollback();
//R::$db->exec("ROLLBACK");
exit; 
*/

/**
 * Test the very basics 
 */
testpack("Basic test suite");
$tests = 0; 
//Test description: Does the redbean core class exist?
asrt( class_exists("RedBean_OODB") , true);
//Test description: Does the redbean decorator class exist?
asrt(class_exists("RedBean_Decorator"), true);
//Test description: Does the redbean database adapter class exist?
asrt(class_exists("RedBean_DBAdapter"),true);
//Test description are the other core classes available?
asrt(class_exists("RedBean_Can"),true);
asrt(class_exists("RedBean_Sieve"),true);
asrt(interface_exists("RedBean_Validator"),true);
asrt(class_exists("RedBean_Setup"),true);
//Test description: Is the database a DBAdapter?
$db = RedBean_OODB::$db;
asrt(($db instanceof RedBean_DBAdapter),true);
//Test decription: Can we retrieve the version no. ?
asrt(is_string(RedBean_OODB::getVersionInfo()),true);
//Test description: test multiple database support
testpack("multi database");
$old = RedBean_Setup::kickstart("mysql:host=localhost;dbname=test","root","",false,"innodb",false);
asrt(R::$db->getCell("select database()"),"test");
RedBean_Setup::kickstart("mysql:host=localhost;dbname=tutorial","root","",false,"innodb",false);
asrt(R::$db->getCell("select database()"),"tutorial");
RedBean_Setup::reconnect( $old );
asrt(R::$db->getCell("select database()"),"oodb");


//Test description: Test importing of data from post array or custom array
testpack("Import");
R::gen("Thing");
$_POST["first"]="abc";
$_POST["second"]="xyz";
$thing = new Thing;
asrt($thing->import(array("first"=>"a","second"=>2))->getFirst(),"a");
asrt($thing->importFromPost("nonexistant")->getFirst(),"a");
asrt($thing->importFromPost(array("first"))->getFirst(),"abc");
asrt($thing->importFromPost(array("first"))->getSecond(),2);
asrt($thing->importFromPost()->getSecond(),"xyz");

//Test description: Tests whether gen() can produce classes with framework compliant names or using proper namespacing.
//always test in pairs because something might go wrong after first class (during class_exist check for instance!)
testpack("Framework Integration Tools");
asrt(RedBean_OODB::gen("Entity1,Entity2"),true);
asrt(class_exists("Entity1"),true);
asrt(class_exists("Entity2"),true);
asrt(RedBean_OODB::gen("Entity3,Entity4","prefix_","_suffix"),true);
asrt(class_exists("prefix_Entity3_suffix"),true);
asrt(class_exists("prefix_Entity4_suffix"),true);
asrt(RedBean_OODB::gen("a\b\Entity5,c\d\Entity6"),true);
asrt(class_exists("\a\b\Entity5"),true);
asrt(class_exists("\c\d\Entity6"),true);
asrt(RedBean_OODB::gen(".,--"),false);

SmartTest::instance()->testPack = "Observers";
R::gen("Employee");
$employee = new Employee;

class TestObserver implements RedBean_Observer {
	public $signal = "";
	public function onEvent( $event, RedBean_Observable $observer ) {
		$this->signal=$event;
	}
}
$observer = new TestObserver;
$employee->addEventListener( "deco_set",$observer );
$employee->addEventListener( "deco_get",$observer );
$employee->addEventListener( "deco_clearrelated",$observer );
$employee->addEventListener( "deco_add",$observer );
$employee->addEventListener( "deco_remove",$observer );
$employee->addEventListener( "deco_attach",$observer );
$employee->addEventListener( "deco_numof",$observer );
$employee->addEventListener( "deco_belongsto",$observer );
$employee->addEventListener( "deco_exclusiveadd",$observer );
$employee->addEventListener( "deco_children",$observer );
$employee->addEventListener( "deco_parent",$observer );
$employee->addEventListener( "deco_siblings",$observer );
$employee->addEventListener( "deco_importpost",$observer );
$employee->addEventListener( "deco_import",$observer );
$employee->addEventListener( "deco_copy",$observer );
$employee->addEventListener( "deco_free",$observer );
$observer->signal="";
$employee->setName("test");
asrt($observer->signal,"deco_set");
$observer->signal="";
$employee->getName();
asrt($observer->signal,"deco_get");
$observer->signal="";
$employee->getRelatedCustomer();
asrt($observer->signal,"deco_get");
$observer->signal="";
$employee->is("nerd");
asrt($observer->signal,"deco_get");
$observer->signal="";
$employee->clearRelated("nerd");
asrt($observer->signal,"deco_clearrelated");
$observer->signal="";
$employee2 = new Employee;
$employee2->setName("Minni");
$employee->add($employee2);
asrt($observer->signal,"deco_add");
$observer->signal="";
$employee->remove($employee2);
asrt($observer->signal,"deco_remove");
$observer->signal="";
$employee->attach($employee2);
asrt($observer->signal,"deco_attach");
$observer->signal="";
$employee->numofEmployee();
asrt($observer->signal,"deco_numof");
$observer->signal="";
$employee->belongsTo($employee2);
asrt($observer->signal,"deco_belongsto");
$observer->signal="";
$employee->exclusiveAdd($employee2);
asrt($observer->signal,"deco_exclusiveadd");
$observer->signal="";
$employee->parent();
asrt($observer->signal,"deco_parent");
$observer->signal="";
$employee->children($employee2);
asrt($observer->signal,"deco_children");
$observer->signal="";
$employee->siblings($employee2);
asrt($observer->signal,"deco_siblings");
$observer->signal="";
$employee->copy();
asrt($observer->signal,"deco_copy");


SmartTest::instance()->testPack = "Sieves";
R::gen("Employee");
$e = new Employee;
$e->setName("Max");
asrt(RedBean_Sieve::make(array("name"=>"RedBean_Validator_AlphaNumeric"))->valid($e),true);
$e->setName("Ma.x");
asrt(RedBean_Sieve::make(array("name"=>"RedBean_Validator_AlphaNumeric"))->valid($e),false);
$e->setName("Max")->setFunct("sales");
asrt(count(RedBean_Sieve::make(array("name"=>"RedBean_Validator_AlphaNumeric","funct"=>"RedBean_Validator_AlphaNumeric"))->validAndReport($e,"RedBean_Validator_AlphaNumeric")),2);
$e->setName("x")->setFunct("");
asrt(count(RedBean_Sieve::make(array("name"=>"RedBean_Validator_AlphaNumeric","funct"=>"RedBean_Validator_AlphaNumeric"))->validAndReport($e,"RedBean_Validator_AlphaNumeric")),2);
asrt(count(RedBean_Sieve::make(array("a"=>"RedBean_Validator_AlphaNumeric","b"=>"RedBean_Validator_AlphaNumeric"))->validAndReport($e,"RedBean_Validator_AlphaNumeric")),2);


//Test alphanumeric validation
testpack("Validators");
$validator = new RedBean_Validator_AlphaNumeric();
asrt($validator->check("Max"), true);
asrt($validator->check("M...a x"), false);

//Test description: Test redbean table-space
testpack("Configuration tester");
//insert garbage tables
$db->exec(" CREATE TABLE `nonsense` (`a` VARCHAR( 11 ) NOT NULL ,`b` VARCHAR( 11 ) NOT NULL ,`j` VARCHAR( 11 ) NOT NULL	) ENGINE = MYISAM ");
Redbean_OODB::clean();
Redbean_OODB::gen("trash");
$trash = new Trash();
$trash->save();
Redbean_OODB::clean();
Redbean_OODB::setLocking( false ); //turn locking off
$alltables = $db->getCol("show tables");
SmartTest::instance()->progress(); ;
if (!in_array("dtyp",$alltables)) SmartTest::failedTest();
SmartTest::instance()->progress(); ;
if (!in_array("redbeantables",$alltables)) SmartTest::failedTest();
SmartTest::instance()->progress(); ;
if (!in_array("locking",$alltables)) SmartTest::failedTest();
SmartTest::instance()->progress(); ;
if (!in_array("nonsense",$alltables)) SmartTest::failedTest();
SmartTest::instance()->progress(); ;
if (in_array("trash",$alltables)) SmartTest::failedTest();
$db->exec("drop table `nonsense`");

//Test description: KeepInShape() tester
testpack("Optimizer and Garbage collector");
$db->exec("CREATE TABLE  `slimtable` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,`col1` VARCHAR( 255 ) NOT NULL ,`col2` TEXT NOT NULL ,`col3` INT( 11 ) UNSIGNED NOT NULL ,PRIMARY KEY (  `id` ) ) ENGINE = MYISAM");
$db->exec("INSERT INTO  `redbeantables` (`id` ,`tablename`) VALUES (NULL ,  'slimtable');");
$db->exec("INSERT INTO  `slimtable` (`id` ,`col1` ,`col2` ,`col3`) VALUES (NULL ,  '1',  'mustbevarchar',  '1000');");
$db->exec("CREATE TABLE  `indexer` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,`highcard` VARCHAR( 255 ) NOT NULL ,`lowcard` TEXT NOT NULL ,`lowcard2` INT( 11 ) UNSIGNED NOT NULL ,`highcard2` LONGTEXT NOT NULL ,PRIMARY KEY (  `id` )) ENGINE = MYISAM");
$db->exec("INSERT INTO  `redbeantables` (`id` ,`tablename`) VALUES (NULL ,  'indexer');");
$db->exec("INSERT INTO  `redbeantables` (`id` ,`tablename`) VALUES (NULL ,  'empcol');");
$db->exec("CREATE TABLE  `empcol` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,`aaa` INT( 11) UNSIGNED,`bbb` INT(11) UNSIGNED,`ccc` INT( 11 ) UNSIGNED,PRIMARY KEY (  `id` )) ENGINE = MYISAM");
$db->exec("INSERT INTO  `empcol` (`id` ,`aaa` )VALUES (NULL ,  1 )");
for($i=0; $i<20; $i++){
$db->exec("INSERT INTO  `indexer` (`id` ,`highcard` ,`lowcard` ,`lowcard2`,`highcard2`) VALUES ( NULL ,  rand(),  'a',  rand(), CONCAT( rand()*100, '".str_repeat('x',1000)."' ) );");
}


R::gen('empcol,slimtable,indexer');


RedBean_OODB::keepInShape( true, "empcol", "aaa" );
RedBean_OODB::keepInShape( true, "empcol", "bbb" );
RedBean_OODB::keepInShape( true, "slimtable", "col1" );
RedBean_OODB::keepInShape( true, "slimtable", "col2" );
RedBean_OODB::keepInShape( true, "slimtable", "col3" );
RedBean_OODB::keepInShape( true, "indexer", "highcard" );
RedBean_OODB::keepInShape( true, "indexer", "highcard2" );
RedBean_OODB::keepInShape( true, "indexer", "lowcard" );
RedBean_OODB::keepInShape( true, "indexer", "lowcard2" );
$empcol = new empcol;
asrt(empcol::where(' @ifexists:aaa=1 or @ifexists:bbb=1')->count(),1);
$row = $db->getRow("select * from slimtable limit 1");
asrt($row["col1"],'1');
asrt($row["col2"],"mustbevarchar");
asrt($row["col3"],'1000');
asrt(count($db->get("describe slimtable")),4); 
RedBean_OODB::dropColumn("slimtable","col3");
asrt(count($db->get("describe slimtable")),3); 
$db->exec("CREATE TABLE  `garbagetable` (`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,`highcard` VARCHAR( 255 ) NOT NULL ,PRIMARY KEY (  `id` )) ENGINE = MYISAM");
$db->exec("INSERT INTO  `redbeantables` (`id` ,`tablename`)VALUES (NULL ,  'garbagetable');");
RedBean_OODB::KeepInShape( true );
$tables = RedBean_OODB::showTables(); 
asrt(in_array("garbagetable",$tables),false);
//Test: can we extend from a bean and still use magic setters / getters?
R::gen("ACat,Dog");
class Cat extends ACat {
	public function mew(){
		return 123;
	}	
}
$cat = new Cat;
$cat->name = 'Garfield';
$id = $cat->save();
$cat = new Cat($id);
asrt( $cat->name, "Garfield" );
asrt( $cat->mew(), 123 );

//Test if we remove an associated item, dont remove the item itself
$dog = new Dog;
$dog->name="Ody";
$cat->add( $dog );

$dog = $cat->getRelatedDog();
asrt( count($dog) , 1 );


Dog::delete( $dog[0] ); //Ody goes on holiday
asrt( count($cat->getRelatedDog()), 0 );
//Cat should still exist!
$cat = new Cat($id);
asrt( $cat->name, "Garfield" );


//Test description; Query writer should support following query requests
testpack("Query Writer");
$writer = new QueryWriter_MySQL;
$queries=array("prepare_innodb","starttransaction","clear_dtyp","setup_dtyp","setup_locking",
"setup_tables","destruct","release","show_rtables","create_table","register_table","describe",
"infertype","readtype","reset_dtyp","add_column","insert","remove_expir_lock","get_lock","aq_lock",
"update_expir_lock","create_assoc","add_assoc","add_assoc_now","unassoc","create_tree",
"unique","add_child","num_related","deltreetype","unassoctype1","unassoctype2","get_parent",
"get_children","drop_tables","truncate_rtables","releaseall","create","get_null","test_column",
"update_test","measure","remove_test","drop_test","variance","count","index2","drop_column","index1","where",
"unregister_table","get_bean","get_assoc","unassoc_all_t1","unassoc_all_t2","trash","update","widen_column","find"
,"list","remove_child","deltree","fastload","bean_exists","stat","distinct","drop_type");
foreach($queries as $query){
	try{ $writer->getQuery( $query, array("updatevalues"=>array(),"fields"=>array(),"insertcolumns"=>array(), "insertvalues"=>array(),
	"searchoperators"=>array(), "ids"=>array(), "bean"=>RedBean_OODB::dispense("x"), "tables"=>array()) ); pass(); }catch(Exception $e){ fail(); }
}
try{ $writer->getQuery("unsupported"); fail(); }catch(Exception $e){ pass(); }
$cols = $writer->getTableColumns("redbeantables",RedBean_OODB::$db);
asrt(count($cols),2);
$col = array_shift($cols);
asrt($col["Field"],"id");
asrt($col["Type"],"int(11) unsigned");
asrt($col["Null"],"NO");
asrt($col["Default"],null);
asrt($col["Extra"],"auto_increment");

//Test description: is the bean properly checked?
testpack("Bean Checking");
$bean = RedBean_OODB::dispense("bean");
try{RedBean_OODB::checkBean($bean);pass();}catch(RedBean_Exception_Security $oE){ fail(); }
$bean->type = null;
try{RedBean_OODB::checkBean($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean = RedBean_OODB::dispense("bean");
$bean->id = null;
try{RedBean_OODB::checkBean($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = -1;
try{RedBean_OODB::checkBean($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = 0.5;
try{RedBean_OODB::checkBean($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = "5";
try{RedBean_OODB::checkBean($bean);pass();}catch(RedBean_Exception_Security $oE){ fail(); }
$bean->id = "a";
try{RedBean_OODB::checkBean($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = array();
try{RedBean_OODB::checkBean($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = $bean;
try{RedBean_OODB::checkBean($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = 0;
try{RedBean_OODB::checkBean($bean);pass();}catch(RedBean_Exception_Security $oE){ fail(); }
$bean->type = "redbeantables";
try{RedBean_OODB::checkBean($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->type = "dtyp";
try{RedBean_OODB::checkBean($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->type = "locking";
try{RedBean_OODB::checkBean($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
//Some tests for checkAssoc
testpack("checkAssoc");
$bean->type = "justabean";
$bean->id = "a";
try{RedBean_OODB::checkBeanForAssoc($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = array();
try{RedBean_OODB::checkBeanForAssoc($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = $bean;
try{RedBean_OODB::checkBeanForAssoc($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = 0;
try{RedBean_OODB::checkBeanForAssoc($bean);pass();}catch(RedBean_Exception_Security $oE){ fail(); }
$bean->type = "redbeantables";
try{RedBean_OODB::checkBeanForAssoc($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->type = "dtyp";
try{RedBean_OODB::checkBeanForAssoc($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->type = "locking";
try{RedBean_OODB::checkBeanForAssoc($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }





//Tests for each individual engine
function testsperengine( $engine ) {

	
	testpack("Anemic Model on ".$engine);
	//Test basic, fundamental OODBBean functions with Anemic Model
	asrt(is_numeric(RedBean_OODB::getVersionNumber()),true);
	$file = RedBean_OODB::dispense("file");
	asrt((RedBean_OODB::dispense("file") instanceof OODBBean),true);
	//Test description: has the type property been set?
	asrt($file->type,"file");
	//Test description: Is the ID set and 0?
	asrt((isset($file->id) && $file->id===0),true);
	//Test don't accept arrays or objects
	try{ $file->anArray = array(1,2,3); $id = RedBean_OODB::set( $file ); fail(); }catch(Exception $e){ pass(); }
	try{ $file->anObject = $file;  $id = RedBean_OODB::set( $file ); fail(); }catch(Exception $e){ pass(); }
	unset($file->anArray);
	unset($file->anObject); 
	
	
	//Test description: test table management features
	testpack("Table Management on ".$engine);
	$cnt = count(RedBean_OODB::showTables());
	RedBean_OODB::addTable("newtable");
	asrt(count(RedBean_OODB::showTables()), (++$cnt));
	RedBean_OODB::dropTable("newtable");
	asrt(count(RedBean_OODB::showTables()), (--$cnt));
	
	
	
	//Test description: can we set and get a bean?
	$file->name="document";
	$id = RedBean_OODB::set( $file );
	asrt(is_numeric($id),true);
	//Test description: can we load it again using an ID?
	$file = RedBean_OODB::getById("file",$id);
	asrt($file->name,"document");
	//Test description: If a bean does not exist RedBean OODB must throw an exception
	try{RedBean_OODB::getById("file",999); fail(); }catch(RedBean_Exception_FailedAccessBean $e){ pass(); };
	//Test description: Only certain IDs are valid: >0 and only integers! using intval!
	//becomes 1
	try{RedBean_OODB::getById("file",1.1); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); };
	//becomes 0
	try{RedBean_OODB::getById("file",0.9); fail(); }catch(RedBean_Exception_FailedAccessBean $e){ pass(); };
	//becomes 1 (due to abs())
	try{RedBean_OODB::getById("file",-1); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); };
	//Test description: can we fastload a bean?
	try{$file2 = RedBean_OODB::getById("file",2,array("name"=>"picture")); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); }
	asrt($file2->name,"picture");
	
	
	//Test description: test whether we can infer data types
	testpack("Data Type Detection");
	asrt(RedBean_OODB::inferType(1),0);
	asrt(RedBean_OODB::inferType(255),0);
	asrt(RedBean_OODB::inferType(256),1);
	asrt(RedBean_OODB::inferType(-1),2);
	asrt(RedBean_OODB::inferType("12345.9"),3);
	asrt(RedBean_OODB::inferType(str_repeat('a',40000)),4);
	
	
	
	//Test description: can add a property dynamically?
	$file->content=42;
	$id = RedBean_OODB::set( $file );
	$file = RedBean_OODB::getById( "file", $id );
	asrt($file->content,"42");
	//Test description: Can we store a value of a different type in the same property?
	$file->content="Lorem Ipsum";
	$id = RedBean_OODB::set( $file );
	$file = RedBean_OODB::getById( "file", $id );
	asrt($file->content,"Lorem Ipsum");

	testpack("Anemic Model Bean Manipulation on ".$engine);
	//Test description: save and load a complete bean with several properties
	$bean = RedBean_OODB::dispense("note");
	$bean->message = "hello";
	$bean->color=3;
	$bean->date = time();
	$bean->special='n';
	$bean->state = 90;
	$id = RedBean_OODB::set($bean); 
	$bean2 = RedBean_OODB::getById("note", $id);
	asrt(($bean2->state == 90 && $bean2->special =='n' && $bean2->message =='hello'),true);	
	//Test description: change the message property, can we modify it?
	$bean->message = "What is life but a dream?";
	RedBean_OODB::set($bean);
	$bean2 = RedBean_OODB::getById("note", $id); //Using same ID!
	asrt($bean2->message,"What is life but a dream?");
	//Test description can we choose other values, smaller... bigger?
	$bean->message = 1;
	$bean->color = "green";
	$bean->date = str_repeat("BLABLA", 100);
	RedBean_OODB::set($bean);
	$bean2 = RedBean_OODB::getById("note", $id); //Using same ID!
	asrt($bean2->message,"1");
	asrt($bean2->date,$bean->date);
	asrt($bean2->green,$bean->green);
	//Test description: test whether we can save/load UTF8 values
	testpack("UTF8 ".$engine);
	$txt = file_get_contents("utf8.txt");
	$bean->message=$txt;
	RedBean_OODB::set($bean);
	$bean2 = RedBean_OODB::getById("note", $id); //Using same ID!
	asrt($bean2->message,file_get_contents("utf8.txt"));
	global $tests;
	
	//Test description: test whether we can associate anemic beans
	testpack("Associations $engine ");
	$note = $bean;
	$person = RedBean_OODB::dispense("person");
	$person->age = 50;
	$person->name = "Bob";
	$person->gender = "m";
	RedBean_OODB::set( $person );
	RedBean_OODB::associate( $person, $note );
	$memo = RedBean_OODB::getById( "note", 1 );
	$authors = RedBean_OODB::getAssoc( $memo, "person" );
	asrt(count($authors),1); 
	RedBean_OODB::trash( $authors[1] );
	$authors = RedBean_OODB::getAssoc( $memo, "person" );
	asrt(count($authors),0);

	testpack("Put a Bean in the Database - various types $engine ");
	$person = RedBean_OODB::dispense("person");
	$person->name = "John";
	$person->age= 35;
	$person->gender = "m";
	$person->hasJob = true;
	$id = RedBean_OODB::set( $person ); $johnid=$id;
	$person2 = RedBean_OODB::getById( "person", $id );
	
	asrt(intval($person2->age),intval($person->age)); 
	$person2->anotherprop = 2;
	RedBean_OODB::set( $person2 );
	$person = RedBean_OODB::dispense("person");
	$person->name = "Bob";
	$person->age= 50;
	$person->gender = "m";
	$person->hasJob = false;
	$bobid = RedBean_OODB::set( $person );
	
	testpack("getBySQL $engine ");
	$ids = RedBean_OODB::getBySQL("`gender`={gender} order by `name` asc",array("gender"=>"m"),"person");
	asrt(count($ids),2);
	$ids = RedBean_OODB::getBySQL("`gender`={gender} OR `color`={clr} ",array("gender"=>"m","clr"=>"red"),"person");
	asrt(count($ids),0);
	$ids = RedBean_OODB::getBySQL("`gender`={gender} AND `color`={clr} ",array("gender"=>"m","clr"=>"red"),"person");
	asrt(count($ids),0);
	
	//Test description: table names should always be case insensitive, no matter the table name
	testpack("case insens. $engine ");
	R::gen("PERSON");
	$dummy = new Person;
	asrt($dummy->getData()->type,"person");
	
	//Test description: table names should contain no underscores
	testpack("tablenames. $engine ");
	R::gen("under_score_s");
	asrt(class_exists("under_score_s"),true);
	$a = new under_score_s;
	asrt($a->getData()->type,"underscores");
	
	//Test description: find()
	testpack("anemic find");
	$dummy->age = 40;
	$rawdummy = $dummy->getData();
	asrt(count(RedBean_OODB::find( $rawdummy, array("age"=>">"))),1);
	$rawdummy->age = 20;
	asrt(count(RedBean_OODB::find( $rawdummy, array("age"=>">"))),2);
	$rawdummy->age = 100;
	asrt(count(RedBean_OODB::find( $rawdummy, array("age"=>">"))),0);
	$rawdummy->age = 100;
	asrt(count(RedBean_OODB::find( $rawdummy, array("age"=>"<="))),2);
	$rawdummy->name="ob";
	asrt(count(RedBean_OODB::find( $rawdummy, array("name"=>"LIKE"))),1);
	$rawdummy->name="o";
	asrt(count(RedBean_OODB::find( $rawdummy, array("name"=>"LIKE"))),2);
	$rawdummy->gender="m";
	asrt(count(RedBean_OODB::find( $rawdummy, array("gender"=>"="))),2);
	$rawdummy->gender="f";
	asrt(count(RedBean_OODB::find( $rawdummy, array("gender"=>"="))),0);
	asrt(count(RedBean_OODB::listAll("person")),2);
	asrt(count(RedBean_OODB::listAll("person",0,1)),1);
	asrt(count(RedBean_OODB::listAll("person",1)),1);
	
	
	//Test description: associate
	testpack("anemic association");
	$searchBean = RedBean_OODB::dispense("person");
	$searchBean->gender = "m";
	SmartTest::instance()->progress();
	$app = RedBean_OODB::dispense("appointment");
	$app->kind = "dentist";
	RedBean_OODB::set($app);
	RedBean_OODB::associate( $person2, $app );
	$arr = RedBean_OODB::getAssoc( $person2, "appointment" );
	$appforbob = array_shift($arr);
	if (!$appforbob || $appforbob->kind!="dentist") {
		SmartTest::failedTest();
	} 
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "delete a bean?";
	$person = RedBean_OODB::getById( "person", $bobid );
	RedBean_OODB::trash( $person );
	try{
	$person = RedBean_OODB::getById( "person", $bobid);$ok=0;
	}catch(RedBean_Exception_FailedAccessBean $e){
		$ok=true;
	}
	if (!$ok) {
		SmartTest::failedTest();
	}
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "unassociate two beans?";
	$john = RedBean_OODB::getById( "person", $johnid); //hmmmmmm gaat mis bij innodb
	$app = RedBean_OODB::getById( "appointment", 1);
	RedBean_OODB::unassociate($john, $app);
	$john2 = RedBean_OODB::getById( "person", $johnid);
	$appsforjohn = RedBean_OODB::getAssoc($john2,"appointment");
	if (count($appsforjohn)>0) SmartTest::failedTest();
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "unassociate by deleting a bean?";
	$anotherdrink = RedBean_OODB::dispense("whisky");
	$anotherdrink->name = "bowmore";
	$anotherdrink->age = 18;
	$anotherdrink->singlemalt = 'y';
	RedBean_OODB::set( $anotherdrink );
	RedBean_OODB::associate( $anotherdrink, $john );
	$hisdrinks = RedBean_OODB::getAssoc( $john, "whisky" );
	if (count($hisdrinks)!==1) SmartTest::failedTest();
	RedBean_OODB::trash( $anotherdrink );
	$hisdrinks = RedBean_OODB::getAssoc( $john, "whisky" );
	if (count($hisdrinks)!==0) SmartTest::failedTest();
	SmartTest::instance()->progress(); ; 
	
	//Test description: trees
	testpack("anemic trees");
	$pete = RedBean_OODB::dispense("person");
	$pete->age=48;
	$pete->gender="m";
	$pete->name="Pete";
	$peteid = RedBean_OODB::set( $pete );
	$rob = RedBean_OODB::dispense("person");
	$rob->age=19;
	$rob->name="Rob";
	$rob->gender="m";
	$saskia = RedBean_OODB::dispense("person");
	$saskia->age=20;
	$saskia->name="Saskia";
	$saskia->gender="f";
	$idsaskia = RedBean_OODB::set( $saskia );
	$idrob = RedBean_OODB::set( $rob );
	RedBean_OODB::addChild( $pete, $rob );
	RedBean_OODB::addChild( $pete, $saskia );
	$children = RedBean_OODB::getChildren( $pete );
	$names=0;
	if (is_array($children) && count($children)===2) {
		foreach($children as $child){
			if ($child->name==="Rob") $names++;
			if ($child->name==="Saskia") $names++;
		}
	}
	
	if (!$names) fail();
	$daddies = RedBean_OODB::getParent( $saskia );
	$daddy = array_pop( $daddies );
	if ($daddy->name === "Pete") $ok = 1; else $ok = 0;
	if (!$ok) fail();
	pass();
	testpack("remove a child from a parent-child tree?");
	RedBean_OODB::removeChild( $daddy, $saskia );
	$children = RedBean_OODB::getChildren( $pete );
	asrt(count($children),1);
	$only = array_pop($children);
	asrt($only->name,"Rob");
	
	//test can we move a node in the tree?
	testpack("change parent");
	$node1 = RedBean_OODB::dispense("node");
	$node1->name = "node1";
	$node2 = RedBean_OODB::dispense("node");
	$node2->name = "node2";
	$node3 = RedBean_OODB::dispense("node");
	$node3->name = "node3";
	RedBean_OODB::set($node1);
	RedBean_OODB::set($node2);
	RedBean_OODB::set($node3);
	RedBean_OODB::addChild($node1,$node2);
	asrt(count(RedBean_OODB::getChildren($node1)),1);
	asrt(count(RedBean_OODB::getChildren($node3)),0);
	RedBean_OODB::addChild($node1,$node2);
	//nothing changes due to unique constraint 
	asrt(count(RedBean_OODB::getChildren($node1)),1);
	asrt(count(RedBean_OODB::getChildren($node3)),0);
	RedBean_OODB::addChild($node3,$node2);
	asrt(count(RedBean_OODB::getChildren($node1)),1);
	asrt(count(RedBean_OODB::getChildren($node3)),1);
	//now swap! 
	RedBean_OODB::removeChild($node1,$node2);
	asrt(count(RedBean_OODB::getChildren($node1)),0);
	asrt(count(RedBean_OODB::getChildren($node3)),1);
	//swap back again
	RedBean_OODB::removeChild($node3,$node2);
	RedBean_OODB::addChild($node1,$node2);
	asrt(count(RedBean_OODB::getChildren($node1)),1);
	asrt(count(RedBean_OODB::getChildren($node3)),0);
	$node = array_shift(RedBean_OODB::getParent($node2));
	asrt($node->name,"node1");
	RedBean_OODB::associate($node2, $node3); //normal assoc
	RedBean_OODB::associate($node2, RedBean_OODB::dispense("nodeb"));
	asrt(count(RedBean_OODB::getParent($node2)),1);
	asrt(count(RedBean_OODB::getAssoc($node2, "node")),1);
	asrt(count(RedBean_OODB::getAssoc($node2, "nodeb")),1);
	RedBean_OODB::deleteAllAssocType("node",$node2);
	asrt(count(RedBean_OODB::getParent($node2)),0);
	asrt(count(RedBean_OODB::getAssoc($node2, "node")),0);
	asrt(count(RedBean_OODB::getAssoc($node2, "nodeb")),1);
	RedBean_OODB::deleteAllAssoc($node2);
	asrt(count(RedBean_OODB::getParent($node2)),0);
	asrt(count(RedBean_OODB::getAssoc($node2, "node")),0);
	asrt(count(RedBean_OODB::getAssoc($node2, "nodeb")),0);
	
	
	//exit;
	
	//Test description: on the fly saving while associating beans
	testpack("save on the fly while associating?");
	$food = RedBean_OODB::dispense("dish");
	$food->name="pizza";
	RedBean_OODB::associate( $food, $pete );
	$petesfood = RedBean_OODB::getAssoc( $pete, "dish" );
	asrt((is_array($petesfood) && count($petesfood)===1),true);
	RedBean_OODB::unassociate( $food, $pete );
	$petesfood = RedBean_OODB::getAssoc( $pete, "dish" );
	asrt((is_array($petesfood) && count($petesfood)===0),true);
	
	
	//Test description: test whether we can trash beans
	testpack("trash on $engine ");
	$food = RedBean_OODB::dispense("dish");
	$food->name="spaghetti";
	//no exception or error... must be able to trash unsaved beans
	try{ RedBean_OODB::trash( $food ); pass(); }catch(Exception $e){ fail();}
	asrt(count(RedBean_OODB::find($pete,array("name"=>"="))),1);
	asrt(count(RedBean_OODB::find($saskia,array("name"=>"="))),1);
	RedBean_OODB::trash( $pete );
	RedBean_OODB::trash( $saskia );
	asrt(count(RedBean_OODB::find($pete,array("name"=>"="))),0);
	asrt(count(RedBean_OODB::find($saskia,array("name"=>"="))),0);
	
	
	
	
	//Test decorator
	//Test description: can we find beans on the basis of similarity?
	testpack("finder on decorator and listAll on decorator $engine ");
	$person = RedBean_OODB::dispense("person");
	$person = RedBean_OODB::dispense("person");
	$person->age = 50;
	$person->name = "Bob";
	$person->gender = "m";
	RedBean_OODB::set( $person );
	$dummy = new Person;
	$dummy->age = 40;
	asrt(count(Person::find( $dummy, array("age"=>">"))),1);
	$dummy->age = 20;
	asrt(count(Person::find( $dummy, array("age"=>">"))),2);
	$dummy->age = 100;
	asrt(count(Person::find( $dummy, array("age"=>">"))),0);
	$dummy->age = 100;
	asrt(count(Person::find( $dummy, array("age"=>"<="))),3);
	$dummy->name="ob";
	asrt(count(Person::find( $dummy, array("name"=>"LIKE"))),2);
	$dummy->name="o";
	asrt(count(Person::find( $dummy, array("name"=>"LIKE"))),3);
	$dummy->gender="m";
	asrt(count(Person::find( $dummy, array("gender"=>"="))),3);
	$dummy->gender="f";
	asrt(count(Person::find( $dummy, array("gender"=>"="))),0);
	asrt(count(Person::listAll()),3);
	asrt(count(Person::listAll(0,1)),1);
	asrt(count(Person::listAll(1)),2);
	
	
	
	//Test description Cans
	RedBean_OODB::trash( $rob );
	testpack("Cans of Beans $engine ");
	$can = Person::where("`gender`={gender} order by `name`  asc",array("gender"=>"m"),"person");
	//test array access
	foreach($can as $item) {
		if ($item->getName() == "Bob" || $item->getName() == "John" ) {
			SmartTest::instance()->progress();
		}
		else {
			SmartTest::failedTest();
		}
	}
	testpack("Can-methods for $engine ");
	$bean = $can[0];
	asrt($bean->name,"Bob");
	asrt($can->count(),2);
	$can->rewind();
	asrt($can->key(), 0);
	asrt($can->valid(), true);
	asrt($can->current()->getName(), "Bob");
	$can->next();
	asrt($can->key(), 1);
	asrt($can->valid(), false);
	asrt($can->current()->getName(), "John");
	$can->seek(0);
	asrt($can->key(), 0);
	$beans = $can->getBeans();
	asrt(count($beans),2);
	$can->reverse();
	$bean = $can[0];
	asrt($bean->name,"John");
	$can->reverse();
	$bean = $can[0];
	asrt($bean->name,"Bob");
	$can->slice( 0, 1 );
	$can->rewind();
	asrt($can->current()->getName(), "Bob");
	asrt($can->count(), 1);
	$b1 = array_shift($beans); 
	asrt($b1->name,"Bob");
	asrt($can->wrap($b1->getData())->getName(),$b1->getName());
	$lst = $can->getList();
	asrt(count($lst),1);
	asrt($lst[0]["type"],"person");
	asrt($lst[0]["id"],"7");
	asrt($lst[0]["name"],"Bob");
	asrt(count($lst[0]),7);
	
	//Test description: basic functionality where()
	testpack("where() $engine");
	$beans = Person::where("`gender`={gender} order by `name` asc",array("gender"=>"m"),"person")->getBeans();
	
	if (count($beans)!=2) {
		SmartTest::failedTest();
	} 
	SmartTest::instance()->progress();
	
	//without backticks should still work
	$beans = Person::where("gender={person} order by `name` asc",array("person"=>"m"),"person")->getBeans();
	
	if (count($beans)!=2) {
		SmartTest::failedTest();
	}
	SmartTest::instance()->progress(); 
	
	//like comparing should still work
	$beans = Person::where("gender={gender} and `name` LIKE {name} order by `name` asc",array(
				"gender"=>"m",
				"name" => "B%"
	),"person")->getBeans();
	
	if (count($beans)!=1) {
		SmartTest::failedTest();
	} 
	SmartTest::instance()->progress();
	
	
	
	
	
	//test aggregation functions
		
	//insert stat table
	$s = RedBean_OODB::dispense("stattest");
	$s->amount = 1;
	RedBean_OODB::set( $s );
	$s = RedBean_OODB::dispense("stattest");
	$s->amount = 2;
	RedBean_OODB::set( $s );
	$s = RedBean_OODB::dispense("stattest");
	$s->amount = 3;
	$id = RedBean_OODB::set( $s );
	
	//Test description: quick aggregation functions
	testpack("can we use aggr functions using Redbean?");
	asrt(RedBean_OODB::exists("stattest",$id),true);
	asrt(RedBean_OODB::exists("stattest",99),false);
	asrt(intval(RedBean_OODB::numberof("stattest")),3); 
	asrt(intval(RedBean_OODB::maxof("stattest","amount")),3); 
	asrt(intval(RedBean_OODB::minof("stattest","amount")),1); 
	asrt(intval(RedBean_OODB::avgof("stattest","amount")),2); 
	asrt(intval(RedBean_OODB::sumof("stattest","amount")),6); 
	asrt(count(RedBean_OODB::distinct("stattest","amount")),3); 
	
		
	RedBean_OODB::setLocking( true );
	$i=3;
	SmartTest::instance()->testPack="generate only valid classes?";
	try{ $i += RedBean_OODB::gen(""); SmartTest::instance()->progress(); ; }catch(Exception $e){ SmartTest::failedTest(); } //nothing
	try{ $i += RedBean_OODB::gen("."); SmartTest::instance()->progress(); ; }catch(Exception $e){ SmartTest::failedTest(); } //illegal chars
	try{ $i += RedBean_OODB::gen(","); SmartTest::instance()->progress(); ; }catch(Exception $e){ SmartTest::failedTest(); } //illegal chars
	try{ $i += RedBean_OODB::gen("null"); SmartTest::instance()->progress(); ; }catch(Exception $e){ SmartTest::failedTest(); } //keywords
	try{ $i += RedBean_OODB::gen("Exception"); SmartTest::instance()->progress(); ; }catch(Exception $e){ SmartTest::failedTest(); } //reserved
	
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "generate classes using Redbean?";
	
	if (!class_exists("Bug")) {
		$i += RedBean_OODB::gen("Bug");
		if ($i!==4) SmartTest::failedTest();
	}
	else {
		if ($i!==3) SmartTest::failedTest();
	}
	
	if (!class_exists("Bug")) {
		SmartTest::failedTest();
	}
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "use getters and setters";
	$bug = new Bug;
	$bug->setSomething( sha1("abc") );
	if ($bug->getSomething()!=sha1("abc") ) {
		SmartTest::failedTest();
	}
	
	
	//can we use non existing props? --triggers fatal..
	$bug->getHappy();
	

	
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "Use boolean values and retrieve them with is()?";
	if ($bug->isHappy()) {
		SmartTest::failedTest();
	}
	SmartTest::instance()->progress(); ;
	
	
	$bug->setHappy(true);
	$bug->save();
	$bug = new Bug(1);
	if (!$bug->isHappy()) {
		SmartTest::failedTest();
	}
	SmartTest::instance()->progress(); ;
	
	$bug->setHappy(false);
	if ($bug->isHappy()) {
		SmartTest::failedTest();
	}
	
	
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "avoid race-conditions by locking?";
	RedBean_OODB::gen("Cheese,Wine");
	$cheese = new Cheese;
	$cheese->setName('Brie');
	$cheese->save();
	$cheese = new Cheese(1);
	//try to mess with the locking system...
	$oldkey = RedBean_OODB::$pkey;
	RedBean_OODB::$pkey = 1234;
	$cheese = new Cheese(1);
	$cheese->setName("Camembert");
	$ok=0;
	try{
	$cheese->save();
	}
	catch(RedBean_Exception_FailedAccessBean $e) {
		$ok=1;
	}
	if (!$ok) SmartTest::failedTest(); 
	
	SmartTest::instance()->progress();
	$bordeaux = new Wine;
	$bordeaux->setRegion("Bordeaux");
	
	try{
	$bordeaux->add( $cheese );
	}
	catch(RedBean_Exception_FailedAccessBean $e) {
		$ok=1;
	}
	if (!$ok) SmartTest::failedTest(); 
	SmartTest::instance()->progress();
	
	try{
	$bordeaux->attach( $cheese );
	}
	catch(RedBean_Exception_FailedAccessBean $e) {
		$ok=1;
	}
	if (!$ok) SmartTest::failedTest(); 
	SmartTest::instance()->progress();
	
	try{
	$bordeaux->add( new Wine() );
	$ok=1;
	}
	catch(RedBean_Exception_FailedAccessBean $e) {
		$ok=0;
	}
	if (!$ok) SmartTest::failedTest(); 
	
	SmartTest::instance()->progress(); ;
	
	RedBean_OODB::$pkey = $oldkey;
	$cheese = new Cheese(1);
	$cheese->setName("Camembert");
	$ok=0;
	try{
	$cheese->save();
	$ok=1;
	}
	catch(RedBean_Exception_FailedAccessBean $e) {
		$ok=0;
	}
	if (!$ok) SmartTest::failedTest(); 
	
	
	SmartTest::instance()->progress();
	try{
	RedBean_OODB::$pkey = 999;
	RedBean_OODB::setLockingTime(0);
	sleep(1);
	$cheese = new Cheese(1);
	$cheese->setName("Cheddar");
	
	$cheese->save();
	RedBean_OODB::setLockingTime(10); //*
	
	SmartTest::instance()->progress(); ;
	}catch(Exception $e) {
		SmartTest::failedTest();
	}
	
	try{
	RedBean_OODB::$pkey = 123;
	RedBean_OODB::setLockingTime(100);
	$cheese = new Cheese(1);
	$cheese->setName("Cheddar2");
	$cheese->save();
	RedBean_OODB::setLockingTime(10);
	SmartTest::failedTest();
	}catch(Exception $e) {
	SmartTest::instance()->progress(); ;	
	}
	
	
	try{
	RedBean_OODB::$pkey = 42;
	RedBean_OODB::setLockingTime(0);
	$cheese = new Cheese(1);
	$cheese->setName("Cheddar3");
	$cheese->save();
	RedBean_OODB::setLockingTime(10);
	SmartTest::instance()->progress(); ;
	}catch(Exception $e) {
		SmartTest::failedTest();
	}
	
	//test value ranges
	SmartTest::instance()->progress(); ;
	SmartTest::instance()->testPack = "protect inner state of RedBean";
	try{
	RedBean_OODB::setLockingTime( -1 );
	SmartTest::failedTest();
	}catch(RedBean_Exception_InvalidArgument $e){  }
	
	SmartTest::instance()->progress(); ;
	
	try{
	RedBean_OODB::setLockingTime( 1.5 );
	SmartTest::failedTest();
	}catch(RedBean_Exception_InvalidArgument $e){  }
	
	SmartTest::instance()->progress(); ;
	try{
	RedBean_OODB::setLockingTime( "aaa" );
	SmartTest::failedTest();
	}catch(RedBean_Exception_InvalidArgument $e){  }
	
	SmartTest::instance()->progress(); ;
	try{
	RedBean_OODB::setLockingTime( null );
	SmartTest::failedTest();
	}catch(RedBean_Exception_InvalidArgument $e){  }
	
	SmartTest::instance()->progress(); ;
	
	
	//test convenient tree functions
	 SmartTest::instance()->testPack="convient tree functions";
	if (!class_exists("Person")) RedBean_OODB::gen("person");
	$donald = new Person();
	$donald->setName("Donald");
	$donald->save();
	$kwik = new Person();
	$kwik->setName("Kwik");
	$kwik->save();
	$kwek = new Person();
	$kwek->setName("Kwek");
	$kwek->save();
	$kwak = new Person();
	$kwak->setName("Kwak");
	$kwak->save();
	$donald->attach( $kwik );
	$donald->attach( $kwek );
	$donald->attach( $kwak );
	if (count($donald->children())!=3) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); ;
	if (count($kwik->siblings())!=2) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); ;
	
	//todo
	if ($kwik->hasParent($donald)!=true) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); ;
	if ($donald->hasParent($kwak)!=false) {SmartTest::failedTest();}else SmartTest::instance()->progress(); ;
	
	if ($donald->hasChild($kwak)!=true) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); ;
	if ($donald->hasChild($donald)!=false) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); ;
	if ($kwak->hasChild($kwik)!=false) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); ;
	
	if ($kwak->hasSibling($kwek)!=true) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); ;
	if ($kwak->hasSibling($kwak)!=false) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); ;
	if ($kwak->hasSibling($donald)!=false) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); ;
	
	
	//copy
	SmartTest::instance()->testPack="copy functions";
	$kwak2 = $kwak->copy();
	$id = $kwak2->save();
	$kwak2 = new Person( $id );
	if ($kwak->getName() != $kwak2->getName()) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); ;
	
	asrt(count($donald->children()),3);
	Person::delete($kwek);
	asrt(count($donald->children()),2);
	Person::delete($kwik);
	asrt(count($donald->children()),1);
	Person::delete($kwak);
	asrt(count($donald->children()),0);
	
	SmartTest::instance()->testPack="countRelated";
	R::gen("Blog,Comment");
	$blog = new Blog;
	$blog2 = new Blog;
	$blog->setTitle("blog1");
	$blog2->setTitle("blog2");
	for($i=0; $i<5; $i++){
		$comment = new Comment;
		$comment->setText( "comment no.  $i " );
		$blog->add( $comment );	
	}
	for($i=0; $i<3; $i++){
		$comment = new Comment;
		$comment->setText( "comment no.  $i " );
		$blog2->add( $comment );	
	}
	
	if ($blog->numofComment()!==5) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); ;
	if ($blog2->numofComment()!==3) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); ;
	
	
	SmartTest::instance()->testPack="associate tables of the same name";
	$blog = new Blog;
	$blogb = new Blog;
	$blog->title='blog a';
	$blogb->title='blog b';
	$blog->add( $blogb );
	$b = $blog->getRelatedBlog();
	if (count($b)!==1) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); ;
	$b=	array_pop($b);
	if ($b->title!='blog b') {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	
	
	SmartTest::instance()->testPack="inferTypeII patch";
	$blog->rating = 4294967295;
	$blog->save();
	$id = $blog->getID();
	$blog2->rating = -1;
	$blog2->save();
	$blog = new Blog( $id );
	if ($blog->getRating()!=4294967295) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	
	SmartTest::instance()->testPack="Longtext column type";
	$blog->message = str_repeat("x",65535);
	$blog->save();
	$blog = new Blog( $id );
	
	if (strlen($blog->message)!=65535) {SmartTest::failedTest(); }else SmartTest::instance()->progress();
	$rows = RedBean_OODB::$db->get("describe blog");
	if($rows[3]["Type"]!="text")  {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	$blog->message = str_repeat("x",65536);
	$blog->save();
	$blog = new Blog( $id );
	if (strlen($blog->message)!=65536) {SmartTest::failedTest(); }else SmartTest::instance()->progress();
	$rows = RedBean_OODB::$db->get("describe blog");
	if($rows[3]["Type"]!="longtext")  {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	Redbean_OODB::clean();
	
	SmartTest::instance()->testPack="Export";
	RedBean_OODB::gen("justabean");
	$oBean = new JustABean();
	$oBean->setA("a");
	$oOtherBean = new OODBBean();
	$oOtherBean->a = "b";
	$oBean2 = new OODBBean();
	$oBean->exportTo( $oBean2);
	if ($oBean2->a!=="a") {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	$oBean2 = $oBean->exportTo($oBean2);
	if ($oBean2->a!=="a") {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	$oBean->exportTo($oBean2, $oOtherBean);
	if ($oBean2->a!=="b") {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	$arr = array();
	$oBean->exportTo($arr);
	if ($arr["a"]!=="a") {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	$arr = array();
	$arr = $oBean->exportTo($arr);
	if ($arr["a"]!=="a") {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	$arr = $oBean->exportTo($arr, $oOtherBean);
	if ($arr["a"]!=="b") {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	
	SmartTest::instance()->testPack="Export Array";
	$oBean->a = "a";
	$oInnerBean = new JustABean;
	$oInnerBean->setID(123);
	$oBean->innerbean = $oInnerBean;
	$arr = $oBean->exportAsArr();
	if (!is_array($arr)) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	if ($arr["innerbean"]!==123) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	if ($arr["a"]!=="a") {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	
	//test 1-to-n 
	SmartTest::instance()->testPack="1-to-n relations";
	R::gen("Track,Disc");
	$cd1 = new Disc;
	$cd1->name='first';
	$cd1->save();
	$cd2 = new Disc;
	$cd2->name='second';
	$cd2->save();
	$track = new Track;
	$track->title = "song 1";
	$track->belongsTo( $cd1 );
	$discs = $track->getRelatedDisc();
	asrt(count($discs),1);
	$track->belongsTo( $cd2 );
	$discs = $track->getRelatedDisc();
	asrt(count($discs),1);
	$track2 = new Track;
	$track2->title = "song 2";
	$cd1->exclusiveAdd( $track2 );
	asrt(count($track->getRelatedDisc()),1);
	$cd2->exclusiveAdd( $track2 );
	asrt(count($track->getRelatedDisc()),1);

	RedBean_OODB::gen('SomeBean');
	$b = new SomeBean;
	$b->aproperty = 1;
	$b->save();
	$b = new SomeBean;
	$b->anotherprop = 1;
	$b->save();
	asrt(RedBean_OODB::numberof("SomeBean"),2);
	RedBean_OODB::trashAll("SomeBean");
	asrt(RedBean_OODB::numberof("SomeBean"),0);
	
	RedBean_OODB::gen("Book");
	$book = new Book;
	$book->setTitle('about a red bean');
	RedBean_OODB::gen("Page");
	$page1 = new Page;
	$page2 = new Page;
	asrt(count($book->getRelatedPage()),0);
	$book->add($page1);
	asrt(count($book->getRelatedPage()),1);
	$book->add($page2);
	asrt(count($book->getRelatedPage()),2);
	$book->remove($page1);
	asrt(count($book->getRelatedPage()),1);
	$book->remove($page2);
	asrt(count($book->getRelatedPage()),0);
}


RedBean_OODB::gen("justabean");
SmartTest::instance()->testPack="Security";
$maliciousproperty = "\"";
$oBean = new JustABean;
$oBean->$maliciousproperty = "a";
try {
	$oBean->save();
	SmartTest::failedTest(); 
}
catch(RedBean_Exception_Security $e){
	SmartTest::instance()->progress();	
}
$maliciousproperty = "\"test";
$oBean = new JustABean;
$oBean->$maliciousproperty = "a";
try {
	$oBean->save();
	SmartTest::instance()->progress();
	 
}
catch(RedBean_Exception_Security $e){
	SmartTest::failedTest();	
}
$converted = "test";
if ($oBean->$converted!=="a") {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
$maliciousvalue = "\"abc";
$oBean->another = $maliciousvalue;
$oBean->save();
$oBean->another;
if ($oBean->another!=="\"abc") {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 

	


SmartTest::instance()->testPack="select only valid engines?";
try{RedBean_OODB::setEngine("nonsensebase"); SmartTest::failedTest(); }catch(Exception $e){ SmartTest::instance()->progress(); ; }
try{RedBean_OODB::setEngine("INNODB"); SmartTest::failedTest(); }catch(Exception $e){ SmartTest::instance()->progress(); ; }
try{RedBean_OODB::setEngine("MYISaM"); SmartTest::failedTest(); }catch(Exception $e){ SmartTest::instance()->progress(); ; }
try{RedBean_OODB::setEngine(""); SmartTest::failedTest(); }catch(Exception $e){ SmartTest::instance()->progress(); ; }

RedBean_OODB::setEngine("myisam");
testsperengine("MYSQL-MYISAM");
RedBean_OODB::setEngine("innodb");
testsperengine("MYSQL-INNODB");


printtext("\n<BR>ALL TESTS PASSED. REDBEAN SHOULD WORK FINE.\n");
