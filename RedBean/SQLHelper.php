<?php
/**
 * RedBean SQL Helper
 * Allows you to mix PHP and SQL as if they were one language.
 *
 * @file    RedBean/SQLHelper.php
 * @desc    Allows you to mix PHP and SQL as if they were one language
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_SQLHelper
{

	/**
	 * @var RedBean_Adapter
	 */
	protected $adapter;

	/**
	 * @var boolean
	 */
	protected $capture = FALSE;

	/**
	 * @var string
	 */
	protected $sql = '';

	/**
	 * @var boolean
	 */
	protected static $flagUseCamelCase = TRUE;

	/**
	 * @var array
	 */
	protected $params = array();

	/**
	 * Toggles support for camelCased statements.
	 * If set to TRUE this will turn camelCase into spaces.
	 * For instance leftJoin becomes
	 * 'left join'.
	 *
	 * @param boolean $yesNo TRUE to use camelcase mode
	 *
	 * @return void
	 */
	public static function useCamelCase( $yesNo )
	{
		self::$flagUseCamelCase = (boolean) $yesNo;
	}

	/**
	 * Constructor.
	 * Allows you to mix PHP and SQL as if they were one language.
	 *
	 * @param RedBean_Adapter_DBAdapter $adapter database adapter for querying
	 */
	public function __construct( RedBean_Adapter $adapter )
	{
		$this->adapter = $adapter;
	}

	/**
	 * Magic method to construct SQL query.
	 * Accepts any kind of message and turns it into an SQL statement and
	 * adds it to the query string.
	 * If camelcase is set to TRUE camelCase transitions will be turned into spaces.
	 * Underscores will be replaced with spaces as well.
	 * Arguments will be imploded using a comma as glue character and are also added
	 * to the query.
	 *
	 * If capture mode is on, this method returns a reference to itself allowing
	 * chaining.
	 *
	 * If capture mode if off, this method will immediately exceute the resulting
	 * SQL query and return a string result.
	 *
	 * @param string $funcName name of the next SQL statement/keyword
	 * @param array  $args     list of statements to be seperated by commas
	 *
	 * @return string|RedBean_SQLHelper
	 */
	public function __call( $funcName, $args = array() )
	{
		if ( self::$flagUseCamelCase ) {
			static $funcCache = array();

			if ( !isset( $funcCache[$funcName] ) ) {
				$funcCache[$funcName] = strtolower( preg_replace( '/(?<=[a-z])([A-Z])|([A-Z])(?=[a-z])/', '_$1$2', $funcName ) );
			}

			$funcName = $funcCache[$funcName];
		}

		$funcName = str_replace( '_', ' ', $funcName );

		if ( $this->capture ) {
			$this->sql .= ' ' . $funcName . ' ' . implode( ',', $args );

			return $this;
		} else {
			return $this->adapter->getCell( 'SELECT ' . $funcName . '(' . implode( ',', $args ) . ')' );
		}
	}

	/**
	 * Begins SQL query.
	 * Turns on capture mode. The helper will now postpone execution of the
	 * resulting SQL until the get() method has been invoked.
	 *
	 * @return RedBean_SQLHelper
	 */
	public function begin()
	{
		$this->capture = TRUE;

		return $this;
	}

	/**
	 * Adds a value to the parameter list.
	 * This method adds a value to the list of parameters that will be bound
	 * to the SQL query. Chainable.
	 *
	 * @param mixed $param parameter to be added
	 *
	 * @return RedBean_SQLHelper
	 */
	public function put( $param )
	{
		$this->params[] = $param;

		return $this;
	}

	/**
	 * Executes query and returns the result.
	 * In capture mode this method will execute the query you have build using
	 * this helper and return the result.
	 * The parameter determines how to retrieve the results from the query.
	 * Possible options are: 'cell', 'row', 'col' or 'all'.
	 * Use cell to obtain a single cell, row for a row, col for a column and all for
	 * a multidimensional array.
	 *
	 * @param string $retrieval One of these 'cell', 'row', 'col' or 'all'.
	 *
	 * @return mixed $result
	 */
	public function get( $what = '' )
	{
		$what = 'get' . ucfirst( $what );

		$rs   = $this->adapter->$what( $this->sql, $this->params );

		$this->clear();

		return $rs;
	}

	/**
	 * Clears the parameter list as well as the SQL query string.
	 *
	 * @return RedBean_SQLHelper
	 */
	public function clear()
	{
		$this->sql     = '';
		$this->params  = array();
		$this->capture = FALSE; //turn off capture mode (issue #142)

		return $this;
	}

	/**
	 * To explicitly add a piece of SQL.
	 *
	 * @param string $sql sql
	 *
	 * @return RedBean_SQLHelper
	 */
	public function addSQL( $sql )
	{
		if ( $this->capture ) {
			$this->sql .= ' ' . $sql . ' ';
		}

		return $this;
	}

	/**
	 * Returns query parts.
	 * This method returns the query parts in an array.
	 * This method returns an array with the following format:
	 *
	 * array(
	 *        string $sqlStatementString,
	 *        array $parameters
	 * )
	 *
	 * @return array
	 */
	public function getQuery()
	{
		$list = array( $this->sql, $this->params );
		$this->clear();

		return $list;
	}

	/**
	 * Nests another query builder query in the current query.
	 *
	 * @param RedBean_SQLHelper
	 *
	 * @return RedBean_SQLHelper
	 */
	public function nest( RedBean_SQLHelper $sqlHelper )
	{
		list( $sql, $params ) = $sqlHelper->getQuery();

		$this->sql .= $sql;

		$this->params += $params;

		return $this;
	}

	/**
	 * Writes a '(' to the sql query.
	 *
	 * @return RedBean_SQLHelper
	 */
	public function open()
	{
		if ( $this->capture ) {
			$this->sql .= ' ( ';
		}

		return $this;
	}

	/**
	 * Writes a ')' to the sql query.
	 *
	 * @return RedBean_SQLHelper
	 */
	public function close()
	{
		if ( $this->capture ) {
			$this->sql .= ' ) ';
		}

		return $this;
	}

	/**
	 * Generates question mark slots for an array of values.
	 * For each entry of the array this method generates a single
	 * question mark character slot. Finally the slots are glued
	 * separated by commas and returned as a single string.
	 *
	 * @param array $array Array with values to generate slots for
	 *
	 * @return string
	 */
	public function genSlots( $array )
	{
		if ( is_array( $array ) && count( $array ) > 0 ) {
			$filler = array_fill( 0, count( $array ), '?' );

			return implode( ',', $filler );
		} else {
			return '';
		}
	}

	/**
	 * Returns a new SQL Helper with the same adapter as the current one.
	 *
	 * @return RedBean_SQLHelper
	 */
	public function getNew()
	{
		return new self( $this->adapter );
	}
}
