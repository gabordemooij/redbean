<?php
/**
 * Default Bean Formatter
 *
 * @file				RedBean/QueryWriter/SQLite.php
 * @description			Default Bean Formatter
 * 						Specifies the default rules for formatting beans in
 * 						the database; converting them to tables and columns.
 * @author				Gabor de Mooij
 * @license				BSD
 */
class RedBean_DefaultBeanFormatter implements RedBean_IBeanFormatter {

	/**
	 * Holds the prefix to be used for tables.
	 * @var string
	 */
	protected $tablePrefixStr = '';

	/**
	 * Sets the prefix to be used for tables
	 *
	 * @param string $tablePrefixStr prefix string for tables
	 */
	public function setPrefix($prefix) {
		$this->tablePrefixStr = preg_replace('/\W/','',$prefix);
	}

	/**
	 * Formats a table
	 *
	 * @param string $type type
	 */
	public function formatBeanTable( $type ){
		return $this->tablePrefixStr.$type;
	}
	/**
	 * Formats an ID
	 *
	 * @param string $type type
	 */
	public function formatBeanID( $type ){
		return 'id';
	}
	/**
	 * Returns the alias for a type
	 *
	 * @param  $type aliased type
	 *
	 * @return string $type type
	 */
	public function getAlias( $type ) {
		if ($t = RedBean_OODBBean::$fetchType) {
			$type = $t;
			RedBean_OODBBean::$fetchType = null;
		}
		return $type;
	}
}
