<?php

/**
 * Support functions for RedBeanPHP.
 *
 * @file    RedBeanPHP/Functions.php
 * @desc    Additional convenience shortcut functions for RedBeanPHP
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

/**
 * Convenience function for ENUM short syntax in queries.
 * 
 * Usage:
 * 
 * R::find( 'paint', ' color_id = ? ', [ EID('color:yellow') ] );
 * 
 * @param string $enumName enum code as you would pass to R::enum()
 *
 * @return mixed
 */
function EID($enumName) {
	return \RedBeanPHP\Facade::enum($enumName)->id;
}