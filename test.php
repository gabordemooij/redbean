<html>
<body style="background-color:black;font-family:courier;font-size:9px;color:white;">
<br>Welcome to RedBean UNIT TESTS, this unit test suite is ONLY SUCCESFUL IF ALL UNIT TESTS pass. If you see a message like 'all X tests passed, redbean should work fine' AND THERE ARE NO-WARINGS OR ERRORS... THEN ALL TESTS HAVE BEEN PASSED SUCCESSFULLY.
<?php 

//Some unittests for RedBean
//Written by G.J.G.T. de Mooij

//I used this file to develop and tweak Redbean
//Redbean has been developed TEST DRIVEN (TDD)


class SmartTest {
	private static $me = false;
	private $canwe = '';
	public static function instance() {
		if (!self::$me) self::$me = new SmartTest();
		return self::$me;
	}
	public function __set( $canwe, $v ) {
		if ($canwe=="canwe") {
			$this->canwe = $v;
			
			echo "<br>processing testpack: ".RedBean_OODB::getEngine()."-".$v." ...now testing: ";
		    ob_flush();
		    flush(); 
			
		}
	}
	
	public function __get( $canwe ) {
		return $this->canwe;
	}
	
	public function progress() {
		 global $tests;
		 $tests++;
		 echo "[".$tests."]";
		 ob_flush();
		 flush();
	}
	
}

require("oodb.php");

SmartTest::instance()->canwe = "Basic test suite";
$tests = 0; SmartTest::instance()->progress(); ;

if (class_exists("RedBean_OODB")) {
	SmartTest::instance()->progress(); ;
}
else {
	die("<b style='color:red'>Error Failed test $tests "); 	
}

if (class_exists("RedBean_Decorator")) {
	SmartTest::instance()->progress(); ;
}
else {
	die("<b style='color:red'>Error Failed test $tests "); 	
}


if (class_exists("RedBean_DBAdapter")) {
	SmartTest::instance()->progress(); ;
}
else {
	die("<b style='color:red'>Error Failed test $tests "); 	
}

//test whether short notations are active

if (!$shortnotation_for_redbean || class_exists($shortnotation_for_redbean)) {
	SmartTest::instance()->progress(); ;
}
else {
	die("<b style='color:red'>Error Failed test $tests "); 	
}


if (!$shortnotation_for_redbeandecorator || class_exists($shortnotation_for_redbeandecorator)) {
	SmartTest::instance()->progress(); ;
}
else {
	die("<b style='color:red'>Error Failed test $tests "); 	
}



try{
$db = RedBean_OODB::$db;

if ($db instanceof RedBean_DBAdapter) SmartTest::instance()->progress(); else die("<b style='color:red'>Error Failed test $tests ");


if ((RedBean_OODB::getVersionInfo())) {
	SmartTest::instance()->progress(); ;
}
else {
	die("<b style='color:red'>Error Failed test $tests "); 
}

if ((RedBean_OODB::getVersionNumber())) {
	SmartTest::instance()->progress(); ;
}else {
	die("<b style='color:red'>Error Failed test $tests "); 
}

SmartTest::instance()->progress(); ;
}
catch(Exception $e) {
	die("<b style='color:red'>Exception during init... "); 
}

//drop the note table


//insert garbage tables
$db->exec(" CREATE TABLE `nonsense` (
			`a` VARCHAR( 11 ) NOT NULL ,
			`b` VARCHAR( 11 ) NOT NULL ,
			`j` VARCHAR( 11 ) NOT NULL
			) ENGINE = MYISAM ");

Redbean_OODB::clean();

//SmartTest::instance()->canwe



/* separate test for transactions...
Redbean_OODB::gen("trash");
$trash = new Trash();
$trash->save();
throw new Exception("test transactions here..");
exit
*/

SmartTest::instance()->progress(); ; SmartTest::instance()->canwe ="call garbage collector without any tables?";
try{
	RedBean_OODB::gc();
}
catch(Exception $e) {
	die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
}

Redbean_OODB::gen("trash");
$trash = new Trash();
$trash->save();

Redbean_OODB::clean();


Redbean_OODB::setLocking( false ); //turn locking off

$alltables = $db->getCol("show tables");
SmartTest::instance()->progress(); ;
if (!in_array("dtyp",$alltables)) die("<b style='color:red'>Error Failed test $tests ");
SmartTest::instance()->progress(); ;
if (!in_array("redbeantables",$alltables)) die("<b style='color:red'>Error Failed test $tests ");
SmartTest::instance()->progress(); ;
if (!in_array("locking",$alltables)) die("<b style='color:red'>Error Failed test $tests ");
SmartTest::instance()->progress(); ;
if (!in_array("searchindex",$alltables)) die("<b style='color:red'>Error Failed test $tests ");
SmartTest::instance()->progress(); ;
if (!in_array("nonsense",$alltables)) die("<b style='color:red'>Error Failed test $tests ");
SmartTest::instance()->progress(); ;
if (in_array("trash",$alltables)) die("<b style='color:red'>Error Failed test $tests ");


$db->exec("drop table `nonsense`");


