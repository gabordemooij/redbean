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
		printtext("FAILED TEST");
		exit;
	}
}

function testpack($name) {
	printtext("testing: ".$name);
}

//Use this database for tests
require("allinone.php");
RedBean_Setup::kickstart("mysql:host=localhost;dbname=oodb","root","",false,"innodb",false);



SmartTest::instance()->testPack = "Basic test suite";
$tests = 0; SmartTest::instance()->progress(); ;

//Test description: Does the redbean core class exist?
if (class_exists("RedBean_OODB")) {
	SmartTest::instance()->progress(); ;
}
else {
	SmartTest::failedTest(); 	
}

//Test description: Does the redbean decorator class exist?
if (class_exists("RedBean_Decorator")) {
	SmartTest::instance()->progress(); ;
}
else {
	SmartTest::failedTest();	
}

//Test description: Does the redbean database adapter class exist?
if (class_exists("RedBean_DBAdapter")) {
	SmartTest::instance()->progress(); ;
}
else {
	SmartTest::failedTest();	
}




//Test description: Check other basic functions
try{
	$db = RedBean_OODB::$db;
	if ($db instanceof RedBean_DBAdapter) SmartTest::instance()->progress(); else SmartTest::failedTest();
	if ((RedBean_OODB::getVersionInfo())) {
		SmartTest::instance()->progress(); ;
	}
	else {
		SmartTest::failedTest(); 
	}
	if ((RedBean_OODB::getVersionNumber())) {
		SmartTest::instance()->progress(); ;
	}else {
		SmartTest::failedTest(); 
	}
	SmartTest::instance()->progress(); ;
}
catch(Exception $e) {
	SmartTest::failedTest();
}



SmartTest::instance()->testPack = "Import";
R::gen("Thing");
$_POST["first"]="abc";
$_POST["second"]="xyz";
$thing = new Thing;
SmartTest::instance()->test($thing->import(array("first"=>"a","second"=>2))->getFirst(),"a");
SmartTest::instance()->test($thing->importFromPost("nonexistant")->getFirst(),"a");
SmartTest::instance()->test($thing->importFromPost(array("first"))->getFirst(),"abc");
SmartTest::instance()->test($thing->importFromPost(array("first"))->getSecond(),2);
SmartTest::instance()->test($thing->importFromPost()->getSecond(),"xyz");


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
SmartTest::instance()->test($observer->signal,"deco_set");
$observer->signal="";
$employee->getName();
SmartTest::instance()->test($observer->signal,"deco_get");
$observer->signal="";
$employee->getRelatedCustomer();
SmartTest::instance()->test($observer->signal,"deco_get");
$observer->signal="";
$employee->is("nerd");
SmartTest::instance()->test($observer->signal,"deco_get");
$observer->signal="";
$employee->clearRelated("nerd");
SmartTest::instance()->test($observer->signal,"deco_clearrelated");
$observer->signal="";
$employee2 = new Employee;
$employee2->setName("Minni");
$employee->add($employee2);
SmartTest::instance()->test($observer->signal,"deco_add");
$observer->signal="";
$employee->remove($employee2);
SmartTest::instance()->test($observer->signal,"deco_remove");
$observer->signal="";
$employee->attach($employee2);
SmartTest::instance()->test($observer->signal,"deco_attach");
$observer->signal="";
$employee->numofEmployee();
SmartTest::instance()->test($observer->signal,"deco_numof");
$observer->signal="";
$employee->belongsTo($employee2);
SmartTest::instance()->test($observer->signal,"deco_belongsto");
$observer->signal="";
$employee->exclusiveAdd($employee2);
SmartTest::instance()->test($observer->signal,"deco_exclusiveadd");
$observer->signal="";
$employee->parent();
SmartTest::instance()->test($observer->signal,"deco_parent");
$observer->signal="";
$employee->children($employee2);
SmartTest::instance()->test($observer->signal,"deco_children");
$observer->signal="";
$employee->siblings($employee2);
SmartTest::instance()->test($observer->signal,"deco_siblings");
$observer->signal="";
$employee->copy();
SmartTest::instance()->test($observer->signal,"deco_copy");


