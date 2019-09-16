<?php

namespace RedBeanPHP\Util;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean;

/**
 * Feature Utility
 *
 * The Feature Utility class provides an easy way to turn
 * on or off features. This allows us to introduce new features
 * without accidentally breaking backward compatibility.
 *
 * @file    RedBeanPHP/Util/Feature.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Feature
{
	/* Feature set constants */
	const C_FEATURE_NOVICE_LATEST = 'novice/latest';
	const C_FEATURE_LATEST        = 'latest';
	const C_FEATURE_NOVICE_5_4    = 'novice/5.4';
	const C_FEATURE_5_4           = '5.4';
	const C_FEATURE_NOVICE_5_3    = 'novice/5.3';
	const C_FEATURE_5_3           = '5.3';
	const C_FEATURE_ORIGINAL      = 'original';

	/**
	 * Selects the feature set you want as specified by
	 * the label.
	 *
	 * Available labels:
	 *
	 * novice/latest:
	 * - forbid R::nuke()
	 * - enable automatic relation resolver based on foreign keys
	 * - forbid R::store(All)( $bean, TRUE ) (Hybrid mode)
	 * - use IS-NULL conditions in findLike() etc
	 *
	 * latest:
	 * - allow R::nuke()
	 * - enable auto resolve
	 * - allow hybrid mode
	 * - use IS-NULL conditions in findLike() etc
	 *
	 * novice/X or X:
	 * - keep everything as it was in version X
	 *
	 * Usage:
	 *
	 * <code>
	 * R::useFeatureSet( 'novice/latest' );
	 * </code>
	 *
	 * @param string $label label
	 *
	 * @return void
	 */
	public static function feature( $label ) {
		switch( $label ) {
			case self::C_FEATURE_NOVICE_LATEST:
			case self::C_FEATURE_NOVICE_5_4:
				OODBBean::useFluidCount( FALSE );
				R::noNuke( TRUE );
				R::setAutoResolve( TRUE );
				R::setAllowHybridMode( FALSE );
				R::useISNULLConditions( TRUE );
				break;
			case self::C_FEATURE_LATEST:
			case self::C_FEATURE_5_4:
				OODBBean::useFluidCount( FALSE );
				R::noNuke( FALSE );
				R::setAutoResolve( TRUE );
				R::setAllowHybridMode( TRUE );
				R::useISNULLConditions( TRUE );
				break;
			case self::C_FEATURE_NOVICE_5_3:
				OODBBean::useFluidCount( TRUE );
				R::noNuke( TRUE );
				R::setAutoResolve( TRUE );
				R::setAllowHybridMode( FALSE );
				R::useISNULLConditions( FALSE );
				break;
			case self::C_FEATURE_5_3:
				OODBBean::useFluidCount( TRUE );
				R::noNuke( FALSE );
				R::setAutoResolve( TRUE );
				R::setAllowHybridMode( FALSE );
				R::useISNULLConditions( FALSE );
				break;
			case self::C_FEATURE_ORIGINAL:
				OODBBean::useFluidCount( TRUE );
				R::noNuke( FALSE );
				R::setAutoResolve( FALSE );
				R::setAllowHybridMode( FALSE );
				R::useISNULLConditions( FALSE );
				break;
			default:
				throw new \Exception("Unknown feature set label.");
				break;
		}
	}
}
