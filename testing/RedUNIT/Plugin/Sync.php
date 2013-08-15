<?php
/**
 * RedUNIT_Plugin_Sync
 *
 * @file    RedUNIT/Plugin/Sync.php
 * @desc    Tests sync functionality.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Plugin_Sync extends RedUNIT_Plugin
{
	/**
	 * Test sync.
	 * 
	 * @return void
	 */
	public function testSync()
	{
		testpack( 'Test Schema Syncing. Setup...' );

		$source = R::$toolbox;

		foreach ( R::$toolboxes as $sKey => $tb ) if ( $tb === $source ) {
			$sourceKey = $sKey;
		}

		$this->createAPaintiningByMonet();

		foreach ( R::$toolboxes as $key => $toolbox ) {
			if ( $toolbox === R::$toolbox ) {
				continue;
			}

			testpack( 'Testing schema sync from ' . get_class( $source->getWriter() ) . ' to: -> ' . get_class( $toolbox->getWriter() ) );

			//$toolbox->getDatabaseAdapter()->getDatabase()->setDebugMode(1); //keep this here, might be handy for debugging.

			$w = $toolbox->getWriter();

			$w->wipeAll();

			$parasol        = R::dispense( 'parasol' );

			$parasol->color = 'white';

			$toolbox->getRedBean()->store( $parasol );

			$tables = array_flip( $toolbox->getWriter()->getTables() );

			asrt( !isset( $tables['monet'] ), true );
			asrt( !isset( $tables['painting'] ), true );
			asrt( !isset( $tables['monet_painting'] ), true );
			asrt( !isset( $tables['garden_painting'] ), true );
			asrt( !isset( $tables['lilly'] ), true );
			asrt( !isset( $tables['bridge'] ), true );
			asrt( isset( $tables['parasol'] ), true );

			$columns = $toolbox->getWriter()->getColumns( 'parasol' );

			asrt( count( $columns ), 2 );

			asrt( isset( $columns['color'] ), true );
			asrt( isset( $columns['length'] ), false );

			try {
				R::syncSchema( 'non-existant', $sourceKey );

				fail();
			} catch ( RedBean_Exception_Security $e ) {
				pass();
			}

			try {
				R::syncSchema( $sourceKey, 'non-existant' );

				fail();
			} catch ( RedBean_Exception_Security $e ) {
				pass();
			}

			R::syncSchema( $sourceKey, $key );

			$tables = array_flip( $toolbox->getWriter()->getTables() );

			asrt( isset( $tables['monet'] ), true );
			asrt( isset( $tables['painting'] ), true );
			asrt( isset( $tables['monet_painting'] ), false );
			asrt( isset( $tables['garden_painting'] ), true );
			asrt( isset( $tables['lilly'] ), true );
			asrt( isset( $tables['bridge'] ), true );
			asrt( isset( $tables['parasol'] ), true );

			$columns = $source->getWriter()->getColumns( 'parasol' );

			asrt( count( $columns ), 3 );

			asrt( isset( $columns['color'] ), true );
			asrt( isset( $columns['length'] ), true );

			R::configureFacadeWithToolbox( $toolbox );

			R::freeze( true );

			$id      = $this->createAPaintiningByMonet();

			$columns = R::$writer->getColumns( 'monet' );

			$monet   = R::load( 'monet', $id );

			$wclass  = get_class( $toolbox->getWriter() );

			asrt( $monet->born, '1840-11-14' );
			asrt( count( $monet->ownPainting ), 2 );

			foreach ( $monet->ownPainting as $painting ) {
				asrt( count( $painting->sharedGarden ), 1 );

				asrt( (
					( count( $painting->ownLilly ) === 10 && count( $painting->ownBridge ) === 1 )
					||
					( count( $painting->ownLady ) === 1 )
				), true );

				if ( count( $painting->ownLady ) === 1 ) {
					$lady = reset( $painting->ownLady );

					asrt( $lady->parasol->color, 'red' );

					asrt( ( $lady->parasol->length == 1.2 ), true );
				}

				if ( count( $painting->ownLilly ) ) {
					$lilly = reset( $painting->ownLilly );

					asrt( $lilly->color, 'purple' );

					$bridge = reset( $painting->ownBridge );

					asrt( (boolean) $bridge->broken, false );
				}

				$garden = reset( $painting->sharedGarden );

				asrt( (int) $garden->size, 10 );
			}

			R::freeze( false );

			R::configureFacadeWithToolbox( $source );
		}
	}

	/**
	 * Dispense an artist named Monet and add a painting of a garden with
	 * a bridge and ten lilies floating on the water as well as another
	 * painting of a lady in a garden. And before we dream away at the
	 * sight of these pictures return the primary key of Monet.
	 *
	 * @return integer $id
	 */
	private function createAPaintiningByMonet()
	{
		$artist              = R::dispense( 'monet' );

		$artist->born        = '1840-11-14';

		$paintings           = R::dispense( 'painting', 2 );

		$artist->ownPainting = $paintings;

		$parasol             = R::dispense( 'parasol' );

		$parasol->color      = 'red';

		$parasol->length     = 1.2;

		$bridge              = R::dispense( 'bridge' );
		$lilies             = R::dispense( 'lilly', 10 );
		$garden              = R::dispense( 'garden' );

		$garden->size        = 10;

		foreach ( $lilies as $lilly ) {
			$lilly->color = 'purple';
		}

		$bridge->broken               = false;

		$lady                         = R::dispense( 'lady' );

		$lady->parasol                = $parasol;

		$paintings[0]->sharedGarden[] = $garden;
		$paintings[1]->sharedGarden[] = $garden;

		$paintings[0]->ownLady        = array( $lady );

		$paintings[1]->ownLilly       = $lilies;

		$paintings[1]->ownBridge      = array( $bridge );

		return R::store( $artist );
	}
}
