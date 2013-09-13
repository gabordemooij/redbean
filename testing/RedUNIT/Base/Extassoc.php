<?php
/**
 * RedUNIT_Base_Extassoc
 *
 * @file    RedUNIT/Base/Extassoc.php
 * @desc    Tests extended associations, associations with additional properties in
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Extassoc extends RedUNIT_Base
{
	/**
	 * Test extended associations.
	 * 
	 * @return void
	 */
	public function testExtAssoc()
	{
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();

		R::nuke();

		$webpage = $redbean->dispense( "webpage" );

		$webpage->title = "page with ads";

		$ad = $redbean->dispense( "ad" );

		$ad->title = "buy this!";

		$top = $redbean->dispense( "placement" );

		$top->position = "top";

		$bottom = $redbean->dispense( "placement" );

		$bottom->position = "bottom";

		$ea = new RedBean_AssociationManager_ExtAssociationManager( $toolbox );

		$ea->extAssociate( $ad, $webpage, $top );

		$ads    = $redbean->batch( "ad", $ea->related( $webpage, "ad" ) );
		$adsPos = $redbean->batch( "ad_webpage", $ea->related( $webpage, "ad", TRUE ) );

		asrt( count( $ads ), 1 );
		asrt( count( $adsPos ), 1 );

		$theAd    = array_pop( $ads );
		$theAdPos = array_pop( $adsPos );

		asrt( $theAd->title, $ad->title );
		asrt( $theAdPos->position, $top->position );

		$ad2 = $redbean->dispense( "ad" );

		$ad2->title = "buy this too!";

		$ea->extAssociate( $ad2, $webpage, $bottom );

		$ads = $redbean->batch( "ad", $ea->related( $webpage, "ad", TRUE ) );

		asrt( count( $ads ), 2 );
	}
}
