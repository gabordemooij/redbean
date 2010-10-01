<?php
/**
 * RedBean Abstract Query Writer
 * @file 		RedBean/QueryWriter/AQueryWriter.php
 * @description
 *					Represents an abstract Database to RedBean
 *					To write a driver for a different database for RedBean
 *					Contains a number of functions all implementors can
 *					inherit or override.
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

abstract class RedBean_AQueryWriter {

	public $tableFormatter;

	/**
	 * Returns the string identifying a table for a given type.
	 * @param string $type
	 * @return string $table
	 */
	public function getFormattedTableName($type) {
		if ($this->tableFormatter) return $this->tableFormatter->formatBeanTable($type);
		return $type;
	}


	/**
	 * Returns the column name that should be used
	 * to store and retrieve the primary key ID.
	 * @param string $type
	 * @return string $idfieldtobeused
	 */
	public function getIDField( $type ) {
		if ($this->tableFormatter) return $this->tableFormatter->formatBeanID($type);
		return  "id";
	}

	/**
	 * Sets the Bean Formatter to be used to handle
	 * custom/advanced DB<->Bean
	 * Mappings.
	 * @param RedBean_IBeanFormatter $beanFormatter
	 * @return void
	 */
	public function setBeanFormatter( RedBean_IBeanFormatter $beanFormatter ) {
		$this->tableFormatter = $beanFormatter;
	}


}