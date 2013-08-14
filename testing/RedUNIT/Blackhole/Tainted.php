<?php
/**
 * RedUNIT_Blackhole_Tainted
 *
 * @file    RedUNIT/Blackhole/Tainted.php
 * @desc    Tests tainted flag for OODBBean objects.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Blackhole_Tainted extends RedUNIT_Blackhole
{
	/**
	 * Test tainted.
	 * 
	 * @return void
	 */
	public function testTainted()
	{
		testpack( 'Original Tainted Tests' );

		$redbean = R::$redbean;

		$spoon = $redbean->dispense( "spoon" );

		asrt( $spoon->getMeta( "tainted" ), true );

		$spoon->dirty = "yes";

		asrt( $spoon->getMeta( "tainted" ), true );

		testpack( 'Tainted List test' );

		$note = R::dispense( 'note' );

		$note->text = 'abc';

		$note->ownNote[] = R::dispense( 'note' )->setAttr( 'text', 'def' );

		$id = R::store( $note );

		$note = R::load( 'note', $id );

		asrt( $note->isTainted(), false );

		// Shouldn't affect tainted
		$note->text;

		asrt( $note->isTainted(), false );

		$note->ownNote;

		asrt( $note->isTainted(), true );

		testpack( 'Tainted Test Old Value' );

		$text = $note->old( 'text' );

		asrt( $text, 'abc' );

		asrt( $note->hasChanged( 'text' ), false );

		$note->text = 'xxx';

		asrt( $note->hasChanged( 'text' ), true );

		$text = $note->old( 'text' );

		asrt( $text, 'abc' );

		testpack( 'Tainted Non-exist' );

		asrt( $note->hasChanged( 'text2' ), false );

		testpack( 'Misc Tainted Tests' );

		$bean = R::dispense( 'bean' );

		$bean->hasChanged( 'prop' );

		$bean->old( 'prop' );
	}
}
