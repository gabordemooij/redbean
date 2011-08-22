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
	 * Bean Table mapper.
	 * Formats a bean table. Given a type this function returns the name of the
	 * table to be used for querying this type of bean.
	 * This allows you to map a certain type of bean to a specified table.
	 *
	 * @param string $type type
	 *
	 * @return string $table table
	 */
	public function formatBeanTable( $type );

	/**
	 *
	 * Formats the primary key field for a certain type of bean. Given a type
	 * this function returns the ID field to be used for that type.
	 *
	 * @param string $type type
	 *
	 * @return string $id id
	 */
	public function formatBeanID( $type );

	/**
	 * Given a type, this function returns an alias.
	 * This function is used to resolve aliased types; for instance
	 * a leader is an alias of type person etc.
	 *
	 * @param  string $type type
	 *
	 * @return string $alias alias
	 */
	public function getAlias( $type );

}