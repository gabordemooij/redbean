<?php
/**
 * RedUNIT_Mysql_Views
 * 
 * @file 			RedUNIT/Mysql/Views.php
 * @description		Tests creation of database views.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Mysql_Views extends RedUNIT_Mysql {
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		$p='';
		R::nuke();
		R::exec(" drop table if exists prefix_bandmember_musician ");
		R::exec(" drop table if exists prefix_band_bandmember ");
		R::exec(" drop table if exists bandmember_musician ");
		R::exec(" drop table if exists band_bandmember ");
		
		R::exec(" drop table if exists musician ");
		R::exec(" drop table if exists bandmember ");
		R::exec(" drop table if exists band ");
		R::exec(" drop table if exists prefix_musician ");
		R::exec(" drop table if exists prefix_bandmember ");
		R::exec(" drop table if exists prefix_band ");
		
		list( $mickey, $donald, $goofy ) = R::dispense("musician",3);
		list( $vocals1, $vocals2, $keyboard1, $drums, $vocals3, $keyboard2 ) = R::dispense("bandmember",6);
		list( $band1, $band2 ) = R::dispense("band",2);
		$band1->name = "The Groofy"; $band2->name="Wickey Mickey";
		$mickey->name = "Mickey"; $goofy->name = "Goofy"; $donald->name = "Donald";
		$vocals1->instrument = "voice"; $vocals2->instrument="voice";$keyboard1->instrument="keyboard";$drums->instrument="drums";
		$vocals3->instrument = "voice"; $keyboard2->instrument="keyboard";
		$vocals3->bandleader=true;
		$drums->bandleader=true;
		$drums->notes = "noisy";
		$vocals3->notes = "tenor";
		
		R::associate($mickey,$vocals1);
		R::associate($donald,$vocals2);
		R::associate($donald,$keyboard1);
		R::associate($goofy,$drums);
		R::associate($mickey,$vocals3);
		R::associate($donald,$keyboard2);
		
		R::associate($band1,$vocals1);
		R::associate($band1,$vocals2);
		R::associate($band1,$keyboard1);
		R::associate($band1,$drums);
		
		R::associate($band2,$vocals3);
		R::associate($band2,$keyboard2);
		
		try{
			R::view("bandlist","band");
			fail();
		}
		catch(Exception $e) {
			pass();
		}
		
		try{
			R::view("bandlist","band,bandmember,musician");
			pass();
		}
		catch(Exception $e) {
			print_r($e);
			fail();
		}
		//can we do a simple query?
		$nameOfBandWithID1 = R::getCell("select `name` from ".$p."bandlist where id = 1 group by  id");
		asrt($nameOfBandWithID1,"The Groofy");
		//can we generate a report? list all bandleaders
		$bandleaders = R::getAll("select  `bandleader_of_bandmember`,`name_of_musician`,`name` AS bandname
			from ".$p."bandlist where `bandleader_of_bandmember` =  1 group by id ");
		
		foreach($bandleaders as $bl) {
			if ($bl["bandname"]=="Wickey Mickey") {
				asrt($bl["name_of_musician"],"Mickey");
			}
			if ($bl["bandname"]=="The Groofy") {
				asrt($bl["name_of_musician"],"Goofy");
			}
		}
		//can we draw statistics?
		$inHowManyBandsDoYouPlay = R::getAll("select
		`name_of_musician` ,count( distinct `id`) as bands
		from ".$p."bandlist group by `id_of_musician`  order by `name_of_musician` asc
		");
		asrt($inHowManyBandsDoYouPlay[0]["name_of_musician"],"Donald");
		asrt($inHowManyBandsDoYouPlay[0]["bands"],'2');
		asrt($inHowManyBandsDoYouPlay[1]["name_of_musician"],"Goofy");
		asrt($inHowManyBandsDoYouPlay[1]["bands"],'1');
		asrt($inHowManyBandsDoYouPlay[2]["name_of_musician"],"Mickey");
		asrt($inHowManyBandsDoYouPlay[2]["bands"],'2');
		//who plays in band 2
		//can we make a selectbox
		$selectbox = R::getAll("
			select m.id, m.name, b.id as selected from ".$p."musician as m
			left join ".$p."bandlist as b on b.id_of_musician = m.id and
			b.id =2
			order by m.name asc
		");
		
		asrt($selectbox[0]["name"],"Donald");
		asrt($selectbox[0]["selected"],"2");
		asrt($selectbox[1]["name"],"Goofy");
		asrt($selectbox[1]["selected"],null);
		asrt($selectbox[2]["name"],"Mickey");
		asrt($selectbox[2]["selected"],"2");
	}
}