function testsperengine() {

	global $tests;
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe ="perform generic bean manipulation";
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
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	SmartTest::instance()->progress(); ;
	
	$bean->message = "lorem ipsum";
	RedBean_OODB::set($bean);
	
	
	$bean->message = 1;
	$bean->color = "green";
	$bean->date = str_repeat("BLABLA", 100);
	RedBean_OODB::set($bean);
	
	unset($bean);
	$bean = RedBean_OODB::dispense("note");
	$bean->color="green";
	//print_r($bean);
	$bean3 = RedBean_OODB::find( $bean, array("color"=>"=") );
	if (count($bean3)!==1) die("<b style='color:red'>Error CANNOT1:".SmartTest::instance()->canwe); 
	
	unset($bean);
	$bean = RedBean_OODB::dispense("note");
	$bean->state = 80;
	$bean3 = RedBean_OODB::find( $bean, array( "state"=>">" ) );
	if (count($bean3)!==1) die("<b style='color:red'>Error CANNOT2:".SmartTest::instance()->canwe); 
	
	SmartTest::instance()->progress(); ;
	try{
	$bla = RedBean_OODB::find( $bean, array( "undefined"=>">" ) );
	}catch(Exception $e){
	//dont fail if prop does not exist
	die("<b style='color:red'>Error CANNOT3:".SmartTest::instance()->canwe);
	}
	
	SmartTest::instance()->progress(); ;
	$note = $bean3[1];
	$person = RedBean_OODB::dispense("person");
	$person->age = 50;
	$person->name = "Bob";
	$person->gender = "m";
	RedBean_OODB::set( $person );
	RedBean_OODB::associate( $person, $note );
	
	$memo = RedBean_OODB::getById( "note", 1 );
	$authors = RedBean_OODB::getAssoc( $memo, "person" );
	if (count($authors)!==1) die("<b style='color:red'>Error CANNOT4:".SmartTest::instance()->canwe); 
	
	RedBean_OODB::trash( $authors[1] );
	//$ok=false;
	
	//try{
	$authors = RedBean_OODB::getAssoc( $memo, "person" );
	if (count($authors)>0) $ok=0;
	//}
	//catch(Exception $e){
	//	$ok=1;
	//} 
	
	if (!$ok) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	//unit tests
	//drop the note table
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "dispense an RedBean_OODB Bean";
	$oBean = RedBean_OODB::dispense();
	if (!($oBean instanceof OODBBean)){
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "put a bean in the database";
	$person = RedBean_OODB::dispense("person");
	$person->name = "John";
	$person->age= 35;
	$person->gender = "m";
	$person->hasJob = true;
	$id = RedBean_OODB::set( $person ); $johnid=$id;
	//echo "--->$id";
	$person2 = RedBean_OODB::getById( "person", $id );
	
	if (($person2->age) != ($person->age)) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	$person2->anotherprop = 2;
	RedBean_OODB::set( $person2 );
	
	
	$person = RedBean_OODB::dispense("person");
	$person->name = "Bob";
	$person->age= 50;
	$person->gender = "m";
	$person->hasJob = false;
	$bobid = RedBean_OODB::set( $person );
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "find records on basis of similarity";
	
	//RedBean_OODB::closeAllBeansOfType("person");
	
	$searchBean = RedBean_OODB::dispense("person");
	$searchBean->gender = "m";
	$persons = RedBean_OODB::find( $searchBean, array("gender"=>"=") );
	
	
	if (count($persons)!=2) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	//RedBean_OODB::closeAllBeansOfType("person");
	
	$searchBean = RedBean_OODB::dispense("person");
	$searchBean->name = "John";
	$persons = RedBean_OODB::find( $searchBean, array("name"=>"LIKE") );
	
	if (count($persons)!=1) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	$searchBean2 = RedBean_OODB::dispense("person");
	$searchBean2->name = "John";
	$searchBean2->gender = "m";
	$persons2 = RedBean_OODB::find( $searchBean2, array("gender"=>"=") );
	
	if (count($persons2)!=2) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	
	//test limits (start end etc..)
	$searchBean2 = RedBean_OODB::dispense("person");
	$searchBean2->name = "John";
	$searchBean2->gender = "m";
	$persons2 = RedBean_OODB::find( $searchBean2, array("gender"=>"="),1 );
	
	if (count($persons2)!=1) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	SmartTest::instance()->progress(); 
	
	$persons2 = RedBean_OODB::find( $searchBean2, array("gender"=>"="),0,1 );
	
	if (count($persons2)!=1) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	SmartTest::instance()->progress(); 
	
	$persons2 = RedBean_OODB::find( $searchBean2, array("gender"=>"="),0,1,"age ASC" );
	
	if (count($persons2)==1) {
		$who = array_pop($persons2);
		if ($who->name!="John") {
			die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
		}
	}
	else {	die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);	}
	
	SmartTest::instance()->progress(); 
	
	$persons2 = RedBean_OODB::find( $searchBean2, array("gender"=>"="),0,1,"age DESC" );
		
	if (count($persons2)==1) {
		$who = array_pop($persons2);
		if ($who->name!="Bob") {
			die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
		}
	}
	else {	die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);	}
	
	SmartTest::instance()->progress(); 
	
	//test extra sql
	$persons2 = RedBean_OODB::find( $searchBean2, array("gender"=>"="),0,1,"age DESC","order by age ASC limit 1" );
		if (count($persons2)==1) {
			$who = array_pop($persons2);
			if ($who->name!="John") {
				die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
			}
		}
		else {	die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);	}
	
	SmartTest::instance()->progress(); 
	
	$searchBean = RedBean_OODB::dispense("person");
	$searchBean->age = "20";
	$searchBean->gender = "m";
	$persons = RedBean_OODB::find( $searchBean, array("age"=>">") );
		
	if (count($persons)!=2) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	
	SmartTest::instance()->progress(); 
	
	
	
	$searchBean = RedBean_OODB::dispense("person");
	$searchBean->age = "20";
	$searchBean->gender = "v";
	$persons = RedBean_OODB::find( $searchBean, array("age"=>">","gender"=>"=") );
	
	
	if (count($persons)!=0) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	SmartTest::instance()->progress(); 
	
	
	$searchBean = RedBean_OODB::dispense("person");
	$searchBean->age = "50";
	$searchBean->name = "Bob";
	$searchBean->gender = "m";
	$persons = RedBean_OODB::find( $searchBean,array("age"=>"=","name"=>"LIKE","gender"=>"=") );
	
	
	if (count($persons)!=1) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	
	$whisky = RedBean_OODB::dispense("whisky");
	$whisky->name = "Glen Old";
	$whisky->age= 50;
	RedBean_OODB::set( $whisky );
	
	
	$searchBean = RedBean_OODB::dispense("whisky");
	$searchBean->age = "12";
	$drinks = RedBean_OODB::find( $searchBean, array("age"=>">") );
	
	
	if (count($drinks)!=1) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "associate beans with eachother?";
	$app = RedBean_OODB::dispense("appointment");
	$app->kind = "dentist";
	RedBean_OODB::set($app);
	RedBean_OODB::associate( $person2, $app );
	$appforbob = array_shift(RedBean_OODB::getAssoc( $person2, "appointment" ));
	
	if (!$appforbob || $appforbob->kind!="dentist") {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	} 
	
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "delete a bean?";
	$person = RedBean_OODB::getById( "person", $bobid );
	
	RedBean_OODB::trash( $person );
	try{
	$person = RedBean_OODB::getById( "person", $bobid);$ok=0;
	}catch(ExceptionFailedAccessBean $e){
		$ok=true;
	}
	
	if (!$ok) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "unassociate two beans?";
	$john = RedBean_OODB::getById( "person", $johnid); //hmmmmmm gaat mis bij innodb
	
	/*
	SELECT * FROM `person` WHERE id = 2
	Fatal error: Uncaught exception 'ExceptionFailedAccessBean' with message 'bean not found' in /Applications/xampp/xamppfiles/htdocs/cms/oodb.php:1161 Stack trace: #0 /Applications/xampp/xamppfiles/htdocs/cms/test.php(275): RedBean_OODB::getById('person', 2) #1 {main} thrown in /Applications/xampp/xamppfiles/htdocs/cms/oodb.php on line 1161
	
	*/
	
	
	$app = RedBean_OODB::getById( "appointment", 1);
	RedBean_OODB::unassociate($john, $app);
	
	$john2 = RedBean_OODB::getById( "person", $johnid);
	$appsforjohn = RedBean_OODB::getAssoc($john2,"appointment");
	if (count($appsforjohn)>0) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "unassociate by deleting a bean?";
	
	$anotherdrink = RedBean_OODB::dispense("whisky");
	$anotherdrink->name = "bowmore";
	$anotherdrink->age = 18;
	$anotherdrink->singlemalt = 'y';
	RedBean_OODB::set( $anotherdrink );
	RedBean_OODB::associate( $anotherdrink, $john );
	
	$hisdrinks = RedBean_OODB::getAssoc( $john, "whisky" );
	if (count($hisdrinks)!==1) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	
	RedBean_OODB::trash( $anotherdrink );
	$hisdrinks = RedBean_OODB::getAssoc( $john, "whisky" );
	if (count($hisdrinks)!==0) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "create parent child relationships?";
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
	
	if (!$names) die("<b style='color:red'>Error CANNOT1:".SmartTest::instance()->canwe);
	
	$daddies = RedBean_OODB::getParent( $saskia );
	$daddy = array_pop( $daddies );
	if ($daddy->name === "Pete") $ok = 1; else $ok = 0;
	if (!$ok) die("<b style='color:red'>Error CANNOT2:".SmartTest::instance()->canwe);
	
		
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "remove a child from a parent-child tree?";
	
	RedBean_OODB::removeChild( $daddy, $saskia );
	$children = RedBean_OODB::getChildren( $pete );
	
	
	$ok=0;
	if (count($children)===1) {
		$only = array_pop($children);
		if ($only->name==="Rob") $ok=1;
	}
	
	if (!$ok) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	
	//test exceptions
	
	$ok=0;
	try{
		RedBean_OODB::addChild( $daddy, $whisky );
	}
	catch( ExceptionInvalidParentChildCombination $e ) {
		$ok=1;
	}
	
	if (!$ok) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	
	$ok=0;
	
	try{
		RedBean_OODB::removeChild( $daddy, $whisky );
	}
	catch( ExceptionInvalidParentChildCombination $e ) {
		$ok=1;
	}
	
	if (!$ok) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "save on the fly while associating?";
	
	$food = RedBean_OODB::dispense("dish");
	$food->name="pizza";
	RedBean_OODB::associate( $food, $pete );
	
	$petesfood = RedBean_OODB::getAssoc( $pete, "food" );
	if (is_array($petesfood) && count($petesfood)===1) $ok=1;
	if (!$ok) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	
	
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
	
	SmartTest::instance()->canwe = "can we use aggr functions using Redbean?";
	if (RedBean_OODB::numberof("stattest")!=3) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); else SmartTest::instance()->progress(); 
	if (RedBean_OODB::maxof("stattest","amount")!=3) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); else SmartTest::instance()->progress(); 
	if (RedBean_OODB::minof("stattest","amount")!=1) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); else SmartTest::instance()->progress(); 
	if (RedBean_OODB::avgof("stattest","amount")!=2) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); else SmartTest::instance()->progress(); 
	if (RedBean_OODB::sumof("stattest","amount")!=6) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); else SmartTest::instance()->progress(); 
	if (count(RedBean_OODB::distinct("stattest","amount"))!=3) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); else SmartTest::instance()->progress(); 
	
	//test list function
	SmartTest::instance()->canwe = "can we use list functions using Redbean?";
	if (count(RedBean_OODB::listAll("stattest"))!=3) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); else SmartTest::instance()->progress();
	if (count(RedBean_OODB::listAll("stattest",1))!=2) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); else SmartTest::instance()->progress();
	if (count(RedBean_OODB::listAll("stattest",1,1))!=1) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); else SmartTest::instance()->progress();
	if (count(RedBean_OODB::listAll("stattest",1,2))!=2) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); else SmartTest::instance()->progress();
	if (count(RedBean_OODB::listAll("stattest",1,1,""," limit 100 "))!=3) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); else SmartTest::instance()->progress();
	
	//now test with decorator =======================================
	
	RedBean_OODB::setLocking( true );
	$i=3;
	SmartTest::instance()->canwe="generate only valid classes?";
	try{ $i += RedBean_OODB::gen(""); SmartTest::instance()->progress(); ; }catch(Exception $e){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); } //nothing
	try{ $i += RedBean_OODB::gen("."); SmartTest::instance()->progress(); ; }catch(Exception $e){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); } //illegal chars
	try{ $i += RedBean_OODB::gen(","); SmartTest::instance()->progress(); ; }catch(Exception $e){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); } //illegal chars
	try{ $i += RedBean_OODB::gen("null"); SmartTest::instance()->progress(); ; }catch(Exception $e){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); } //keywords
	try{ $i += RedBean_OODB::gen("Exception"); SmartTest::instance()->progress(); ; }catch(Exception $e){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); } //reserved
	
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "generate classes using Redbean?";
	
	if (!class_exists("Bug")) {
		$i += RedBean_OODB::gen("Bug");
		if ($i!==4) die("<b style='color:red'>Error CANNOT $i:".SmartTest::instance()->canwe);
	}
	else {
		if ($i!==3) die("<b style='color:red'>Error CANNOT $i:".SmartTest::instance()->canwe);
	}
	
	if (!class_exists("Bug")) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "use getters and setters";
	$bug = new Bug;
	$bug->setSomething( sha1("abc") );
	if ($bug->getSomething()!=sha1("abc") ) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	
	//can we use non existing props? --triggers fatal..
	$bug->getHappy();
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "use oget and oset?";
	RedBean_OODB::gen("Project");
	
	$proj = new Project;
	$proj->setName("zomaar");
	$bug->osetProject( $proj );
	$bug->save();
	
	$oldbug = new Bug(1);
	$oldproj = $oldbug->ogetProject();
	if ($oldproj->getName()!="zomaar"){
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);	
	}
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "Use boolean values and retrieve them with is()?";
	if ($bug->isHappy()) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	SmartTest::instance()->progress(); ;
	
	
	$bug->setHappy(true);
	$bug->save();
	$bug = new Bug(1);
	if (!$bug->isHappy()) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	SmartTest::instance()->progress(); ;
	
	$bug->setHappy(false);
	if ($bug->isHappy()) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "break oget/oset assoc?";
	$bug->osetProject( null );
	$bug->save();
	$bug = null;
	$bug = new Bug(1);
	
	$proj = $bug->ogetProject();
	if ($proj->getID() > 0) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe ="Use the decorator to associate items?";
	
	$bug = null;
	$bug1 = new Bug;
	$bug2 = new Bug;
	
	$bug1->setName("b1");
	$bug2->setName("b2");
	$p = new Project;
	$p->setProjectNum( 42 );
	$p->add( $bug1 );
	$p->add( $bug2 );
	
	$p = null;
	$b = new Project;
	$b->setProjectNum( 42 );
	//also fck up case...
	$arr = RedBean_Decorator::find( $b, array("PRoJECTnuM"=>"=") );
	$proj = array_pop($arr);
	$bugs = $proj->getRelatedBug();
	if (count($bugs)!==2) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe ="use hierarchies?";
	$sub1 = new Project;
	$sub2 = new Project;
	$sub3 = new Project;
	$sub1->setName("a");
	$sub2->setName("b");
	$sub2->setDate( time() );
	$sub3->setName("c");
	$sub2->attach( $sub3 );
	$proj->attach($sub1)->attach($sub2);
	
	$arr = RedBean_Decorator::find( $b, array("PRoJECTnuM"=>"=") );
	$proj = array_pop($arr);
	$c = $proj->children();
	
	foreach($c as $c1) {
		if ($c1->getName()=="b") break;
	}
	
	$c2 = $c1->children();
	$sub3 = array_pop( $c2 );
	if ($sub3->getName()!="c") {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "make our own models";
	
	
	if (!class_exists("Customer")) {
	class Customer extends RedBean_Decorator {
		public function __construct( $id = 0) {
			parent::__construct( "Customer", $id );
		}
		
		//each customer may only have one project
		public function setProject( Project $project ) {
			$this->clearRelatedProject();
			$this->command( "add", array($project) );
		}
		public function add( $what ) {
			if ($what instanceof Project) return false;
			$this->command( "add", array($what) );
		}
	}
	}
	
	
	$p2 = new Project;
	$p2->setName("hihi");
	$cust = new Customer;
	
	$cust->setProject( $p2 );
	$cust->add( $p2 );
	$cust->setProject( $proj );
	$ps = $cust->getRelatedProject();
	
	if (count($ps)>1) {
		die("<b style='color:red'>Error CANNOT1:".SmartTest::instance()->canwe);
	}
	SmartTest::instance()->progress(); ;
	
	
	$p = array_pop( $ps );
	if ($p->getName()=="hihi") {
		die("<b style='color:red'>Error CANNOT2:".SmartTest::instance()->canwe);
	}
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "delete all assoc";
	$cust->clearAllRelations();
	$ps = $cust->getRelatedProject();
	if (count($ps)>0) {
		die("<b style='color:red'>Error CANNOT1:".SmartTest::instance()->canwe);
	}
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "not mess with redbean";
	$bla = new Customer();
	Redbean_Decorator::find( $bla, array() );
	$ok=0;
	try{
		Redbean_Decorator::find( $bla, array("bkaa"=>"q=") );
	}
	catch(ExceptionInvalidFindOperator $e){
	$ok=1;
	}
	if (!$ok){
	die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "manipulate hierarchies";
	$cust2 = new Customer;
	$cust->attach($cust2);
	$c = $cust->children();
	if (count($c)!==1) {
		die("<b style='color:red'>Error CANNOT1:".SmartTest::instance()->canwe);
	}
	SmartTest::instance()->progress(); ;
	
	
	$cust->remove( $cust2 );
	$c = $cust->children();
	if (count($c)!==0) {
		die("<b style='color:red'>Error CANNOT2:".SmartTest::instance()->canwe); exit;
	}
	SmartTest::instance()->progress(); ;
	
	
	$cust->attach($cust2);
	Customer::delete($cust2);
	$c = $cust->children();
	if (count($c)!==0) {
		die("<b style='color:red'>Error CANNOT3:".SmartTest::instance()->canwe); exit;
	}
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "remove associations";
	$cust3 = new Customer;
	$cust4 = new Customer;
	$cust3->add( $cust4 );
	$c = $cust3->getRelatedCustomer();
	if (count($c)!==1) {
		die("<b style='color:red'>Error CANNOT1:".SmartTest::instance()->canwe); exit;
	}
	SmartTest::instance()->progress(); ;
	
	
	$cust3->remove( $cust4 );
	$c = $cust3->getRelatedCustomer();
	if (count($c)!==0) {
		die("<b style='color:red'>Error CANNOT2:".SmartTest::instance()->canwe); exit;
	}
	SmartTest::instance()->progress(); ;
	
	
	$cust3 = new Customer;
	$cust4 = new Customer;
	$cust3->add( $cust4 );
	$cust3->add( $cust4 );//also test multiple assoc
	$c = $cust3->getRelatedCustomer();
	if (count($c)!==1) {
		die("<b style='color:red'>Error CANNOT3:".SmartTest::instance()->canwe); exit;
	}
	SmartTest::instance()->progress(); ;
	
	$cust4->remove( $cust3 );
	$c = $cust3->getRelatedCustomer();
	if (count($c)!==0) {
		die("<b style='color:red'>Error CANNOT4:".SmartTest::instance()->canwe); exit;
	}
	SmartTest::instance()->progress(); ;
	
	
	$cust3 = new Customer;
	$cust4 = new Customer;
	$cust3->add( $cust4 );
	$cust3->add( $cust4 );//also test multiple assoc
	$c = $cust3->getRelatedCustomer();
	if (count($c)!==1) {
		die("<b style='color:red'>Error CANNOT5:".SmartTest::instance()->canwe); exit;
	}
	SmartTest::instance()->progress(); ;
	
	Customer::delete($cust4);
	$c = $cust3->getRelatedCustomer();
	if (count($c)!==0) {
		die("<b style='color:red'>Error CANNOT6:".SmartTest::instance()->canwe); exit;
	}
	SmartTest::instance()->progress(); ;
	
	
	SmartTest::instance()->canwe = "import from post";
	$_POST["hallo"] = 123;
	$_POST["there"] = 456;
	$_POST["nope"] = 789;
	$cust = new Customer;
	$cust->importFromPost(array("hallo","there"));
	
	if ($cust->getHallo()==123 && $cust->getThere()==456 && !$cust->getNope()) {
		SmartTest::instance()->progress(); ;
	}
	else {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); exit;
	}
	
	foreach($cust->problems() as $p){
		if ($p) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); 
	} 
	
	SmartTest::instance()->progress(); ;
	
	$_POST["hallo"] = 123;
	$_POST["there"] = 456;
	$_POST["nope"] = 789;
	$cust = new Customer;
	$cust->importFromPost("hallo,there");
	if ($cust->getHallo()==123 && $cust->getThere()==456 && !$cust->getNope()) {
		SmartTest::instance()->progress(); ;
	}
	else {
		die("<b style='color:red'>Error CANNOT2:".SmartTest::instance()->canwe); exit;
	}
	
	foreach($cust->problems() as $p){
		if ($p) die("<b style='color:red'>Error CANNOT3:".SmartTest::instance()->canwe); 
	} 
	
	SmartTest::instance()->progress(); ;
	
	$_POST["hallo"] = 123;
	$_POST["there"] = 456;
	$_POST["nope"] = 789;
	$cust = new Customer;
	$cust->importFromPost();
	
	if ($cust->getHallo()==123 && $cust->getThere()==456 && $cust->getNope()) {
		SmartTest::instance()->progress(); ;
	}
	else {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); exit;
	}
	
	foreach($cust->problems() as $p){
		if ($p) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); 
	} 
	
	SmartTest::instance()->progress(); ;
	
	
	
	if (!class_exists("Trick")) {
	class Trick extends RedBean_Decorator {
		public function __construct( $id = 0) {
			parent::__construct( "Customer", $id );
		}
		public function setHallo(){
			return "hallodaar";
		}
	}
	}
	
	
	$trick = new Trick;
	$trick->importFromPost(array("hallo","there"));
	
	$message = array_shift( $trick->problems());
	if ($message==="hallodaar") {
		SmartTest::instance()->progress(); ;
	}
	else {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); exit;
	}
	
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "avoid race-conditions by locking?";
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
	catch(ExceptionFailedAccessBean $e) {
		$ok=1;
	}
	if (!$ok) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); 
	
	SmartTest::instance()->progress();
	$bordeaux = new Wine;
	$bordeaux->setRegion("Bordeaux");
	
	try{
	$bordeaux->add( $cheese );
	}
	catch(ExceptionFailedAccessBean $e) {
		$ok=1;
	}
	if (!$ok) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); 
	SmartTest::instance()->progress();
	
	try{
	$bordeaux->attach( $cheese );
	}
	catch(ExceptionFailedAccessBean $e) {
		$ok=1;
	}
	if (!$ok) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); 
	SmartTest::instance()->progress();
	
	try{
	$bordeaux->add( new Wine() );
	$ok=1;
	}
	catch(ExceptionFailedAccessBean $e) {
		$ok=0;
	}
	if (!$ok) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); 
	
	SmartTest::instance()->progress(); ;
	RedBean_OODB::$pkey = $oldkey;
	$cheese = new Cheese(1);
	$cheese->setName("Camembert");
	$ok=0;
	try{
	$cheese->save();
	$ok=1;
	}
	catch(ExceptionFailedAccessBean $e) {
		$ok=0;
	}
	if (!$ok) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); 
	
	
	SmartTest::instance()->progress();
	try{
	RedBean_OODB::$pkey = 999;
	RedBean_OODB::setLockingTime(0);
	$cheese = new Cheese(1);
	$cheese->setName("Cheddar");
	$cheese->save();
	RedBean_OODB::setLockingTime(10);
	SmartTest::instance()->progress(); ;
	}catch(Exception $e) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	try{
	RedBean_OODB::$pkey = 123;
	RedBean_OODB::setLockingTime(100);
	$cheese = new Cheese(1);
	$cheese->setName("Cheddar2");
	$cheese->save();
	RedBean_OODB::setLockingTime(10);
	die("<b style='color:red'>Error CANNOT-C:".SmartTest::instance()->canwe);
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
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	
	//test value ranges
	SmartTest::instance()->progress(); ;
	SmartTest::instance()->canwe = "protect inner state of RedBean";
	try{
	RedBean_OODB::setLockingTime( -1 );
	die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}catch(ExceptionInvalidArgument $e){  }
	
	SmartTest::instance()->progress(); ;
	SmartTest::instance()->canwe = "protect inner state of RedBean";
	try{
	RedBean_OODB::setLockingTime( 1.5 );
	die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}catch(ExceptionInvalidArgument $e){  }
	
	SmartTest::instance()->progress(); ;
	SmartTest::instance()->canwe = "protect inner state of RedBean";
	try{
	RedBean_OODB::setLockingTime( "aaa" );
	die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}catch(ExceptionInvalidArgument $e){  }
	
	SmartTest::instance()->progress(); ;
	SmartTest::instance()->canwe = "protect inner state of RedBean";
	try{
	RedBean_OODB::setLockingTime( null );
	die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}catch(ExceptionInvalidArgument $e){  }
	
	SmartTest::instance()->progress(); ;
	
	//can we reset logs
	SmartTest::instance()->canwe="reset the logs?";
	$logs = RedBean_DBAdapter::getLogs();
	//are the logs working?
	if (is_array($logs) && count($logs)>0) { SmartTest::instance()->progress(); ; } else { die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); } 
	RedBean_DBAdapter::resetLogs();
	$logs = RedBean_DBAdapter::getLogs();
	if (is_array($logs) && count($logs)===0) { SmartTest::instance()->progress(); ; } else { die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); } 
	
	
	SmartTest::instance()->progress(); ; SmartTest::instance()->canwe= "freeze the database?";
	RedBean_OODB::freeze();
	$joop = new Project;
	$joop->setName("Joop");
	$joopid = $joop->save();
	
	if (!is_numeric($joopid) || $joopid < 1) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	SmartTest::instance()->progress(); ;
	
	$joop->setBlaaataap("toppiedoe");
	$joop->save();
	$joop2 = new Project( $joopid );
	$name = $joop2->getName();
	$blaataap = $joop2->getBlaataap();
	if ($name!=="Joop") die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	SmartTest::instance()->progress(); ;
	if (!is_null($blaataap)) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	SmartTest::instance()->progress(); ;
	
	
	try{
	RedBean_OODB::gen("haas");
	$haas = new Haas;
	$haas->setHat("redhat");
	$id = $haas->save();
	if ($id!==0) {
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	SmartTest::instance()->progress(); ;
	}
	catch(Exception $e) {
	die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	SmartTest::instance()->progress(); ;
	
	
	$cheese = new Cheese;
	$cheese->setName("bluecheese");
	$cheeseid = $cheese->save();
	if (!$cheeseid) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	$anothercheese = new Cheese;
	$cheese->add($anothercheese);
	$cheese->attach($anothercheese);
	$a1 = $cheese->getRelatedCheese();
	$a2 = $cheese->children();
	if (!is_array($a1) || (is_array($a1) && count($a1)!==0)) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	SmartTest::instance()->progress(); ;
	if (!is_array($a2) || (is_array($a2) && count($a2)!==0)) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	SmartTest::instance()->progress(); ;
	
	
	//now scan the logs for database modifications
	$logs = strtolower( implode(",",RedBean_DBAdapter::getLogs()) );
	if (strpos("alter",$logs)!==false) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	SmartTest::instance()->progress(); ;
	
	if (strpos("truncate",$logs)!==false) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	SmartTest::instance()->progress(); ;
	
	if (strpos("drop",$logs)!==false) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	SmartTest::instance()->progress(); ;
	
	if (strpos("change",$logs)!==false) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	SmartTest::instance()->progress(); ;
	
	if (strpos("show",$logs)!==false) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	SmartTest::instance()->progress(); ;
	
	if (strpos("describe",$logs)!==false) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	SmartTest::instance()->progress(); ;
	
	if (strpos("drop database",$logs)!==false) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	SmartTest::instance()->progress(); ;
	
	
	//should be unable to do gc() and optimize() and clean() and resetAll()
	RedBean_DBAdapter::resetLogs();
	if (RedBean_OODB::gc() && count(RedBean_DBAdapter::getLogs())>0){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if (RedBean_OODB::optimizeIndexes() && count(RedBean_DBAdapter::getLogs())>0){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if (RedBean_OODB::clean() && count(RedBean_DBAdapter::getLogs())>0){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if (RedBean_OODB::registerUpdate("cheese") && count(RedBean_DBAdapter::getLogs())<1){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if (RedBean_OODB::registerSearch("cheese") && count(RedBean_DBAdapter::getLogs())<1){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;

	 SmartTest::instance()->canwe="can we unfreeze the database";
	try{
	RedBean_OODB::unfreeze();
	}catch(Exception $e){
		die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
	}
	SmartTest::instance()->progress(); ;
	
	//should be ABLE to do gc() and optimize() and clean() and resetAll()
	RedBean_DBAdapter::resetLogs();
	if (!RedBean_OODB::gc() && count(RedBean_DBAdapter::getLogs())< 1 ){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if (!RedBean_OODB::optimizeIndexes() && count(RedBean_DBAdapter::getLogs())<1){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if (!RedBean_OODB::clean() && count(RedBean_DBAdapter::getLogs())<1){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if (!RedBean_OODB::registerUpdate("cheese") && count(RedBean_DBAdapter::getLogs())<1){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if (!RedBean_OODB::registerSearch("cheese") && count(RedBean_DBAdapter::getLogs())<1){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	
	//test convenient tree functions
	 SmartTest::instance()->canwe="convient tree functions";
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
	if (count($donald->children())!=3) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if (count($kwik->siblings())!=2) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	
	//todo
	if ($kwik->hasParent($donald)!=true) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if ($donald->hasParent($kwak)!=false) {die("<b style='color:red'>Error CANNOT2:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	
	if ($donald->hasChild($kwak)!=true) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if ($donald->hasChild($donald)!=false) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if ($kwak->hasChild($kwik)!=false) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	
	if ($kwak->hasSibling($kwek)!=true) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if ($kwak->hasSibling($kwak)!=false) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if ($kwak->hasSibling($donald)!=false) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	
	//copy
	SmartTest::instance()->canwe="copy functions";
	$kwak2 = $kwak->copy();
	$id = $kwak2->save();
	$kwak2 = new Person( $id );
	if ($kwak->getName() != $kwak2->getName()) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	
	
	SmartTest::instance()->canwe="countRelated";
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
	
	if ($blog->numofComment()!==5) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	if ($blog2->numofComment()!==3) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	
	
	SmartTest::instance()->canwe="associate tables of the same name";
	$blog = new Blog;
	$blogb = new Blog;
	$blog->title='blog a';
	$blogb->title='blog b';
	$blog->add( $blogb );
	$b = $blog->getRelatedBlog();
	if (count($b)!==1) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); ;
	$b=	array_pop($b);
	if ($b->title!='blog b') {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); 
	
	
	SmartTest::instance()->canwe="inferTypeII patch";
	$blog->rating = 4294967295;
	$blog->save();
	$id = $blog->getID();
	$blog2->rating = -1;
	$blog2->save();
	$blog = new Blog( $id );
	if ($blog->getRating()!=4294967295) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); 
	
	SmartTest::instance()->canwe="Longtext column type";
	$blog->message = str_repeat("x",65535);
	$blog->save();
	$blog = new Blog( $id );
	if (strlen($blog->message)!=65535) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress();
	$rows = RedBean_OODB::$db->get("describe blog");
	if($rows[3]["Type"]!="text")  {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); 
	$blog->message = str_repeat("x",65536);
	$blog->save();
	$blog = new Blog( $id );
	if (strlen($blog->message)!=65536) {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress();
	$rows = RedBean_OODB::$db->get("describe blog");
	if($rows[3]["Type"]!="longtext")  {die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress(); 
	Redbean_OODB::clean();
}

SmartTest::instance()->canwe="select only valid engines?";
try{RedBean_OODB::setEngine("nonsensebase"); die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }catch(Exception $e){ SmartTest::instance()->progress(); ; }
try{RedBean_OODB::setEngine("INNODB"); die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }catch(Exception $e){ SmartTest::instance()->progress(); ; }
try{RedBean_OODB::setEngine("MYISaM"); die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }catch(Exception $e){ SmartTest::instance()->progress(); ; }
try{RedBean_OODB::setEngine(""); die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }catch(Exception $e){ SmartTest::instance()->progress(); ; }


RedBean_OODB::setEngine("myisam");

SmartTest::instance()->canwe="performance monitor";
R::gen("Patient");
$patient = new Patient;
$patient->surname="Van Dalen";
$patient->address="Street 1";
$patient->save();
for( $i=0; $i<100; $i++ ){ 
$patient = new Patient;
$patient->surname="Waals $i ";
$patient->address="Street 2$i ";
$patient->save();
}
$dummy = new Patient;
$dummy->address = "lane";
$db->exec("insert into searchindex VALUES(null,'filler1',0) ");
$db->exec("insert into searchindex VALUES(null,'filler2',0) ");
$db->exec("insert into searchindex VALUES(null,'filler3',0) ");
$db->exec("insert into searchindex VALUES(null,'filler4',0) ");
$db->exec("insert into searchindex VALUES(null,'filler5',0) ");
$db->exec("insert into searchindex VALUES(null,'patient.address',0) ");
$db->exec("update searchindex set cnt=100 where ind='patient.address' ");
$db->exec("update searchindex set cnt=0 where ind!='patient.address' ");
if (count($db->get("SHOW INDEX FROM patient"))!==1){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress();
Patient::find( $dummy, array("address"=>"="));
if ($db->getCell("select cnt from searchindex where ind='patient.address'")!=101){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress();
R::optimizeIndexes( true );
if (count($db->get("SHOW INDEX FROM patient"))!==2){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress();
$db->exec(" update patient set address='street same' ");
R::optimizeIndexes( true );
if (count($db->get("SHOW INDEX FROM patient"))!==1){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress();
$db->exec(" update patient set address=rand() ");
R::optimizeIndexes( true );
if (count($db->get("SHOW INDEX FROM patient"))!==2){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress();
$db->exec("update searchindex set cnt=0 where ind='patient.address' ");
$db->exec("update searchindex set cnt=100 where ind!='patient.address' ");
R::optimizeIndexes( true );
if (count($db->get("SHOW INDEX FROM patient"))!==1){ die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe); }else SmartTest::instance()->progress();

testsperengine();
RedBean_OODB::setEngine("innodb");
testsperengine();



SmartTest::instance()->progress(); ; SmartTest::instance()->canwe = "clean up the database?";

//insert garbage tables
$db->exec(" CREATE TABLE `oodb`.`garbage1` (
			`a` VARCHAR( 11 ) NOT NULL ,
			`b` VARCHAR( 11 ) NOT NULL ,
			`c` VARCHAR( 11 ) NOT NULL ,
			`d` VARCHAR( 11 ) NOT NULL ,
			`e` VARCHAR( 11 ) NOT NULL ,
			`f` VARCHAR( 11 ) NOT NULL ,
			`g` VARCHAR( 11 ) NOT NULL ,
			`h` VARCHAR( 11 ) NOT NULL ,
			`i` VARCHAR( 11 ) NOT NULL ,
			`j` VARCHAR( 11 ) NOT NULL
			) ENGINE = MYISAM ");


//insert garbage tables
$db->exec(" CREATE TABLE `oodb`.`garbage2` (
			`a` VARCHAR( 11 ) NOT NULL ,
			`b` VARCHAR( 11 ) NOT NULL ,
			`c` VARCHAR( 11 ) NOT NULL ,
			`d` VARCHAR( 11 ) NOT NULL ,
			`e` VARCHAR( 11 ) NOT NULL ,
			`f` VARCHAR( 11 ) NOT NULL ,
			`g` VARCHAR( 11 ) NOT NULL ,
			`h` VARCHAR( 11 ) NOT NULL ,
			`i` VARCHAR( 11 ) NOT NULL ,
			`j` VARCHAR( 11 ) NOT NULL
			) ENGINE = MYISAM ");



$db->exec("INSERT INTO `oodb`.`garbage1` (
			`a` ,
			`b` ,
			`c` ,
			`d` ,
			`e` ,
			`f` ,
			`g` ,
			`h` ,
			`i` ,
			`j`
			)
			VALUES (
			'aaa', 'bbb', 'cccc', 'dddd', 'eee', '', 'fff', 'ggg', '', 'hhh'
			);
");

Redbean_OODB::addTable("garbage1");
Redbean_OODB::addTable("garbage2");


$cols = $db->get("describe garbage1");
$ok=0;
if (count($cols)===10) SmartTest::instance()->progress(); 
	
if (RedBean_OODB::gc()) SmartTest::instance()->progress();
	
		
for($i=0; $i<100; $i++){
	RedBean_OODB::gc();
}

$cols = $db->get("describe garbage1");
if (count($cols)===8) { $ok=1;  SmartTest::instance()->progress(); }

$tables = $db->get("show tables");
foreach($tables as $t){
	if ($t=="garbage2") $ok=0; 
}
if (!$ok) die("<b style='color:red'>Error CANNOT:".SmartTest::instance()->canwe);
SmartTest::instance()->progress(); 
	



echo "<br><br><b style='color:green'>All $tests tests passed, redbean should work fine.</b>";

?>
</body>
</html>	