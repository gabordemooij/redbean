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
class RObj { public function __call($f,$a){ return call_user_func_array("R::$f", $a); } }
class R {

	/**
	 * If you prefer to call methods on an instance, you can
	 * use this method.
	 */
	public static function getInstance() {
		return new RObj;
	}
	
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
	 * Contains an instance of the Tree Manager
	 * @var RedBean_TreeManager
	 */
	public static $treeManager;

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
	 *
	 * Constains an instance of the RedBean Link Manager
	 * @var RedBean_LinkManager
	 *
	 */
	public static $linkManager;

	/**
	 * Returns version ID string
	 * Version No format: <Major>.<Minor>.<Maintenance>.<Fix/Update>
	 * 
	 * @return string $version Version ID
	 */
	public static function getVersion() {
		return "1.2.9.1";
	}

	/**
	 * Kickstarts redbean for you.
	 * @param string $dsn
	 * @param string $username
	 * @param string $password
	 */
	public static function setup( $dsn="sqlite:/tmp/red.db", $username=NULL, $password=NULL ) {
		RedBean_Setup::kickstart( $dsn, $username, $password );
		self::$toolbox = RedBean_Setup::getToolBox();
		self::$writer = self::$toolbox->getWriter();
		self::$adapter = self::$toolbox->getDatabaseAdapter();
		self::$redbean = self::$toolbox->getRedBean();
		self::$associationManager = new RedBean_AssociationManager( self::$toolbox );
		self::$treeManager = new RedBean_TreeManager( self::$toolbox );
		self::$linkManager = new RedBean_LinkManager( self::$toolbox );
		self::$extAssocManager = new RedBean_ExtAssociationManager( self::$toolbox );
		$helper = new RedBean_ModelHelper();
		self::$redbean->addEventListener("update", $helper );
		self::$redbean->addEventListener("open", $helper );
		self::$redbean->addEventListener("delete", $helper );
		self::$redbean->addEventListener("after_delete", $helper );
		self::$redbean->addEventListener("after_update", $helper );
		self::$redbean->addEventListener("dispense", $helper );
		
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
	public static function dispense( $type ) {
		return self::$redbean->dispense( $type );
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
		return ($id ? R::load($type,(int)$id) : R::dispense($type));
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
			$bean = R::dispense("typeLess");
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
	public static function unassociate( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 ) {
		return self::$associationManager->unassociate( $bean1, $bean2 );
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
	 * @param RedBean_OODBBean $bean the bean you have
	 * @param string				$type the type of beans you want
	 * @param string				$sql  SQL snippet for extra filtering
	 * @param array				$val  values to be inserted in SQL slots
	 *
	 * @return array $beans	beans yielded by your query.
	 */
	public static function related( RedBean_OODBBean $bean, $type, $sql=null, $values=array()) {

		$keys = self::$associationManager->related( $bean, $type );
		if (count($keys)==0) return array();
		if (!$sql) return self::batch($type, $keys);
		$idfield = self::$writer->getIDField( $type );
		$sqlSnippet = self::$writer->getSQLSnippetFilter($idfield, $keys, $sql);
		return self::find( $type, $sqlSnippet, $values );

		
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
		
		$keys = self::$associationManager->related( $bean, $type );
		$idfield = self::$writer->getIDField( $type );
		$sqlSnippet = self::$writer->getSQLSnippetFilter($idfield, $keys, $sql, true);
		return self::find( $type, $sqlSnippet, $values );

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
	 * @param RedBean_OODBBean $bean
	 * @param string $type
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
	 * Attaches $child bean to $parent bean.
	 *
	 * @param RedBean_OODBBean $parent parent
	 * @param RedBean_OODBBean $child  child
	 * @return mixed
	 */
	public static function attach( RedBean_OODBBean $parent, RedBean_OODBBean $child ) {
		return self::$treeManager->attach( $parent, $child );
	}

	/**
	 * @deprecated
	 * Links two beans using a foreign key field, 1-N Assoc only.
	 *
	 * @param RedBean_OODBBean $bean1 bean1
	 * @param RedBean_OODBBean $bean2 bean2
	 *
	 * @return mixed
	 */
	public static function link( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, $name = null ) {
		return self::$linkManager->link( $bean1, $bean2, $name );
	}
	/**
	 *
	 * @deprecated
	 * @param RedBean_OODBBean $bean     bean
	 * @param string				$typeName type
	 *
	 * @return mixed
	 */
	public static function getBean( RedBean_OODBBean $bean, $typeName, $name = null ) {
		return self::$linkManager->getBean($bean, $typeName, $name );
	}
	/**
	 *	@deprecated
	 * @param RedBean_OODBBean $bean		 bean
	 * @param string				$typeName type
	 *
	 * @return mixed
	 */
	public static function getKey( RedBean_OODBBean $bean, $typeName, $name = null ) {
		return self::$linkManager->getKey($bean, $typeName, $name );
	}
	/**
	 * @deprecated
	 *
	 * @param RedBean_OODBBean $bean		 bean
	 * @param string				$typeName type
	 */
	public static function breakLink( RedBean_OODBBean $bean, $typeName, $name = null ) {
		return self::$linkManager->breakLink( $bean, $typeName, $name );
	}

	/**
	 * Returns all children beans under parent bean $parent
	 *
	 * @param RedBean_OODBBean $parent parent
	 *
	 * @return array $childBeans child beans
	 */
	public static function children( RedBean_OODBBean $parent ) {
		return self::$treeManager->children( $parent );
	}

	/**
	 * Returns the parent of a bean.
	 *
	 * @param RedBean_OODBBean $bean bean
	 *
	 * @return RedBean_OODBBean $bean bean
	 */
	public static function getParent( RedBean_OODBBean $bean ) {
		return self::$treeManager->getParent( $bean );
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
	public static function find( $type, $sql="1", $values=array() ) {
		return Finder::where( $type, $sql, $values );
	}


	/**
	 * @deprecated
	 *
	 * Use related() instead.
	 *
	 * Convenience Method
	 *
	 * @param RedBean_OODBBean $bean   bean
	 * @param string				$type   type
	 * @param string				$sql    sql
	 * @param array				$values values
	 *
	 * @return array $beans
	 */
	public static function findRelated( RedBean_OODBBean $bean, $type, $sql=" id IN (:keys) ", $values=array()  ) {
		$keys = self::$associationManager->related($bean,$type);
		$sql=str_replace(":keys",implode(",",$keys),$sql);
		return self::find($type,$sql,$values);
	}

	/**
	 * @deprecated
	 *
	 * Use related() instead.
	 *
	 * Convenience Method
	 *
	 * @param RedBean_OODBBean $bean   bean
	 * @param string				$type   type
	 * @param string				$sql    sql
	 * @param array				$values values
	 *
	 * @return array $beans
	 */
	public static function findLinks( RedBean_OODBBean $bean, $type, $sql=" id IN (:keys) ", $values=array() ) {
		$keys = self::$linkManager->getKeys($bean,$type);
		$sql=str_replace(":keys",implode(",",$keys),$sql);
		return self::find($type,$sql,$values);
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
	public static function findAndExport($type, $sql="1", $values=array()) {
		$items = Finder::where( $type, $sql, $values );
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
	public static function findOne( $type, $sql="1", $values=array()) {
		$items = R::find($type,$sql,$values);
		return reset($items);
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
	 * Returns a simple list instead of beans, based
	 * on a type, property and an SQL where clause.
	 *
	 * @param string $type  type
	 * @param string $prop  property
	 * @param string $where SQL
	 *
	 * @return array $list array items
	 */
	public static function lst( $type,$prop,$sql=" 1 " ) {
		$list = self::find($type,$sql);
		$listItems = array();
		foreach($list as $id=>$item) {
			$listItems[] = $item->$prop;
		}
		return $listItems;
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
		return self::secureExec(function($sql, $values) {
			return R::$adapter->exec( $sql, $values );
		}, NULL,$sql, $values );
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
		return self::secureExec(function($sql, $values) {
			return R::$adapter->get( $sql, $values );
		}, array(), $sql, $values);
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
		return self::secureExec(function($sql, $values) {
			return R::$adapter->getCell( $sql, $values );
		}, NULL, $sql, $values);
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
		return self::secureExec(function($sql, $values) {
			return R::$adapter->getRow( $sql, $values );
		}, array(),$sql, $values);
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
		return self::secureExec(function($sql, $values) {
			return R::$adapter->getCol( $sql, $values );
		}, array(),$sql, $values);
	}

	/**
	 * Executes SQL function but corrects for SQL states.
	 *
	 * @param closure $func		closure
	 * @param mixed   $default default value to return
	 * @param string  $sql		SQL
	 * @param array   $values  values for slots
	 *
	 * @return mixed $results
	 */
	private static function secureExec( $func, $default=NULL, $sql, $values ) {
		if (!self::$redbean->isFrozen()) {
			try {
				$rs = $func($sql,$values);
			}catch(RedBean_Exception_SQL $e) { //die($e);
				if(self::$writer->sqlStateIn($e->getSQLState(),
				array(
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
				)) {
					return $default;
				}
				else {
					throw $e;
				}

			}
			return $rs;
		}
		else {
			return $func($sql,$values);
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
		$copy = R::dispense($type);
		$copy->import( $bean->export() );
		$copy->copyMetaFrom( $bean );
		$copy->id = 0;
		R::store($copy);
		$associatedBeanTypes = explode(",",$associatedBeanTypesStr);
		foreach($associatedBeanTypes as $associatedBeanType) {
			$assocBeans = R::related($bean, $associatedBeanType);
			foreach($assocBeans as $assocBean) {
				R::associate($copy,$assocBean);
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
		R::store($bean1);
		R::store($bean2);
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
			$tags = R::related( $bean, "tag");
			$foundTags = array();
			foreach($tags as $tag) {
				$foundTags[] = $tag->title;
			}
			return implode(",",$foundTags);
		}
	
		
		if ($tagList!==false && !is_array($tagList)) $tags = explode( ",", (string)$tagList); else $tags=$tagList;

		if (is_array($tags)) {
		foreach($tags as $tag) {
			if (preg_match("/\W/",$tag)) throw new RedBean_Exception("Invalid Tag. Tags may only contain alpha-numeric characters");
		}
		}
		

		R::clearRelations( $bean, "tag" );
		if ($tagList===false) return;
		
		foreach($tags as $tag) {
			
			$t = R::findOne("tag"," title = ? ",array($tag));
			if (!$t) {
				$t = R::dispense("tag");
				$t->title = $tag;
				R::store($t);
			}
			R::associate( $bean, $t ); 
		}

	}
	
	


}

//Helper functions
function tbl($table) {
	return R::$writer->getFormattedTableName($table);
}

function ID($id) {
	return R::$writer->getIDField($table);
}