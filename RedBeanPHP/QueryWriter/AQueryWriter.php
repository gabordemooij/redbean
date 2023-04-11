<?php

namespace RedBeanPHP\QueryWriter;

use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\RedException\SQL as SQLException;

/**
 * RedBeanPHP Abstract Query Writer.
 * Represents an abstract Database to RedBean
 * To write a driver for a different database for RedBean
 * Contains a number of functions all implementors can
 * inherit or override.
 *
 * @file    RedBeanPHP/QueryWriter/AQueryWriter.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class AQueryWriter
{
	/**
	 * Constant: Select Snippet 'FOR UPDATE'
	 */
	const C_SELECT_SNIPPET_FOR_UPDATE = 'FOR UPDATE';
	const C_DATA_TYPE_ONLY_IF_NOT_EXISTS = 80;
	const C_DATA_TYPE_MANUAL = 99;

	/**
	 * @var array
	 */
	private static $sqlFilters = array();

	/**
	 * @var boolean
	 */
	private static $flagSQLFilterSafeMode = FALSE;

	/**
	 * @var boolean
	 */
	private static $flagNarrowFieldMode = TRUE;

	/**
	 * @var boolean
	 */
	protected static $flagUseJSONColumns = FALSE;

	/**
	 * @var boolean
	 */
	protected static $enableISNULLConditions = FALSE;

	/**
	 * @var array
	 */
	public static $renames = array();

	/**
	 * @var DBAdapter
	 */
	protected $adapter;

	/**
	 * @var string
	 */
	protected $defaultValue = 'NULL';

	/**
	 * @var string
	 */
	protected $quoteCharacter = '';

	/**
	 * @var boolean
	 */
	protected $flagUseCache = TRUE;

	/**
	 * @var array
	 */
	protected $cache = array();

	/**
	 * @var integer
	 */
	protected $maxCacheSizePerType = 20;

	/**
	 * @var string
	 */
	protected $sqlSelectSnippet = '';

	/**
	 * @var array
	 */
	public $typeno_sqltype = array();

	/**
	 * @var array
	 */
	public $sqltype_typeno = array();

	/**
	 * @var array
	 */
	public $encoding = array();

	/**
	 * @var bool
	 */
	protected static $noNuke = false;

	/**
	 * @var bool
	 */
	protected static $treatFalseAsInt = FALSE;

	/**
	 * Sets a data definition template to change the data
	 * creation statements per type.
	 *
	 * For instance to add  ROW_FORMAT=DYNAMIC to all MySQL tables
	 * upon creation:
	 *
	 * <code>
	 * $sql = $writer->getDDLTemplate( 'createTable', '*' );
	 * $writer->setDDLTemplate( 'createTable', '*', $sql . '  ROW_FORMAT=DYNAMIC ' );
	 * </code>
	 *
	 * For property-specific templates set $beanType to:
	 * account.username -- then the template will only be applied to SQL statements relating
	 * to that column/property.
	 *
	 * @param string $type     ( 'createTable' | 'widenColumn' | 'addColumn' )
	 * @param string $beanType ( type of bean or '*' to apply to all types )
	 * @param string $template SQL template, contains %s for slots
	 *
	 * @return void
	 */
	public function setDDLTemplate( $type, $beanType, $template )
	{
		$this->DDLTemplates[ $type ][ $beanType ] = $template;
	}

	/**
	 * Returns the specified data definition template.
	 * If no template can be found for the specified type, the template for
	 * '*' will be returned instead.
	 *
	 * @param string      $type     ( 'createTable' | 'widenColumn' | 'addColumn' )
	 * @param string      $beanType ( type of bean or '*' to apply to all types )
	 * @param string|NULL $property specify if you're looking for a property-specific template
	 *
	 * @return string
	 */
	public function getDDLTemplate( $type, $beanType = '*', $property = NULL )
	{
		$key = ( $property ) ? "{$beanType}.{$property}" : $beanType;
		if ( isset( $this->DDLTemplates[ $type ][ $key ] ) ) {
			return $this->DDLTemplates[ $type ][ $key ];
		}
		if ( isset( $this->DDLTemplates[ $type ][ $beanType ] ) ) {
			return $this->DDLTemplates[ $type ][ $beanType ];
		}
		return $this->DDLTemplates[ $type ][ '*' ];
	}

	/**
	 * Toggles support for IS-NULL-conditions.
	 * If IS-NULL-conditions are enabled condition arrays
	 * for functions including findLike() are treated so that
	 * 'field' => NULL will be interpreted as field IS NULL
	 * instead of being skipped. Returns the previous
	 * value of the flag.
	 *
	 * @param boolean $flag TRUE or FALSE
	 *
	 * @return boolean
	 */
	public static function useISNULLConditions( $flag )
	{
		$old = self::$enableISNULLConditions;
		self::$enableISNULLConditions = $flag;
		return $old;
	}

	/**
	 * Toggles support for automatic generation of JSON columns.
	 * Using JSON columns means that strings containing JSON will
	 * cause the column to be created (not modified) as a JSON column.
	 * However it might also trigger exceptions if this means the DB attempts to
	 * convert a non-json column to a JSON column. Returns the previous
	 * value of the flag.
	 *
	 * @param boolean $flag TRUE or FALSE
	 *
	 * @return boolean
	 */
	public static function useJSONColumns( $flag )
	{
		$old = self::$flagUseJSONColumns;
		self::$flagUseJSONColumns = $flag;
		return $old;
	}

	/**
	 * Toggles support for nuke().
	 * Can be used to turn off the nuke() feature for security reasons.
	 * Returns the old flag value.
	 *
	 * @param boolean $flag TRUE or FALSE
	 *
	 * @return boolean
	 */
	public static function forbidNuke( $flag ) {
		$old = self::$noNuke;
		self::$noNuke = (bool) $flag;
		return $old;
	}

	/**
	 * If set to TRUE, this will cause SQL bindings with an
	 * explicit FALSE value to convert to 0 instead of ''.
	 * Returns the old flag value.
	 *
	 * @param boolean $flag TRUE or FALSE
	 *
	 * @return boolean
	 */
	public static function treatFalseBindingsAsInt( $flag ) {
		$old = self::$treatFalseAsInt;
		self::$treatFalseAsInt = (bool) $flag;
		return $old;
	}

	/**
	 * Checks whether a number can be treated like an int.
	 *
	 * @param  string $value string representation of a certain value
	 *
	 * @return boolean
	 */
	public static function canBeTreatedAsInt( $value )
	{
		return (bool) ( strval( ($value === FALSE && self::$treatFalseAsInt) ? 0 : $value ) === strval( intval( $value ) ) );
	}

	/**
	 * @see QueryWriter::getAssocTableFormat
	 */
	public static function getAssocTableFormat( $types )
	{
		sort( $types );

		$assoc = implode( '_', $types );

		return ( isset( self::$renames[$assoc] ) ) ? self::$renames[$assoc] : $assoc;
	}

	/**
	 * @see QueryWriter::renameAssociation
	 */
	public static function renameAssociation( $from, $to = NULL )
	{
		if ( is_array( $from ) ) {
			foreach ( $from as $key => $value ) self::$renames[$key] = $value;

			return;
		}

		self::$renames[$from] = $to;
	}

	/**
	 * Globally available service method for RedBeanPHP.
	 * Converts a camel cased string to a snake cased string.
	 *
	 * @param string $camel camelCased string to convert to snake case
	 *
	 * @return string
	 */
	public static function camelsSnake( $camel )
	{
		return strtolower( preg_replace( '/(?<=[a-z])([A-Z])|([A-Z])(?=[a-z])/', '_$1$2', $camel ) );
	}

	/**
	 * Globally available service method for RedBeanPHP.
	 * Converts a snake cased string to a camel cased string.
	 *
	 * @param string  $snake   snake_cased string to convert to camelCase
	 * @param boolean $dolphin exception for Ids - (bookId -> bookID)
	 *                         too complicated for the human mind, only dolphins can understand this
	 *
	 * @return string
	 */
	public static function snakeCamel( $snake, $dolphinMode = false )
	{
		$camel = lcfirst( str_replace(' ', '', ucwords( str_replace('_', ' ', $snake ) ) ) );
		if ( $dolphinMode ) {
			$camel = preg_replace( '/(\w)Id$/', '$1ID', $camel );
		}
		return $camel;
	}

	/**
	 * Clears renames.
	 *
	 * @return void
	 */
	public static function clearRenames()
	{
		self::$renames = array();
	}

	/**
	 * Toggles 'Narrow Field Mode'.
	 * In Narrow Field mode the queryRecord method will
	 * narrow its selection field to
	 *
	 * SELECT {table}.*
	 *
	 * instead of
	 *
	 * SELECT *
	 *
	 * This is a better way of querying because it allows
	 * more flexibility (for instance joins). However if you need
	 * the wide selector for backward compatibility; use this method
	 * to turn OFF Narrow Field Mode by passing FALSE.
	 * Default is TRUE.
	 *
	 * @param boolean $narrowField TRUE = Narrow Field FALSE = Wide Field
	 *
	 * @return void
	 */
	public static function setNarrowFieldMode( $narrowField )
	{
		self::$flagNarrowFieldMode = (boolean) $narrowField;
	}

	/**
	 * Sets SQL filters.
	 * This is a lowlevel method to set the SQL filter array.
	 * The format of this array is:
	 *
	 * <code>
	 * array(
	 * 		'<MODE, i.e. 'r' for read, 'w' for write>' => array(
	 * 			'<TABLE NAME>' => array(
	 * 				'<COLUMN NAME>' => '<SQL>'
	 * 			)
	 * 		)
	 * )
	 * </code>
	 *
	 * Example:
	 *
	 * <code>
	 * array(
	 *   QueryWriter::C_SQLFILTER_READ => array(
	 * 	'book' => array(
	 * 		'title' => ' LOWER(book.title) '
	 * 	)
	 * )
	 * </code>
	 *
	 * Note that you can use constants instead of magical chars
	 * as keys for the uppermost array.
	 * This is a lowlevel method. For a more friendly method
	 * please take a look at the facade: R::bindFunc().
	 *
	 * @param array list of filters to set
	 *
	 * @return void
	 */
	public static function setSQLFilters( $sqlFilters, $safeMode = FALSE )
	{
		self::$flagSQLFilterSafeMode = (boolean) $safeMode;
		self::$sqlFilters = $sqlFilters;
	}

	/**
	 * Returns current SQL Filters.
	 * This method returns the raw SQL filter array.
	 * This is a lowlevel method. For a more friendly method
	 * please take a look at the facade: R::bindFunc().
	 *
	 * @return array
	 */
	public static function getSQLFilters()
	{
		return self::$sqlFilters;
	}

	/**
	 * Returns a cache key for the cache values passed.
	 * This method returns a fingerprint string to be used as a key to store
	 * data in the writer cache.
	 *
	 * @param array $keyValues key-value to generate key for
	 *
	 * @return string
	 */
	private function getCacheKey( $keyValues )
	{
		return json_encode( $keyValues );
	}

	/**
	 * Returns the values associated with the provided cache tag and key.
	 *
	 * @param string $cacheTag cache tag to use for lookup
	 * @param string $key      key to use for lookup
	 *
	 * @return mixed
	 */
	private function getCached( $cacheTag, $key )
	{
		$sql = $this->adapter->getSQL();
		if ($this->updateCache()) {
			if ( isset( $this->cache[$cacheTag][$key] ) ) {
				return $this->cache[$cacheTag][$key];
			}
		}

		return NULL;
	}

	/**
	 * Checks if the previous query had a keep-cache tag.
	 * If so, the cache will persist, otherwise the cache will be flushed.
	 *
	 * Returns TRUE if the cache will remain and FALSE if a flush has
	 * been performed.
	 *
	 * @return boolean
	 */
	private function updateCache()
	{
		$sql = $this->adapter->getSQL();
		if ( strpos( $sql, '-- keep-cache' ) !== strlen( $sql ) - 13 ) {
			// If SQL has been taken place outside of this method then something else then
			// a select query might have happened! (or instruct to keep cache)
			$this->cache = array();
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Stores data from the writer in the cache under a specific key and cache tag.
	 * A cache tag is used to make sure the cache remains consistent. In most cases the cache tag
	 * will be the bean type, this makes sure queries associated with a certain reference type will
	 * never contain conflicting data.
	 * Why not use the cache tag as a key? Well
	 * we need to make sure the cache contents fits the key (and key is based on the cache values).
	 * Otherwise it would be possible to store two different result sets under the same key (the cache tag).
	 *
	 * In previous versions you could only store one key-entry, I have changed this to
	 * improve caching efficiency (issue #400).
	 *
	 * @param string    $cacheTag cache tag (secondary key)
	 * @param string    $key      key to store values under
	 * @param array|int $values   rows or count to be stored
	 *
	 * @return void
	 */
	private function putResultInCache( $cacheTag, $key, $values )
	{
		if ( isset( $this->cache[$cacheTag] ) ) {
			if ( count( $this->cache[$cacheTag] ) > $this->maxCacheSizePerType ) array_shift( $this->cache[$cacheTag] );
		} else {
			$this->cache[$cacheTag] = array();
		}
		$this->cache[$cacheTag][$key] = $values;
	}

	/**
	 * Creates an SQL snippet from a list of conditions of format:
	 *
	 * <code>
	 * array(
	 *    key => array(
	 *           value1, value2, value3 ....
	 *        )
	 * )
	 * </code>
	 *
	 * @param array  $conditions list of conditions
	 * @param array  $bindings   parameter bindings for SQL snippet
	 * @param string $addSql     additional SQL snippet to append to result
	 *
	 * @return string
	 */
	private function makeSQLFromConditions( $conditions, &$bindings, $addSql = '' )
	{
		reset( $bindings );
		$firstKey       = key( $bindings );
		$paramTypeIsNum = ( is_numeric( $firstKey ) );
		$counter        = 0;

		$sqlConditions = array();
		foreach ( $conditions as $column => $values ) {
			if ( $values === NULL ) {
				if ( self::$enableISNULLConditions ) {
					$sqlConditions[] = $this->esc( $column ) . ' IS NULL';
				}
				continue;
			}

			if ( is_array( $values ) ) {
				if ( empty( $values ) ) continue;
			} else {
				$values = array( $values );
			}

			$checkOODB = reset( $values );
			if ( $checkOODB instanceof OODBBean && $checkOODB->getMeta( 'type' ) === $column && substr( $column, -3 ) != '_id' )
				$column = $column . '_id';


			$sql = $this->esc( $column );
			$sql .= ' IN ( ';

			if ( $paramTypeIsNum ) {
				$sql .= implode( ',', array_fill( 0, count( $values ), '?' ) ) . ' ) ';

				array_unshift($sqlConditions, $sql);

				foreach ( $values as $k => $v ) {
					if ( $v instanceof OODBBean ) {
						$v = $v->id;
					}
					$values[$k] = strval( $v );

					array_unshift( $bindings, $v );
				}
			} else {

				$slots = array();

				foreach( $values as $k => $v ) {
					if ( $v instanceof OODBBean ) {
						$v = $v->id;
					}
					$slot            = ':slot'.$counter++;
					$slots[]         = $slot;
					$bindings[$slot] = strval( $v );
				}

				$sql .= implode( ',', $slots ).' ) ';
				$sqlConditions[] = $sql;
			}
		}

		$sql = '';
		if ( !empty( $sqlConditions ) ) {
			$sql .= " WHERE ( " . implode( ' AND ', $sqlConditions ) . ") ";
		}

		$addSql = $this->glueSQLCondition( $addSql, !empty( $sqlConditions ) ? QueryWriter::C_GLUE_AND : NULL );
		if ( $addSql ) $sql .= $addSql;

		return $sql;
	}

	/**
	 * Returns the table names and column names for a relational query.
	 *
	 * @param string  $sourceType type of the source bean
	 * @param string  $destType   type of the bean you want to obtain using the relation
	 * @param boolean $noQuote    TRUE if you want to omit quotes
	 *
	 * @return array
	 */
	private function getRelationalTablesAndColumns( $sourceType, $destType, $noQuote = FALSE )
	{
		$linkTable   = $this->esc( $this->getAssocTable( array( $sourceType, $destType ) ), $noQuote );
		$sourceCol   = $this->esc( $sourceType . '_id', $noQuote );

		if ( $sourceType === $destType ) {
			$destCol = $this->esc( $destType . '2_id', $noQuote );
		} else {
			$destCol = $this->esc( $destType . '_id', $noQuote );
		}

		$sourceTable = $this->esc( $sourceType, $noQuote );
		$destTable   = $this->esc( $destType, $noQuote );

		return array( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol );
	}

	/**
	 * Determines whether a string can be considered JSON or not.
	 * This is used by writers that support JSON columns. However
	 * we don't want that code duplicated over all JSON supporting
	 * Query Writers.
	 *
	 * @param string $value value to determine 'JSONness' of.
	 *
	 * @return boolean
	 */
	protected function isJSON( $value )
	{
		return (
			is_string($value) &&
			is_array(json_decode($value, TRUE)) &&
			(json_last_error() == JSON_ERROR_NONE)
		);
	}

	/**
	 * Given a type and a property name this method
	 * returns the foreign key map section associated with this pair.
	 *
	 * @param string $type     name of the type
	 * @param string $property name of the property
	 *
	 * @return array|NULL
	 */
	protected function getForeignKeyForTypeProperty( $type, $property )
	{
		$property = $this->esc( $property, TRUE );

		try {
			$map = $this->getKeyMapForType( $type );
		} catch ( SQLException $e ) {
			return NULL;
		}

		foreach( $map as $key ) {
			if ( $key['from'] === $property ) return $key;
		}
		return NULL;
	}

	/**
	 * Returns the foreign key map (FKM) for a type.
	 * A foreign key map describes the foreign keys in a table.
	 * A FKM always has the same structure:
	 *
	 * <code>
	 * array(
	 * 	'name'      => <name of the foreign key>
	 *    'from'      => <name of the column on the source table>
	 *    'table'     => <name of the target table>
	 *    'to'        => <name of the target column> (most of the time 'id')
	 *    'on_update' => <update rule: 'SET NULL','CASCADE' or 'RESTRICT'>
	 *    'on_delete' => <delete rule: 'SET NULL','CASCADE' or 'RESTRICT'>
	 * )
	 * </code>
	 *
	 * @note the keys in the result array are FKDLs, i.e. descriptive unique
	 * keys per source table. Also see: AQueryWriter::makeFKLabel for details.
	 *
	 * @param string $type the bean type you wish to obtain a key map of
	 *
	 * @return array
	 */
	protected function getKeyMapForType( $type )
	{
		return array();
	}

	/**
	 * This method makes a key for a foreign key description array.
	 * This key is a readable string unique for every source table.
	 * This uniform key is called the FKDL Foreign Key Description Label.
	 * Note that the source table is not part of the FKDL because
	 * this key is supposed to be 'per source table'. If you wish to
	 * include a source table, prefix the key with 'on_table_<SOURCE>_'.
	 *
	 * @param string $from  the column of the key in the source table
	 * @param string $type  the type (table) where the key points to
	 * @param string $to    the target column of the foreign key (mostly just 'id')
	 *
	 * @return string
	 */
	protected function makeFKLabel($from, $type, $to)
	{
		return "from_{$from}_to_table_{$type}_col_{$to}";
	}

	/**
	 * Returns an SQL Filter snippet for reading.
	 *
	 * @param string $type type of bean
	 *
	 * @return string
	 */
	protected function getSQLFilterSnippet( $type )
	{
		$existingCols = array();
		if (self::$flagSQLFilterSafeMode) {
			$existingCols = $this->getColumns( $type );
		}

		$sqlFilters = array();
		if ( isset( self::$sqlFilters[QueryWriter::C_SQLFILTER_READ][$type] ) ) {
			foreach( self::$sqlFilters[QueryWriter::C_SQLFILTER_READ][$type] as $property => $sqlFilter ) {
				if ( !self::$flagSQLFilterSafeMode || isset( $existingCols[$property] ) ) {
					$sqlFilters[] = $sqlFilter.' AS '.$property.' ';
				}
			}
		}
		$sqlFilterStr = ( count($sqlFilters) ) ? ( ','.implode( ',', $sqlFilters ) ) : '';
		return $sqlFilterStr;
	}

	/**
	 * Generates a list of parameters (slots) for an SQL snippet.
	 * This method calculates the correct number of slots to insert in the
	 * SQL snippet and determines the correct type of slot. If the bindings
	 * array contains named parameters this method will return named ones and
	 * update the keys in the value list accordingly (that's why we use the &).
	 *
	 * If you pass an offset the bindings will be re-added to the value list.
	 * Some databases cant handle duplicate parameter names in queries.
	 *
	 * @param array   &$valueList    list of values to generate slots for (gets modified if needed)
	 * @param array   $otherBindings list of additional bindings
	 * @param integer $offset        start counter at...
	 *
	 * @return string
	 */
	protected function getParametersForInClause( &$valueList, $otherBindings, $offset = 0 )
	{
		if ( is_array( $otherBindings ) && count( $otherBindings ) > 0 ) {
			reset( $otherBindings );

			$key = key( $otherBindings );

			if ( !is_numeric($key) ) {
				$filler  = array();
				$newList = (!$offset) ? array() : $valueList;
				$counter = $offset;

				foreach( $valueList as $value ) {
					$slot           = ':slot' . ( $counter++ );
					$filler[]       = $slot;
					$newList[$slot] = $value;
				}

				// Change the keys!
				$valueList = $newList;

				return implode( ',', $filler );
			}
		}

		return implode( ',', array_fill( 0, count( $valueList ), '?' ) );
	}

	/**
	 * Adds a data type to the list of data types.
	 * Use this method to add a new column type definition to the writer.
	 * Used for UUID support.
	 *
	 * @param integer $dataTypeID    magic number constant assigned to this data type
	 * @param string  $SQLDefinition SQL column definition (e.g. INT(11))
	 *
	 * @return self
	 */
	protected function addDataType( $dataTypeID, $SQLDefinition )
	{
		$this->typeno_sqltype[ $dataTypeID ] = $SQLDefinition;
		$this->sqltype_typeno[ $SQLDefinition ] = $dataTypeID;
		return $this;
	}

	/**
	 * Returns the sql that should follow an insert statement.
	 *
	 * @param string $table name
	 *
	 * @return string
	 */
	protected function getInsertSuffix( $table )
	{
		return '';
	}

	/**
	 * Checks whether a value starts with zeros. In this case
	 * the value should probably be stored using a text datatype instead of a
	 * numerical type in order to preserve the zeros.
	 *
	 * @param string $value value to be checked.
	 *
	 * @return boolean
	 */
	protected function startsWithZeros( $value )
	{
		$value = strval( $value );

		if ( strlen( $value ) > 1 && strpos( $value, '0' ) === 0 && strpos( $value, '0.' ) !== 0 ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Inserts a record into the database using a series of insert columns
	 * and corresponding insertvalues. Returns the insert id.
	 *
	 * @param string $table         table to perform query on
	 * @param array  $insertcolumns columns to be inserted
	 * @param array  $insertvalues  values to be inserted
	 *
	 * @return integer
	 */
	protected function insertRecord( $type, $insertcolumns, $insertvalues )
	{
		$default = $this->defaultValue;
		$suffix  = $this->getInsertSuffix( $type );
		$table   = $this->esc( $type );

		if ( count( $insertvalues ) > 0 && is_array( $insertvalues[0] ) && count( $insertvalues[0] ) > 0 ) {

			$insertSlots = array();
			foreach ( $insertcolumns as $k => $v ) {
				$insertcolumns[$k] = $this->esc( $v );

				if (isset(self::$sqlFilters['w'][$type][$v])) {
					$insertSlots[] = self::$sqlFilters['w'][$type][$v];
				} else {
					$insertSlots[] = '?';
				}
			}

			$insertSQL = "INSERT INTO $table ( id, " . implode( ',', $insertcolumns ) . " ) VALUES
			( $default, " . implode( ',', $insertSlots ) . " ) $suffix";

			$ids = array();
			foreach ( $insertvalues as $i => $insertvalue ) {
				$ids[] = $this->adapter->getCell( $insertSQL, $insertvalue, $i );
			}

			$result = count( $ids ) === 1 ? array_pop( $ids ) : $ids;
		} else {
			$result = $this->adapter->getCell( "INSERT INTO $table (id) VALUES($default) $suffix" );
		}

		if ( $suffix ) return $result;

		$last_id = $this->adapter->getInsertID();

		return $last_id;
	}

	/**
	 * Checks table name or column name.
	 *
	 * @param string $table table string
	 *
	 * @return string
	 */
	protected function check( $struct )
	{
		if ( !is_string( $struct ) || !preg_match( '/^[a-zA-Z0-9_]+$/', $struct ) ) {
			throw new RedException( 'Identifier does not conform to RedBeanPHP security policies.' );
		}

		return $struct;
	}

	/**
	 * Checks whether the specified type (i.e. table) already exists in the database.
	 * Not part of the Object Database interface!
	 *
	 * @param string $table table name
	 *
	 * @return boolean
	 */
	public function tableExists( $table )
	{
		$tables = $this->getTables();

		return in_array( $table, $tables );
	}

	/**
	 * @see QueryWriter::glueSQLCondition
	 */
	public function glueSQLCondition( $sql, $glue = NULL )
	{
		static $snippetCache = array();

		if ( is_null( $sql ) ) {
			return '';
		}

		if ( trim( $sql ) === '' ) {
			return $sql;
		}

		$key = $glue . '|' . $sql;

		if ( isset( $snippetCache[$key] ) ) {
			return $snippetCache[$key];
		}

		$lsql = ltrim( $sql );

		if ( preg_match( '/^(INNER|LEFT|RIGHT|JOIN|AND|OR|WHERE|ORDER|GROUP|HAVING|LIMIT|OFFSET)\s+/i', $lsql ) ) {
			if ( $glue === QueryWriter::C_GLUE_WHERE && stripos( $lsql, 'AND' ) === 0 ) {
				$snippetCache[$key] = ' WHERE ' . substr( $lsql, 3 );
			} else {
				$snippetCache[$key] = $sql;
			}
		} else {
			$snippetCache[$key] = ( ( $glue === QueryWriter::C_GLUE_AND ) ? ' AND ' : ' WHERE ') . $sql;
		}

		return $snippetCache[$key];
	}

	/**
	 * @see QueryWriter::glueLimitOne
	 */
	public function glueLimitOne( $sql = '')
	{
		return ( strpos( strtoupper( ' ' . $sql ), ' LIMIT ' ) === FALSE ) ? ( $sql . ' LIMIT 1 ' ) : $sql;
	}

	/**
	 * @see QueryWriter::esc
	 */
	public function esc( $dbStructure, $dontQuote = FALSE )
	{
		$this->check( $dbStructure );

		return ( $dontQuote ) ? $dbStructure : $this->quoteCharacter . $dbStructure . $this->quoteCharacter;
	}

	/**
	 * @see QueryWriter::addColumn
	 */
	public function addColumn( $beanType, $column, $field )
	{
		$table  = $beanType;
		$type   = $field;
		$table  = $this->esc( $table );
		$column = $this->esc( $column );

		$type = ( isset( $this->typeno_sqltype[$type] ) ) ? $this->typeno_sqltype[$type] : '';

		$this->adapter->exec( sprintf( $this->getDDLTemplate('addColumn', $beanType, $column ), $table, $column, $type ) );
	}

	/**
	 * @see QueryWriter::updateRecord
	 */
	public function updateRecord( $type, $updatevalues, $id = NULL )
	{
		$table = $type;

		if ( !$id ) {
			$insertcolumns = $insertvalues = array();

			foreach ( $updatevalues as $pair ) {
				$insertcolumns[] = $pair['property'];
				$insertvalues[]  = $pair['value'];
			}

			//Otherwise psql returns string while MySQL/SQLite return numeric causing problems with additions (array_diff)
			return (string) $this->insertRecord( $table, $insertcolumns, array( $insertvalues ) );
		}

		if ( $id && !count( $updatevalues ) ) {
			return $id;
		}

		$table = $this->esc( $table );
		$sql   = "UPDATE $table SET ";

		$p = $v = array();

		foreach ( $updatevalues as $uv ) {

			if ( isset( self::$sqlFilters['w'][$type][$uv['property']] ) ) {
				$p[] = " {$this->esc( $uv["property"] )} = ". self::$sqlFilters['w'][$type][$uv['property']];
			} else {
				$p[] = " {$this->esc( $uv["property"] )} = ? ";
			}

			$v[] = $uv['value'];
		}

		$sql .= implode( ',', $p ) . ' WHERE id = ? ';

		$v[] = $id;

		$this->adapter->exec( $sql, $v );

		return $id;
	}

	/**
	 * @see QueryWriter::parseJoin
	 */
	public function parseJoin( $type, $sql, $cteType = NULL )
	{
		if ( strpos( $sql, '@' ) === FALSE ) {
			return $sql;
		}

		$sql = ' ' . $sql;
		$joins = array();
		$joinSql = '';

		if ( !preg_match_all( '#@((shared|own|joined)\.[^\s(,=!?]+)#', $sql, $matches ) )
			return $sql;

		$expressions = $matches[1];
		// Sort to make the joins from the longest to the shortest
		uasort( $expressions, function($a, $b) {
			return substr_count( $b, '.' ) - substr_count( $a, '.' );
		});

		$nsuffix = 1;
		foreach ( $expressions as $exp ) {
			$explosion = explode( '.', $exp );
			$joinTable = $type;
			$joinType  = array_shift( $explosion );
			$lastPart  = array_pop( $explosion );
			$lastJoin  = end($explosion);
			if ( ( $index = strpos( $lastJoin, '[' ) ) !== FALSE ) {
				$lastJoin = substr( $lastJoin, 0, $index);
			}
			reset($explosion);

			// Let's check if we already joined that chain
			// If that's the case we skip this
			$joinKey  = implode( '.', $explosion );
			foreach ( $joins as $chain => $suffix ) {
				if ( strpos ( $chain, $joinKey ) === 0 ) {
					$sql = str_replace( "@{$exp}", "{$lastJoin}__rb{$suffix}.{$lastPart}", $sql );
					continue 2;
				}
			}
			$sql = str_replace( "@{$exp}", "{$lastJoin}__rb{$nsuffix}.{$lastPart}", $sql );
			$joins[$joinKey] = $nsuffix;

			// We loop on the elements of the join
			$i = 0;
			while ( TRUE ) {
				$joinInfo = $explosion[$i];
				if ( $i ) {
					$joinType = $explosion[$i-1];
					$joinTable = $explosion[$i-2];
				}

				$aliases = array();
				if ( ( $index = strpos( $joinInfo, '[' ) ) !== FALSE ) {
					if ( preg_match_all( '#(([^\s:/\][]+)[/\]])#', $joinInfo, $matches ) ) {
						$aliases = $matches[2];
						$joinInfo = substr( $joinInfo, 0, $index);
					}
				}
				if ( ( $index = strpos( $joinTable, '[' ) ) !== FALSE ) {
					$joinTable = substr( $joinTable, 0, $index);
				}

				if ( $i ) {
					$joinSql .= $this->writeJoin( $joinTable, $joinInfo, 'INNER', $joinType, FALSE, "__rb{$nsuffix}", $aliases, NULL );
				} else {
					$joinSql .= $this->writeJoin( $joinTable, $joinInfo, 'LEFT', $joinType, TRUE, "__rb{$nsuffix}", $aliases, $cteType );
				}

				$i += 2;
				if ( !isset( $explosion[$i] ) ) {
					break;
				}
			}
			$nsuffix++;
		}

		$sql = str_ireplace( ' where ', ' WHERE ', $sql );
		if ( strpos( $sql, ' WHERE ') === FALSE ) {
			if ( preg_match( '/^(ORDER|GROUP|HAVING|LIMIT|OFFSET)\s+/i', trim($sql) ) ) {
				$sql = "{$joinSql} {$sql}";
			} else {
				$sql = "{$joinSql} WHERE {$sql}";
			}
		} else {
			$sqlParts = explode( ' WHERE ', $sql, 2 );
			$sql = "{$sqlParts[0]} {$joinSql} WHERE {$sqlParts[1]}";
		}

		return $sql;
	}

	/**
	 * @see QueryWriter::writeJoin
	 */
	public function writeJoin( $type, $targetType, $leftRight = 'LEFT', $joinType = 'parent', $firstOfChain = TRUE, $suffix = '', $aliases = array(), $cteType = NULL )
	{
		if ( $leftRight !== 'LEFT' && $leftRight !== 'RIGHT' && $leftRight !== 'INNER' )
			throw new RedException( 'Invalid JOIN.' );

		$globalAliases = OODBBean::getAliases();
		if ( isset( $globalAliases[$targetType] ) ) {
			$destType      = $globalAliases[$targetType];
			$asTargetTable = $this->esc( $targetType.$suffix );
		} else {
			$destType      = $targetType;
			$asTargetTable = $this->esc( $destType.$suffix );
		}

		if ( $firstOfChain ) {
			$table = $this->esc( $type );
		} else {
			$table = $this->esc( $type.$suffix );
		}
		$targetTable = $this->esc( $destType );

		if ( $joinType == 'shared' ) {

			if ( isset( $globalAliases[$type] ) ) {
				$field      = $this->esc( $globalAliases[$type], TRUE );
				if ( $aliases && count( $aliases ) === 1 ) {
					$assocTable = reset( $aliases );
				} else {
					$assocTable = $this->getAssocTable( array( $cteType ? $cteType : $globalAliases[$type], $destType ) );
				}
			} else {
				$field      = $this->esc( $type, TRUE );
				if ( $aliases && count( $aliases ) === 1 ) {
					$assocTable = reset( $aliases );
				} else {
					$assocTable = $this->getAssocTable( array( $cteType ? $cteType : $type, $destType ) );
				}
			}
			$linkTable      = $this->esc( $assocTable );
			$asLinkTable    = $this->esc( $assocTable.$suffix );
			$leftField      = "id";
			$rightField     = $cteType ? "{$cteType}_id" : "{$field}_id";
			$linkField      = $this->esc( $destType, TRUE );
			$linkLeftField  = "id";
			$linkRightField = "{$linkField}_id";

			$joinSql = " {$leftRight} JOIN {$linkTable}";
			if ( isset( $globalAliases[$targetType] ) || $suffix ) {
				$joinSql .= " AS {$asLinkTable}";
			}
			$joinSql .= " ON {$table}.{$leftField} = {$asLinkTable}.{$rightField}";
			$joinSql .= " {$leftRight} JOIN {$targetTable}";
			if ( isset( $globalAliases[$targetType] ) || $suffix ) {
				$joinSql .= " AS {$asTargetTable}";
			}
			$joinSql .= " ON {$asTargetTable}.{$linkLeftField} = {$asLinkTable}.{$linkRightField}";

		} elseif ( $joinType == 'own' ) {

			$field      = $this->esc( $type, TRUE );
			$rightField = "id";

			$joinSql = " {$leftRight} JOIN {$targetTable}";
			if ( isset( $globalAliases[$targetType] ) || $suffix ) {
				$joinSql .= " AS {$asTargetTable}";
			}

			if ( $aliases ) {
				$conditions = array();
				foreach ( $aliases as $alias ) {
					$conditions[] = "{$asTargetTable}.{$alias}_id = {$table}.{$rightField}";
				}
				$joinSql .= " ON ( " . implode( ' OR ', $conditions ) . " ) ";
			} else {
				$leftField  = $cteType ? "{$cteType}_id" : "{$field}_id";
				$joinSql .= " ON {$asTargetTable}.{$leftField} = {$table}.{$rightField} ";
			}

		} else {

			$field      = $this->esc( $targetType, TRUE );
			$leftField  = "id";

			$joinSql = " {$leftRight} JOIN {$targetTable}";
			if ( isset( $globalAliases[$targetType] ) || $suffix ) {
				$joinSql .= " AS {$asTargetTable}";
			}

			if ( $aliases ) {
				$conditions = array();
				foreach ( $aliases as $alias ) {
					$conditions[] = "{$asTargetTable}.{$leftField} = {$table}.{$alias}_id";
				}
				$joinSql .= " ON ( " . implode( ' OR ', $conditions ) . " ) ";
			} else {
				$rightField = "{$field}_id";
				$joinSql .= " ON {$asTargetTable}.{$leftField} = {$table}.{$rightField} ";
			}

		}

		return $joinSql;
	}

	/**
	 * Sets an SQL snippet to be used for the next queryRecord() operation.
	 * A select snippet will be inserted at the end of the SQL select statement and
	 * can be used to modify SQL-select commands to enable locking, for instance
	 * using the 'FOR UPDATE' snippet (this will generate an SQL query like:
	 * 'SELECT * FROM ... FOR UPDATE'. After the query has been executed the
	 * SQL snippet will be erased. Note that only the first upcoming direct or
	 * indirect invocation of queryRecord() through batch(), find() or load()
	 * will be affected. The SQL snippet will be cached.
	 *
	 * @param string $sql SQL snippet to use in SELECT statement.
	 *
	 * return self
	 */
	public function setSQLSelectSnippet( $sqlSelectSnippet = '' ) {
		$this->sqlSelectSnippet = $sqlSelectSnippet;
		return $this;
	}

	/**
	 * @see QueryWriter::queryRecord
	 */
	public function queryRecord( $type, $conditions = array(), $addSql = NULL, $bindings = array() )
	{
		if ( $this->flagUseCache && $this->sqlSelectSnippet != self::C_SELECT_SNIPPET_FOR_UPDATE ) {
			$key = $this->getCacheKey( array( $conditions, trim("$addSql {$this->sqlSelectSnippet}"), $bindings, 'select' ) );
			if ( $cached = $this->getCached( $type, $key ) ) {
				return $cached;
			}
		}

		$table = $this->esc( $type );

		$sqlFilterStr = '';
		if ( count( self::$sqlFilters ) ) {
			$sqlFilterStr = $this->getSQLFilterSnippet( $type );
		}

		if ( is_array ( $conditions ) && !empty ( $conditions ) ) {
			$sql = $this->makeSQLFromConditions( $conditions, $bindings, $addSql );
		} else {
			$sql = $this->glueSQLCondition( $addSql );
		}
		$sql = $this->parseJoin( $type, $sql );
		$fieldSelection = self::$flagNarrowFieldMode ? "{$table}.*" : '*';
		$sql   = "SELECT {$fieldSelection} {$sqlFilterStr} FROM {$table} {$sql} {$this->sqlSelectSnippet} -- keep-cache";
		$this->sqlSelectSnippet = '';
		$rows  = $this->adapter->get( $sql, $bindings );

		if ( $this->flagUseCache && !empty( $key ) ) {
			$this->putResultInCache( $type, $key, $rows );
		}

		return $rows;
	}

	/**
	 * @see QueryWriter::queryRecordWithCursor
	 */
	public function queryRecordWithCursor( $type, $addSql = NULL, $bindings = array() )
	{
		$table = $this->esc( $type );

		$sqlFilterStr = '';
		if ( count( self::$sqlFilters ) ) {
			$sqlFilterStr = $this->getSQLFilterSnippet( $type );
		}

		$sql = $this->glueSQLCondition( $addSql, NULL );

		$sql = $this->parseJoin( $type, $sql );
		$fieldSelection = self::$flagNarrowFieldMode ? "{$table}.*" : '*';

		$sql = "SELECT {$fieldSelection} {$sqlFilterStr} FROM {$table} {$sql} -- keep-cache";

		return $this->adapter->getCursor( $sql, $bindings );
	}

	/**
	 * @see QueryWriter::queryRecordRelated
	 */
	public function queryRecordRelated( $sourceType, $destType, $linkIDs, $addSql = '', $bindings = array() )
	{
		list( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol ) = $this->getRelationalTablesAndColumns( $sourceType, $destType );

		if ( $this->flagUseCache ) {
			$key = $this->getCacheKey( array( $sourceType, implode( ',', $linkIDs ), trim($addSql), $bindings, 'selectrelated' ) );
			if ( $cached = $this->getCached( $destType, $key ) ) {
				return $cached;
			}
		}

		$addSql = $this->glueSQLCondition( $addSql, QueryWriter::C_GLUE_WHERE );
		$inClause = $this->getParametersForInClause( $linkIDs, $bindings );

		$sqlFilterStr = '';
		if ( count( self::$sqlFilters ) ) {
			$sqlFilterStr = $this->getSQLFilterSnippet( $destType );
		}

		if ( $sourceType === $destType ) {
			$inClause2 = $this->getParametersForInClause( $linkIDs, $bindings, count( $bindings ) ); //for some databases
			$sql = "
			SELECT
				{$destTable}.* {$sqlFilterStr} ,
				COALESCE(
				NULLIF({$linkTable}.{$sourceCol}, {$destTable}.id),
				NULLIF({$linkTable}.{$destCol}, {$destTable}.id)) AS linked_by
			FROM {$linkTable}
			INNER JOIN {$destTable} ON
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} IN ($inClause) ) OR
			( {$destTable}.id = {$linkTable}.{$sourceCol} AND {$linkTable}.{$destCol} IN ($inClause2) )
			{$addSql}
			-- keep-cache";

			$linkIDs = array_merge( $linkIDs, $linkIDs );
		} else {
			$sql = "
			SELECT
				{$destTable}.* {$sqlFilterStr},
				{$linkTable}.{$sourceCol} AS linked_by
			FROM {$linkTable}
			INNER JOIN {$destTable} ON
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} IN ($inClause) )
			{$addSql}
			-- keep-cache";
		}

		$bindings = array_merge( $linkIDs, $bindings );

		$rows = $this->adapter->get( $sql, $bindings );

		if ( $this->flagUseCache ) {
			$this->putResultInCache( $destType, $key, $rows );
		}

		return $rows;
	}

	/**
	 * @see QueryWriter::queryRecordLink
	 */
	public function queryRecordLink( $sourceType, $destType, $sourceID, $destID )
	{
		list( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol ) = $this->getRelationalTablesAndColumns( $sourceType, $destType );

		if ( $this->flagUseCache ) {
			$key = $this->getCacheKey( array( $sourceType, $destType, $sourceID, $destID, 'selectlink' ) );
			if ( $cached = $this->getCached( $linkTable, $key ) ) {
				return $cached;
			}
		}

		$sqlFilterStr = '';
		if ( count( self::$sqlFilters ) ) {
			$linkType = $this->getAssocTable( array( $sourceType, $destType ) );
			$sqlFilterStr = $this->getSQLFilterSnippet( "{$linkType}" );
		}

		if ( $sourceTable === $destTable ) {
			$sql = "SELECT {$linkTable}.* {$sqlFilterStr} FROM {$linkTable}
				WHERE ( {$sourceCol} = ? AND {$destCol} = ? ) OR
				 ( {$destCol} = ? AND {$sourceCol} = ? ) -- keep-cache";
			$row = $this->adapter->getRow( $sql, array( $sourceID, $destID, $sourceID, $destID ) );
		} else {
			$sql = "SELECT {$linkTable}.* {$sqlFilterStr} FROM {$linkTable}
				WHERE {$sourceCol} = ? AND {$destCol} = ? -- keep-cache";
			$row = $this->adapter->getRow( $sql, array( $sourceID, $destID ) );
		}

		if ( $this->flagUseCache ) {
			$this->putResultInCache( $linkTable, $key, $row );
		}

		return $row;
	}

	/**
	 * Returns or counts all rows of specified type that have been tagged with one of the
	 * strings in the specified tag list array.
	 *
	 * Note that the additional SQL snippet can only be used for pagination,
	 * the SQL snippet will be appended to the end of the query.
	 *
	 * @param string  $type     the bean type you want to query
	 * @param array   $tagList  an array of strings, each string containing a tag title
	 * @param boolean $all      if TRUE only return records that have been associated with ALL the tags in the list
	 * @param string  $addSql   addition SQL snippet, for pagination
	 * @param array   $bindings parameter bindings for additional SQL snippet
	 * @param string  $wrap     SQL wrapper string (use %s for subquery)
	 *
	 * @return array
	 */
	private function queryTaggedGeneric( $type, $tagList, $all = FALSE, $addSql = '', $bindings = array(), $wrap = '%s' )
	{
		if ( $this->flagUseCache ) {
			$key = $this->getCacheKey( array( implode( ',', $tagList ), $all, trim($addSql), $bindings, 'selectTagged' ) );
			if ( $cached = $this->getCached( $type, $key ) ) {
				return $cached;
			}
		}

		$assocType = $this->getAssocTable( array( $type, 'tag' ) );
		$assocTable = $this->esc( $assocType );
		$assocField = $type . '_id';
		$table = $this->esc( $type );
		$slots = implode( ',', array_fill( 0, count( $tagList ), '?' ) );
		$score = ( $all ) ? count( $tagList ) : 1;

		$sql = "
			SELECT {$table}.* FROM {$table}
			INNER JOIN {$assocTable} ON {$assocField} = {$table}.id
			INNER JOIN tag ON {$assocTable}.tag_id = tag.id
			WHERE tag.title IN ({$slots})
			GROUP BY {$table}.id
			HAVING count({$table}.id) >= ?
			{$addSql}
			-- keep-cache
		";
		$sql = sprintf($wrap,$sql);

		$bindings = array_merge( $tagList, array( $score ), $bindings );
		$rows = $this->adapter->get( $sql, $bindings );

		if ( $this->flagUseCache ) {
			$this->putResultInCache( $type, $key, $rows );
		}

		return $rows;
	}

	/**
	 * @see QueryWriter::queryTagged
	 */
	public function queryTagged( $type, $tagList, $all = FALSE, $addSql = '', $bindings = array() )
	{
		return $this->queryTaggedGeneric( $type, $tagList, $all, $addSql, $bindings );
	}

	/**
	 * @see QueryWriter::queryCountTagged
	 */
	public function queryCountTagged( $type, $tagList, $all = FALSE, $addSql = '', $bindings = array() )
	{
		$rows = $this->queryTaggedGeneric( $type, $tagList, $all, $addSql, $bindings, 'SELECT COUNT(*) AS counted FROM (%s) AS counting' );
		return intval($rows[0]['counted']);
	}

	/**
	 * @see QueryWriter::queryRecordCount
	 */
	public function queryRecordCount( $type, $conditions = array(), $addSql = NULL, $bindings = array() )
	{
		if ( $this->flagUseCache ) {
			$key = $this->getCacheKey( array( $conditions, trim($addSql), $bindings, 'count' ) );
			if ( $cached = $this->getCached( $type, $key ) ) {
				return $cached;
			}
		}

		$table  = $this->esc( $type );

		if ( is_array ( $conditions ) && !empty ( $conditions ) ) {
			$sql = $this->makeSQLFromConditions( $conditions, $bindings, $addSql );
		} else {
			$sql = $this->glueSQLCondition( $addSql );
		}

		$sql = $this->parseJoin( $type, $sql );

		$sql    = "SELECT COUNT(*) FROM {$table} {$sql} -- keep-cache";
		$count  = (int) $this->adapter->getCell( $sql, $bindings );

		if ( $this->flagUseCache ) {
			$this->putResultInCache( $type, $key, $count );
		}

		return $count;
	}

	/**
	 * @see QueryWriter::queryRecordCountRelated
	 */
	public function queryRecordCountRelated( $sourceType, $destType, $linkID, $addSql = '', $bindings = array() )
	{
		list( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol ) = $this->getRelationalTablesAndColumns( $sourceType, $destType );

		if ( $this->flagUseCache ) {
			$cacheType = "#{$sourceType}/{$destType}";
			$key = $this->getCacheKey( array( $sourceType, $destType, $linkID, trim($addSql), $bindings, 'countrelated' ) );
			if ( $cached = $this->getCached( $cacheType, $key ) ) {
				return $cached;
			}
		}

		if ( $sourceType === $destType ) {
			$sql = "
			SELECT COUNT(*) FROM {$linkTable}
			INNER JOIN {$destTable} ON
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} = ? ) OR
			( {$destTable}.id = {$linkTable}.{$sourceCol} AND {$linkTable}.{$destCol} = ? )
			{$addSql}
			-- keep-cache";

			$bindings = array_merge( array( $linkID, $linkID ), $bindings );
		} else {
			$sql = "
			SELECT COUNT(*) FROM {$linkTable}
			INNER JOIN {$destTable} ON
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} = ? )
			{$addSql}
			-- keep-cache";

			$bindings = array_merge( array( $linkID ), $bindings );
		}

		$count = (int) $this->adapter->getCell( $sql, $bindings );

		if ( $this->flagUseCache ) {
			$this->putResultInCache( $cacheType, $key, $count );
		}

		return $count;
	}

	/**
	 * @see QueryWriter::queryRecursiveCommonTableExpression
	 */
	public function queryRecursiveCommonTableExpression( $type, $id, $up = TRUE, $addSql = NULL, $bindings = array(), $selectForm = FALSE )
	{
		if ($selectForm === QueryWriter::C_CTE_SELECT_COUNT) {
			$selectForm = "count(redbeantree.*)";
		} elseif ( $selectForm === QueryWriter::C_CTE_SELECT_NORMAL ) {
			$selectForm = "redbeantree.*";
		}
		$alias     = $up ? 'parent' : 'child';
		$direction = $up ? " {$alias}.{$type}_id = {$type}.id " : " {$alias}.id = {$type}.{$type}_id ";
		/* allow numeric and named param bindings, if '0' exists then numeric */
		if ( array_key_exists( 0,$bindings ) ) {
			array_unshift( $bindings, $id );
			$idSlot = '?';
		} else {
			$idSlot = ':slot0';
			$bindings[$idSlot] = $id;
		}
		$sql = $this->glueSQLCondition( $addSql, QueryWriter::C_GLUE_WHERE );
		$sql = $this->parseJoin( 'redbeantree', $sql, $type );
		$rows = $this->adapter->get("
			WITH RECURSIVE redbeantree AS
			(
				SELECT *
				FROM {$type} WHERE {$type}.id = {$idSlot}
				UNION ALL
				SELECT {$type}.* FROM {$type}
				INNER JOIN redbeantree {$alias} ON {$direction}
			)
			SELECT {$selectForm} FROM redbeantree {$sql};",
			$bindings
		);
		return $rows;
	}

	/**
	 * @see QueryWriter::deleteRecord
	 */
	public function deleteRecord( $type, $conditions = array(), $addSql = NULL, $bindings = array() )
	{
		$table  = $this->esc( $type );

		if ( is_array ( $conditions ) && !empty ( $conditions ) ) {
			$sql = $this->makeSQLFromConditions( $conditions, $bindings, $addSql );
		} else {
			$sql = $this->glueSQLCondition( $addSql );
		}

		$sql    = "DELETE FROM {$table} {$sql}";

		return $this->adapter->exec( $sql, $bindings );
	}

	/**
	 * @see QueryWriter::deleteRelations
	 */
	public function deleteRelations( $sourceType, $destType, $sourceID )
	{
		list( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol ) = $this->getRelationalTablesAndColumns( $sourceType, $destType );

		if ( $sourceTable === $destTable ) {
			$sql = "DELETE FROM {$linkTable}
				WHERE ( {$sourceCol} = ? ) OR
				( {$destCol} = ?  )
			";

			$this->adapter->exec( $sql, array( $sourceID, $sourceID ) );
		} else {
			$sql = "DELETE FROM {$linkTable}
				WHERE {$sourceCol} = ? ";

			$this->adapter->exec( $sql, array( $sourceID ) );
		}
	}

	/**
	 * @see QueryWriter::widenColumn
	 */
	public function widenColumn( $type, $property, $dataType )
	{
		if ( !isset($this->typeno_sqltype[$dataType]) ) return FALSE;

		$table   = $this->esc( $type );
		$column  = $this->esc( $property );

		$newType = $this->typeno_sqltype[$dataType];

		$this->adapter->exec( sprintf( $this->getDDLTemplate( 'widenColumn', $type, $column ), $type, $column, $column, $newType ) );

		return TRUE;
	}

	/**
	 * @see QueryWriter::wipe
	 */
	public function wipe( $type )
	{
		$table = $this->esc( $type );

		$this->adapter->exec( "TRUNCATE $table " );
	}

	/**
	 * @see QueryWriter::renameAssocTable
	 */
	public function renameAssocTable( $from, $to = NULL )
	{
		self::renameAssociation( $from, $to );
	}

	/**
	 * @see QueryWriter::getAssocTable
	 */
	public function getAssocTable( $types )
	{
		return self::getAssocTableFormat( $types );
	}

	/**
	 * Turns caching on or off. Default: off.
	 * If caching is turned on retrieval queries fired after eachother will
	 * use a result row cache.
	 *
	 * @param boolean
	 *
	 * @return void
	 */
	public function setUseCache( $yesNo )
	{
		$this->flushCache();

		$this->flagUseCache = (bool) $yesNo;
	}

	/**
	 * Flushes the Query Writer Cache.
	 * Clears the internal query cache array and returns its overall
	 * size.
	 *
	 * @return mixed
	 */
	public function flushCache( $newMaxCacheSizePerType = NULL, $countCache = TRUE )
	{
		if ( !is_null( $newMaxCacheSizePerType ) && $newMaxCacheSizePerType > 0 ) {
			$this->maxCacheSizePerType = $newMaxCacheSizePerType;
		}
		$count = $countCache ? count( $this->cache, COUNT_RECURSIVE ) : NULL;
		$this->cache = array();
		return $count;
	}

	/**
	 * @deprecated Use esc() instead.
	 *
	 * @param string  $column   column to be escaped
	 * @param boolean $noQuotes omit quotes
	 *
	 * @return string
	 */
	public function safeColumn( $column, $noQuotes = FALSE )
	{
		return $this->esc( $column, $noQuotes );
	}

	/**
	 * @deprecated Use esc() instead.
	 *
	 * @param string  $table    table to be escaped
	 * @param boolean $noQuotes omit quotes
	 *
	 * @return string
	 */
	public function safeTable( $table, $noQuotes = FALSE )
	{
		return $this->esc( $table, $noQuotes );
	}

	/**
	 * @see QueryWriter::addUniqueConstraint
	 */
	public function addUniqueIndex( $type, $properties )
	{
		return $this->addUniqueConstraint( $type, $properties );
	}
}
