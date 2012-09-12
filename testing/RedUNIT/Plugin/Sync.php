<?php
/**
 * RedUNIT_Plugin_Sync
 *  
 * @file 			RedUNIT/Plugin/Sync.php
 * @description		Tests sync functionality.
 *					This test pack is part of the RedBeanPHP ORM Plugin test suite.
 *					This class is part of the RedUNIT test suite for RedBeanPHP.
 *					This class tests whether we can sync() two schemas 
 *					and also tests whether we can sync between two different
 *					database systems, for instance PostgreSQL -> MySQL etc.
 *					All combinations are tested. 
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Plugin_Sync extends RedUNIT_Plugin {
	
	
	/**
	 * Dispense an artist named Monet and add a painting of a garden with
	 * a bridge and ten lillies floating on the water as well as another 
	 * painting of a lady in a garden. And before we dream away at the
	 * sight of these pictures return the primary key of Monet.
	 * 
	 * @return integer $id 
	 */
	private function createAPaintiningByMonet() {
		$artist = R::dispense('monet');
		$artist->born = '1840-11-14';
		$paintings = R::dispense('painting',2);
		$artist->ownPainting = $paintings;
		$parasol = R::dispense('parasol');
		$parasol->color = 'red';
		$parasol->length = 1.2;
		$bridge = R::dispense('bridge');
		$lillies = R::dispense('lilly',10);
		$garden = R::dispense('garden');
		$garden->size = 10;
		foreach($lillies as $lilly) $lilly->color = 'purple';
		$bridge->broken = false;
		$lady = R::dispense('lady');
		$lady->parasol = $parasol;
		$paintings[0]->sharedGarden[] = $garden;
		$paintings[1]->sharedGarden[] = $garden;
		$paintings[0]->ownLady = array( $lady );
		$paintings[1]->ownLilly = $lillies;
		$paintings[1]->ownBridge = array( $bridge );
		return R::store($artist);
	}
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		testpack('Test Schema Syncing. Setup...');
		R::nuke();
		$source = R::$toolbox;
		foreach(R::$toolboxes as $sKey=>$tb) if ($tb===$source) { $sourceKey = $sKey; }
		$this->createAPaintiningByMonet();
		foreach(R::$toolboxes as $key=>$toolbox) {
			if ($toolbox!==R::$toolbox) {
				testpack('Testing schema sync from '.get_class($source->getWriter()).' to: -> '.get_class($toolbox->getWriter()));
				//$toolbox->getDatabaseAdapter()->getDatabase()->setDebugMode(1); //keep this here, might be handy for debugging.
				$w = $toolbox->getWriter();
				$w->wipeAll();
				$parasol = R::dispense('parasol');
				$parasol->color = 'white';
				$toolbox->getRedBean()->store($parasol);
				$tables = array_flip($toolbox->getWriter()->getTables());
				asrt(!isset($tables['monet']),true);
				asrt(!isset($tables['painting']),true);
				asrt(!isset($tables['monet_painting']),true);
				asrt(!isset($tables['garden_painting']),true);
				asrt(!isset($tables['lilly']),true);
				asrt(!isset($tables['bridge']),true);
				asrt(isset($tables['parasol']),true);
				$columns = $toolbox->getWriter()->getColumns('parasol');
				asrt(count($columns),2);
				asrt(isset($columns['color']),true);
				asrt(isset($columns['length']),false);
				R::syncSchema($sourceKey,$key);
				$tables = array_flip($toolbox->getWriter()->getTables());
				asrt(isset($tables['monet']),true);
				asrt(isset($tables['painting']),true);
				asrt(isset($tables['monet_painting']),false);
				asrt(isset($tables['garden_painting']),true);
				asrt(isset($tables['lilly']),true);
				asrt(isset($tables['bridge']),true);
				asrt(isset($tables['parasol']),true);
				$columns = $source->getWriter()->getColumns('parasol');
				asrt(count($columns),3);
				asrt(isset($columns['color']),true);
				asrt(isset($columns['length']),true);
				R::configureFacadeWithToolbox($toolbox);
				R::freeze(true);
				$id = $this->createAPaintiningByMonet();
				$columns = R::$writer->getColumns('monet');
				$monet = R::load('monet',$id);
				$wclass = get_class($toolbox->getWriter());
				asrt($monet->born,'1840-11-14');
				asrt(count($monet->ownPainting),2);
				foreach($monet->ownPainting as $painting) {
					asrt(count($painting->sharedGarden),1);
					asrt((
							(count($painting->ownLilly)===10 && count($painting->ownBridge)===1) 
							|| 
							(count($painting->ownLady)===1)
						 ),true);
					if (count($painting->ownLady)===1) {
						$lady = reset($painting->ownLady);
						asrt($lady->parasol->color,'red');
						asrt(($lady->parasol->length==1.2),true);
					}
					if (count($painting->ownLilly)) {
						$lilly = reset($painting->ownLilly);
						asrt($lilly->color,'purple');

						$bridge = reset($painting->ownBridge);
						asrt((boolean)$bridge->broken,false);
					}
					$garden = reset($painting->sharedGarden);
					asrt((int)$garden->size,10);	
				}
				R::freeze(false);
				R::configureFacadeWithToolbox($source);
			}
		}
	}
}
