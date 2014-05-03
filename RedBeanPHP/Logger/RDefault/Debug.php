<?php

namespace RedBeanPHP\Logger\RDefault;

use RedBeanPHP\Logger as Logger;
use RedBeanPHP\Logger\RDefault as RDefault;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\RedException\Security as Security;

/**
 * Debug logger.
 * A special logger for debugging purposes.
 *
 * @file    RedBean/Logger/RDefault/Debug.php
 * @desc    Debug Logger
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * Provides a debugging logging functions for RedBeanPHP.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Debug extends RDefault implements Logger
{

	/**
	 * Writes a query for logging with all bindings / params filled
	 * in.
	 *
	 * @param string $newSql   the query
	 * @param array  $bindings the bindings to process (key-value pairs)
	 *
	 * @return string
	 */
	private function writeQuery( $newSql, $newBindings )
	{
		$newStr = $newSql;
		foreach( $newBindings as $slot => $value ) {
			if ( strpos( $slot, ':' ) === 0 ) {
				$newStr = str_replace( $slot, $this->fillInValue( $value ), $newStr );
			}
		}
		return $newStr;
	}

	/**
	 * Fills in a value of a binding and truncates the
	 * resulting string if necessary.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	protected function fillInValue( $value )
	{
		if ( is_null( $value ) ) $value = 'NULL';

		$value = strval( $value );
		if ( strlen( $value ) > 20 ) {
			$value = substr( $value, 0, 20 ).'... ';
		}

		if ( !is_numeric( $value ) && $value !== 'NULL') {
			$value = '\''.$value.'\'';
		}

		return $value;
	}

	/**
	 * Dependending on the current mode of operation,
	 * this method will either log and output to STDIN or
	 * just log.
	 *
	 * @param string $str string to log or output and log
	 *
	 * @return void
	 */
	protected function output( $str )
	{
		$this->logs[] = $str;
		if ( !$this->mode ) echo $str .'<br />';
	}

	/**
	 * Normalizes the slots in an SQL string.
	 * Replaces question mark slots with :slot1 :slot2 etc.
	 *
	 * @param string $sql sql to normalize
	 *
	 * @return string
	 */
	protected function normalizeSlots( $sql )
	{
		$i = 0;
		$newSql = $sql;
		while($i < 20 && strpos($newSql, '?') !== FALSE ){
			$pos   = strpos( $newSql, '?' );
			$slot  = ':slot'.$i;
			$begin = substr( $newSql, 0, $pos );
			$end   = substr( $newSql, $pos+1 );
			$newSql = $begin . $slot . $end;
			$i++;
		}
		return $newSql;
	}

	/**
	 * Normalizes the bindings.
	 * Replaces numeric binding keys with :slot1 :slot2 etc.
	 *
	 * @param array $bindings bindings to normalize
	 *
	 * @return array
	 */
	protected function normalizeBindings( $bindings )
	{
		$i = 0;
		$newBindings = array();
		foreach( $bindings as $key => $value ) {
			if ( is_numeric($key) ) {
				$newKey = ':slot'.$i;
				$newBindings[$newKey] = $value;
				$i++;
			} else {
				$newBindings[$key] = $value;
			}
		}
		return $newBindings;
	}

	/**
	 * Logger method.
	 *
	 * Takes a number of arguments tries to create
	 * a proper debug log based on the available data.
	 *
	 * @return void
	 */
	public function log()
	{
		if ( func_num_args() < 1 ) return;

		$sql = func_get_arg( 0 );

		if ( func_num_args() < 2) {
			$bindings = array();
		} else {
			$bindings = func_get_arg( 1 );
		}

		if ( !is_array( $bindings ) ) {
			return $this->output( $sql );
		}

		$newSql = $this->normalizeSlots( $sql );
		$newBindings = $this->normalizeBindings( $bindings );
		$newStr = $this->writeQuery( $newSql, $newBindings );
		$this->output( $newStr );
	}
}
