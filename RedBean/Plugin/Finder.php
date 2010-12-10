<?php
/**
 * RedBean Bean Finder
 *
 * @file					RedBean/Plugin/Finder.php
 * @description		Provides a more convenient way to find beans
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_Finder implements RedBean_Plugin {


	/**
	 * Fetches a collection of OODB Bean objects based on the SQL
	 * criteria provided. For instance;
	 *
	 * - Finder::where("page", " name LIKE '%more%' ");
	 *
	 * Will return all pages that have the word 'more' in their name.
	 * The second argument is actually just plain SQL; the function expects
	 * this SQL to be compatible with a SELECT * FROM TABLE WHERE X query,
	 * where X is ths search string you provide in the second parameter.
	 * Another example, using slots:
	 *
	 * - Finder::where("page", " name LIKE :str ",array(":str"=>'%more%'));
	 *
	 * Also, note that the default search is always 1. So if you do not
	 * specify a search parameter this function will just return every
	 * bean of the given type:
	 *
	 * - Finder::where("page"); //returns all pages
	 *
	 *
	 * @param string $type   type of bean you are looking for
	 * @param string $SQL    SQL code, start with 1 if you want no WHERE-clause
	 * @param array  $values values to bind to slots in query
	 *
	 * @return array $beans beans we come up with..
	 */
	public static function where( $type, $SQL = " 1 ", $values=array(),
			  $tools = false, $ignoreGSQLWarn = false ) {

		if ($SQL==="") $SQL = " 1 ";

		//Sorry, quite draconic filtering
		$type = preg_replace("/\W/","", $type);

		//First get hold of the toolbox
		if (!$tools) $tools = RedBean_Setup::getToolBox();

		RedBean_CompatManager::scanDirect($tools, array(
				  RedBean_CompatManager::C_SYSTEM_MYSQL => "5",
				  RedBean_CompatManager::C_SYSTEM_SQLITE => "3",
				  RedBean_CompatManager::C_SYSTEM_POSTGRESQL => "7"
		));


		//Now get the two tools we need; RedBean and the Adapter
		$redbean = $tools->getRedBean();
		$adapter = $tools->getDatabaseAdapter();
		$writer = $tools->getWriter();

		//Do we need to parse Gold SQL?
		if (!$redbean->isFrozen()) {
			$SQL = self::parseGoldSQL($SQL, $type, $tools);
		}
		else {
			if (!$ignoreGSQLWarn && strpos($SQL,"@")!==false) {
				throw new RedBean_Exception_SQL("Gold SQL is
					only allowed in FLUID mode,
					to ignore use extra argument TRUE for Finder::Where");
			}
		}


		$table = $writer->getFormattedTableName($type);
		//Make a standard ANSI SQL query from the SQL provided
		try {
			$SQL = "SELECT * FROM $table WHERE ".$SQL;

			//Fetch the values using the SQL and value pairs provided
			$rows = $adapter->get($SQL, $values);

		}
		catch(RedBean_Exception_SQL $e) {
			if ($writer->sqlStateIn($e->getSQLState(),array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE
			))) {
				return array();
			}
			else {
				throw $e;
			}
		}


		//Give the rows to RedBean OODB to convert them
		//into beans.
		return $redbean->convertToBeans($type, $rows);


	}

	/**
	 * Parses Gold SQL.
	 * Checks whether columns and tables prefixed with @ exists,
	 * if not they are being replaced by NULL leaving intact the
	 * rest of the query and making the SQL continue to work even
	 * if it's partially broken.
	 * 
	 * @param string $SQL				  sql code to execute
	 * @param string $currentTable	  name of the table
	 * @param RedBean_ToolBox $toolbox toolbox to use
	 *
	 * @return string $SQL resulting sql
	 */
	public static function parseGoldSQL( $SQL, $currentTable,  RedBean_ToolBox $toolbox ) {

		$writer = $toolbox->getWriter();

		//array for the matching in the regex.
		$matches = array();

		//Pattern for our regular expression to filter the prefixes.
		$pattern = "/@[\w\.]+/";

		if (preg_match_all($pattern, $SQL, $matches)) {

			//Get the columns in the master table
			$columns = array_keys( $toolbox->getWriter()->getColumns($currentTable) );
			//Get the tables
			$tables = $writer->getTables();

			//Get the columns we need to check for
			$checks = array_shift( $matches );

			//Loop through the items we need to check...
			foreach($checks as $checkItem) {

				$itemName = substr($checkItem, 1);

				//Ai we need to do a table check as well
				if (strpos($itemName,".")!==false) {

					list($table, $column) = explode(".", $itemName);

					if (!in_array($table, $tables)) {

						$SQL = str_replace("@".$itemName, "NULL", $SQL);

					}
					else {

						$tableCols = array_keys( $toolbox->getWriter()->getColumns($table) );
						if (!in_array($column, ($tableCols))) {
							$SQL = str_replace("@".$itemName, "NULL", $SQL);
						}
						else {
							$SQL = str_replace("@".$itemName, $itemName, $SQL);
						}
					}
				}
				else {

					if (!in_array($itemName, ($columns))) {

						$SQL = str_replace("@".$itemName, "NULL", $SQL);

					}
					else {
						$SQL = str_replace("@".$itemName, $itemName, $SQL);
					}
				}
			}
		}
		return $SQL;
	}

}

/**
 * RedBean Bean Finder
 *
 * @file					RedBean/Plugin/Finder.php
 * @description		Provides a more convenient way to find beans
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Finder extends RedBean_Plugin_Finder {
}