SmartTest::instance()->testPack = "Sieves";
R::gen("Employee");
$e = new Employee;
$e->setName("Max");
SmartTest::instance()->test(RedBean_Sieve::make(array("name"=>"RedBean_Validator_AlphaNumeric"))->valid($e),true);
$e->setName("Ma.x");
SmartTest::instance()->test(RedBean_Sieve::make(array("name"=>"RedBean_Validator_AlphaNumeric"))->valid($e),false);
$e->setName("Max")->setFunct("sales");
SmartTest::instance()->test(count(RedBean_Sieve::make(array("name"=>"RedBean_Validator_AlphaNumeric","funct"=>"RedBean_Validator_AlphaNumeric"))->validAndReport($e,"RedBean_Validator_AlphaNumeric")),2);
$e->setName("x")->setFunct("");
SmartTest::instance()->test(count(RedBean_Sieve::make(array("name"=>"RedBean_Validator_AlphaNumeric","funct"=>"RedBean_Validator_AlphaNumeric"))->validAndReport($e,"RedBean_Validator_AlphaNumeric")),2);
SmartTest::instance()->test(count(RedBean_Sieve::make(array("a"=>"RedBean_Validator_AlphaNumeric","b"=>"RedBean_Validator_AlphaNumeric"))->validAndReport($e,"RedBean_Validator_AlphaNumeric")),2);

SmartTest::instance()->testPack = "Validators";

//Test alphanumeric validation
$validator = new RedBean_Validator_AlphaNumeric();
asrt($validator->check("Max"), true);
asrt($validator->check("M...a x"), false);

//Test numeric validation
$validator = new RedBean_Validator_Numeric();
asrt($validator->check("12"), true);
asrt($validator->check("+12"), true);
asrt($validator->check("-12"), true);
asrt($validator->check("-3.92"), true);
asrt($validator->check("twelve"), false);

//Test email validation
$validator = new RedBean_Validator_Email();
asrt($validator->check("joe@work.com"), true);
asrt($validator->check("joe.work@com"), false);

//Test URI validation
$validator = new RedBean_Validator_URI();
asrt($validator->check("www.adomain.com"), true);
asrt($validator->check(".invaliddomain.com"), false);




//Test description: Test redbean table-space
SmartTest::instance()->testPack = "Configuration tester";

//insert garbage tables
$db->exec(" CREATE TABLE `nonsense` (
			`a` VARCHAR( 11 ) NOT NULL ,
			`b` VARCHAR( 11 ) NOT NULL ,
			`j` VARCHAR( 11 ) NOT NULL
			) ENGINE = MYISAM ");

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
SmartTest::instance()->testPack = "Optimizer and Garbage collector";

