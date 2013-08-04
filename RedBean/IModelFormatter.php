<?php
/**
 * RedBean interface for Model Formatting - Part of FUSE
 *
 * @file       RedBean/ModelFormatter.php
 * @desc       RedBean IModelFormatter
 * @author     Gabor de Mooij and the RedBeanPHP Community
 * @license    BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_IModelFormatter
{
	/**
	 * ModelHelper will call this method of the class
	 * you provide to discover the model
	 *
	 * @param string $model
	 *
	 * @return string $formattedModel
	 */
	public function formatModel( $model );
}
