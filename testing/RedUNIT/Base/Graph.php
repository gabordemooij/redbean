<?php

class RedUNIT_Base_Graph extends RedUNIT_Base {

	public function run() {
		global $currentDriver;
		global $lifeCycle;
	
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
			
				
		list($v1,$v2,$v3) = R::dispense('village',3);
		list($b1,$b2,$b3,$b4,$b5,$b6) = R::dispense('building',6);
		list($f1,$f2,$f3,$f4,$f5,$f6) = R::dispense('farmer',6);
		list($u1,$u2,$u3,$u4,$u5,$u6) = R::dispense('furniture',6);
		list($a1,$a2) = R::dispense('army',2);
		
		$a1->strength = 100;
		$a2->strength = 200;
		$v1->name = 'v1';
		$v2->name = 'v2';
		$v3->name = 'v3';
		$v1->ownBuilding = array($b4,$b6);
		$v2->ownBuilding = array($b1);
		$v3->ownBuilding = array($b5);
		$b1->ownFarmer = array($f1,$f2);
		$b6->ownFarmer = array($f3);
		$b5->ownFarmer = array($f4);
		$b5->ownFurniture = array($u6,$u5,$u4);
		$v2->sharedArmy[] = $a2;
		$v3->sharedArmy = array($a2,$a1);
		$i2=R::store($v2);
		$i1=R::store($v1);
		$i3=R::store($v3);
		$v1 = R::load('village',$i1);
		$v2 = R::load('village',$i2);
		$v3 = R::load('village',$i3);
		asrt(count($v3->ownBuilding),1);
		asrt(count(reset($v3->ownBuilding)->ownFarmer),1);
		asrt(count(reset($v3->ownBuilding)->ownFurniture),3);
		asrt(count(($v3->sharedArmy)),2);
		asrt(count($v1->sharedArmy),0);
		asrt(count($v2->sharedArmy),1);
		asrt(count($v2->ownBuilding),1);
		asrt(count($v1->ownBuilding),2);
		asrt(count(reset($v1->ownBuilding)->ownFarmer),0);
		asrt(count(end($v1->ownBuilding)->ownFarmer),1);
		asrt(count($v3->ownTapestry),0);
		
		//test views for N-1 - we use the village for this
		R::view('people','village,building,farmer,building,furniture');
		//count buildings
		if ($currentDriver=="mysql") {
		$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name="v1"');
		asrt($noOfBuildings,2);
		$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name="v2"');
		asrt($noOfBuildings,1);
		$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name="v3"');
		asrt($noOfBuildings,1);
		}
		
		if ($currentDriver=="pgsql") {
		$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name=\'v1\'');
		asrt($noOfBuildings,2);
		$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name=\'v2\'');
		asrt($noOfBuildings,1);
		$noOfBuildings = (int) R::getCell('select count(distinct id_of_building) as n from people where name=\'v3\'');
		asrt($noOfBuildings,1);
		}
		
		if ($currentDriver=="mysql") {
		//what villages does not have furniture
		$emptyHouses = R::getAll('select name,count(id_of_furniture) from people group by id having count(id_of_furniture) = 0');
		asrt(count($emptyHouses),2);
		foreach($emptyHouses as $empty){
			if ($empty['name']!=='v3') pass(); else fail();
		}
		}
		
		//test invalid views - should trigger error
		try{ R::view('messy','building,village,farmer'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		
		//R::view('impossible','nonexistant,fictional');
		
		//Change the names and add the same building should not change the graph
		$v1->name = 'village I';
		$v2->name = 'village II';
		$v3->name = 'village III';
		$v1->ownBuilding[] = $b4;
		$i2=R::store($v2);
		$i1=R::store($v1);
		$i3=R::store($v3);
		$v1 = R::load('village',$i1);
		$v2 = R::load('village',$i2);
		$v3 = R::load('village',$i3);
		asrt(count($v3->ownBuilding),1);
		asrt(count(reset($v3->ownBuilding)->ownFarmer),1);
		asrt(count(reset($v3->ownBuilding)->ownFurniture),3);
		asrt(count(($v3->sharedArmy)),2);
		asrt(count($v1->sharedArmy),0);
		asrt(count($v2->sharedArmy),1);
		asrt(count($v2->ownBuilding),1);
		asrt(count($v1->ownBuilding),2);
		asrt(count(reset($v1->ownBuilding)->ownFarmer),0);
		asrt(count(end($v1->ownBuilding)->ownFarmer),1);
		asrt(count($v3->ownTapestry),0);
		
		
		$json = '{"mysongs":{"type":"playlist","name":"JazzList","ownTrack":[{"type":"track","name":"harlem nocturne","order":"1","sharedSong":[{"type":"song","url":"music.com.harlem"}],"cover":{"type":"cover","url":"albumart.com\/duke1"}},{"type":"track","name":"brazil","order":"2","sharedSong":[{"type":"song","url":"music.com\/djan"}],"cover":{"type":"cover","url":"picasa\/django"}}]}}';
		
		$playList = json_decode( $json, true );
		$cooker = new RedBean_Cooker;
		$cooker->setToolbox(R::$toolbox);
		
		$playList = ($cooker->graph(($playList)));
		$id = R::store(reset($playList));
		$play = R::load("playlist", $id);
		asrt(count($play->ownTrack),2);
		foreach($play->ownTrack as $track) {
			asrt(count($track->sharedSong),1);
			asrt(($track->cover instanceof RedBean_OODBBean),true);
		}
		
		$json = '{"mysongs":{"type":"playlist","id":"1","ownTrack":[{"type":"track","name":"harlem nocturne","order":"1","sharedSong":[{"type":"song","id":"1"}],"cover":{"type":"cover","id":"2"}},{"type":"track","name":"brazil","order":"2","sharedSong":[{"type":"song","url":"music.com\/djan"}],"cover":{"type":"cover","url":"picasa\/django"}}]}}';
		
		$playList = json_decode( $json, true );
		$cooker = new RedBean_Cooker;
		$cooker->setToolbox(R::$toolbox);
		$playList = ($cooker->graph(($playList)));
		$id = R::store(reset($playList));
		$play = R::load("playlist", $id);
		asrt(count($play->ownTrack),2);
		foreach($play->ownTrack as $track) {
			asrt(count($track->sharedSong),1);
			asrt(($track->cover instanceof RedBean_OODBBean),true);
		}
		$track = reset($play->ownTrack);
		$song = reset($track->sharedSong);
		asrt(intval($song->id),1);
		asrt($song->url,"music.com.harlem");
		
		$json = '{"mysongs":{"type":"playlist","id":"1","ownTrack":[{"type":"track","name":"harlem nocturne","order":"1","sharedSong":[{"type":"song","id":"1","url":"changedurl"}],"cover":{"type":"cover","id":"2"}},{"type":"track","name":"brazil","order":"2","sharedSong":[{"type":"song","url":"music.com\/djan"}],"cover":{"type":"cover","url":"picasa\/django"}}]}}';
		
		$playList = json_decode( $json, true );
		$cooker = new RedBean_Cooker;
		$cooker->setToolbox(R::$toolbox);
		$playList = ($cooker->graph(($playList)));
		$id = R::store(reset($playList));
		$play = R::load("playlist", $id);
		asrt(count($play->ownTrack),2);
		foreach($play->ownTrack as $track) {
			asrt(count($track->sharedSong),1);
			asrt(($track->cover instanceof RedBean_OODBBean),true);
		}
		$track = reset($play->ownTrack);
		$song = reset($track->sharedSong);
		asrt(intval($song->id),1);
		asrt(($song->url),"changedurl");
		
		
		//Tree
		$page = R::dispense('page');
		$page->name = 'root of all evil';
		list( $subPage, $subSubPage, $subNeighbour, $subOfSubNeighbour, $subSister ) = R::dispense('page',5);
		$subPage->name = 'subPage';
		$subSubPage->name = 'subSubPage';
		$subOfSubNeighbour->name = 'subOfSubNeighbour';
		$subNeighbour->name = 'subNeighbour';
		$subSister->name = 'subSister';
		$page->ownPage = array( $subPage, $subNeighbour, $subSister );
		R::store($page);
		asrt(count($page->ownPage),3);
		foreach($page->ownPage as $p) {
			if ($p->name=='subPage') {
				$p->ownPage[] = $subSubPage;
			}
			if ($p->name=='subNeighbour') {
				$p->ownPage[] = $subOfSubNeighbour;
			}
		}
		R::store($page);
		asrt(count($page->ownPage),3);
		list($first, $second) = array_keys($page->ownPage);
		foreach($page->ownPage as $p) {
			if ($p->name=='subPage' || $p->name=='subNeighbour') {
				asrt(count($p->ownPage),1);
			}
			else {
				asrt(count($p->ownPage),0);
			}
		}
		R::nuke();
		
		
		
		
		$canes = candy_canes();
		$id = R::store($canes[0]);
		$cane = R::load('cane',$id);
		asrt($cane->label,'Cane No. 0');
		asrt($cane->cane->label,'Cane No. 1');
		asrt($cane->cane->cane->label,'Cane No. 4');
		asrt($cane->cane->cane->cane->label,'Cane No. 7');
		asrt($cane->cane->cane->cane->cane,NULL);
		
		//test backward compatibility
		asrt($page->owner,null);
		
		
		
		
		RedBean_ModelHelper::setModelFormatter(new DefaultModelFormatter);
		
		
		
		$band = R::dispense('band');
		$musicians = R::dispense('bandmember',5);
		$band->ownBandmember = $musicians;
		try{
		R::store($band);
		fail();
		}
		catch(Exception $e){
		pass();
		}
		$band = R::dispense('band');
		$musicians = R::dispense('bandmember',4);
		$band->ownBandmember = $musicians;
		try{
		$id=R::store($band);
		pass();
		}
		catch(Exception $e){
		fail();
		}
		
		$band=R::load('band',$id);
		$band->ownBandmember[] = R::dispense('bandmember');
		try{
		R::store($band);
		fail();
		}
		catch(Exception $e){
		pass();
		}
		
	//Test fuse
	$lifeCycle = "";	

	$bandmember = R::dispense('bandmember');
	$bandmember->name = 'Fatz Waller';
	$id = R::store($bandmember);
	$bandmember = R::load('bandmember',$id);
	R::trash($bandmember);
	
	
	//echo "\n\n\n".$lifeCycle."\n";
	
	$expected = 'calleddispenseid0calledupdateid0nameFatzWallercalledafter_updateid5nameFatzWallercalleddispenseid0calledopen5calleddeleteid5band_idnullnameFatzWallercalledafter_deleteid0band_idnullnameFatzWaller';
	
	$lifeCycle = preg_replace("/\W/","",$lifeCycle);
	//$expected = "\n\n".preg_replace("/\W/","",$expected)."\n\n";
	
	
	asrt($lifeCycle,$expected);
		
	

	//Test combination of bean formatter and N1
	
	R::$writer->setBeanFormatter(new N1AndFormatter);
	R::nuke();
	$book=R::dispense('book');
	$page=R::dispense('page');
	$book->ownPage[] = $page;
	$bookid = R::store($book);
	pass(); //survive?
	asrt($page->getMeta('cast.book_id'),'id');
	$book = R::load('book',$bookid);
	asrt(count($book->ownPage),1);
	$book->ownPage[] = R::dispense('page');
	$bookid = R::store($book);
	$book = R::load('book',$bookid);
	asrt(count($book->ownPage),2);
	
	//Test whether a nested bean will be saved if tainted
	R::nuke();
	$page = R::dispense('page');
	$page->title = 'a blank page';
	$book = R::dispense('book');
	$book->title = 'shiny white pages';
	$book->ownPage[] = $page;
	$id = R::store($book);
	$book = R::load('book', $id);
	$page = reset($book->ownPage);
	asrt($page->title,'a blank page');
	$page->title = 'slightly different white';
	R::store($book);
	$book = R::load('book', $id);
	$page = reset($book->ownPage);
	asrt($page->title,'slightly different white');
	$page = R::dispense('page');
	$page->title = 'x';
	$book = R::load('book', $id);
	$book->title = 'snow white pages';
	$page->book = $book;
	$pid = R::store($page);
	$page = R::load('page', $pid);
	asrt($page->book->title,'snow white pages');
	
	//test you cannot unset a relation list
	asrt(count($book->ownPage),2);
	unset($book->ownPage);
	$book=R::load('book',R::store($book));
	asrt(count($book->ownPage),2);
	$book->sharedTree = R::dispense('tree');
	R::store($book);
	
	$c = R::count('page');
	asrt(R::count('tree'),1);		
	R::trash($book);
	asrt(R::count('page'),$c);
	asrt(R::count('tree'),1);
	
	}
	
}