$db->exec("
CREATE TABLE  `slimtable` (
`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
`col1` VARCHAR( 255 ) NOT NULL ,
`col2` TEXT NOT NULL ,
`col3` INT( 11 ) UNSIGNED NOT NULL ,
PRIMARY KEY (  `id` )
) ENGINE = MYISAM");

$db->exec("INSERT INTO  `redbeantables` (
`id` ,
`tablename`
)
VALUES (
NULL ,  'slimtable'
);
");

$db->exec("INSERT INTO  `slimtable` (
`id` ,
`col1` ,
`col2` ,
`col3`
)
VALUES (
NULL ,  '1',  'mustbevarchar',  '1000'
);
");


$db->exec("
CREATE TABLE  `indexer` (
`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
`highcard` VARCHAR( 255 ) NOT NULL ,
`lowcard` TEXT NOT NULL ,
`lowcard2` INT( 11 ) UNSIGNED NOT NULL ,
`highcard2` LONGTEXT NOT NULL ,
PRIMARY KEY (  `id` )
) ENGINE = MYISAM");

$db->exec("INSERT INTO  `redbeantables` (
`id` ,
`tablename`
)
VALUES (
NULL ,  'indexer'
);
");



$db->exec("INSERT INTO  `redbeantables` (
`id` ,
`tablename`
)
VALUES (
NULL ,  'empcol'
);
");


$db->exec("
CREATE TABLE  `empcol` (
`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
`aaa` INT( 11) UNSIGNED,
`bbb` INT(11) UNSIGNED,
`ccc` INT( 11 ) UNSIGNED,
PRIMARY KEY (  `id` )
) ENGINE = MYISAM");

$db->exec("INSERT INTO  `empcol` (`id` ,`aaa` )VALUES (NULL ,  1 )");


for($i=0; $i<20; $i++){
$db->exec("INSERT INTO  `indexer` (
`id` ,
`highcard` ,
`lowcard` ,
`lowcard2`,
`highcard2`
)
VALUES (
NULL ,  rand(),  'a',  rand(), CONCAT( rand()*100, '".str_repeat('x',1000)."' )
);
");
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
SmartTest::test(empcol::where(' @ifexists:aaa=1 or @ifexists:bbb=1')->count(),1);

$row = $db->getRow("select * from slimtable limit 1");
SmartTest::test($row["col1"],1);
SmartTest::test($row["col2"],"mustbevarchar");
SmartTest::test($row["col3"],1000);

SmartTest::test(count($db->get("describe slimtable")),4); 
RedBean_OODB::dropColumn("slimtable","col3");
SmartTest::test(count($db->get("describe slimtable")),3); 


$db->exec("
CREATE TABLE  `garbagetable` (
`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
`highcard` VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY (  `id` )
) ENGINE = MYISAM");

$db->exec("INSERT INTO  `redbeantables` (
`id` ,
`tablename`
)
VALUES (
NULL ,  'garbagetable'
);
");


RedBean_OODB::KeepInShape( true );
$tables = RedBean_OODB::showTables(); 
SmartTest::test(in_array("garbagetable",$tables),false);

//Tests for each individual engine
function testsperengine() {

	global $tests;
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack ="perform generic bean manipulation";
	$ok=1;
	
	$bean = RedBean_OODB::dispense("note");
	$bean->message = "hai";
	$bean->color=3;
	$bean->date = time();
	$bean->special='n';
	$bean->state = 90;
	RedBean_OODB::set($bean); 
				
	$bean2 = RedBean_OODB::getById("note",1);
	if ($bean2->state != 90 || $bean2->special !='n' || $bean2->message !='hai') {
		$ok=0;
		SmartTest::failedTest();
	}
	SmartTest::instance()->progress(); ;
	
	$bean->message = "lorem ipsum";
	RedBean_OODB::set($bean);
	
	
	$bean->message = 1;
	$bean->color = "green";
	$bean->date = str_repeat("BLABLA", 100);
	RedBean_OODB::set($bean);
	$note =$bean;
	
	
	SmartTest::instance()->progress(); ;
	$person = RedBean_OODB::dispense("person");
	$person->age = 50;
	$person->name = "Bob";
	$person->gender = "m";
	RedBean_OODB::set( $person );
	RedBean_OODB::associate( $person, $note );
	
	$memo = RedBean_OODB::getById( "note", 1 );
	$authors = RedBean_OODB::getAssoc( $memo, "person" );
	if (count($authors)!==1) SmartTest::failedTest(); 
	
	RedBean_OODB::trash( $authors[1] );
	
	$authors = RedBean_OODB::getAssoc( $memo, "person" );
	if (count($authors)>0) $ok=0;

	
	if (!$ok) {
		SmartTest::failedTest();
	}
	
	//unit tests
	//drop the note table
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "dispense an RedBean_OODB Bean";
	$oBean = RedBean_OODB::dispense();
	if (!($oBean instanceof OODBBean)){
		SmartTest::failedTest();
	}
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "put a bean in the database";
	$person = RedBean_OODB::dispense("person");
	$person->name = "John";
	$person->age= 35;
	$person->gender = "m";
	$person->hasJob = true;
	$id = RedBean_OODB::set( $person ); $johnid=$id;
	$person2 = RedBean_OODB::getById( "person", $id );
	
	if (($person2->age) != ($person->age)) {
		SmartTest::failedTest();
	}
	
	$person2->anotherprop = 2;
	RedBean_OODB::set( $person2 );
	
	
	$person = RedBean_OODB::dispense("person");
	$person->name = "Bob";
	$person->age= 50;
	$person->gender = "m";
	$person->hasJob = false;
	$bobid = RedBean_OODB::set( $person );
	
	SmartTest::instance()->progress();
	
	SmartTest::instance()->testPack = "find records on basis of similarity";
	
	
	
	$ids = RedBean_OODB::getBySQL("`gender`={gender} order by `name` asc",array("gender"=>"m"),"person");
	if (count($ids)!=2) {
		SmartTest::failedTest();
	}
	SmartTest::instance()->progress(); 


	$ids = RedBean_OODB::getBySQL("`gender`={gender} OR `color`={clr} ",array("gender"=>"m","clr"=>"red"),"person");
	if (count($ids)!=0) {
		SmartTest::failedTest();
	}
	SmartTest::instance()->progress(); 
	
	$ids = RedBean_OODB::getBySQL("`gender`={gender} AND `color`={clr} ",array("gender"=>"m","clr"=>"red"),"person");
	if (count($ids)!=0) {
		SmartTest::failedTest();
	} 
	SmartTest::instance()->progress();
	
	R::gen("Person");
	$dummy = new Person;
	$dummy->age = 40;
	SmartTest::instance()->test(count(Person::find( $dummy, array("age"=>">"))),1);
	$dummy->age = 20;
	SmartTest::instance()->test(count(Person::find( $dummy, array("age"=>">"))),2);
	$dummy->age = 100;
	SmartTest::instance()->test(count(Person::find( $dummy, array("age"=>">"))),0);
	$dummy->age = 100;
	SmartTest::instance()->test(count(Person::find( $dummy, array("age"=>"<="))),2);
	$dummy->name="ob";
	SmartTest::instance()->test(count(Person::find( $dummy, array("name"=>"LIKE"))),1);
	$dummy->name="o";
	SmartTest::instance()->test(count(Person::find( $dummy, array("name"=>"LIKE"))),2);
	$dummy->gender="m";
	SmartTest::instance()->test(count(Person::find( $dummy, array("gender"=>"="))),2);
	$dummy->gender="f";
	SmartTest::instance()->test(count(Person::find( $dummy, array("gender"=>"="))),0);
	SmartTest::instance()->test(count(Person::listAll()),2);
	SmartTest::instance()->test(count(Person::listAll(0,1)),1);
	SmartTest::instance()->test(count(Person::listAll(1)),1);
	
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
	
	//test array access
	$bean = $can[0];
	if ($bean->name=="Bob") {
		SmartTest::instance()->progress();
	} 
	else {
		SmartTest::failedTest();
	}
	
	if ($can->count()!=2) {
		SmartTest::failedTest();
	}
	
	SmartTest::instance()->progress();
	
	$can->rewind();
	SmartTest::instance()->test($can->key(), 0);
	SmartTest::instance()->test($can->valid(), true);
	SmartTest::instance()->test($can->current()->getName(), "Bob");
	$can->next();
	SmartTest::instance()->test($can->key(), 1);
	SmartTest::instance()->test($can->valid(), false);
	SmartTest::instance()->test($can->current()->getName(), "John");
	$can->seek(0);
	SmartTest::instance()->test($can->key(), 0);
	
	$beans = $can->getBeans();
	
	if (count($beans)!=2) {
		SmartTest::failedTest();
	} 
	
	SmartTest::instance()->progress();
	
	//test slicing
	$can->slice( 0, 1 );
	$can->rewind();
	SmartTest::instance()->test($can->current()->getName(), "Bob");
	SmartTest::instance()->test($can->count(), 1);
	
	
	
	$b1 = array_shift($beans);
	
	if ($b1->name!="Bob") {
		SmartTest::failedTest();
	}
	SmartTest::instance()->progress();
	
	//basic functionality where()
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
	
	
	$searchBean = RedBean_OODB::dispense("person");
	$searchBean->gender = "m";
	
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "associate beans with eachother?";
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
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "create parent child relationships?";
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
	RedBean_OODB::set( $saskia );
	RedBean_OODB::set( $rob );
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
	
	if (!$names) SmartTest::failedTest();
	
	$daddies = RedBean_OODB::getParent( $saskia );
	$daddy = array_pop( $daddies );
	if ($daddy->name === "Pete") $ok = 1; else $ok = 0;
	if (!$ok) SmartTest::failedTest();
	
		
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "remove a child from a parent-child tree?";
	
	RedBean_OODB::removeChild( $daddy, $saskia );
	$children = RedBean_OODB::getChildren( $pete );
	
	
	$ok=0;
	if (count($children)===1) {
		$only = array_pop($children);
		if ($only->name==="Rob") $ok=1;
	}
	
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->testPack = "save on the fly while associating?";
	
	$food = RedBean_OODB::dispense("dish");
	$food->name="pizza";
	RedBean_OODB::associate( $food, $pete );
	
	$petesfood = RedBean_OODB::getAssoc( $pete, "food" );
	if (is_array($petesfood) && count($petesfood)===1) $ok=1;
	if (!$ok) SmartTest::failedTest();
	RedBean_OODB::unassociate( $food, $pete );
	if (is_array($petesfood) && count($petesfood)===0) $ok=1;
	if (!$ok) SmartTest::failedTest();
	
	
	//some extra tests... quick without further notice.	
	$food = RedBean_OODB::dispense("dish");
	$food->name="spaghetti";
	RedBean_OODB::trash( $food );
	
	
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
	RedBean_OODB::set( $s );
	
	SmartTest::instance()->testPack = "can we use aggr functions using Redbean?";
	if (RedBean_OODB::numberof("stattest")!=3) SmartTest::failedTest(); else SmartTest::instance()->progress(); 
	if (RedBean_OODB::maxof("stattest","amount")!=3) SmartTest::failedTest(); else SmartTest::instance()->progress(); 
	if (RedBean_OODB::minof("stattest","amount")!=1) SmartTest::failedTest(); else SmartTest::instance()->progress(); 
	if (RedBean_OODB::avgof("stattest","amount")!=2) SmartTest::failedTest(); else SmartTest::instance()->progress(); 
	if (RedBean_OODB::sumof("stattest","amount")!=6) SmartTest::failedTest(); else SmartTest::instance()->progress(); 
	if (count(RedBean_OODB::distinct("stattest","amount"))!=3) SmartTest::failedTest(); else SmartTest::instance()->progress(); 
	
		
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
	
	echo '---';
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
	
	SmartTest::test(count($donald->children()),3);
	Person::delete($kwek);
	SmartTest::test(count($donald->children()),2);
	Person::delete($kwik);
	SmartTest::test(count($donald->children()),1);
	Person::delete($kwak);
	SmartTest::test(count($donald->children()),0);
	
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
	SmartTest::instance()->test(count($discs),1);
	$track->belongsTo( $cd2 );
	$discs = $track->getRelatedDisc();
	SmartTest::instance()->test(count($discs),1);
	$track2 = new Track;
	$track2->title = "song 2";
	$cd1->exclusiveAdd( $track2 );
	SmartTest::instance()->test(count($track->getRelatedDisc()),1);
	$cd2->exclusiveAdd( $track2 );
	SmartTest::instance()->test(count($track->getRelatedDisc()),1);

	RedBean_OODB::gen('SomeBean');
	$b = new SomeBean;
	$b->aproperty = 1;
	$b->save();
	$b = new SomeBean;
	$b->anotherprop = 1;
	$b->save();
	SmartTest::test(RedBean_OODB::numberof("SomeBean"),2);
	RedBean_OODB::trashAll("SomeBean");
	SmartTest::test(RedBean_OODB::numberof("SomeBean"),0);
	
	RedBean_OODB::gen("Book");
	$book = new Book;
	$book->setTitle('about a red bean');
	RedBean_OODB::gen("Page");
	$page1 = new Page;
	$page2 = new Page;
	SmartTest::test(count($book->getRelatedPage()),0);
	$book->add($page1);
	SmartTest::test(count($book->getRelatedPage()),1);
	$book->add($page2);
	SmartTest::test(count($book->getRelatedPage()),2);
	$book->remove($page1);
	SmartTest::test(count($book->getRelatedPage()),1);
	$book->remove($page2);
	SmartTest::test(count($book->getRelatedPage()),0);
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

testsperengine();
RedBean_OODB::setEngine("innodb");
testsperengine();



printtext("ALL TESTS PASSED. REDBEAN SHOULD WORK FINE.");
?>
