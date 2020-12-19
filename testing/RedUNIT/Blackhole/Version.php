<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean;

/**
 * Version
 *
 * This test suite tests whether we can properly
 * obtain the version string. It also tests the availability
 * of 'well known' entities like R, EID() and SimpleModel.
 *
 * @file    RedUNIT/Blackhole/Version.php
 * @desc    Tests identification features.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Version extends Blackhole
{
	/**
	 * Returns all features as a single string of
	 * comma separated values. For testing only.
	 *
	 * @return string
	 */
	private function getFeatureFlags()
	{
		$features = array();
		$old = OODBBean::useFluidCount( TRUE );
		OODBBean::useFluidCount( $old );
		$features[] = intval( $old );
		$old = R::noNuke( TRUE );
		R::noNuke( $old );
		$features[] = intval( $old );
		$features[] = 0;
		$old = R::setAllowHybridMode( TRUE );
		R::setAllowHybridMode( $old );
		$features[] = intval( $old );
		$old = R::useISNULLConditions( TRUE );
		R::useISNULLConditions( $old );
		$features[] = intval( $old );
		$features = implode( ',', $features );
		return $features;
	}

	/**
	 * Test version info.
	 *
	 * @return void
	 */
	public function testVersion()
	{
		$version = R::getVersion();
		asrt( is_string( $version ), TRUE );
	}

	/**
	 * Test whether basic tools are available for use.
	 *
	 * @return void
	 */
	public function testTools()
	{
		asrt( class_exists( '\\RedBean_SimpleModel' ), TRUE );
		asrt( class_exists( '\\R' ), TRUE );
		asrt( function_exists( 'EID' ), TRUE );
	}

	/**
	 * Test whether every label returns the correct set of
	 * feature flags.
	 *
	 * @return void
	 */
	public function testFeature()
	{
		R::useFeatureSet('original');
		asrt( $this->getFeatureFlags(), '1,0,0,0,0' );
		R::useFeatureSet('5.3');
		asrt( $this->getFeatureFlags(), '1,0,0,0,0' );
		R::useFeatureSet('novice/5.3');
		asrt( $this->getFeatureFlags(), '1,1,0,0,0' );
		R::useFeatureSet('5.4');
		asrt( $this->getFeatureFlags(), '1,0,0,1,1' );
		R::useFeatureSet('latest');
		asrt( $this->getFeatureFlags(), '1,0,0,1,1' );
		R::useFeatureSet('novice/5.4');
		asrt( $this->getFeatureFlags(), '1,1,0,0,1' );
		R::useFeatureSet('5.5');
		asrt( $this->getFeatureFlags(), '1,0,0,1,1' );
		R::useFeatureSet('novice/5.5');
		asrt( $this->getFeatureFlags(), '1,1,0,0,1' );
		R::useFeatureSet('novice/latest');
		asrt( $this->getFeatureFlags(), '1,1,0,0,1' );
		R::useFeatureSet('original');
		asrt( $this->getFeatureFlags(), '1,0,0,0,0' );
	}

	/**
	 * Test whether an invalid feature set label will
	 * cause an exception.
	 *
	 * @return void
	 */
	public function testInvalidFeatureLabel()
	{
		try {
			R::useFeatureSet('Invalid');
			fail();
		} catch( \Exception $e ) {
			pass();
		}
	}
}