global $lifeCycle;
class Model_Bandmember extends RedBean_SimpleModel {

	public function open() {
		global $lifeCycle;
		$lifeCycle .= "\n called open: ".$this->id;
	}


	public function dispense(){
		global $lifeCycle;
		$lifeCycle .= "\n called dispense() ".$this->bean;
	}

	public function update() {
		global $lifeCycle;
		$lifeCycle .= "\n called update() ".$this->bean;
	}

	public function after_update(){
		global $lifeCycle;
		$lifeCycle .= "\n called after_update() ".$this->bean;
	}

	public function delete() {
		global $lifeCycle;
		$lifeCycle .= "\n called delete() ".$this->bean;
	}

	public function after_delete() {
		global $lifeCycle;
		$lifeCycle .= "\n called after_delete() ".$this->bean;
	}



}


class N1AndFormatter implements RedBean_IBeanFormatter{
	public function formatBeanTable($table) {return "xy_$table";}
	public function formatBeanID( $table ) {return "theid";}
	public function getAlias($a){ return $a; }
}
	
function candy_canes()  {
	$canes = R::dispense('cane',10);
	$i = 0;
	foreach($canes as $k=>$cane) {
	 $canes[$k]->label = 'Cane No. '.($i++);
	}
	$canes[0]->cane = $canes[1];
	$canes[1]->cane = $canes[4];
	$canes[9]->cane = $canes[4];
	$canes[6]->cane = $canes[4];
	$canes[4]->cane = $canes[7];
	$canes[8]->cane = $canes[7];
	return $canes;
}