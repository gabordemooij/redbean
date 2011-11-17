<?php
/**
 * RedBean Facade
 * @file				RedBean/Facade.php
 * @description	Convenience class for RedBeanPHP.
 *						This class hides the object landscape of
 *						RedBean behind a single letter class providing
 *						almost all functionality with simple static calls.
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
class RedBean_Facade {

	/**
	 * Collection of toolboxes
	 * @var array
	 */
	public static $toolboxes = array();
	/**
	 *
	 * Constains an instance of the RedBean Toolbox
	 * @var RedBean_ToolBox
	 *
	 */
	public static $toolbox;

	/**
	 * Constains an instance of RedBean OODB
	 * @var RedBean_OODB
	 */
	public static $redbean;

	/**
	 * Contains an instance of the Query Writer
	 * @var RedBean_QueryWriter
	 */
	public static $writer;

	/**
	 * Contains an instance of the Database
	 * Adapter.
	 * @var RedBean_DBAdapter
	 */
	public static $adapter;


	/**
	 * Contains an instance of the Association Manager
	 * @var RedBean_AssociationManager
	 */
	public static $associationManager;


	/**
	 * Contains an instance of the Extended Association Manager
	 * @var RedBean_ExtAssociationManager
	 */
	public static $extAssocManager;


	/**
	 * Holds an instance of Bean Exporter
	 * @var RedBean_Plugin_BeanExport
	 */
	public static $exporter;

	/**
	 * Holds the Key of the current database.
	 * @var string
	 */
	public static $currentDB = "";

	/**
	 * Returns version ID string
	 * Version No format: <Major>.<Minor>.<Maintenance>.<Fix/Update>
	 *
	 * @return string $version Version ID
	 */
	public static function getVersion() {
		return "2.2";
	}

	/**
	 * Kickstarts redbean for you. This method should be called before you start using
	 * RedBean. The Setup() method can be called without any arguments, in this case it will
	 * try to create a SQLite database in /tmp called red.db (this only works on UNIX-like systems).
	 *
	 * @param string $dsn      Database connection string
	 * @param string $username Username for database
	 * @param string $password Password for database
	 *
	 * @return void
	 */
	public static function setup( $dsn="sqlite:/tmp/red.db", $username=NULL, $password=NULL ) {
		$facadeInstances = self::setupMultiple( array("default"=>array("dsn"=>$dsn,"username"=>$username,"password"=>$password,"frozen"=>false)));
		$facadeInstance = $facadeInstances["default"];
		self::configureFacadeWithToolbox(self::$toolboxes["default"]);
		return $facadeInstance;
	}

	/**
	 * Configures RedBean to work with multiple database and multiple instances of the facade.
	 * This method accepts an array with format:
	 * array( $key =>array('dsn'=>$dsn,'username'=>$username,'password'=>$password,'frozen'=>$trueFalse) )
	 *
	 * @static
	 * @param  array $databases  array with database connection information
	 * @return array $rinstances array with R-instances
	 */
	public static function setupMultiple( $databases ) {
		$objects = array();
		foreach($databases as $key=>$database) {
			self::$toolboxes[$key] = RedBean_Setup::kickstart($database["dsn"],$database["username"],$database["password"],$database["frozen"]);
			$objects[$key] = new RedBean_FacadeHelper($key);
		}
		return $objects;
	}

	/**
	 * Adds a database to the facade, afterwards you can select the database using
	 * selectDatabase($key).
	 *
	 * @static
	 * @param string      $key    ID for the database
	 * @param string      $dsn    DSN for the database
	 * @param string      $user   User for connection
	 * @param null|string $pass   Password for connection
	 * @param bool        $frozen Whether this database is frozen or not
	 *
	 * @return void
	 */
	public static function addDatabase( $key, $dsn, $user, $pass=null, $frozen=false ) {
		self::$toolboxes[$key] = RedBean_Setup::kickstart($dsn,$user,$pass,$frozen);
	}


	/**
	 * Selects a different database for the Facade to work with.
	 *
	 * @static
	 * @param  string $key Key of the database to select
	 * @return int 1
	 */
	public static function selectDatabase($key) {
		if (self::$currentDB===$key) return false;
		self::configureFacadeWithToolbox(self::$toolboxes[$key]);
		self::$currentDB = $key;
		return true;
	}


	/**
	 * Toggles DEBUG mode.
	 * In Debug mode all SQL that happens under the hood will
	 * be printed to the screen.
	 *
	 * @param boolean $tf
	 */
	public static function debug( $tf = true ) {
		self::$adapter->getDatabase()->setDebugMode( $tf );
	}

	/**
	 * Stores a RedBean OODB Bean and returns the ID.
	 *
	 * @param  RedBean_OODBBean $bean bean
	 *
	 * @return integer $id id
	 */
	public static function store( RedBean_OODBBean $bean ) {
		return self::$redbean->store( $bean );
	}


	/**
	 * Freezes RedBean. In frozen mode the schema cannot be altered.
	 * Frozen mode is recommended for production use because it is
	 * secure and fast.
	 *
	 * @param boolean $tf whether to turn it on or off.
	 *
	 * @return void
	 */
	public static function freeze( $tf = true ) {
		self::$redbean->freeze( $tf );
	}


	/**
	 * Loads the bean with the given type and id and returns it.
	 *
	 * @param string  $type type
	 * @param integer $id   id of the bean you want to load
	 *
	 * @return RedBean_OODBBean $bean
	 */
	public static function load( $type, $id ) {
		return self::$redbean->load( $type, $id );
	}

	/**
	 * Deletes the specified bean.
	 *
	 * @param RedBean_OODBBean $bean bean to be deleted
	 *
	 * @return mixed
	 */
	public static function trash( RedBean_OODBBean $bean ) {
		return self::$redbean->trash( $bean );
	}

	/**
	 * Dispenses a new RedBean OODB Bean for use with
	 * the rest of the methods.
	 *
	 * @param string $type type
	 *
	 * @return RedBean_OODBBean $bean a new bean
	 */
	public static function dispense( $type, $num = 1 ) {
		if ($num==1) {
			return self::$redbean->dispense( $type );
		}
		else {
			$beans = array();
			for($v=0; $v<$num; $v++) $beans[] = self::$redbean->dispense( $type );
			return $beans;
		}
	}

	/**
	 * Loads a bean if ID > 0 else dispenses.
	 *
	 * @param string  $type type
	 * @param integer $id   id
	 *
	 * @return RedBean_OODBBean $bean bean
	 */
	public static function loadOrDispense( $type, $id = 0 ) {
		return ($id ? RedBean_Facade::load($type,(int)$id) : RedBean_Facade::dispense($type));
	}

	/**
	 * Convience method. Tries to find beans of a certain type,
	 * if no beans are found, it dispenses a bean of that type.
	 *
	 * @param  string $type   type of bean you are looking for
	 * @param  string $sql    SQL code for finding the bean
	 * @param  array  $values parameters to bind to SQL
	 *
	 * @return array $beans Contains RedBean_OODBBean instances
	 */
	public static function findOrDispense( $type, $sql, $values ) {
		$foundBeans = self::find($type,$sql,$values);
		if (count($foundBeans)==0) return array(self::dispense($type)); else return $foundBeans;
	}

	/**
	 * Associates two Beans. This method will associate two beans with eachother.
	 * You can then get one of the beans by using the related() function and
	 * providing the other bean. You can also provide a base bean in the extra
	 * parameter. This base bean allows you to add extra information to the association
	 * record. Note that this is for advanced use only and the information will not
	 * be added to one of the beans, just to the association record.
	 * It's also possible to provide an array or JSON string as base bean. If you
	 * pass a scalar this function will interpret the base bean as having one
	 * property called 'extra' with the value of the scalar.
	 *
	 * @param RedBean_OODBBean $bean1 bean that will be part of the association
	 * @param RedBean_OODBBean $bean2 bean that will be part of the association
	 * @param mixed $extra            bean, scalar, array or JSON providing extra data.
	 *
	 * @return mixed
	 */
	public static function associate( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, $extra = null ) {
		//No extra? Just associate like always (default)
		if (!$extra) {
			return self::$associationManager->associate( $bean1, $bean2 );
		}
		else{
			if (!is_array($extra)) {
				$info = json_decode($extra,true);
				if (!$info) $info = array("extra"=>$extra);
			}
			else {
				$info = $extra;
			}
			$bean = RedBean_Facade::dispense("typeLess");
			$bean->import($info);
			return self::$extAssocManager->extAssociate($bean1, $bean2, $bean);
		}

	}


	/**
	 * Breaks the association between two beans.
	 * This functions breaks the association between a pair of beans. After
	 * calling this functions the beans will no longer be associated with
	 * eachother. Calling related() with either one of the beans will no longer
	 * return the other bean.
	 *
	 * @param RedBean_OODBBean $bean1 bean
	 * @param RedBean_OODBBean $bean2 bean
	 *
	 * @return mixed
	 */
	public static function unassociate( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 , $fast=false) {
		return self::$associationManager->unassociate( $bean1, $bean2, $fast );
	}

	/**
	 * Returns all the beans associated with $bean.
	 * This method will return an array containing all the beans that have
	 * been associated once with the associate() function and are still
	 * associated with the bean specified. The type parameter indicates the
	 * type of beans you are looking for. You can also pass some extra SQL and
	 * values for that SQL to filter your results after fetching the
	 * related beans.
	 *
	 * Dont try to make use of subqueries, a subquery using IN() seems to
	 * be slower than two queries!
	 *
	 *
	 * @param RedBean_OODBBean $bean the bean you have
	 * @param string           $type the type of beans you want
	 * @param string           $sql  SQL snippet for extra filtering
	 * @param array            $val  values to be inserted in SQL slots
	 *
	 * @return array $beans	beans yielded by your query.
	 */
	public static function related( RedBean_OODBBean $bean, $type, $sql=null, $values=array()) {
		$keys = self::$associationManager->related( $bean, $type );
		if (count($keys)==0) return array();
		if (!$sql) return self::batch($type, $keys);
		$idfield = self::$writer->getIDField( $type );
		$rows = self::$writer->selectRecord( $type, array($idfield=>$keys),array($sql,$values),false );
		return self::$redbean->convertToBeans($type,$rows);
	}


	/**
	 * Checks whether a pair of beans is related N-M. This function does not
	 * check whether the beans are related in N:1 way.
	 *
	 * @static
	 * @param RedBean_OODBBean $bean1 first bean
	 * @param RedBean_OODBBean $bean2 second bean
	 *
	 * @return bool $yesNo whether they are related
	 */
	public static function areRelated( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		return self::$associationManager->areRelated($bean1,$bean2);
	}


	/**
	 * The opposite of related(). Returns all the beans that are not
	 * associated with the bean provided.
	 *
	 * @param RedBean_OODBBean $bean   bean provided
	 * @param string				$type   type of bean you are searching for
	 * @param string				$sql    SQL for extra filtering
	 * @param array				$values values to be inserted in SQL slots
	 *
	 * @return array $beans beans
	 */
	public static function unrelated(RedBean_OODBBean $bean, $type, $sql=null, $values=array()) {
		$idfield = self::$writer->getIDField( $type );
		$keys = self::$associationManager->related( $bean, $type );
		$rows = self::$writer->selectRecord( $type, array($idfield=>$keys), array($sql,$values), false, true );
		return self::$redbean->convertToBeans($type,$rows);

	}


	/**
	 * Returns only single associated bean. This is the default way RedBean
	 * handles N:1 relations, by just returning the 1st one ;)
	 *
	 * @param RedBean_OODBBean $bean   bean provided
	 * @param string				$type   type of bean you are searching for
	 * @param string				$sql    SQL for extra filtering
	 * @param array				$values values to be inserted in SQL slots
	 *
	 *
	 * @return RedBean_OODBBean $bean
	 */
	public static function relatedOne( RedBean_OODBBean $bean, $type, $sql='1', $values=array() ) {
		$beans = self::related($bean, $type, $sql, $values);
		if (count($beans)==0) return null;
		return reset( $beans );
	}

	/**
	 * Clears all associated beans.
	 *
	 * @param RedBean_OODBBean $bean
	 * @param string $type type
	 *
	 * @return mixed
	 */
	public static function clearRelations( RedBean_OODBBean $bean, $type, RedBean_OODBBean $bean2 = null, $extra = null ) {
		$r = self::$associationManager->clearRelations( $bean, $type );
		if ($bean2) {
			self::associate($bean, $bean2, $extra);
		}
		return $r;
	}



	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 *
	 * @param string $type   type
	 * @param string $sql    sql
	 * @param array  $values values
	 *
	 * @return array $beans  beans
	 */
	public static function find( $type, $sql=null, $values=array() ) {
		return self::$redbean->find($type,array(),array($sql,$values));
	}



	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 * The variation also exports the beans (i.e. it returns arrays).
	 *
	 * @param string $type   type
	 * @param string $sql    sql
	 * @param array  $values values
	 *
	 * @return array $arrays arrays
	 */
	public static function findAndExport($type, $sql=null, $values=array()) {
		$items = self::find( $type, $sql, $values );
		$arr = array();
		foreach($items as $key=>$item) {
			$arr[$key]=$item->export();
		}
		return $arr;
	}

	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 * This variation returns the first bean only.
	 *
	 * @param string $type	 type
	 * @param string $sql	 sql
	 * @param array  $values values
	 *
	 * @return RedBean_OODBBean $bean
	 */
	public static function findOne( $type, $sql=null, $values=array()) {
		$items = self::find($type,$sql,$values);
		return reset($items);
	}

	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 * This variation returns the last bean only.
	 *
	 * @param string $type	 type
	 * @param string $sql	 sql
	 * @param array  $values values
	 *
	 * @return RedBean_OODBBean $bean
	 */
	public static function findLast( $type, $sql=null, $values=array() ) {
		$items = self::find( $type, $sql, $values );
		return end( $items );
	}


	/**
	 * Returns an array of beans.
	 *
	 * @param string $type type
	 * @param array  $ids  ids
	 *
	 * @return array $beans
	 */
	public static function batch( $type, $ids ) {
		return self::$redbean->batch($type, $ids);
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql	 sql
	 * @param array  $values values
	 *
	 * @return array $results
	 */
	public static function exec( $sql, $values=array() ) {
		if (!self::$redbean->isFrozen()) {
			try {
				$rs = RedBean_Facade::$adapter->exec( $sql, $values );
			}catch(RedBean_Exception_SQL $e) {
				if(self::$writer->sqlStateIn($e->getSQLState(),
				array(
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
				)) {
					return NULL;
				}
				else {
					throw $e;
				}

			}
			return $rs;
		}
		else {
			return RedBean_Facade::$adapter->exec( $sql, $values );
		}
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql	 sql
	 * @param array  $values values
	 *
	 * @return array $results
	 */
	public static function getAll( $sql, $values=array() ) {

		if (!self::$redbean->isFrozen()) {
			try {
				$rs = RedBean_Facade::$adapter->get( $sql, $values );
			}catch(RedBean_Exception_SQL $e) {
				if(self::$writer->sqlStateIn($e->getSQLState(),
				array(
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
				)) {
					return array();
				}
				else {
					throw $e;
				}

			}
			return $rs;
		}
		else {
			return RedBean_Facade::$adapter->get( $sql, $values );
		}
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql	 sql
	 * @param array  $values values
	 *
	 * @return string $result scalar
	 */
	public static function getCell( $sql, $values=array() ) {

		if (!self::$redbean->isFrozen()) {
			try {
				$rs = RedBean_Facade::$adapter->getCell( $sql, $values );
			}catch(RedBean_Exception_SQL $e) {
				if(self::$writer->sqlStateIn($e->getSQLState(),
				array(
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
				)) {
					return NULL;
				}
				else {
					throw $e;
				}

			}
			return $rs;
		}
		else {
			return RedBean_Facade::$adapter->getCell( $sql, $values );
		}
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql	 sql
	 * @param array  $values values
	 *
	 * @return array $results
	 */
	public static function getRow( $sql, $values=array() ) {

		if (!self::$redbean->isFrozen()) {
			try {
				$rs = RedBean_Facade::$adapter->getRow( $sql, $values );
			}catch(RedBean_Exception_SQL $e) {
				if(self::$writer->sqlStateIn($e->getSQLState(),
				array(
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
				)) {
					return array();
				}
				else {
					throw $e;
				}

			}
			return $rs;
		}
		else {
			return RedBean_Facade::$adapter->getRow( $sql, $values );
		}

	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql	 sql
	 * @param array  $values values
	 *
	 * @return array $results
	 */
	public static function getCol( $sql, $values=array() ) {

		if (!self::$redbean->isFrozen()) {
			try {
				$rs = RedBean_Facade::$adapter->getCol( $sql, $values );
			}catch(RedBean_Exception_SQL $e) {
				if(self::$writer->sqlStateIn($e->getSQLState(),
				array(
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
				)) {
					return array();
				}
				else {
					throw $e;
				}

			}
			return $rs;
		}
		else {
			return RedBean_Facade::$adapter->getCol( $sql, $values );
		}
	}


	/**
	 * Makes a copy of a bean. This method copies the bean and
	 * adds the specified associations.
	 *
	 * For instance: R::copy( $book, "author,library" );
	 *
	 * Duplicates the $book bean and copies the association links
	 * author and library as well. Note that only many-to-many
	 * associations can be copied. Also note that no author or library
	 * beans are copied, only the connections or references to these
	 * beans.
	 *
	 * @param RedBean_OODBBean $bean							bean
	 * @param string				$associatedBeanTypesStr bean types associated
	 *
	 * @return array $copiedBean the duplicated bean
	 */
	public static function copy($bean, $associatedBeanTypesStr="") {
		$type = $bean->getMeta("type");
		$copy = RedBean_Facade::dispense($type);
		$copy->import( $bean->export() );
		$copy->copyMetaFrom( $bean );
		$copy->id = 0;
		RedBean_Facade::store($copy);
		$associatedBeanTypes = explode(",",$associatedBeanTypesStr);
		foreach($associatedBeanTypes as $associatedBeanType) {
			$assocBeans = RedBean_Facade::related($bean, $associatedBeanType);
			foreach($assocBeans as $assocBean) {
				RedBean_Facade::associate($copy,$assocBean);
			}
		}
		$copy->setMeta("original",$bean);
		return $copy;
	}

	/**
	 * Given an array of two beans and a property, this method
	 * swaps the value of the property.
	 * This is handy if you need to swap the priority or orderNo
	 * of an item (i.e. bug-tracking, page order).
	 *
	 * @param array  $beans    beans
	 * @param string $property property
	 */
	public static function swap( $beans, $property ) {
		$bean1 = array_shift($beans);
		$bean2 = array_shift($beans);
		$tmp = $bean1->$property;
		$bean1->$property = $bean2->$property;
		$bean2->$property = $tmp;
		RedBean_Facade::store($bean1);
		RedBean_Facade::store($bean2);
	}

	/**
	 * Converts a series of rows to beans.
	 *
	 * @param string $type type
	 * @param array  $rows must contain an array of arrays.
	 *
	 * @return array $beans
	 */
	public static function convertToBeans($type,$rows) {
		return self::$redbean->convertToBeans($type,$rows);
	}

	/**
	 * This static property can be set to force the system to return
	 * comma separated lists as in legacy versions.
	 *
	 * @var boolean
	 */
	public static $flagUseLegacyTaggingAPI = false;

	/**
	 * Tests whether a bean has been associated with one ore more
	 * of the listed tags. If the third parameter is TRUE this method
	 * will return TRUE only if all tags that have been specified are indeed
	 * associated with the given bean, otherwise FALSE.
	 * If the third parameter is FALSE this
	 * method will return TRUE if one of the tags matches, FALSE if none
	 * match.
	 *
	 * @static
	 * @param  RedBean_OODBBean $bean bean to check for tags
	 * @param  array            $tags list of tags
	 * @param  boolean          $all  whether they must all match or just some
	 *
	 * @return boolean $didMatch Whether the bean has been assoc. with the tags
	 */
	public static function hasTag($bean, $tags, $all=false) {
		$foundtags = RedBean_Facade::tag($bean);
		if (is_string($foundtags)) $foundtags = explode(",",$tags);
		$same = array_intersect($tags,$foundtags);
		if ($all) {
			return (implode(",",$same)===implode(",",$tags));
		}
		return (bool) (count($same)>0);
	}

	/**
	 * Removes all sepcified tags from the bean. The tags specified in
	 * the second parameter will no longer be associated with the bean.
	 *
	 * @static
	 * @param  RedBean_OODBBean $bean    tagged bean
	 * @param  array            $tagList list of tags (names)
	 *
	 * @return void
	 */
	public static function untag($bean,$tagList) {
		if ($tagList!==false && !is_array($tagList)) $tags = explode( ",", (string)$tagList); else $tags=$tagList;
		foreach($tags as $tag) {
			$t = RedBean_Facade::findOne("tag"," title = ? ",array($tag));
			if ($t) {
				RedBean_Facade::unassociate( $bean, $t );
			}
		}
	}

	/**
	 * Tags a bean or returns tags associated with a bean.
	 * If $tagList is null or omitted this method will return a
	 * comma separated list of tags associated with the bean provided.
	 * If $tagList is a comma separated list (string) of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param RedBean_OODBBean $bean    bean
	 * @param mixed				$tagList tags
	 *
	 * @return string $commaSepListTags
	 */
	public static function tag( RedBean_OODBBean $bean, $tagList = null ) {
		if (is_null($tagList)) {
			$tags = RedBean_Facade::related( $bean, "tag");
			$foundTags = array();
			foreach($tags as $tag) {
				$foundTags[] = $tag->title;
			}
			if (self::$flagUseLegacyTaggingAPI) return implode(",",$foundTags);
			return $foundTags;
		}

		RedBean_Facade::clearRelations( $bean, "tag" );
		RedBean_Facade::addTags( $bean, $tagList );
	}

	/**
	 * Adds tags to a bean.
	 * If $tagList is a comma separated list (string) of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param RedBean_OODBBean $bean    bean
	 * @param mixed				$tagList tags
	 *
	 * @return void
	 */
	public static function addTags( RedBean_OODBBean $bean, $tagList ) {
		if ($tagList!==false && !is_array($tagList)) $tags = explode( ",", (string)$tagList); else $tags=$tagList;
		if ($tagList===false) return;

		foreach($tags as $tag) {
			$t = RedBean_Facade::findOne("tag"," title = ? ",array($tag));
			if (!$t) {
				$t = RedBean_Facade::dispense("tag");
				$t->title = $tag;
				RedBean_Facade::store($t);
			}
			RedBean_Facade::associate( $bean, $t );
		}
	}

	/**
	 * @static
	 * Returns all beans that have been tagged with one of the tags given.
	 *
	 * @param  $beanType
	 * @param  $tagList
	 *
	 * @return array
	 */
	public static function tagged( $beanType, $tagList ) {
		if ($tagList!==false && !is_array($tagList)) $tags = explode( ",", (string)$tagList); else $tags=$tagList;
		$collection = array();
		foreach($tags as $tag) {
			$retrieved = array();
			$tag = RedBean_Facade::findOne("tag"," title = ? ", array($tag));
			if ($tag) $retrieved = RedBean_Facade::related($tag, $beanType);
			foreach($retrieved as $key=>$bean) $collection[$key]=$bean;
		}
		return $collection;
	}


	/**
	 * Wipes all beans of type $beanType.
	 *
	 * @param string $beanType type of bean you want to destroy entirely.
	 */
	public static function wipe( $beanType ) {
		RedBean_Facade::$redbean->wipe($beanType);
	}

	/**
	 * Counts beans
	 *
	 * @param string $beanType type of bean
	 *
	 * @return integer $numOfBeans
	 */

	public static function count( $beanType ) {
		return RedBean_Facade::$redbean->count($beanType);
	}

	/**
	 * Configures the facade, want to have a new Writer? A new Object Database or a new
	 * Adapter and you want it on-the-fly? Use this method to hot-swap your facade with a new
	 * toolbox.
	 *
	 * @static
	 * @param RedBean_ToolBox $tb toolbox
	 *
	 * @return RedBean_ToolBox $tb old, rusty, previously used toolbox
	 */
	public static function configureFacadeWithToolbox( RedBean_ToolBox $tb ) {
		$oldTools = self::$toolbox;
		self::$toolbox = $tb;
		self::$writer = self::$toolbox->getWriter();
		self::$adapter = self::$toolbox->getDatabaseAdapter();
		self::$redbean = self::$toolbox->getRedBean();
		self::$associationManager = new RedBean_AssociationManager( self::$toolbox );
		self::$redbean->setAssociationManager(self::$associationManager);
		self::$extAssocManager = new RedBean_ExtAssociationManager( self::$toolbox );
		$helper = new RedBean_ModelHelper();
		self::$redbean->addEventListener("update", $helper );
		self::$redbean->addEventListener("open", $helper );
		self::$redbean->addEventListener("delete", $helper );
		self::$associationManager->addEventListener("delete", $helper );
		self::$redbean->addEventListener("after_delete", $helper );
		self::$redbean->addEventListener("after_update", $helper );
		self::$redbean->addEventListener("dispense", $helper );
		return $oldTools;
	}


	/**
	 * facade method for Cooker.
	 *
	 * @static
	 * @param  $arr
	 * @return array
	 */
	public static function cooker($arr) {
		return RedBean_Cooker::load($arr, RedBean_Facade::$toolbox);
	}

	/**
	 * Creates a view called $viewID by joining types specified in $types.
	 * This function only works in fluid mode, in frozen mode it immediately
	 * returns boolean false.
	 *
	 * @static
	 * @throws RedBean_Exception_Security
	 *
	 * @param  string $viewID  name of the view you want to create
	 * @param  string $types   comma separated list of types
	 *
	 * @return bool	  $success whether the view has been created or not
	 */
	public static function view($viewID, $types) {
		if (self::$redbean->isFrozen()) return false;
		$types = explode(",",$types);
		if (count($types)<2) throw new RedBean_Exception_Security("Creating useless view for just one type? Provide at least two types!");
		$refType = array_shift($types);
		$viewManager = new RedBean_ViewManager( self::$toolbox );
		return $viewManager->createView($viewID,$refType,$types);

	}

	/**
	 * Mass Bean Export function.
	 * Exports all beans specified in the first argument.
	 *
	 * @static
	 * @param  array $beans collection of beans to be exported
	 *
	 * @return array Array containing sub-arrays representing beans
	 */
	public static function exportAll($beans,$recursively=false) {

		if ($recursively) {
			if (!self::$exporter) {
				self::$exporter = new RedBean_Plugin_BeanExport(self::$toolbox);
				self::$exporter->loadSchema();
			}
			return self::$exporter->export($beans);
		}
		else {

			$array = array();
			foreach($beans as $bean) {
				if ($bean instanceof RedBean_OODBBean) {
					$array[] = $bean->export();
				}
			}
			return $array;
		}
	}

	/**
	 * Mass export beans to object instances of a certain class.
	 *
	 * @param array  $beans collection of beans to be exported
	 * @param string $class name of the class to be used
	 *
	 * @return array $instances collection of instances of $class filled with beans
	 */
	public static function exportAllToObj($beans, $classname='stdClass') {
		$array = array();
		foreach($beans as $bean) {
			if ($bean instanceof RedBean_OODBBean) {
				$inst = new $classname;
				$bean->exportToObj($inst);
				$array[] = $inst;
			}
		}
		return $array;
	}


	/**
	 * Facade Convience method for adapter transaction system.
	 * Begins a transaction.
	 *
	 * @static
	 * @return void
	 */
	public static function begin() {
		self::$adapter->startTransaction();
	}

	/**
	 * Facade Convience method for adapter transaction system.
	 * Commits a transaction.
	 *
	 * @static
	 * @return void
	 */
	public static function commit() {
		self::$adapter->commit();
	}

	/**
	 * Facade Convience method for adapter transaction system.
	 * Rolls back a transaction.
	 *
	 * @static
	 * @return void
	 */
	public static function rollback() {
		self::$adapter->rollback();
	}

	/**
	 * Returns a list of columns. Format of this array:
	 * array( fieldname => type )
	 * Note that this method only works in fluid mode because it might be
	 * quite heavy on production servers!
	 *
	 * @static
	 * @param  string $table   name of the table (not type) you want to get columns of
	 *
	 * @return array  $columns list of columns and their types
	 */
	public static function getColumns($table) {
		return self::$writer->getColumns($table);
	}

	/**
	 * Returns a SQL formatted date string (i.e. 1980-01-01 10:00:00)
	 *
	 * @static
	 * @return string $SQLTimeString SQL Formatted time string
	 */
	public static function now() {
		return date('Y-m-d H:i:s');
	}

	/**
	 * Generates question mark slots for an array of values.
	 *
	 * @param array $array
	 * @return string $slots
	 */
	public static function genSlots($array) {
		if (count($array)>0) {
			$filler = array_fill(0,count($array),'?');
			return implode(',',$filler);
		}
		else {
			return '';
		}
	}

	/**
	 * Installs the default bean formatter with a prefix.
	 *
	 * @param string $prefix prefix
	 */
	public static function prefix($prefix) {
		$beanFormatter = new RedBean_DefaultBeanFormatter;
		$beanFormatter->setPrefix($prefix);
		self::$writer->setBeanFormatter($beanFormatter);
	}

	/**
	 * Nukes the entire database.
	 */
	public static function nuke() {
		if (!self::$redbean->isFrozen()) {
			self::$writer->wipeAll();
		}
	}

}

//Compatibility with PHP 5.2 and earlier.
if (function_exists('lcfirst')===false){
	function lcfirst( $str ){
		return (string)(strtolower(substr($str,0,1)).substr($str,1));
    }
}


