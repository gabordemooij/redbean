<?php

R::setup("mysql:host=localhost;dbname=oodb","root"); $db="mysql";





function testids($array) {
	foreach($array as $key=>$bean) {
		asrt(intval($key),intval($bean->getID()));
	}
}

droptables();

if ($db=='sqlite') {
testpack('Test widen column in combination with bean formatter. (discovered while testing FKs)');
class BF extends RedBean_DefaultBeanFormatter {
	public function formatBeanTable($type){ return 'prefixed_'.$type; }
	public function formatBeanID($type){ return 'lousy_shitty_id'; }
}

droptables();
}



//graph
R::exec('drop table if exists band_bandmember');
R::exec('drop table if exists band_location');
R::exec('drop table if exists band_genre');
R::exec('drop table if exists army_village');
R::exec('drop table if exists cd_track');
R::exec('drop table if exists song_track');
R::exec('drop table if exists location');
R::exec('drop table if exists bandmember');
R::exec('drop table if exists band');
R::exec('drop table if exists genre');
R::exec('drop table if exists farmer cascade');
R::exec('drop table if exists furniture');
R::exec('drop table if exists building');
R::exec('drop table if exists village');
R::exec('drop table if exists army');
R::exec('drop table if exists people');
R::exec('drop table if exists song');
R::exec('drop table if exists track');
R::exec('drop table if exists cover');
R::exec('drop table if exists playlist');







droptables();

testpack('Recursive Export');

//require('../RedBean/redbean.inc.php');
//R::setup('mysql:host=localhost;dbname=oodb','root','');

//require('../RedBean/Plugin/BeanExport.php');


testpack('Test foreign keys');
droptables();



testpack('Test Nuke()');
testpack('ext keyword test');
R::freeze(false);





