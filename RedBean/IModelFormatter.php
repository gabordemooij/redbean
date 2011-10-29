<?php
/*
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 * Interface definition of a Model Formatter for Fuse
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
