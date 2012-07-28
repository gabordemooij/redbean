<?php
/**
 * RedUNIT_Base_Sync 
 * @file 			RedUNIT/Base/Sync.php
 * @description		Tests sync functionality.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Sync extends RedUNIT_Base {
	
	
	private function createAPaintiningByMonet() {
		$artist = R::dispense('monet');
		$paintings = R::dispense('painting',2);
		$artist->ownPainting = $paintings;
		$bridge = R::dispense('bridge');
		$lillies = R::dispense('lilly',10);
		$garden = R::dispense('garden');
		$lady = R::dispense('lady');
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
		$this->createAPaintiningByMonet();
		
		$source = R::$toolbox;
		$sync = new RedBean_Plugin_Sync;
		
		foreach(R::$toolboxes as $toolbox) {
			if ($toolbox!==R::$toolbox) {
				testpack('Testing schema sync from '.get_class($source->getWriter()).' to: -> '.get_class($toolbox->getWriter()));
				//$toolbox->getDatabaseAdapter()->getDatabase()->setDebugMode(1); //keep this here, might be handy for debugging.
				$toolbox->getWriter()->wipeAll();
				$tables = array_flip($toolbox->getWriter()->getTables());
				asrt(!isset($tables['monet']),true);
				asrt(!isset($tables['painting']),true);
				asrt(!isset($tables['monet_painting']),true);
				asrt(!isset($tables['garden_painting']),true);
				asrt(!isset($tables['lilly']),true);
				asrt(!isset($tables['bridge']),true);
				$sync->sync($source,$toolbox);
				$tables = array_flip($toolbox->getWriter()->getTables());
				asrt(isset($tables['monet']),true);
				asrt(isset($tables['painting']),true);
				asrt(isset($tables['monet_painting']),false);
				asrt(isset($tables['garden_painting']),true);
				asrt(isset($tables['lilly']),true);
				asrt(isset($tables['bridge']),true);
				R::configureFacadeWithToolbox($toolbox);
				R::freeze(true);
				$id = $this->createAPaintiningByMonet();
				$monet = R::load('monet',$id);
				asrt(count($monet->ownPainting),2);
				foreach($monet->ownPainting as $painting) {
					asrt(count($painting->sharedGarden),1);
					asrt((
							(count($painting->ownLilly)===10 && count($painting->ownBridge)===1) 
							|| 
							(count($painting->ownLady)===1)
						 ),true);
				}
				R::freeze(false);
				R::configureFacadeWithToolbox($source);
				
			}
		}
		
	}
	
}