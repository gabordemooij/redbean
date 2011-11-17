<?php




//Test constraints: cascaded delete
	testpack("Test Cascaded Delete");
//$adapter = $toolbox->getDatabaseAdapter();
//$adapter->getDatabase()->setDebugMode(1);
	try {
		$adapter->exec("ALTER TABLE cask DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b ");
	}catch(Exception $e) {

	}


//$adapter->exec("DROP TRIGGER IF EXISTS fkb8317025deb6e03fc05abaabc748a503b ");

//add cask 101 and whisky 12
	$cask = $redbean->dispense("cask");
	$whisky = $redbean->dispense("whisky");
	$cask->number = 100;
	$whisky->age = 10;
	$a = new RedBean_AssociationManager( $toolbox );
	$a->associate( $cask, $whisky );
//first test baseline behaviour, dead record should remain
	asrt(count($a->related($cask, "whisky")),1);
	$redbean->trash($cask);
//no difference -- DIFFERENCE now because we have included add constr.
	asrt(count($a->related($cask, "whisky")),0);
//	$adapter->exec("DROP TABLE cask_whisky"); //clean up for real test!

//add cask 101 and whisky 12
	$cask = $redbean->dispense("cask");
	$whisky = $redbean->dispense("whisky");
	$cask->number = 101;
	$whisky->age = 12;
	$a = new RedBean_AssociationManager( $toolbox );
	$a->associate( $cask, $whisky );

//add cask 102 and whisky 13
	$cask2 = $redbean->dispense("cask");
	$whisky2 = $redbean->dispense("whisky");
	$cask2->number = 102;
	$whisky2->age = 13;
	$a = new RedBean_AssociationManager( $toolbox );
	$a->associate( $cask2, $whisky2 );

//add constraint
	//asrt(RedBean_Plugin_Constraint::addConstraint($cask, $whisky),true);
//no error for duplicate
	//asrt(RedBean_Plugin_Constraint::addConstraint($cask, $whisky),false);


	//asrt(count($a->related($cask, "whisky")),1);

	$redbean->trash($cask);
	asrt(count($a->related($cask, "whisky")),0); //should be gone now!

	asrt(count($a->related($whisky2, "cask")),1);
	$redbean->trash($whisky2);
	asrt(count($a->related($whisky2, "cask")),0); //should be gone now!

	try {
		$adapter->exec("ALTER TABLE cask DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b ");
	}catch(Exception $e) {

	}

	try {
		$adapter->exec("ALTER TABLE cask DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503a ");
	}catch(Exception $e) {

	}
	try {
		$adapter->exec("ALTER TABLE cask_whisky DROP CONSTRAINT fkb8317025deb6e03fc05abaabc748a503b ");
	}catch(Exception $e) {

	}
		$adapter->exec("DROP TABLE IF EXISTS cask_cask");
	$adapter->exec("DROP TABLE IF EXISTS cask_whisky");
	try {
		$adapter->exec("DROP TABLE IF EXISTS cask CASCADE ");
	}catch(Exception $e) {
		die($e->getMessage());
	}

	$adapter->exec("DROP TABLE IF EXISTS whisky CASCADE ");



//add cask 101 and whisky 12
	$cask = $redbean->dispense("cask");
	$cask->number = 201;
	$cask2 = $redbean->dispense("cask");
	$cask2->number = 202;
	$a->associate($cask,$cask2);
//	asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),true);
//	asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),false);
//now from cache... no way to check if this works :(
//	asrt(RedBean_Plugin_Constraint::addConstraint($cask, $cask2),false);
	asrt(count($a->related($cask, "cask")),1);
	$redbean->trash( $cask2 );
	asrt(count($a->related($cask, "cask")),0);
//now in combination with prefixes

class TestFormatter implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "xx_$table";}
	public function formatBeanID( $table ) {return "id";}
	public function getAlias($a){ return '__';}
}
$oldwriter = $writer;
$oldredbean = $redbean;
$writer = new RedBean_QueryWriter_PostgreSQL( $adapter, false );
$writer->setBeanFormatter( new TestFormatter );
$redbean = new RedBean_OODB( $writer );
$t2 = new RedBean_ToolBox($redbean,$adapter,$writer);
$a = new RedBean_AssociationManager($t2);
$redbean = new RedBean_OODB( $writer );
$b = $redbean->dispense("barrel");
$g = $redbean->dispense("grapes");
$g->type = "merlot";
$b->texture = "wood";
$a->associate($g, $b);
$a = new RedBean_AssociationManager($toolbox);
$writer = $oldwriter;
$redbean=$oldredbean;







testpack("Test Custom ID Field");
class MyWriter extends RedBean_QueryWriter_PostgreSQL {
	public function getIDField( $type ) {
		return $type . "_id";
	}
}
$writer2 = new MyWriter($adapter);
$redbean2 = new RedBean_OODB($writer2);
$movie = $redbean2->dispense("movie");
asrt(isset($movie->movie_id),true);
$movie->name="movie 1";
$movieid = $redbean2->store($movie);
asrt(($movieid>0),true);
$columns = array_keys( $writer->getColumns("movie") );
asrt(in_array("movie_id",$columns),true);
asrt(in_array("id",$columns),false);
$movie2 = $redbean2->dispense("movie");
asrt(isset($movie2->movie_id),true);
$movie2->name="movie 2";
$movieid2 = $redbean2->store($movie2);
$movie1 = $redbean2->load("movie",$movieid);
asrt($movie->name,"movie 1");
$movie2 = $redbean2->load("movie",$movieid2);
asrt($movie2->name,"movie 2");
$movies = $redbean2->batch("movie", array($movieid,$movieid2));
asrt(count($movies),2);
asrt($movies[$movieid]->name,"movie 1");
asrt($movies[$movieid2]->name,"movie 2");
$toolbox2 = new RedBean_ToolBox($redbean2, $adapter, $writer2);

