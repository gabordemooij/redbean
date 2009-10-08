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
			printtext("processing testpack: ".RedBean_OODB::getInstance()->getEngine()."-".$v." ...now testing: ");
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
		
		fail();
	}
	
	public static function test( $value, $expected ) {
		if ($value != $expected) {
			fail();
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

$tables = R::getInstance()->getToolBox()->getDatabase()->getCol("show tables");
foreach($tables as $t){
	if ($t!="dtyp" && $t!="redbeantables" && $t!="locking") {
		R::getInstance()->getToolBox()->getDatabase()->exec("DROP TABLE `$t`");
	}
};



//====== TEST TRANSACTIONS =====

/*
//Mind though that table will continue to exits, this test is only about the record in Trans
//Unfortunately you have to run this test apart to see the effect. Check manually.
R::getInstance()->generate("Trans");
$t = new Trans;
$t->name = "part of trans";
$t->save();
//throw new Exception("aa"); //trans should not be in database
//R::getInstance()->rollback();
//R::getInstance()->getToolBox()->getDatabase()->exec("ROLLBACK");
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
$db = RedBean_OODB::getInstance()->getToolBox()->getDatabase();
asrt(($db instanceof RedBean_DBAdapter),true);
//Test description: test multiple database support
testpack("multi database");
$redbean = RedBean_Setup::kickstart("mysql:host=localhost;dbname=oodb","root","",false,"innodb",false);
$old = $redbean->getToolBox()->getDatabase();
RedBean_Setup::kickstart("mysql:host=localhost;dbname=tutorial","root","",false,"innodb",false);
asrt(R::getInstance()->getToolBox()->getDatabase()->getCell("select database()"),"tutorial");
RedBean_Setup::reconnect( $old );
asrt(R::getInstance()->getToolBox()->getDatabase()->getCell("select database()"),"oodb");
$db = R::getInstance()->getToolBox()->getDatabase();

testpack("legacy");
R::gen("myclass");
asrt(class_exists("myclass"), true);
R::keepInShape();
pass();

//Test description: Test importing of data from post array or custom array
testpack("Import");
R::getInstance()->generate("Thing");
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
asrt(RedBean_OODB::getInstance()->generate("Entity1,Entity2"),true);
asrt(class_exists("Entity1"),true);
asrt(class_exists("Entity2"),true);
asrt(RedBean_OODB::getInstance()->generate("Entity3,Entity4","prefix_","_suffix"),true);
asrt(class_exists("prefix_Entity3_suffix"),true);
asrt(class_exists("prefix_Entity4_suffix"),true);
asrt(RedBean_OODB::getInstance()->generate("a\b\Entity5,c\d\Entity6"),true);
asrt(class_exists("\a\b\Entity5"),true);
asrt(class_exists("\c\d\Entity6"),true);
asrt(RedBean_OODB::getInstance()->generate(".,--"),false);

SmartTest::instance()->testPack = "Observers";
R::getInstance()->generate("Employee");
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
$employee->isNerd();
asrt($observer->signal,"deco_get");
$observer->signal="";
$employee->clearRelatedNerd();
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
R::getInstance()->generate("Employee");
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
RedBean_OODB::getInstance()->clean();
RedBean_OODB::getInstance()->generate("trash");
$trash = new Trash();
$trash->save();
RedBean_OODB::getInstance()->clean();
//RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLocking( false ); //turn locking off
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
//Test description: test whether relation tables are spared during keepinshape (github issue #3)
$db->exec("CREATE TABLE  `deletethis` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,`col1` VARCHAR( 255 ) NOT NULL ,`col2` TEXT NOT NULL ,`col3` INT( 11 ) UNSIGNED NOT NULL ,PRIMARY KEY (  `id` ) ) ENGINE = MYISAM");
$db->exec("CREATE TABLE  `deletethis2` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,`col1` VARCHAR( 255 ) NOT NULL ,`col2` TEXT NOT NULL ,`col3` INT( 11 ) UNSIGNED NOT NULL ,PRIMARY KEY (  `id` ) ) ENGINE = MYISAM");
$db->exec("INSERT INTO  `redbeantables` (`id` ,`tablename`) VALUES (NULL ,  'deletethis');");
$db->exec("INSERT INTO  `redbeantables` (`id` ,`tablename`) VALUES (NULL ,  'deletethis2');");
$db->exec("CREATE TABLE  `deletethis_deletethis2` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,`col1` VARCHAR( 255 ) NOT NULL ,`col2` TEXT NOT NULL ,`col3` INT( 11 ) UNSIGNED NOT NULL ,PRIMARY KEY (  `id` ) ) ENGINE = MYISAM");
$db->exec("INSERT INTO  `redbeantables` (`id` ,`tablename`) VALUES (NULL ,  'deletethis_deletethis2');");
$db->exec("CREATE TABLE  `deletethis_dontdeletethis` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,`col1` VARCHAR( 255 ) NOT NULL ,`col2` TEXT NOT NULL ,`col3` INT( 11 ) UNSIGNED NOT NULL ,PRIMARY KEY (  `id` ) ) ENGINE = MYISAM");
$db->exec("INSERT INTO  `redbeantables` (`id` ,`tablename`) VALUES (NULL ,  'deletethis_dontdeletethis');");


R::getInstance()->generate('dontdelete,dontdelete2');
$dontdelete = new dontdelete;
$dontdelete->save();
asrt(intval($db->getCell("SELECT COUNT(*) FROM redbeantables WHERE tablename='dontdelete'")),1);
asrt(intval($db->getCell("SELECT COUNT(*) FROM redbeantables WHERE tablename='deletethis'")),1);
$dontdelete->add( new dontdelete2 );
asrt(intval($db->getCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'oodb' AND table_name = 'deletethis'")),1);
asrt(intval($db->getCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'oodb' AND table_name = 'dontdelete'")),1);
asrt(intval($db->getCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'oodb' AND table_name = 'dontdelete2'")),1);
asrt(intval($db->getCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'oodb' AND table_name = 'dontdelete_dontdelete2'")),1);
asrt(intval($db->getCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'oodb' AND table_name = 'deletethis_dontdeletethis'")),1);
asrt(intval($db->getCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'oodb' AND table_name = 'deletethis_deletethis2'")),1);
R::getInstance()->keepInShape( true );
asrt(intval($db->getCell("SELECT COUNT(*) FROM redbeantables WHERE tablename='dontdelete'")),1);
asrt(intval($db->getCell("SELECT COUNT(*) FROM redbeantables WHERE tablename='deletethis'")),0);
asrt(intval($db->getCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'oodb' AND table_name = 'deletethis'")),0);
asrt(intval($db->getCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'oodb' AND table_name = 'dontdelete'")),1);
asrt(intval($db->getCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'oodb' AND table_name = 'dontdelete2'")),1);
asrt(intval($db->getCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'oodb' AND table_name = 'dontdelete_dontdelete2'")),1);
asrt(intval($db->getCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'oodb' AND table_name = 'deletethis_dontdeletethis'")),0);
asrt(intval($db->getCell("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'oodb' AND table_name = 'deletethis_deletethis2'")),0);


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


R::getInstance()->generate('empcol,slimtable,indexer');


RedBean_OODB::getInstance()->keepInShape( true, "empcol", "aaa" );
RedBean_OODB::getInstance()->keepInShape( true, "empcol", "bbb" );
RedBean_OODB::getInstance()->keepInShape( true, "slimtable", "col1" );
RedBean_OODB::getInstance()->keepInShape( true, "slimtable", "col2" );
RedBean_OODB::getInstance()->keepInShape( true, "slimtable", "col3" );
RedBean_OODB::getInstance()->keepInShape( true, "indexer", "highcard" );
RedBean_OODB::getInstance()->keepInShape( true, "indexer", "highcard2" );
RedBean_OODB::getInstance()->keepInShape( true, "indexer", "lowcard" );
RedBean_OODB::getInstance()->keepInShape( true, "indexer", "lowcard2" );
$empcol = new empcol;
asrt(empcol::where(' @ifexists:aaa=1 or @ifexists:bbb=1')->count(),1);
$row = $db->getRow("select * from slimtable limit 1");
asrt($row["col1"],'1');
asrt($row["col2"],"mustbevarchar");
asrt($row["col3"],'1000');
asrt(count($db->get("describe slimtable")),4); 
RedBean_OODB::getInstance()->dropColumn("slimtable","col3");
asrt(count($db->get("describe slimtable")),3); 
$db->exec("CREATE TABLE  `garbagetable` (`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,`highcard` VARCHAR( 255 ) NOT NULL ,PRIMARY KEY (  `id` )) ENGINE = MYISAM");
$db->exec("INSERT INTO  `redbeantables` (`id` ,`tablename`)VALUES (NULL ,  'garbagetable');");
RedBean_OODB::getInstance()->KeepInShape( true );
$tables = RedBean_OODB::getInstance()->showTables(); 
asrt(in_array("garbagetable",$tables),false);
//Test: can we extend from a bean and still use magic setters / getters?
R::getInstance()->generate("ACat,Dog");
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

//Test Description: if we associate two empty beans we ids must be set
$r = RedBean_OODB::getInstance();

$emptybean1 = $r->dispense("emptybean1");
$emptybean2 = $r->dispense("emptybean2");
$emptybean2->test = 1;
$r->set( $emptybean2 );
$r->trash( $emptybean2 );
$emptybean2 = $r->dispense("emptybean2");
$r->associate($emptybean1,$emptybean2);
try{$res =  $r->getAssoc($emptybean1,"emptybean2"); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); }
asrt(is_array($res),true);
asrt(count($res),1);
$emptybean2 = array_shift($res);
asrt(($emptybean2->id!=0),true);


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
	try{ $writer->getQuery( $query, array("time"=>"","col"=>"","engine"=>"","id1"=>"","id2"=>"",
                    "rollback"=>"","key"=>"","value"=>"","locktime"=>"",
                    "indexname"=>"","property"=>"","t1"=>"","t2"=>"",
                    "t"=>"","tbl"=>"","column"=>"","newtype"=>"","start"=>"","end"=>"","extraSQL"=>"","orderby"=>"",
                    "table"=>"","assoctable"=>"","id"=>"","pid"=>"","cid"=>"",
                    "updatevalues"=>array(),"fields"=>array(),"insertcolumns"=>array(),
                    "insertvalues"=>array(),
	"searchoperators"=>array(), "ids"=>array(), "bean"=>RedBean_OODB::getInstance()->dispense("x"), "tables"=>array(), "type"=>"","stat"=>"","field"=>"" ) ); pass(); }catch(Exception $e){ fail(); }
}
try{ $writer->getQuery("unsupported"); fail(); }catch(Exception $e){ pass(); }
$cols = $writer->getTableColumns("redbeantables",RedBean_OODB::getInstance()->getToolBox()->getDatabase());
asrt(count($cols),2);
$col = array_shift($cols);
asrt($col["Field"],"id");
asrt($col["Type"],"int(11) unsigned");
asrt($col["Null"],"NO");
asrt($col["Default"],null);
asrt($col["Extra"],"auto_increment");

//Test description: is the bean properly checked?
testpack("Bean Checking");
$bean = RedBean_OODB::getInstance()->dispense("bean");
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->check($bean);pass();}catch(RedBean_Exception_Security $oE){ fail(); }
$bean->type = null;
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->check($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean = RedBean_OODB::getInstance()->dispense("bean");
$bean->id = null;
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->check($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = -1;
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->check($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = 0.5;
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->check($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = "5";
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->check($bean);pass();}catch(RedBean_Exception_Security $oE){ fail(); }
$bean->id = "a";
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->check($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = array();
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->check($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = $bean;
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->check($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = 0;
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->check($bean);pass();}catch(RedBean_Exception_Security $oE){ fail(); }
$bean->type = "redbeantables";
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->check($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->type = "dtyp";
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->check($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->type = "locking";
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->check($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
//Some tests for checkAssoc
testpack("checkAssoc");
$bean->type = "justabean";
$bean->id = "a";
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->checkBeanForAssoc($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = array();
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->checkBeanForAssoc($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = $bean;
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->checkBeanForAssoc($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->id = 0;
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->checkBeanForAssoc($bean);pass();}catch(RedBean_Exception_Security $oE){ fail(); }
$bean->type = "redbeantables";
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->checkBeanForAssoc($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->type = "dtyp";
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->checkBeanForAssoc($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }
$bean->type = "locking";
try{RedBean_OODB::getInstance()->getToolBox()->getBeanChecker()->checkBeanForAssoc($bean);fail();}catch(RedBean_Exception_Security $oE){ pass(); }


//Test filtering
testpack("Filter classes");
$r = RedBean_OODB::getInstance();
$r->generate("StrA_NGER");
$o = new StrA_NGER;
asrt($o->getData()->type,"stranger");
$r->getToolBox()->add( "filter", new RedBean_Mod_Filter_NullFilter );
asrt(($r->getToolBox()->getFilter() instanceof RedBean_Mod_Filter_NullFilter),true);
$r->generate("StrA_NGER2");
$o = new StrA_NGER2;
asrt($o->getData()->type,"StrA_NGER2");
$p = "Wr0_NGProp";
$o->$p = 42;
asrt($o->$p,42);
$o->save();
$r->getToolBox()->add( "filter", new RedBean_Mod_Filter_Strict );
pass();
$filter = new RedBean_Mod_Filter_Strict;
try{ $filter->property("_"); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $filter->property(""); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $filter->property(" "); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $filter->property("-"); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $filter->property("...."); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $filter->table("_"); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $filter->table(""); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $filter->table(" "); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $filter->table("-"); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $filter->table("...."); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $filter->table("type"); pass(); }catch(RedBean_Exception_Security $e){ fail(); }
try{ $filter->table("id"); pass(); }catch(RedBean_Exception_Security $e){ fail(); }
try{ $filter->property("type"); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
try{ $filter->property("id"); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
asrt($filter->property("F Ilter TH-_iS!"),"filterthis");
asrt($filter->table("F Ilter TH-_iS!"),"filterthis");

//Tests for each individual engine
function testsperengine( $engine ) {

	
	testpack("Anemic Model on ".$engine);
	//Test basic, fundamental RedBean_OODBBean functions with Anemic Model
	$file = RedBean_OODB::getInstance()->dispense("file");
	asrt((RedBean_OODB::getInstance()->dispense("file") instanceof RedBean_OODBBean),true);
	//Test description: has the type property been set?
	asrt($file->type,"file");
	//Test description: Is the ID set and 0?
	asrt((isset($file->id) && $file->id===0),true);
	//Test don't accept arrays or objects
	try{ $file->anArray = array(1,2,3); $id = RedBean_OODB::getInstance()->set( $file ); fail(); }catch(Exception $e){ pass(); }
	try{ $file->anObject = $file;  $id = RedBean_OODB::getInstance()->set( $file ); fail(); }catch(Exception $e){ pass(); }
	unset($file->anArray);
	unset($file->anObject); 
	
	
	//Test description: test table management features
	testpack("Table Management on ".$engine);
	$cnt = count(RedBean_OODB::getInstance()->showTables());
	RedBean_OODB::getInstance()->addTable("newtable");
	asrt(count(RedBean_OODB::getInstance()->showTables()), (++$cnt));
	RedBean_OODB::getInstance()->dropTable("newtable");
	asrt(count(RedBean_OODB::getInstance()->showTables()), (--$cnt));
	
	
	
	//Test description: can we set and get a bean?
	$file->name="document";
	$id = RedBean_OODB::getInstance()->set( $file );
	asrt(is_numeric($id),true);
	//Test description: can we load it again using an ID?
	$file = RedBean_OODB::getInstance()->getById("file",$id);
	asrt($file->name,"document");
	//Test description: If a bean does not exist RedBean OODB must throw an exception
	try{RedBean_OODB::getInstance()->getById("file",999); fail(); }catch(RedBean_Exception_FailedAccessBean $e){ pass(); };
	//Test description: Only certain IDs are valid: >0 and only integers! using intval!
	//becomes 1
	try{RedBean_OODB::getInstance()->getById("file",1.1); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); };
	//becomes 0
	try{RedBean_OODB::getInstance()->getById("file",0.9); fail(); }catch(RedBean_Exception_FailedAccessBean $e){ pass(); };
	//becomes 1 (due to abs())
	try{RedBean_OODB::getInstance()->getById("file",-1); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); };
	//Test description: can we fastload a bean?
	try{$file2 = RedBean_OODB::getInstance()->getById("file",2,array("name"=>"picture")); pass(); }catch(RedBean_Exception_FailedAccessBean $e){ fail(); }
	asrt($file2->name,"picture");
	
	
	//Test description: test whether we can infer data types
	testpack("Data Type Detection");
	asrt(RedBean_OODB::getInstance()->inferType(1),0);
	asrt(RedBean_OODB::getInstance()->inferType(255),0);
	asrt(RedBean_OODB::getInstance()->inferType(256),1);
	asrt(RedBean_OODB::getInstance()->inferType(-1),2);
	asrt(RedBean_OODB::getInstance()->inferType("12345.9"),3);
	asrt(RedBean_OODB::getInstance()->inferType(str_repeat('a',40000)),4);
	
	
	
	//Test description: can add a property dynamically?
	$file->content=42;
	$id = RedBean_OODB::getInstance()->set( $file );
	$file = RedBean_OODB::getInstance()->getById( "file", $id );
	asrt($file->content,"42");
	//Test description: Can we store a value of a different type in the same property?
	$file->content="Lorem Ipsum";
	$id = RedBean_OODB::getInstance()->set( $file );
	$file = RedBean_OODB::getInstance()->getById( "file", $id );
	asrt($file->content,"Lorem Ipsum");

	testpack("Anemic Model Bean Manipulation on ".$engine);
	//Test description: save and load a complete bean with several properties
	$bean = RedBean_OODB::getInstance()->dispense("note");
	$bean->message = "hello";
	$bean->color=3;
	$bean->date = time();
	$bean->special='n';
	$bean->state = 90;
	$id = RedBean_OODB::getInstance()->set($bean); 
	$bean2 = RedBean_OODB::getInstance()->getById("note", $id);
	asrt(($bean2->state == 90 && $bean2->special =='n' && $bean2->message =='hello'),true);	
	//Test description: change the message property, can we modify it?
	$bean->message = "What is life but a dream?";
	RedBean_OODB::getInstance()->set($bean);
	$bean2 = RedBean_OODB::getInstance()->getById("note", $id); //Using same ID!
	asrt($bean2->message,"What is life but a dream?");
	//Test description can we choose other values, smaller... bigger?
	$bean->message = 1;
	$bean->color = "green";
	$bean->date = str_repeat("BLABLA", 100);
	RedBean_OODB::getInstance()->set($bean);
	$bean2 = RedBean_OODB::getInstance()->getById("note", $id); //Using same ID!
	asrt($bean2->message,"1");
	asrt($bean2->date,$bean->date);
	@asrt($bean2->green,$bean->green);
	//Test description: test whether we can save/load UTF8 values
	testpack("UTF8 ".$engine);
	$txt = file_get_contents("utf8.txt");
	$bean->message=$txt;
	RedBean_OODB::getInstance()->set($bean);
	$bean2 = RedBean_OODB::getInstance()->getById("note", $id); //Using same ID!
	asrt($bean2->message,file_get_contents("utf8.txt"));
	global $tests;
	
	//Test description: test whether we can associate anemic beans
	testpack("Associations $engine ");
	$note = $bean;
	$person = RedBean_OODB::getInstance()->dispense("person");
	$person->age = 50;
	$person->name = "Bob";
	$person->gender = "m";
	RedBean_OODB::getInstance()->set( $person );
	RedBean_OODB::getInstance()->associate( $person, $note );
	$memo = RedBean_OODB::getInstance()->getById( "note", 1 );
	$authors = RedBean_OODB::getInstance()->getAssoc( $memo, "person" );
	asrt(count($authors),1); 
	RedBean_OODB::getInstance()->trash( $authors[1] );
	$authors = RedBean_OODB::getInstance()->getAssoc( $memo, "person" );
	asrt(count($authors),0);

	testpack("Put a Bean in the Database - various types $engine ");
	$person = RedBean_OODB::getInstance()->dispense("person");
	$person->name = "John";
	$person->age= 35;
	$person->gender = "m";
	$person->hasJob = true;
	$id = RedBean_OODB::getInstance()->set( $person ); $johnid=$id;
	$person2 = RedBean_OODB::getInstance()->getById( "person", $id );
	
	asrt(intval($person2->age),intval($person->age)); 
	$person2->anotherprop = 2;
	RedBean_OODB::getInstance()->set( $person2 );
	$person = RedBean_OODB::getInstance()->dispense("person");
	$person->name = "Bob";
	$person->age= 50;
	$person->gender = "m";
	$person->hasJob = false;
	$bobid = RedBean_OODB::getInstance()->set( $person );
	
	testpack("getBySQL $engine ");
	$ids = RedBean_OODB::getInstance()->getBySQL("`gender`={gender} order by `name` asc",array("gender"=>"m"),"person");
	asrt(count($ids),2);
	$ids = RedBean_OODB::getInstance()->getBySQL("`gender`={gender} OR `color`={clr} ",array("gender"=>"m","clr"=>"red"),"person");
	asrt(count($ids),0);
	$ids = RedBean_OODB::getInstance()->getBySQL("`gender`={gender} AND `color`={clr} ",array("gender"=>"m","clr"=>"red"),"person");
	asrt(count($ids),0);
	
	//Test description: table names should always be case insensitive, no matter the table name
	testpack("case insens. $engine ");
	R::getInstance()->generate("PERSON");
	$dummy = new Person;
	asrt($dummy->getData()->type,"person");
	
	//Test description: table names should contain no underscores
	testpack("tablenames. $engine ");
	R::getInstance()->generate("under_score_s");
	asrt(class_exists("under_score_s"),true);
	$a = new under_score_s;
	asrt($a->getData()->type,"underscores");
	
	//Test description: find()
	testpack("anemic find");
	$dummy->age = 40;
	$rawdummy = $dummy->getData();
	asrt(count(RedBean_OODB::getInstance()->find( $rawdummy, array("age"=>">"))),1);
	$rawdummy->age = 20;
	asrt(count(RedBean_OODB::getInstance()->find( $rawdummy, array("age"=>">"))),2);
	$rawdummy->age = 100;
	asrt(count(RedBean_OODB::getInstance()->find( $rawdummy, array("age"=>">"))),0);
	$rawdummy->age = 100;
	asrt(count(RedBean_OODB::getInstance()->find( $rawdummy, array("age"=>"<="))),2);
	$rawdummy->name="ob";
	asrt(count(RedBean_OODB::getInstance()->find( $rawdummy, array("name"=>"LIKE"))),1);
	$rawdummy->name="o";
	asrt(count(RedBean_OODB::getInstance()->find( $rawdummy, array("name"=>"LIKE"))),2);
	$rawdummy->gender="m";
	asrt(count(RedBean_OODB::getInstance()->find( $rawdummy, array("gender"=>"="))),2);
	$rawdummy->gender="f";
	asrt(count(RedBean_OODB::getInstance()->find( $rawdummy, array("gender"=>"="))),0);
	asrt(count(RedBean_OODB::getInstance()->listAll("person")),2);
	asrt(count(RedBean_OODB::getInstance()->listAll("person",0,1)),1);
	asrt(count(RedBean_OODB::getInstance()->listAll("person",1)),1);
	
	
	//Test description: associate
	testpack("anemic association");
	$searchBean = RedBean_OODB::getInstance()->dispense("person");
	$searchBean->gender = "m";
	SmartTest::instance()->progress();
	$app = RedBean_OODB::getInstance()->dispense("appointment");
	$app->kind = "dentist";
	RedBean_OODB::getInstance()->set($app);
	RedBean_OODB::getInstance()->associate( $person2, $app );
	$arr = RedBean_OODB::getInstance()->getAssoc( $person2, "appointment" );
	$appforbob = array_shift($arr);
	if (!$appforbob || $appforbob->kind!="dentist") {
		SmartTest::failedTest();
	} 
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "delete a bean?";
	$person = RedBean_OODB::getInstance()->getById( "person", $bobid );
	RedBean_OODB::getInstance()->trash( $person );
	try{
	$person = RedBean_OODB::getInstance()->getById( "person", $bobid);$ok=0;
	}catch(RedBean_Exception_FailedAccessBean $e){
		$ok=true;
	}
	if (!$ok) {
		SmartTest::failedTest();
	}
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "unassociate two beans?";
	$john = RedBean_OODB::getInstance()->getById( "person", $johnid); //hmmmmmm gaat mis bij innodb
	$app = RedBean_OODB::getInstance()->getById( "appointment", 1);
	RedBean_OODB::getInstance()->unassociate($john, $app);
	$john2 = RedBean_OODB::getInstance()->getById( "person", $johnid);
	$appsforjohn = RedBean_OODB::getInstance()->getAssoc($john2,"appointment");
	if (count($appsforjohn)>0) SmartTest::failedTest();
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "unassociate by deleting a bean?";
	$anotherdrink = RedBean_OODB::getInstance()->dispense("whisky");
	$anotherdrink->name = "bowmore";
	$anotherdrink->age = 18;
	$anotherdrink->singlemalt = 'y';
	RedBean_OODB::getInstance()->set( $anotherdrink );
	RedBean_OODB::getInstance()->associate( $anotherdrink, $john );
	$hisdrinks = RedBean_OODB::getInstance()->getAssoc( $john, "whisky" );
	if (count($hisdrinks)!==1) SmartTest::failedTest();
	RedBean_OODB::getInstance()->trash( $anotherdrink );
	$hisdrinks = RedBean_OODB::getInstance()->getAssoc( $john, "whisky" );
	if (count($hisdrinks)!==0) SmartTest::failedTest();
	SmartTest::instance()->progress(); ; 
	
	//Test description: trees
	testpack("anemic trees");

	$pete = RedBean_OODB::getInstance()->dispense("person");
	$pete->age=48;
	$pete->gender="m";
	$pete->name="Pete";
	$peteid = RedBean_OODB::getInstance()->set( $pete );
        $rob = RedBean_OODB::getInstance()->dispense("person");
	$rob->age=19;
	$rob->name="Rob";
	$rob->gender="m";
	$saskia = RedBean_OODB::getInstance()->dispense("person");
	$saskia->age=20;
	$saskia->name="Saskia";
	$saskia->gender="f";
	$idsaskia = RedBean_OODB::getInstance()->set( $saskia );
	$idrob = RedBean_OODB::getInstance()->set( $rob );
	RedBean_OODB::getInstance()->addChild( $pete, $rob );
	RedBean_OODB::getInstance()->addChild( $pete, $saskia );
	$children = RedBean_OODB::getInstance()->getChildren( $pete );
	$names=0;
	if (is_array($children) && count($children)===2) {
		foreach($children as $child){
			if ($child->name==="Rob") $names++;
			if ($child->name==="Saskia") $names++;
		}
	}
	
	if (!$names) fail();
	$daddies = RedBean_OODB::getInstance()->getParent( $saskia );
	$daddy = array_pop( $daddies );
	if ($daddy->name === "Pete") $ok = 1; else $ok = 0;
	if (!$ok) fail();
	pass();
	testpack("remove a child from a parent-child tree?");
	RedBean_OODB::getInstance()->removeChild( $daddy, $saskia );
	$children = RedBean_OODB::getInstance()->getChildren( $pete );
	asrt(count($children),1);
	$only = array_pop($children);
	asrt($only->name,"Rob");
	
	//test can we move a node in the tree?
	testpack("change parent");
	$node1 = RedBean_OODB::getInstance()->dispense("node");
	$node1->name = "node1";
	$node2 = RedBean_OODB::getInstance()->dispense("node");
	$node2->name = "node2";
	$node3 = RedBean_OODB::getInstance()->dispense("node");
	$node3->name = "node3";
	RedBean_OODB::getInstance()->set($node1);
	RedBean_OODB::getInstance()->set($node2);
	RedBean_OODB::getInstance()->set($node3);
	RedBean_OODB::getInstance()->addChild($node1,$node2);
	asrt(count(RedBean_OODB::getInstance()->getChildren($node1)),1);
	asrt(count(RedBean_OODB::getInstance()->getChildren($node3)),0);
	RedBean_OODB::getInstance()->addChild($node1,$node2);
	//nothing changes due to unique constraint 
	asrt(count(RedBean_OODB::getInstance()->getChildren($node1)),1);
	asrt(count(RedBean_OODB::getInstance()->getChildren($node3)),0);
	RedBean_OODB::getInstance()->addChild($node3,$node2);
	asrt(count(RedBean_OODB::getInstance()->getChildren($node1)),1);
	asrt(count(RedBean_OODB::getInstance()->getChildren($node3)),1);
	//now swap! 
	RedBean_OODB::getInstance()->removeChild($node1,$node2);
	asrt(count(RedBean_OODB::getInstance()->getChildren($node1)),0);
	asrt(count(RedBean_OODB::getInstance()->getChildren($node3)),1);
	//swap back again
	RedBean_OODB::getInstance()->removeChild($node3,$node2);
	RedBean_OODB::getInstance()->addChild($node1,$node2);
	asrt(count(RedBean_OODB::getInstance()->getChildren($node1)),1);
	asrt(count(RedBean_OODB::getInstance()->getChildren($node3)),0);
        $nodes = RedBean_OODB::getInstance()->getParent($node2);
	$node = array_shift($nodes);
	asrt($node->name,"node1");
	RedBean_OODB::getInstance()->associate($node2, $node3); //normal assoc
	RedBean_OODB::getInstance()->associate($node2, RedBean_OODB::getInstance()->dispense("nodeb"));
	asrt(count(RedBean_OODB::getInstance()->getParent($node2)),1);
	asrt(count(RedBean_OODB::getInstance()->getAssoc($node2, "node")),1);
	asrt(count(RedBean_OODB::getInstance()->getAssoc($node2, "nodeb")),1);
	RedBean_OODB::getInstance()->deleteAllAssocType("node",$node2);
	asrt(count(RedBean_OODB::getInstance()->getParent($node2)),0);
	asrt(count(RedBean_OODB::getInstance()->getAssoc($node2, "node")),0);
	asrt(count(RedBean_OODB::getInstance()->getAssoc($node2, "nodeb")),1);
	RedBean_OODB::getInstance()->deleteAllAssoc($node2);
	asrt(count(RedBean_OODB::getInstance()->getParent($node2)),0);
	asrt(count(RedBean_OODB::getInstance()->getAssoc($node2, "node")),0);
	asrt(count(RedBean_OODB::getInstance()->getAssoc($node2, "nodeb")),0);
	
	
	//exit;
	
	//Test description: on the fly saving while associating beans
	testpack("save on the fly while associating?");
	$food = RedBean_OODB::getInstance()->dispense("dish");
	$food->name="pizza";
	RedBean_OODB::getInstance()->associate( $food, $pete );
	$petesfood = RedBean_OODB::getInstance()->getAssoc( $pete, "dish" );
	asrt((is_array($petesfood) && count($petesfood)===1),true);
	RedBean_OODB::getInstance()->unassociate( $food, $pete );
	$petesfood = RedBean_OODB::getInstance()->getAssoc( $pete, "dish" );
	asrt((is_array($petesfood) && count($petesfood)===0),true);
	
	
	//Test description: test whether we can trash beans
	testpack("trash on $engine ");
	$food = RedBean_OODB::getInstance()->dispense("dish");
	$food->name="spaghetti";
	//no exception or error... must be able to trash unsaved beans
	try{ RedBean_OODB::getInstance()->trash( $food ); pass(); }catch(Exception $e){ fail();}
	asrt(count(RedBean_OODB::getInstance()->find($pete,array("name"=>"="))),1);
	asrt(count(RedBean_OODB::getInstance()->find($saskia,array("name"=>"="))),1);
	RedBean_OODB::getInstance()->trash( $pete );
	RedBean_OODB::getInstance()->trash( $saskia );
	asrt(count(RedBean_OODB::getInstance()->find($pete,array("name"=>"="))),0);
	asrt(count(RedBean_OODB::getInstance()->find($saskia,array("name"=>"="))),0);
	
	
	
	
	//Test decorator
	//Test description: can we find beans on the basis of similarity?
	testpack("finder on decorator and listAll on decorator $engine ");
	
	//Test description: we should not be able to modify type and id
	$person = new Person;
	try{ $person->id = 9; fail(); }catch(RedBean_Exception_Security $e){ pass(); }
	try{ $person->type = 'alien'; fail(); }catch(RedBean_Exception_Security $e){ pass(); }
	try{ $person->setID(9); fail(); }catch(RedBean_Exception_Security $e){ pass(); }

        
	try{ $person->setType('alien'); fail(); }catch(RedBean_Exception_Security $e){ pass(); }
	
	$person = RedBean_OODB::getInstance()->dispense("person");
	$person->age = 50;
	$person->name = "Bob";
	$person->gender = "m";
	RedBean_OODB::getInstance()->set( $person );
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
	
	//Test description Can we create read only instances?
        testpack("Read Only Beans");
        RedBean_OODB::getInstance()->generate("Story");
        $story = new Story;
        $story->name = "Never Ending";
        asrt($story->isReadOnly(), false);
        RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLocking( false );
        $id = $story->save();
        RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLocking( true );
        $story2 = Story::getReadOnly( $id );
        asrt($story2->isReadOnly(), true);
       

	//Test description Cans
	RedBean_OODB::getInstance()->trash( $rob );
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
	$s = RedBean_OODB::getInstance()->dispense("stattest");
	$s->amount = 1;
	RedBean_OODB::getInstance()->set( $s );
	$s = RedBean_OODB::getInstance()->dispense("stattest");
	$s->amount = 2;
	RedBean_OODB::getInstance()->set( $s );
	$s = RedBean_OODB::getInstance()->dispense("stattest");
	$s->amount = 3;
	$id = RedBean_OODB::getInstance()->set( $s );
	
	//Test description: quick aggregation functions
	testpack("can we use aggr functions using Redbean?");
	asrt(RedBean_OODB::getInstance()->exists("stattest",$id),true);
	asrt(RedBean_OODB::getInstance()->exists("stattest",99),false);
	asrt(intval(RedBean_OODB::getInstance()->numberof("stattest")),3); 
	asrt(intval(RedBean_OODB::getInstance()->maxof("stattest","amount")),3); 
	asrt(intval(RedBean_OODB::getInstance()->minof("stattest","amount")),1); 
	asrt(intval(RedBean_OODB::getInstance()->avgof("stattest","amount")),2); 
	asrt(intval(RedBean_OODB::getInstance()->sumof("stattest","amount")),6); 
	asrt(count(RedBean_OODB::getInstance()->distinct("stattest","amount")),3); 
	
		
	RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLocking( true );
	$i=3;
	SmartTest::instance()->testPack="generate only valid classes?";
	try{ $i += RedBean_OODB::getInstance()->generate(""); SmartTest::instance()->progress(); ; }catch(Exception $e){ SmartTest::failedTest(); } //nothing
	try{ $i += RedBean_OODB::getInstance()->generate("."); SmartTest::instance()->progress(); ; }catch(Exception $e){ SmartTest::failedTest(); } //illegal chars
	try{ $i += RedBean_OODB::getInstance()->generate(","); SmartTest::instance()->progress(); ; }catch(Exception $e){ SmartTest::failedTest(); } //illegal chars
	try{ $i += RedBean_OODB::getInstance()->generate("null"); SmartTest::instance()->progress(); ; }catch(Exception $e){ SmartTest::failedTest(); } //keywords
	try{ $i += RedBean_OODB::getInstance()->generate("Exception"); SmartTest::instance()->progress(); ; }catch(Exception $e){ SmartTest::failedTest(); } //reserved
	
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "generate classes using Redbean?";
	
	if (!class_exists("Bug")) {
		$i += RedBean_OODB::getInstance()->generate("Bug");
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
	
	
	
	pass();
	
	testpack("avoid race-conditions by locking?");
	RedBean_OODB::getInstance()->generate("Cheese,Wine");
	$cheese = new Cheese;
	$cheese->setName('Brie');
	$cheese->save();
	$cheese = new Cheese(1);
	//try to mess with the locking system... simulate another proces by providing another key
	$oldkey = RedBean_OODB::getInstance()->pkey;
	RedBean_OODB::getInstance()->pkey = 1234;
	$cheese = new Cheese(1);
	$cheese->setName("Camembert");
	//Test description: you should not be able to save a read-lock bean (modifying assoc table)
	try{ $cheese->save(); fail();	}catch(RedBean_Exception_FailedAccessBean $e) { pass(); }
	$bordeaux = new Wine;
	$bordeaux->setRegion("Bordeaux");
	//Test description: you should not be able to add to a read-locked bean (modifying assoc table)
	try{ $bordeaux->add( $cheese ); fail();	}catch(RedBean_Exception_FailedAccessBean $e) { pass();	}
	//Test description: you should not be able to attach to a read-locked bean (modifying assoc table)
	try{ $bordeaux->attach( $cheese ); fail(); }catch(RedBean_Exception_FailedAccessBean $e) { pass(); }
	//but your own object is unaffected
	try{ $bordeaux->add( new Wine() ); pass(); }catch(RedBean_Exception_FailedAccessBean $e) { fail(); }
	//switch back to the original session
	RedBean_OODB::getInstance()->pkey = $oldkey;
	$cheese = new Cheese(1);
	$cheese->setName("Camembert");
	try{ $cheese->save(); pass(); }catch(RedBean_Exception_FailedAccessBean $e) { fail();}
	//now pretend to be a third session
	RedBean_OODB::getInstance()->pkey = 999;
	//however locking is turned off now
	RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLocking( false );
	//and we modify the previously crafted record
	$cheese = new Cheese(1);
	$cheese->setName("Cheddar");
	try{ $cheese->save(); pass(); }catch(Exception $e){ fail(); }
	//restore locking
	RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLocking( true );
	//verify that locking with time 0 and locking turned on is simply a no-go, because no object
	//will have enough time to aqcuire a lock
	RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLockingTime(0); 
	$merlot = new Wine;
	$merlot->setGrape("Merlot");
	$id = $merlot->save();
	$merlot = new Wine( $id );
	$merlot->setRegion("Santa Rita");
	try{ $merlot->save(); fail(); }catch(Exception $e){ pass(); }
	
	//Test description: same kind of test; different key and time, should not be able to write to cheese 1
	RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLockingTime(100);
	RedBean_OODB::getInstance()->pkey = $oldkey;
	$cheese = new Cheese(1);
	RedBean_OODB::getInstance()->pkey = 123;
	$cheese = new Cheese(1);
	$cheese->setName("Cheddar2");
	try{ $cheese->save(); fail();}catch(Exception $e) { pass();	}
	
	//Test description: now test whether a lock expires
	echo '---'; //test marker
	//Once again a different session
	RedBean_OODB::getInstance()->pkey = 42;
	//We will have to wait 2 seconds before it expires
	RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLockingTime(2);
	//Open the bean
	sleep(2); 
	$cheese = new Cheese(1);
	$cheese->setName("Cheddar3");
	try{  $cheese->save(); pass(); }catch(Exception $e) { fail();	}
	//reset
	RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLockingTime(10);
	
	
	testpack("protect inner state of RedBean");
	try{
	RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLockingTime( -1 );
	SmartTest::failedTest();
	}catch(RedBean_Exception_InvalidArgument $e){  }
	
	SmartTest::instance()->progress(); ;
	
	try{
	RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLockingTime( 1.5 );
	SmartTest::failedTest();
	}catch(RedBean_Exception_InvalidArgument $e){  }
	
	SmartTest::instance()->progress(); ;
	try{
	RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLockingTime( "aaa" );
	SmartTest::failedTest();
	}catch(RedBean_Exception_InvalidArgument $e){  }
	
	SmartTest::instance()->progress(); ;
	try{
	RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLockingTime( null );
	SmartTest::failedTest();
	}catch(RedBean_Exception_InvalidArgument $e){  }
	
	SmartTest::instance()->progress(); ;
	
	
	//test convenient tree functions
	 SmartTest::instance()->testPack="convient tree functions";
	if (!class_exists("Person")) RedBean_OODB::getInstance()->generate("person");
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
	R::getInstance()->generate("Blog,Comment");
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
	$rows = RedBean_OODB::getInstance()->getToolBox()->getDatabase()->get("describe blog");
	if($rows[3]["Type"]!="text")  {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	$blog->message = str_repeat("x",65536);
	$blog->save();
	$blog = new Blog( $id );
	if (strlen($blog->message)!=65536) {SmartTest::failedTest(); }else SmartTest::instance()->progress();
	$rows = RedBean_OODB::getInstance()->getToolBox()->getDatabase()->get("describe blog");
	if($rows[3]["Type"]!="longtext")  {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	RedBean_OODB::getInstance()->clean();
	
	SmartTest::instance()->testPack="Export";
	RedBean_OODB::getInstance()->generate("justabean");
	$oBean = new JustABean();
	$oBean->setA("a");
	$oOtherBean = new RedBean_OODBBean();
	$oOtherBean->a = "b";
	$oBean2 = new RedBean_OODBBean();
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
	$inner= new JustABean();
	$id = $inner->save();
	$oBean->innerbean = $inner;
	$arr = $oBean->exportAsArr();
	if (!is_array($arr)) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	if ($arr["innerbean"]!==$id) {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	if ($arr["a"]!=="a") {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 
	
	//test 1-to-n 
	SmartTest::instance()->testPack="1-to-n relations";
	R::getInstance()->generate("Track,Disc");
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

	RedBean_OODB::getInstance()->generate('SomeBean');
	$b = new SomeBean;
	$b->aproperty = 1;
	$b->save();
	$b = new SomeBean;
	$b->anotherprop = 1;
	$b->save();
	asrt(RedBean_OODB::getInstance()->numberof("SomeBean"),2);
	RedBean_OODB::getInstance()->trashAll("SomeBean");
	asrt(RedBean_OODB::getInstance()->numberof("SomeBean"),0);
	
	RedBean_OODB::getInstance()->generate("Book");
	$book = new Book;
	$book->setTitle('about a red bean');
	RedBean_OODB::getInstance()->generate("Page");
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


RedBean_OODB::getInstance()->generate("justabean");
SmartTest::instance()->testPack="Security";
$maliciousproperty = "\"";
$oBean = R::getInstance()->dispense("justabean");
$oBean->$maliciousproperty = "a";
try {
	R::getInstance()->set($oBean);
	SmartTest::failedTest(); 
}
catch(RedBean_Exception_Security $e){
	SmartTest::instance()->progress();	
}
$maliciousproperty = "\"test";
$oBean = R::getInstance()->dispense("justabean");
$oBean->$maliciousproperty = "a";
try {
	R::getInstance()->set($oBean);
	fail();
}
catch(RedBean_Exception_Security $e){
	pass();
}

$maliciousvalue = "\"abc";
$oBean = R::getInstance()->dispense("justabean");
$oBean->another = $maliciousvalue;
R::getInstance()->set($oBean);
$oBean->another;
if ($oBean->another!=="\"abc") {SmartTest::failedTest(); }else SmartTest::instance()->progress(); 

//test decorator as well
$malicious = array( "a"=>"\"a", "b"=>"\'b", "x"=>" x  " );
$bean = new JustABean;
foreach($malicious as $k=>$m) {
    $bean->$m = sha1($m);
    asrt(($bean->$m === $bean->$k),true);
}
$m = "";
try{ $bean->$m = 1; fail(); }catch(RedBean_Exception_Security $e){ pass(); }


SmartTest::instance()->testPack="select only valid engines?";
try{RedBean_OODB::getInstance()->setEngine("nonsensebase"); SmartTest::failedTest(); }catch(Exception $e){ SmartTest::instance()->progress(); ; }
try{RedBean_OODB::getInstance()->setEngine("INNODB"); SmartTest::failedTest(); }catch(Exception $e){ SmartTest::instance()->progress(); ; }
try{RedBean_OODB::getInstance()->setEngine("MYISaM"); SmartTest::failedTest(); }catch(Exception $e){ SmartTest::instance()->progress(); ; }
try{RedBean_OODB::getInstance()->setEngine(""); SmartTest::failedTest(); }catch(Exception $e){ SmartTest::instance()->progress(); ; }

RedBean_OODB::getInstance()->setEngine("myisam");
testsperengine("MYSQL-MYISAM");
RedBean_OODB::getInstance()->setEngine("innodb");
testsperengine("MYSQL-INNODB");


printtext("\n<BR>ALL TESTS PASSED. REDBEAN SHOULD WORK FINE.\n");
