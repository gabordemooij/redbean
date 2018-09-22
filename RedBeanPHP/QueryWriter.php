<?php

namespace RedBeanPHP;

/**
 * QueryWriter
 * Interface for QueryWriters.
 * Describes the API for a QueryWriter.
 *
 * Terminology:
 *
 * - beautified property (a camelCased property, has to be converted first)
 * - beautified type (a camelCased type, has to be converted first)
 * - type (a bean type, corresponds directly to a table)
 * - property (a bean property, corresponds directly to a column)
 * - table (a checked and quoted type, ready for use in a query)
 * - column (a checked and quoted property, ready for use in query)
 * - tableNoQ (same as type, but in context of a database operation)
 * - columnNoQ (same as property, but in context of a database operation)
 *
 * @file    RedBeanPHP/QueryWriter.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface QueryWriter
{
	/**
	 * SQL filter constants
	 */
	const C_SQLFILTER_READ  = 'r';
	const C_SQLFILTER_WRITE = 'w';

	/**
	 * Query Writer constants.
	 */
	const C_SQLSTATE_NO_SUCH_TABLE                  = 1;
	const C_SQLSTATE_NO_SUCH_COLUMN                 = 2;
	const C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION = 3;
	const C_SQLSTATE_LOCK_TIMEOUT                   = 4;

	/**
	 * Define data type regions
	 *
	 * 00 - 80: normal data types
	 * 80 - 99: special data types, only scan/code if requested
	 * 99     : specified by user, don't change
	 */
	const C_DATATYPE_RANGE_SPECIAL   = 80;
	const C_DATATYPE_RANGE_SPECIFIED = 99;

	/**
	 * Define GLUE types for use with glueSQLCondition methods.
	 * Determines how to prefix a snippet of SQL before appending it
	 * to other SQL (or integrating it, mixing it otherwise).
	 *
	 * WHERE - glue as WHERE condition
	 * AND   - glue as AND condition
	 */
	const C_GLUE_WHERE = 1;
	const C_GLUE_AND   = 2;

	/**
	 * Writes an SQL Snippet for a JOIN, returns the
	 * SQL snippet string.
	 *
	 * @note A default implementation is available in AQueryWriter
	 * unless a database uses very different SQL this should suffice.
	 *
	 * @param string $type       source type
	 * @param string $targetType target type (type to join)
	 * @param string $joinType   type of join (possible: 'LEFT', 'RIGHT' or 'INNER').
	 *
	 * @return string $joinSQLSnippet
	 */
	public function writeJoin( $type, $targetType, $joinType );

	/**
	 * Glues an SQL snippet to the beginning of a WHERE clause.
	 * This ensures users don't have to add WHERE to their query snippets.
	 *
	 * The snippet gets prefixed with WHERE or AND
	 * if it starts with a condition.
	 *
	 * If the snippet does NOT start with a condition (or this function thinks so)
	 * the snippet is returned as-is.
	 *
	 * The GLUE type determines the prefix:
	 *
	 * * NONE  prefixes with WHERE
	 * * WHERE prefixes with WHERE and replaces AND if snippets starts with AND
	 * * AND   prefixes with AND
	 *
	 * This method will never replace WHERE with AND since a snippet should never
	 * begin with WHERE in the first place. OR is not supported.
	 *
	 * Only a limited set of clauses will be recognized as non-conditions.
	 * For instance beginning a snippet with complex statements like JOIN or UNION
	 * will not work. This is too complex for use in a snippet.
	 *
	 * @note A default implementation is available in AQueryWriter
	 * unless a database uses very different SQL this should suffice.
	 *
	 * @param string  $sql  SQL Snippet
	 * @param integer $glue the GLUE type - how to glue (C_GLUE_WHERE or C_GLUE_AND)
	 *
	 * @return string
	 */
	public function glueSQLCondition( $sql, $glue = NULL );

	/**
	 * Determines if there is a LIMIT 1 clause in the SQL.
	 * If not, it will add a LIMIT 1. (used for findOne).
	 *
	 * @note A default implementation is available in AQueryWriter
	 * unless a database uses very different SQL this should suffice.
	 *
	 * @param string $sql query to scan and adjust
	 *
	 * @return string
	 */
	public function glueLimitOne( $sql );

	/**
	 * Returns the tables that are in the database.
	 *
	 * @return array
	 */
	public function getTables();

	/**
	 * This method will create a table for the bean.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type type of bean you want to create a table for
	 *
	 * @return void
	 */
	public function createTable( $type );

	/**
	 * Returns an array containing all the columns of the specified type.
	 * The format of the return array looks like this:
	 * $field => $type where $field is the name of the column and $type
	 * is a database specific description of the datatype.
	 *
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type type of bean you want to obtain a column list of
	 *
	 * @return array
	 */
	public function getColumns( $type );

	/**
	 * Returns the Column Type Code (integer) that corresponds
	 * to the given value type. This method is used to determine the minimum
	 * column type required to represent the given value. There are two modes of
	 * operation: with or without special types. Scanning without special types
	 * requires the second parameter to be set to FALSE. This is useful when the
	 * column has already been created and prevents it from being modified to
	 * an incompatible type leading to data loss. Special types will be taken
	 * into account when a column does not exist yet (parameter is then set to TRUE).
	 *
	 * Special column types are determines by the AQueryWriter constant
	 * C_DATA_TYPE_ONLY_IF_NOT_EXISTS (usually 80). Another 'very special' type is type
	 * C_DATA_TYPE_MANUAL (usually 99) which represents a user specified type. Although
	 * no special treatment has been associated with the latter for now.
	 *
	 * @param string  $value                   value
	 * @param boolean $alsoScanSpecialForTypes take special types into account
	 *
	 * @return integer
	 */
	public function scanType( $value, $alsoScanSpecialForTypes = FALSE );

	/**
	 * This method will add a column to a table.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type   name of the table
	 * @param string  $column name of the column
	 * @param integer $field  data type for field
	 *
	 * @return void
	 */
	public function addColumn( $type, $column, $field );

	/**
	 * Returns the Type Code for a Column Description.
	 * Given an SQL column description this method will return the corresponding
	 * code for the writer. If the include specials flag is set it will also
	 * return codes for special columns. Otherwise special columns will be identified
	 * as specified columns.
	 *
	 * @param string  $typedescription description
	 * @param boolean $includeSpecials whether you want to get codes for special columns as well
	 *
	 * @return integer
	 */
	public function code( $typedescription, $includeSpecials = FALSE );

	/**
	 * This method will widen the column to the specified data type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type     type / table that needs to be adjusted
	 * @param string  $column   column that needs to be altered
	 * @param integer $datatype target data type
	 *
	 * @return void
	 */
	public function widenColumn( $type, $column, $datatype );

	/**
	 * Selects records from the database.
	 * This methods selects the records from the database that match the specified
	 * type, conditions (optional) and additional SQL snippet (optional).
	 *
	 * @param string $type       name of the table you want to query
	 * @param array  $conditions criteria ( $column => array( $values ) )
	 * @param string $addSql     additional SQL snippet
	 * @param array  $bindings   bindings for SQL snippet
	 *
	 * @return array
	 */
	public function queryRecord( $type, $conditions = array(), $addSql = NULL, $bindings = array() );

	/**
	 * Selects records from the database and returns a cursor.
	 * This methods selects the records from the database that match the specified
	 * type, conditions (optional) and additional SQL snippet (optional).
	 *
	 * @param string $type       name of the table you want to query
	 * @param array  $conditions criteria ( $column => array( $values ) )
	 * @param string $addSQL     additional SQL snippet
	 * @param array  $bindings   bindings for SQL snippet
	 *
	 * @return Cursor
	 */
	public function queryRecordWithCursor( $type, $addSql = NULL, $bindings = array() );

	/**
	 * Returns records through an intermediate type. This method is used to obtain records using a link table and
	 * allows the SQL snippets to reference columns in the link table for additional filtering or ordering.
	 *
	 * @param string $sourceType source type, the reference type you want to use to fetch related items on the other side
	 * @param string $destType   destination type, the target type you want to get beans of
	 * @param mixed  $linkID     ID to use for the link table
	 * @param string $addSql     Additional SQL snippet
	 * @param array  $bindings   Bindings for SQL snippet
	 *
	 * @return array
	 */
	public function queryRecordRelated( $sourceType, $destType, $linkID, $addSql = '', $bindings = array() );

	/**
	 * Returns the row that links $sourceType $sourcID to $destType $destID in an N-M relation.
	 *
	 * @param string $sourceType source type, the first part of the link you're looking for
	 * @param string $destType   destination type, the second part of the link you're looking for
	 * @param string $sourceID   ID for the source
	 * @param string $destID     ID for the destination
	 *
	 * @return array|null
	 */
	public function queryRecordLink( $sourceType, $destType, $sourceID, $destID );

	/**
	 * Counts the number of records in the database that match the
	 * conditions and additional SQL.
	 *
	 * @param string $type       name of the table you want to query
	 * @param array  $conditions criteria ( $column => array( $values ) )
	 * @param string $addSQL     additional SQL snippet
	 * @param array  $bindings   bindings for SQL snippet
	 *
	 * @return integer
	 */
	public function queryRecordCount( $type, $conditions = array(), $addSql = NULL, $bindings = array() );

	/**
	 * Returns the number of records linked through $linkType and satisfying the SQL in $addSQL/$bindings.
	 *
	 * @param string $sourceType source type
	 * @param string $targetType the thing you want to count
	 * @param mixed  $linkID     the of the source type
	 * @param string $addSQL     additional SQL snippet
	 * @param array  $bindings   bindings for SQL snippet
	 *
	 * @return integer
	 */
	public function queryRecordCountRelated( $sourceType, $targetType, $linkID, $addSQL = '', $bindings = array() );

	/**
	 * Returns all rows of specified type that have been tagged with one of the
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
	 *
	 * @return array
	 */
	public function queryTagged( $type, $tagList, $all = FALSE, $addSql = '', $bindings = array() );

	/**
	 * Returns all parent rows or child rows of a specified row.
	 * Given a type specifier and a primary key id, this method returns either all child rows
	 * as defined by having <type>_id = id or all parent rows as defined per id = <type>_id
	 * taking into account an optional SQL snippet along with parameters.
	 *
	 * @param string  $type     the bean type you want to query rows for
	 * @param integer $id       id of the reference row
	 * @param boolean $up       TRUE to query parent rows, FALSE to query child rows
	 * @param string  $addSql   optional SQL snippet to embed in the query
	 * @param array   $bindings parameter bindings for additional SQL snippet
	 *
	 * @return array
	 */
	public function queryRecursiveCommonTableExpression( $type, $id, $up = TRUE, $addSql = NULL, $bindings = array() );

	/**
	 * This method should update (or insert a record), it takes
	 * a table name, a list of update values ( $field => $value ) and an
	 * primary key ID (optional). If no primary key ID is provided, an
	 * INSERT will take place.
	 * Returns the new ID.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type         name of the table to update
	 * @param array   $updatevalues list of update values
	 * @param integer $id           optional primary key ID value
	 *
	 * @return integer
	 */
	public function updateRecord( $type, $updatevalues, $id = NULL );

	/**
	 * Deletes records from the database.
	 * @note $addSql is always prefixed with ' WHERE ' or ' AND .'
	 *
	 * @param string $type       name of the table you want to query
	 * @param array  $conditions criteria ( $column => array( $values ) )
	 * @param string $addSql     additional SQL
	 * @param array  $bindings   bindings
	 *
	 * @return void
	 */
	public function deleteRecord( $type, $conditions = array(), $addSql = '', $bindings = array() );

	/**
	 * Deletes all links between $sourceType and $destType in an N-M relation.
	 *
	 * @param string $sourceType source type
	 * @param string $destType   destination type
	 * @param string $sourceID   source ID
	 *
	 * @return void
	 */
	public function deleteRelations( $sourceType, $destType, $sourceID );

	/**
	 * @see QueryWriter::addUniqueConstaint
	 */
	public function addUniqueIndex( $type, $columns );

	/**
	 * This method will add a UNIQUE constraint index to a table on columns $columns.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type               target bean type
	 * @param array  $columnsPartOfIndex columns to include in index
	 *
	 * @return void
	 */
	public function addUniqueConstraint( $type, $columns );

	/**
	 * This method will check whether the SQL state is in the list of specified states
	 * and returns TRUE if it does appear in this list or FALSE if it
	 * does not. The purpose of this method is to translate the database specific state to
	 * a one of the constants defined in this class and then check whether it is in the list
	 * of standard states provided.
	 *
	 * @param string $state SQL state to consider
	 * @param array  $list  list of standardized SQL state constants to check against
	 * @param array  $extraDriverDetails Some databases communicate state information in a driver-specific format
	 *                                   rather than through the main sqlState code. For those databases, this extra
	 *                                   information can be used to determine the standardized state
	 *
	 * @return boolean
	 */
	public function sqlStateIn( $state, $list, $extraDriverDetails = array() );

	/**
	 * This method will remove all beans of a certain type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  string $type bean type
	 *
	 * @return void
	 */
	public function wipe( $type );

	/**
	 * This method will add a foreign key from type and field to
	 * target type and target field.
	 * The foreign key is created without an action. On delete/update
	 * no action will be triggered. The FK is only used to allow database
	 * tools to generate pretty diagrams and to make it easy to add actions
	 * later on.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 *
	 * @param  string $type           type that will have a foreign key field
	 * @param  string $targetType     points to this type
	 * @param  string $property       field that contains the foreign key value
	 * @param  string $targetProperty field where the fk points to
	 * @param  string $isDep          whether target is dependent and should cascade on update/delete
	 *
	 * @return void
	 */
	public function addFK( $type, $targetType, $property, $targetProperty, $isDep = FALSE );

	/**
	 * This method will add an index to a type and field with name
	 * $name.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type     type to add index to
	 * @param string $name     name of the new index
	 * @param string $property field to index
	 *
	 * @return void
	 */
	public function addIndex( $type, $name, $property );

	/**
	 * Checks and filters a database structure element like a table of column
	 * for safe use in a query. A database structure has to conform to the
	 * RedBeanPHP DB security policy which basically means only alphanumeric
	 * symbols are allowed. This security policy is more strict than conventional
	 * SQL policies and does therefore not require database specific escaping rules.
	 *
	 * @param string  $databaseStructure name of the column/table to check
	 * @param boolean $noQuotes          TRUE to NOT put backticks or quotes around the string
	 *
	 * @return string
	 */
	public function esc( $databaseStructure, $dontQuote = FALSE );

	/**
	 * Removes all tables and views from the database.
	 *
	 * @return void
	 */
	public function wipeAll();

	/**
	 * Renames an association. For instance if you would like to refer to
	 * album_song as: track you can specify this by calling this method like:
	 *
	 * <code>
	 * renameAssociation('album_song','track')
	 * </code>
	 *
	 * This allows:
	 *
	 * <code>
	 * $album->sharedSong
	 * </code>
	 *
	 * to add/retrieve beans from track instead of album_song.
	 * Also works for exportAll().
	 *
	 * This method also accepts a single associative array as
	 * its first argument.
	 *
	 * @param string|array $fromType original type name, or array
	 * @param string       $toType   new type name (only if 1st argument is string)
	 *
	 * @return void
	 */
	public function renameAssocTable( $fromType, $toType = NULL );

	/**
	 * Returns the format for link tables.
	 * Given an array containing two type names this method returns the
	 * name of the link table to be used to store and retrieve
	 * association records. For instance, given two types: person and
	 * project, the corresponding link table might be: 'person_project'.
	 *
	 * @param  array $types two types array($type1, $type2)
	 *
	 * @return string
	 */
	public function getAssocTable( $types );

	/**
	 * Given a bean type and a property, this method
	 * tries to infer the fetch type using the foreign key
	 * definitions in the database.
	 * For instance: project, student -> person.
	 * If no fetchType can be inferred, this method will return NULL.
	 *
	 * @note QueryWriters do not have to implement this method,
	 * it's optional. A default version is available in AQueryWriter.
	 *
	 * @param $type     the source type to fetch a target type for
	 * @param $property the property to fetch the type of
	 *
	 * @return string|NULL
	 */
	public function inferFetchType( $type, $property );
}
