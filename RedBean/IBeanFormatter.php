<?php
/**
 * @name RedBean IBeanFormatter
 * @file RedBean/IBeanFormatter.php
 * @author Gabor de Mooij and the RedBean Team
 * @copyright Gabor de Mooij (c)
 * @license BSD
 *
 * The RedBean IBeanFormatter interface describes what methods
 * a BeanFormatter class should implement.
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_IBeanFormatter {

	/**
	 *
	 * @param string $type type
	 */
	public function formatBeanTable( $type );

	/**
	 *
	 * @param string $id id
	 */
	public function formatBeanID( $id );

}