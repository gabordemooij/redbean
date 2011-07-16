<?php
/**
 * Abstract Bean Formatter
 *
 * @file				RedBean/QueryWriter/SQLite.php
 * @description			Abstract Bean Formatter
 *						To write a driver for a different database for RedBean
 *						you should only have to change this file.
 * @author				Gabor de Mooij
 * @license				BSD
 */
abstract class RedBean_ABeanFormatter implements RedBean_IBeanFormatter {


	/**
	 * Formats a table
	 *
	 * @param string $type type
	 */
	public function formatBeanTable( $type ){
		return $type;
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
	 * @abstract
	 * 
	 * @param  $type aliased type
	 *
	 * @return string $type type
	 */
	public function getAlias( $type ) {
		return $type;
	}

}
