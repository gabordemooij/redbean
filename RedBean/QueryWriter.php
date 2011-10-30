<?php
/**
 * QueryWriter
 * Interface for QueryWriters
 * @file			RedBean/QueryWriter.php
 * @description		Describes the API for a QueryWriter
 * @author			Gabor de Mooij
 * @license			BSD
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
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_QueryWriter {

	/**
	 * QueryWriter Constant Identifier.
	 * Identifies a situation in which a table has not been found in
	 * the database.
	 */
	const C_SQLSTATE_NO_SUCH_TABLE = 1;

	/**
	 * QueryWriter Constant Identifier.
	 * Identifies a situation in which a perticular column has not
	 * been found in the database.
	 */
	const C_SQLSTATE_NO_SUCH_COLUMN = 2;

	/**
	 * QueryWriter Constant Identifier.
	 * Identifies a situation in which a perticular column has not
	 * been found in the database.
	 */
	const C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION = 3;

	/**
	 * Returns the table to store beans of a given type.
	 *
	 * @param string $type type of bean you want the table name of
	 *
	 * @return string $tableName name of the table where bean records are stored
	 */
	public function getFormattedTableName($type);

	/**
	 * Returns the tables that are in the database.
	 *
	 * @return array $arrayOfTables list of tables
	 */
	public function getTables();

	/**
	 * This method should create a table for the bean.
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
	public function scanType($value);

	/**
	 * This method should add a column to a table.
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
	 * This method should return a data type constant based on the
	 * SQL type definition. This function is meant to compare column data
	 * type to check whether a column is wide enough to represent the desired
	 * values.
	 *
	 * @param integer $typedescription SQL type description from database
	 *
	 * @return integer $type
	 */
	public function code($typedescription);

	/**
	 * This method should widen the column to the specified data type.
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
	public function updateRecord($type, $updatevalues, $id=null);


	/**
	 * This method should select a record. You should be able to provide a
	 * collection of conditions using the following format:
	 * array( $field1 => array($possibleValue1, $possibleValue2,... $possibleValueN ),
	 * ...$fieldN=>array(...));
	 * Also, additional SQL can be provided. This SQL snippet will be appended to the
	 * query string. If the $delete parameter is set to TRUE instead of selecting the
	 * records they will be deleted.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type   type of bean to select records from
	 * @param array   $cond   conditions using the specified format
	 * @param string  $asql   additional sql
	 * @param boolean $delete  IF TRUE delete records (optional)
	 * @param boolean $inverse IF TRUE inverse the selection (optional)
	 *
	 * @return array $records selected records
	 */
	public function selectRecord($type, $conditions, $addSql = null, $delete = false, $inverse = false);


	/**
	 * This method should add a UNIQUE constraint index to a table on columns $columns.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type               type
	 * @param array  $columnsPartOfIndex columns to include in index
	 *
	 * @return void
	 */
	public function addUniqueIndex($type,$columns);

	/**
	 * Returns the property that contains the Primary Key ID in an
	 * OODBBean instance.
	 *
	 * @param string $tableOfTheBean
	 *
	 * @return void
	 */
	public function getIDField($type);


	/**
	 * This method should check whether the SQL state is in the list of specified states
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
	public function sqlStateIn( $state, $list );

	/**
	 * This method should remove all beans of a certain type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  string $type bean type
	 *
	 * @return void
	 */
	public function wipe($type);

	/**
	 * This method should count the number of beans of the given type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  string $type type of bean to count
	 *
	 * @return integer $numOfBeans number of beans found
	 */
	public function count($type);



	/**
	 * This method should set the new bean formatter. A bean formatter is an instance
	 * of the class BeanFormatter that determines how a bean should be represented
	 * in the database.
	 *
	 * @param RedBean_IBeanFormatter $beanFormatter bean format
	 *
	 * @return void
	 */
	public function setBeanFormatter(RedBean_IBeanFormatter $beanFormatter);

	/**
	 * This method should create a view with name $viewID and
	 * based on the reference type. A list of types
	 * will be provided in the second argument. This method should create
	 * a view by joining each type in the list (using LEFT OUTER JOINS) to the
	 * reference type. If a type is mentioned multiple times it does not need
	 * to be re-joined but the next type should be joined to that type instead.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  string $referenceType reference type
	 * @param  array  $constraints   list of types
	 * @param  string $viewID		 name of the new view
	 *
	 * @return void
	 */
	public function createView($referenceType, $types, $viewID);

	/**
	 * Given a field type const. from this class this method should
	 * return the database specific SQL description of the data type.
	 *
	 * @param integer $type data type constant
	 *
	 * @return void
	 */
	public function getFieldType($type = "");

	/**
	 * This method should filter a column name so that it can
	 * be used safely in a query for a specific database.
	 *
	 * @param  string $name		the column name
	 * @param  bool   $noQuotes whether you want to omit quotes
	 *
	 * @return string $clean the clean version of the column name
	 */
	public function safeColumn($name, $noQuotes = false);

	/**
	 * This method should filter a type name so that it can
	 * be used safely in a query for a specific database. It actually
	 * converts a type to a table. TYPE -> TABLE
	 *
	 * @param string $name     the name of the type
	 * @param bool   $noQuotes whether you want to omit quotes in table name
	 *
	 * @return string $tablename clean table name for use in query
	 */
	public function safeTable($name, $noQuotes = false);

	/**
	 * This method should add a constraint. If one of the beans gets trashed
	 * the other, related bean should be removed as well.
	 *
	 * @param RedBean_OODBBean $bean1      first bean
	 * @param RedBean_OODBBean $bean2      second bean
	 * @param bool 			   $dontCache  by default we use a cache, TRUE = NO CACHING (optional)
	 *
	 * @return void
	 */
	public function addConstraint( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, $dontCache = false );

	/**
	 * This method should return the format for link tables.
	 * Given an array containing two type names this method returns the
	 * name of the link table to be used to store and retrieve
	 * association records.
	 *
	 * @param  array $types two types array($type1,$type2)
	 *
	 * @return string $linktable name of the link table
	 */
	public function getAssocTableFormat($types);

	/**
	 * This method should add a foreign key from type and field to
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
	public function addFK( $type, $targetType, $field, $targetField);


	/**
	 * This method should add an index to a type and field with name
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
	 * This method should return the true type name of a given alias.
	 * For instance 'magazine' -> 'book' if magazine is just another name
	 * of the real 'type' called book.
	 *
	 * @param  string $type type
	 *
	 * @return string $realType type
	 */
	public function getAlias($type);


	/**
	 * This method should return the datatype to be used for primary key IDS and
	 * foreign keys. Return one if the data type constants.
	 *
	 * @return integer $const data type to be used for IDS.
	 */
	public function getTypeForID();

}