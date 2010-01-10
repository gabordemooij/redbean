<?php
/**
 * RedBean Bean Finder
 * @file 		RedBean/Plugin/Finder.php
 * @description		Provides a more convenient way to find beans
 *
 * @author			Gabor de Mooij
 * @license			BSD
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
	 * @param string $type
	 * @param string $SQL
	 * @param array $values
	 * @return array $beans
	 */
	public static function where( $type, $SQL = " 1 ", $values=array() ) {

		//Sorry, quite draconic filtering
		$type = preg_replace("/\W/","", $type);

		//First get hold of the toolbox
		$tools = RedBean_Setup::getToolBox();

		RedBean_CompatManager::scanDirect($tools, array(RedBean_CompatManager::C_SYSTEM_MYSQL => "5"));

		//Now get the two tools we need; RedBean and the Adapter
		$redbean = $tools->getRedBean();
		$adapter = $tools->getDatabaseAdapter();

		//Make a standard ANSI SQL query from the SQL provided
		try{
			$SQL = "SELECT * FROM $type WHERE ".$SQL;

			//Fetch the values using the SQL and value pairs provided
			$rows = $adapter->get($SQL, $values);

		}
		catch(RedBean_Exception_SQL $e) { 
			if ($e->getSQLState()=="42S02" || $e->getSQLState()=="42S22") { //no such table? no problem. may happen.
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

}

//Short Alias for this class
class Finder extends RedBean_Plugin_Finder { }

