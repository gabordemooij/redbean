<?php

namespace RedBeanPHP\Util;
use RedBeanPHP\Facade as R;

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
	 *
	 * latest:
	 * - allow R::nuke()
	 * - enable auto resolve
	 * - allow hybrid mode
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
			case "novice/latest":
			case "novice/5.4":
				R::noNuke( TRUE );
				R::setAutoResolve( TRUE );
				R::setAllowHybridMode( FALSE );
				R::useISNULLConditions( TRUE );
				break;
			case "latest":
			case "5.4":
				R::noNuke( FALSE );
				R::setAutoResolve( TRUE );
				R::setAllowHybridMode( TRUE );
				break;
			case "novice/5.3":
				R::noNuke( TRUE );
				R::setAutoResolve( TRUE );
				R::setAllowHybridMode( FALSE );
				break;
			case "5.3":
				R::noNuke( FALSE );
				R::setAutoResolve( TRUE );
				R::setAllowHybridMode( FALSE );
				break;
			default:
				throw new \Exception("Unknown feature set label.");
				break;
		}
	}
}
