<?php
/**
 * RedBean interface for Model Formatting - Part of FUSE
 * 
 * @name 		RedBean IModelFormatter
 * @file 		RedBean/ModelFormatter.php
 * @author 		Gabor de Mooij
 * @license 	BSD
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_IModelFormatter {

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