$a2 = new RedBean_AssociationManager($toolbox2);
$a2->associate($movie1,$movie2);
$movies = $a2->related($movie1, "movie");
asrt(count($movies),1);
asrt((int) $movies[0],(int) $movieid2);
$movies = $a2->related($movie2, "movie");
asrt(count($movies),1);
asrt((int) $movies[0],(int) $movieid);
$genre = $redbean2->dispense("genre");
$genre->name="western";
$a2->associate($movie,$genre);
$movies = $a2->related($genre, "movie");
asrt(count($movies),1);
asrt((int)$movies[0],(int)$movieid);
$a2->unassociate($movie,$genre);
$movies = $a2->related($genre, "movie");
asrt(count($movies),0);
$a2->clearRelations($movie, "movie");
$movies = $a2->related($movie1, "movie");
asrt(count($movies),0);
//$pdo->setDebugMode(0);


testpack("Test Table Prefixes");
R::setup(
  "pgsql:host={$ini['pgsql']['host']} dbname={$ini['pgsql']['schema']}",
  $ini['pgsql']['user'],
  $ini['pgsql']['pass']
);


R::$writer->tableFormatter = new MyTableFormatter;
$_tables = $writer->getTables();
if (in_array("page_user",$_tables)) $pdo->Execute("DROP TABLE page_user");
if (in_array("page_page",$_tables)) $pdo->Execute("DROP TABLE page_page");
if (in_array("xx_page_user",$_tables)) $pdo->Execute("DROP TABLE xx_page_user");
if (in_array("xx_page_page",$_tables)) $pdo->Execute("DROP TABLE xx_page_page");

if (in_array("page",$_tables)) $pdo->Execute("DROP TABLE page");
if (in_array("user",$_tables)) $pdo->Execute("DROP TABLE \"user\"");
if (in_array("xx_page",$_tables)) $pdo->Execute("DROP TABLE xx_page");
if (in_array("xx_user",$_tables)) $pdo->Execute("DROP TABLE xx_user");
//R::debug(1);
$page = R::dispense("page");
$page->title = "mypage";
$id=R::store($page);
$page = R::dispense("page");
$page->title = "mypage2";
R::store($page);
$beans = R::find("page"," id > 0");
asrt(count($beans),2);
$user = R::dispense("user");
$user->name="me";
R::store($user);
R::associate($user,$page);
asrt(count(R::related($user,"page")),1);
$page = R::load("page",$id);
asrt($page->title,"mypage");
R::associate($user,$page);
asrt(count(R::related($user,"page")),2);
asrt(count(R::related($page,"user")),1);
$user2 = R::dispense("user");
$user2->name="Bob";
R::store($user2);
$user3 = R::dispense("user");
$user3->name="Kim";
R::store($user3);
$t = R::$writer->getTables();
asrt(in_array("xx_page",$t),true);
asrt(in_array("xx_page_user",$t),true);
asrt(in_array("xx_user",$t),true);
asrt(in_array("page",$t),false);
asrt(in_array("page_user",$t),false);
asrt(in_array("user",$t),false);
$page2 = R::dispense("page");
$page2->title = "mypagex";
R::store($page2);
R::associate($page,$page2,'{"bla":2}');
$pgs = R::related($page,"page");
$p = reset($pgs);
asrt($p->title,"mypagex");
asrt((int)R::getCell("select bla from xx_page_page where bla > 0"),2);
$t = R::$writer->getTables();
asrt(in_array("xx_page_page",$t),true);
asrt(in_array("page_page",$t),false);


testpack("Testing: combining table prefix and IDField");
if (in_array("cms_blog",$_tables)) $pdo->Execute("DROP TABLE cms_blog");
class MyBeanFormatter implements RedBean_IBeanFormatter{
    public function formatBeanTable($table) {
        return "cms_$table";
    }
    public function formatBeanID( $table ) {
        return "{$table}_id"; // append table name to id. The table should not inclide the prefix.
    }
    public function getAlias($a){ return '__';}
}


R::$writer->setBeanFormatter(new MyBeanFormatter());
$blog = R::dispense('blog');
$blog->title = 'testing';
$blog->blog = 'tesing';
R::store($blog);
$blogpost = (R::load("blog",1));
asrt((isset($blogpost->cms_blog_id)),false);
asrt((isset($blogpost->blog_id)),true);
asrt(in_array("blog_id",array_keys(R::$writer->getColumns("blog"))),true);
asrt(in_array("cms_blog_id",array_keys(R::$writer->getColumns("blog"))),false);




testpack("New relations");



//this module tests whether values we store are the same we get returned
//PDO is a bit unpred. with this but using STRINGIFY attr this should work we test this here




printtext("\nALL TESTS PASSED. REDBEAN SHOULD WORK FINE.\n");


}catch(Exception $e) {
	echo "\n\n\n".$e->getMessage();
	echo "<pre>".$e->getTraceAsString();
}
