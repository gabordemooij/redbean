<?php
/**
 * QueryWriter
 * Interface for QueryWriters
 * 
 * @file			RedBean/QueryWriter.php
 * @desc			Describes the API for a QueryWriter
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 * Notes:
 * - Whenever you see a parameter called $table or $type you should always
 * be aware of the fact that this argument contains a Bean Type string, not the
 * actual table name. These raw type names are passed to safeTable() to obtain the
 * actual name of the database table. Don't let the names confuse you $type/$table
 * refers to Bean Type, not physical database table names!
 * - This is the interface for FLUID database drivers. Drivers intended to support
 * just FROZEN mode should implement the IceWriter instead.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_QueryWriter {
	/**
	 * Query Writer constants.
	 */
	const C_SQLSTATE_NO_SUCH_TABLE = 1;
	const C_SQLSTATE_NO_SUCH_COLUMN = 2;
	const C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION = 3;
	
	/**
	 * Returns the tables that are in the database.
	 *
	 * @return array $arrayOfTables list of tables
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
	public function createTable($type);
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
	 * @return array $listOfColumns list of columns ($field=>$type)
	 */
	public function getColumns($type);
	/**
	 * Returns the Column Type Code (integer) that corresponds
	 * to the given value type. This method is used to determine the minimum
	 * column type required to represent the given value.
	 *
	 * @param string $value value
	 *
	 * @return integer $type type
	 */
	public function scanType($value, $alsoScanSpecialForTypes = false);
	/**
	 * This method will add a column to a table.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type   name of the table
	 * @param string  $column name of the column
	 * @param integer $field  data type for field
	 *
	 * @return void
	 *
	 */
	public function addColumn($type, $column, $field);
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
	 * @return integer $typecode code
	 */
	public function code($typedescription, $includeSpecials = false);
	/**
	 * This method will widen the column to the specified data type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type       type / table that needs to be adjusted
	 * @param string  $column     column that needs to be altered
	 * @param integer $datatype   target data type
	 *
	 * @return void
	 */
	public function widenColumn($type, $column, $datatype);
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
	 * @param integer $id			optional primary key ID value
	 *
	 * @return integer $id the primary key ID value of the new record
	 */
	public function updateRecord($type, $updatevalues, $id = null);
	/**
	 * @deprecated
	 */
	public function selectRecord($type, $conditions, $addSql = null, $delete = false, $inverse = false);
	/**
	 * Selects records from the database.
	 * 
	 * @param string       $type       name of the table you want to query
	 * @param array        $conditions criteria ( $column => array( $values ) )
	 * @param string|array $allSql     additional SQL snippet, either a string or: array($SQL, $bindings)
	 * @param boolean      $all        if FALSE and $addSQL is SET prefixes $addSQL with ' WHERE ' or ' AND ' 
	 */
	public function queryRecord($type, $conditions, $addSql = null, $all = false);
	/**
	 * Selects records from the database.
	 * 
	 * @param string       $type       name of the table you want to query
	 * @param array        $conditions criteria ( $column => array( $values ) )
	 * @param string|array $allSql     additional SQL snippet, either a string or: array($SQL, $bindings)
	 */
	public function queryRecordCount($type, $conditions, $addSql = null);
	/**
	 * Returns the number of records linked through $linkType and satisfying the SQL in $addSQL/$params.
	 *  
	 * @param string $sourceType source type
	 * @param string $targetType the thing you want to count
	 * @param mixed  $linkID     the of the source type
	 * @param string $addSQL     additional SQL snippet
	 * @param array  $params     bindings for SQL snippet
	 * 
	 * @return integer
	 */
	public function queryRecordCountRelated($sourceType, $targetType, $linkID, $addSQL = '', $params = array());
	
	/**
	 * Returns records through an intermediate type. This method is used to obtain records using a link table and
	 * allows the SQL snippets to reference columns in the link table for additional filtering or ordering.
	 * 
	 * @param string $sourceType source type, the reference type you want to use to fetch related items on the other side
	 * @param string $destType   destination type, the target type you want to get beans of
	 * @param mixed  $linkID     ID to use for the link table
	 * @param string $addSql     Additional SQL snippet
	 * @param array  $params     Bindings for SQL snippet
	 */
	public function queryRecordRelated($sourceType, $destType, $linkID, $addSql = '', $params = array());
	
	/**
	 * Deletes records from the database.
	 * @note $addSql is always prefixed with ' WHERE ' or ' AND .'
	 * 
	 * @param string       $type       name of the table you want to query
	 * @param array        $conditions criteria ( $column => array( $values ) )
	 * @param string|array $allSql     additional SQL snippet, either a string or: array($SQL, $bindings)
	 */
	public function deleteRecord($type, $conditions, $addSql = null);
	/**
	 * Selects records from the database using an 'NOT IN'-clause for conditions..
	 * @note $addSql is always prefixed with ' WHERE ' or ' AND .'
	 * 
	 * @param string       $type       name of the table you want to query
	 * @param array        $conditions criteria ( $column => array( $values ) )
	 * @param string|array $allSql     additional SQL snippet, either a string or: array($SQL, $bindings)
	 */
	public function queryRecordInverse($type, $conditions, $addSql = null);
	/**
	 * This method will add a UNIQUE constraint index to a table on columns $columns.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type               type
	 * @param array  $columnsPartOfIndex columns to include in index
	 *
	 * @return void
	 */
	public function addUniqueIndex($type, $columns);
	/**
	 * This method will check whether the SQL state is in the list of specified states
	 * and returns true if it does appear in this list or false if it
	 * does not. The purpose of this method is to translate the database specific state to
	 * a one of the constants defined in this class and then check whether it is in the list
	 * of standard states provided.
	 *
	 * @param string $state sql state
	 * @param array  $list  list
	 *
	 * @return boolean $isInList
	 */
	public function sqlStateIn($state, $list);
	/**
	 * This method will remove all beans of a certain type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  string $type bean type
	 *
	 * @return void
	 */
	public function wipe($type);
	/**
	 * This method will count the number of beans of the given type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  string $type type of bean to count
	 *
	 * @return integer $numOfBeans number of beans found
	 */
	public function count($type);
	/**
	 * This method will add a constraint. If one of the beans gets trashed
	 * the other, related bean will be removed as well.
	 *
	 * @param RedBean_OODBBean $bean1      first bean
	 * @param RedBean_OODBBean $bean2      second bean
	 *
	 * @return void
	 */
	public function addConstraint(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2);
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
	 * @param  string $type	       type that will have a foreign key field
	 * @param  string $targetType  points to this type
	 * @param  string $field       field that contains the foreign key value
	 * @param  string $targetField field where the fk points to
	 *
	 * @return void
	 */
	public function addFK($type, $targetType, $field, $targetField);
	/**
	 * This method will add an index to a type and field with name
	 * $name.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  $type   type to add index to
	 * @param  $name   name of the new index
	 * @param  $column field to index
	 *
	 * @return void
	 */
	public function addIndex($type, $name, $column);
	
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
	public function esc($databaseStructure, $dontQuote = false);
	
	/**
	 * Removes all tables and views from the database. 
	 */
	public function wipeAll();
	
	/**
	 * Renames an association. For instance if you would like to refer to
	 * album_song as: track you can specify this by calling this method like:
	 * 
	 * renameAssociation('album_song','track')
	 * 
	 * This allows:
	 * 
	 * $album->sharedSong 
	 * 
	 * to add/retrieve beans from track instead of album_song.
	 * Also works for exportAll().
	 * 
	 * This method also accepts a single associative array as
	 * its first argument.
	 * 
	 * @param string|array $from
	 * @param string $to (optional)
	 * 
	 * @return void 
	 */
	public function renameAssocTable($from, $to = null);
	
	/**
	 * Returns an SQL snippet for use with selectRecord to obtain records
	 * from a link table.
	 *  
	 * @param string $sourceType the type of bean you want to use as a link reference
	 * @param string $destType   the target type
	 * @param string $linkType   link table to use
	 * 
	 * @return string 
	 */
	//public function getLinkBlock($sourceType, $destType, $linkType, $linkID);
	

	
	/** 
	 * Returns the format for link tables.
	 * Given an array containing two type names this method returns the
	 * name of the link table to be used to store and retrieve
	 * association records.
	 *
	 * @param  array $types two types array($type1, $type2)
	 *
	 * @return string $linktable name of the link table
	 */
	public function getAssocTable($types);
}
