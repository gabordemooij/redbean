<?php

namespace RedBeanPHP\Util;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;

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
	 * Usage:
	 * R::useFeatureSet( 'novice/latest' );
	 *
	 * @param string $label label
	 *
	 * @return void
	 */
	public static function feature( $label ) {
		switch( $label ) {
			case "novice/latest":
				AQueryWriter::noNuke( TRUE );
				R::setAutoResolve( TRUE );
				break;
			case "latest":
				AQueryWriter::noNuke( TRUE );
				R::setAutoResolve( TRUE );
				break;
			case "novice/5.3":
				AQueryWriter::noNuke( TRUE );
				R::setAutoResolve( TRUE );
				break;
			case "5.3":
				AQueryWriter::noNuke( TRUE );
				R::setAutoResolve( TRUE );
				break;
			default:
				throw new \Exception("Unknown feature set label.");
				break;
		}
	}
}
