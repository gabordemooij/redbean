<?php 

//BRNRDPROJECT-REDBEAN - SOURCE CODE

/**

--- welcome to

                   .______.                         
_______   ____   __| _/\_ |__   ____ _____    ____  
\_  __ \_/ __ \ / __ |  | __ \_/ __ \\__  \  /    \ 
 |  | \/\  ___// /_/ |  | \_\ \  ___/ / __ \|   |  \
 |__|    \___  >____ |  |___  /\___  >____  /___|  /
             \/     \/      \/     \/     \/     \/ 



|RedBean Database Objects -
|Written by Gabor de Mooij (c) copyright 2009


|List of Contributors:
|Sean Hess 
|Alan Hogan
|Desfrenes

======================================================
|						       RedBean is Licensed BSD
------------------------------------------------------
|RedBean is a OOP Database Simulation Middleware layer
|for php.
------------------------------------------------------
|Loosely based on an idea by Erik Roelofs - thanks man

VERSION 0.5

======================================================
Official GIT HUB:
git://github.com/buurtnerd/redbean.git
http://github.com/buurtnerd/redbean/tree/master
======================================================



Copyright (c) 2009, G.J.G.T (Gabor) de Mooij
All rights reserved.

a Buurtnerd project


Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
* Redistributions of source code must retain the above copyright
notice, this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright
notice, this list of conditions and the following disclaimer in the
documentation and/or other materials provided with the distribution.
* Neither the name of the <organization> nor the
names of its contributors may be used to endorse or promote products
derived from this software without specific prior written permission.

All advertising materials mentioning features or use of this software
are encouraged to display the following acknowledgement:
This product is powered by RedBean written by Gabor de Mooij (http://www.redbeanphp.com)


----




THIS SOFTWARE IS PROVIDED BY GABOR DE MOOIJ ''AS IS'' AND ANY
EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL GABOR DE MOOIJ BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.



WARNING
THIS IS AN PRE-BETA VERSION, DONT USE THIS CODE ON PRODUCTION SERVERS

*/

/**
 * Can (Can of Beans)
 * @package 		RedBean/Can.php
 * @description		A lightweight collection for beans
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Can implements Iterator ,  ArrayAccess , SeekableIterator , Countable {
	
	/**
	 * 
	 * @var array
	 */
	private $collectionIDs = null;
	
	/**
	 * 
	 * @var string
	 */
	private $type = null;
	
	/**
	 * 
	 * @var int
	 */
	private $pointer = 0;
	
	/**
	 * 
	 * @var int
	 */
	private $num = 0;
	
	/**
	 * Constructor
	 * @param $type
	 * @param $collection
	 * @return RedBean_Can $instance
	 */
	public function __construct( $type="", $collection = array() ) {
		
		$this->collectionIDs = $collection;
		$this->type = $type;
		$this->num = count( $this->collectionIDs );
	}
	
	/**
	 * Wraps an OODBBean in a RedBean_Decorator
	 * @param OODBBean $bean
	 * @return RedBean_Decorator $deco
	 */
	public function wrap( $bean ) {

		$dclass = PRFX.$this->type.SFFX;
		$deco = new $dclass( floatval( $bean->id ) );
		$deco->setData( $bean );
		return $deco;
		
	}
	
	/**
	 * Returns the number of beans in this can
	 * @return int $num
	 */
	public function count() {
		
		return $this->num;
	
	}
	
	/**
	 * Returns all the beans inside this can
	 * @return array $beans
	 */
	public function getBeans() {

		$rows = RedBean_OODB::fastloader( $this->type, $this->collectionIDs );
		
		$beans = array();
		
		if (is_array($rows)) {
			foreach( $rows as $row ) {
				//Use the fastloader for optimal performance (takes row as data)
				$beans[] = $this->wrap( RedBean_OODB::getById( $this->type, $row["id"] , $row) );
			}
		}

		return $beans;
	}
	
	public function slice( $begin=0, $end=0 ) {
		$this->collectionIDs = array_slice( $this->collectionIDs, $begin, $end);
		$this->num = count( $this->collectionIDs );
	} 
	
	/**
	 * Returns the current bean
	 * @return RedBean_Decorator $bean
	 */
	public function current() {
		if (isset($this->collectionIDs[$this->pointer])) {
			$id = $this->collectionIDs[$this->pointer];
			return $this->wrap( RedBean_OODB::getById( $this->type, $id ) );
		}
		else {
			return null;
		}
	}
	
	/**
	 * Returns the key of the current bean
	 * @return int $key
	 */
	public function key() {
		return $this->pointer;
	}
	
	
	/**
	 * Advances the internal pointer to the next bean in the can
	 * @return int $pointer
	 */
	public function next() {
		return ++$this->pointer;	
	}
	
	/**
	 * Sets the internal pointer to the previous bean in the can
	 * @return int $pointer
	 */
	public function prev() {
		if ($this->pointer > 0) {
			return ++$this->pointer;
		}else {
			return 0;
		}
	}
	
	/**
	 * Resets the internal pointer to the first bean in the can
	 * @return void
	 */
	public function rewind() {
		$this->pointer=0;
		return 0;	
	}
	
	/**
	 * Sets the internal pointer to the specified key in the can
	 * @param int $seek
	 * @return void
	 */
	public function seek( $seek ) {
		$this->pointer = (int) $seek;
	}
	
	/**
	 * Checks there are any more beans in this can
	 * @return boolean $morebeans
	 */
	public function valid() {
		return ($this->num > ($this->pointer+1));
	}
	
	/**
	 * Checks there are any more beans in this can
	 * Same as valid() but this method has a more descriptive name
	 * @return boolean $morebeans
	 */
	public function hasMoreBeans() {
		return $this->valid();
	}
	
	/**
	 * Makes it possible to use this object as an array
	 * Sets the offset
	 * @param $offset
	 * @param $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
        $this->collectionIDs[$offset] = $value;
    }
    
    /**
	 * Makes it possible to use this object as an array
	 * Checks the offset
	 * @param $offset
	 * @return boolean $isset
	 */
	public function offsetExists($offset) {
        return isset($this->collectionIDs[$offset]);
    }
    
    /**
	 * Makes it possible to use this object as an array
	 * Unsets the value at offset
	 * @param $offset
	 * @return void
	 */
    public function offsetUnset($offset) {
        unset($this->collectionIDs[$offset]);
    }
    
    /**
	 * Makes it possible to use this object as an array
	 * Gets the bean at a given offset
	 * @param $offset
	 * @return RedBean_Decorator $bean
	 */
	public function offsetGet($offset) {
    	
    	if (isset($this->collectionIDs[$offset])) {
			$id = $this->collectionIDs[$offset];
			return $this->wrap( RedBean_OODB::getById( $this->type, $id ) );
		}
		else {
			return null;
		}
      
    }
    
    /**
     * Returns the can as a list (array)
     * @return array
     */
    public function getList() {
    	$list = array();
    	$beans = $this->getBeans();
    	foreach($beans as $bean) {
    		$list[] = $bean->exportAsArr();
    	}
    	return $list;
    }
    
    /**
     * Reverses the order of IDs
     * @return unknown_type
     */
    public function reverse() {
    	$this->collectionIDs = array_reverse($this->collectionIDs, true);
    	return $this;
    }
	
	
}
/**
 * DBAdapter (Database Adapter)
 * @package 		RedBean/DBAdapter.php
 * @description		An adapter class to connect various database systems to RedBean
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_DBAdapter extends RedBean_Observable {

	/**
	 *
	 * @var ADODB
	 */
	private $db = null;
	
	/**
	 * 
	 * @var string
	 */
	private $sql = "";

	
	/**
	 *
	 * @param $database
	 * @return unknown_type
	 */
	public function __construct($database) {
		$this->db = $database;
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	public function getSQL() {
		return $this->sql;
	}

	/**
	 * Escapes a string for use in a Query
	 * @param $sqlvalue
	 * @return unknown_type
	 */
	public function escape( $sqlvalue ) {
		return $this->db->Escape($sqlvalue);
	}

	/**
	 * Executes SQL code
	 * @param $sql
	 * @return unknown_type
	 */
	public function exec( $sql , $noevent=false) {
		
		if (!$noevent){
			$this->sql = $sql;
			$this->signal("sql_exec", $this);
		}
		return $this->db->Execute( $sql );
	}

	/**
	 * Multi array SQL fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function get( $sql ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		
		
		return $this->db->GetAll( $sql );
	}

	/**
	 * SQL row fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function getRow( $sql ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		
		
		return $this->db->GetRow( $sql );
	}

	/**
	 * SQL column fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function getCol( $sql ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		
		
		return $this->db->GetCol( $sql );
	}

	/**
	 * Retrieves a single cell
	 * @param $sql
	 * @return unknown_type
	 */
	public function getCell( $sql ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		
		
		$arr = $this->db->GetCol( $sql );
		if ($arr && is_array($arr))	return ($arr[0]); else return false;
	}

	/**
	 * Returns last inserted id
	 * @return unknown_type
	 */
	public function getInsertID() {
		return $this->db->getInsertID();
	}

	/**
	 * Returns number of affected rows
	 * @return unknown_type
	 */
	public function getAffectedRows() {
		return $this->db->Affected_Rows();
	}
	
	/**
	 * Unwrap the original database object
	 * @return $database
	 */
	public function getDatabase() {
		return $this->db;
	}
	
	/**
	 * Return latest error message
	 * @return string $message
	 */
	public function getErrorMsg() {
		return $this->db->Errormsg();
	}

}
/**
 * Decorator
 * @package 		RedBean/Decorator.php
 * @description		Adds additional so-called 'porcelain' functions to the bean objects
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Decorator extends RedBean_Observable implements IteratorAggregate {

	/**
	 *
	 * @var OODBBean
	 */
	protected $data = null;

	/**
	 *
	 * @var string
	 */
	protected $type = "";

	/**
	 * @var array
	 */

	protected $problems = array();



	/**
	 * Constructor, loads directly from main table
	 * @param $type
	 * @param $id
	 * @return unknown_type
	 */
	public function __construct( $type=false, $id=0, $lock=false ) {

		$id = floatval( $id );
		if (!$type) {
			throw new Exception("Undefined bean type");
		}
		else {
			$this->type = preg_replace( "[\W_]","", strtolower($type));
			//echo $this->type;
			if ($id > 0) { //if the id is higher than 0 load data
				$this->data = RedBean_OODB::getById( $this->type, $id, $lock );
			}
			else { //otherwise, dispense a regular empty OODBBean
				$this->data = RedBean_OODB::dispense( $this->type );
			}
		}
	}

	/**
	 * Free memory of a class, drop column in db
	 * @param $property
	 * @return unknown_type
	 */
	public function free( $property ) {
		$this->signal("deco_free", $this);
		RedBean_OODB::dropColumn( $this->type, $property );
	}

	/**

	* Quick service to copy post values to properties
	* @param $selection
	* @return unknown_type
	*/
	public function importFromPost( $selection=null ) {
		$this->signal("deco_importpost", $this);
		if (!$selection) {
			$selection = array_keys($_POST);
		}

		if (is_string($selection)) {
			$selection = explode(",",$selection);
		}

		if ($selection && is_array($selection) && count($selection) > 0) {
			foreach( $selection as $field ) {
				$setter = "set".ucfirst( $field );
				if (isset( $_POST[$field] )) {
					$resp = $this->$setter( $_POST[ $field ]  );
				}
			}
		}

		return $this;
	}

	/**
	 * Imports an array or object
	 * If this function returns boolean true, no problems
	 * have occurred during the import and all values have been copies
	 * succesfully.
	 * @param $arr or $obj
	 * @return boolean $anyproblems
	 */
	public function import( $arr ) {
		$this->signal("deco_import", $this);
		foreach( $arr as $key=>$val ) {
			$setter = "set".ucfirst( $key );
			$resp = $this->$setter( $val );
				
		}
		return $this;

	}


	/**
	 * Magic method call, this provides basic accessor functionalities
	 */
	public function __call( $method, $arguments ) {
		return $this->command( $method, $arguments );
	}

	/**
	 * Magic getter. Another way to handle accessors
	 */
	public function __get( $name ) {
		$this->signal("deco_get", $this);
		$name = strtolower( $name );
		return isset($this->data->$name) ? $this->data->$name : null;
	}

	/**
	 * Magic setter. Another way to handle accessors
	 */
	public function __set( $name, $value ) {
		$this->signal("deco_set", $this);
		$name = strtolower( $name );
		$this->data->$name = $value;
	}

	/**
	 * To perform a command normally handles by the magic method __call
	 * use this one. This makes it easy to overwrite a method like set
	 * and then route its result to the original method
	 * @param $method
	 * @param $arguments
	 * @return unknown_type
	 */
	public function command( $method, $arguments ) {

		if (strpos( $method,"set" ) === 0) {
			$prop = substr( $method, 3 );
			$this->$prop = $arguments[0];
			return $this;

		}
		elseif (strpos($method,"getRelated")===0)	{
			$this->signal("deco_get", $this);
			$prop = strtolower( substr( $method, 10 ) );
			$beans = RedBean_OODB::getAssoc( $this->data, $prop );
			$decos = array();
			$dclass = PRFX.$prop.SFFX;

			if ($beans && is_array($beans)) {
				foreach($beans as $b) {
					$d = new $dclass();
					$d->setData( $b );
					$decos[] = $d;
				}
			}
			return $decos;
		}
		elseif (strpos( $method, "get" ) === 0) {
			$prop = substr( $method, 3 );
			return $this->$prop;
		}
		elseif (strpos( $method, "is" ) === 0) {
			$prop = strtolower( substr( $method, 2 ) );
			if (!isset($this->data->$prop)) {
				$this->signal("deco_get",$this);
				return false;
			}
			return ($this->data->$prop ? TRUE : FALSE);
		}
		else if (strpos($method,"add") === 0) { //@add
			$this->signal("deco_add",$this);
			$deco = $arguments[0];
			$bean = $deco->getData();
			RedBean_OODB::associate($this->data, $bean);
			return $this;
		}
		else if (strpos($method,"remove")===0) {
			$this->signal("deco_remove",$this);
			$deco = $arguments[0];
			$bean = $deco->getData();
			RedBean_OODB::unassociate($this->data, $bean);
			return $this;
		}
		else if (strpos($method,"attach")===0) {
			$this->signal("deco_attach",$this);
			$deco = $arguments[0];
			$bean = $deco->getData();
			RedBean_OODB::addChild($this->data, $bean);
			return $this;
		}
		else if (strpos($method,"clearRelated")===0) {
			$this->signal("deco_clearrelated",$this);
			$type = strtolower( substr( $method, 12 ) );
			RedBean_OODB::deleteAllAssocType($type, $this->data);
			return $this;
		}
		else if (strpos($method,"numof")===0) {
			$this->signal("deco_numof",$this);
			$type = strtolower( substr( $method, 5 ) );
			return RedBean_OODB::numOfRelated($type, $this->data);
				
		}
	}

	/**
	 * Enforces an n-to-1 relationship
	 * @param $deco
	 * @return unknown_type
	 */
	public function belongsTo( $deco ) {
		$this->signal("deco_belongsto", $this);
		RedBean_OODB::deleteAllAssocType($deco->getType(), $this->data);
		RedBean_OODB::associate($this->data, $deco->getData());
	}

	/**
	 * Enforces an 1-to-n relationship
	 * @param $deco
	 * @return unknown_type
	 */
	public function exclusiveAdd( $deco ) {
		$this->signal("deco_exclusiveadd", $this);
		RedBean_OODB::deleteAllAssocType($this->type,$deco->getData());
		RedBean_OODB::associate($deco->getData(), $this->data);
	}

	/**
	 * Returns the parent object of the current object if any
	 * @return RedBean_Decorator $oBean
	 */
	public function parent() {
		$this->signal("deco_parent", $this);
		$beans = RedBean_OODB::getParent( $this->data );
		if (count($beans) > 0 ) $bean = array_pop($beans); else return null;
		$dclass = PRFX.$this->type.SFFX;
		$deco = new $dclass();
		$deco->setData( $bean );
		return $deco;
	}

	/**
	 * Returns array of siblings (objects with the same parent except the object itself)
	 * @return array $aObjects
	 */
	public function siblings() {
		$this->signal("deco_siblings", $this);
		$beans = RedBean_OODB::getParent( $this->data );
		if (count($beans) > 0 ) {
			$bean = array_pop($beans);
		}
		else {
			return null;
		}
		$beans = RedBean_OODB::getChildren( $bean );
		$decos = array();
		$dclass = PRFX.$this->type.SFFX;
		if ($beans && is_array($beans)) {
			foreach($beans as $b) {
				if ($b->id != $this->data->id) {
					$d = new $dclass();
					$d->setData( $b );
					$decos[] = $d;
				}
			}
		}
		return $decos;
	}

	/**
	 * Returns array of child objects
	 * @return array $aObjects
	 */
	public function children() {
		$this->signal("deco_children", $this);
		$beans = RedBean_OODB::getChildren( $this->data );
		$decos = array();
		$dclass = PRFX.$this->type.SFFX;
		if ($beans && is_array($beans)) {
			foreach($beans as $b) {
				$d = new $dclass();
				$d->setData( $b );
				$decos[] = $d;
			}
		}
		return $decos;
	}

	/**
	 * Returns whether a node has a certain parent in its ancestry
	 * @param $deco
	 * @return boolean $found
	 */
	public function hasParent( $deco ) {
		$me = $this;
		while( $parent = $me->parent() ) {
			if ($deco->getID() == $parent->getID()) {
				return true;
			}
			else {
				$me = $parent;
			}
		}
		return false;
	}

	/**
	 * Searches children of a specific tree node for the target child
	 * @param $deco
	 * @return boolean $found
	 */
	public function hasChild( $deco ) {

		$nodes = array($this);
		while($node = array_shift($nodes)) {
			if ($node->getID() == $deco->getID() &&
			($node->getID() != $this->getID())) {
				return true;
			}
			if ($children = $node->children()) {
				$nodes = array_merge($nodes, $children);
			}
		}
		return false;

	}

	/**
	 * Searches if a node has the specified sibling
	 * @param $deco
	 * @return boolean $found
	 */
	public function hasSibling( $deco ) {
		$siblings = $this->siblings();
		foreach( $siblings as $sibling ) {
			if ($sibling->getID() == $deco->getID()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * This function simply copies the model and returns it
	 * @return RedBean_Decorator $oRD
	 */
	public function copy() {
		$this->signal("deco_copy", $this);
		$clone = new self( $this->type, 0 );
		$clone->setData( $this->getData() );
		return $clone;
	}

	/**
	 * Clears all associations
	 * @return unknown_type
	 */
	public function clearAllRelations() {
		$this->signal("deco_clearrelations", $this);
		RedBean_OODB::deleteAllAssoc( $this->getData() );
	}

	/**
	 * Gets data directly
	 * @return OODBBean
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Sets data directly
	 * @param $data
	 * @return void
	 */
	public function setData( $data ) {
		$this->data = $data;
	}

	/**
	 * Inserts or updates the bean
	 * Returns the ID
	 * @return unknown_type
	 */
	public function save() {
		$this->signal("deco_save", $this);
		return RedBean_OODB::set( $this->data );
	}

	/**
	 * Deletes the bean
	 * @param $deco
	 * @return unknown_type
	 */
	public static function delete( $deco ) {
		RedBean_OODB::trash( $deco->getData() );
	}


	/**
	 * Explicitly forward-locks a decorated bean
	 * @return unknown_type
	 */
	public function lock() {
		RedBean_OODB::openBean($this->getData());
	}

	/**
	 * Explicitly unlocks a decorated bean
	 * @return unknown_type
	 */
	public function unlock() {
		RedBean_OODB::closeBean( $this->getData());
	}


	/**
	 * Closes and unlocks the bean
	 * @param $deco
	 * @return unknown_type
	 */
	public static function close( $deco ) {
		RedBean_OODB::closeBean( $deco->getData() );
	}

	/**
	 * Creates a redbean decorator for a specified type
	 * @param $type
	 * @param $id
	 * @return unknown_type
	 */
	public static function make( $type="", $id ){
		return new RedBean_Decorator( $type, $id );
	}


	/**
	 * Exports a bean to a view
	 * @param $bean
	 * @return unknown_type
	 */
	public function exportTo( &$bean, $overridebean=false ) {


		foreach($this->data as $prop=>$value) {

			//what value should we use?
			if (is_object($overridebean) && isset($overridebean->$prop)) {
				$value = $overridebean->$prop;
			}
			elseif (is_array($overridebean) && isset($overridebean[$prop])) {
				$value = $overridebean[$prop];
			}

			if (is_object($value)){
				$value = $value->getID();
			}

			if (is_object($bean)) {
				$bean->$prop = $value;
			}
			elseif (is_array($bean)) {
				$bean[$prop] = $value;
			}
		}

		return $bean;
	}


	/**
	 * Exports a bean as an array
	 * @param $bean
	 * @return array $arr
	 */
	public function exportAsArr() {
		$arr = array();
		foreach($this->data as $prop=>$value) {
			if ($value instanceof RedBean_Decorator){
				$value = $value->getID();
			}
			$arr[ $prop ] = $value;

		}
		return  $arr;
	}

	/**
	 * Finds another decorator
	 * @param $deco
	 * @param $filter
	 * @return array $decorators
	 */
	public static function find( $deco, $filter, $start=0, $end=100, $orderby=" id ASC ", $extraSQL=false ) {

		if (!is_array($filter)) {
			return array();
		}

		if (count($filter)<1) {
			return array();
		}

		//make all keys of the filter lowercase
		$filters = array();
		foreach($filter as $key=>$f) {
			$filters[strtolower($key)] =$f;

			if (!in_array($f,array("=","!=","<",">","<=",">=","like","LIKE"))) {
				throw new ExceptionInvalidFindOperator();
			}

		}

		$beans = RedBean_OODB::find( $deco->getData(), $filters, $start, $end, $orderby, $extraSQL );

		$decos = array();
		$dclass = PRFX.$deco->type.SFFX;
		foreach( $beans as $bean ) {
			$decos[ $bean->id ] = new $dclass( floatval( $bean->id ) );
			$decos[ $bean->id ]->setData( $bean );
		}
		return $decos;
	}

	/**
	 * Returns an iterator
	 * @return Iterator $i
	 */
	public function getIterator() {
		$o = new ArrayObject($this->data);
		return $o->getIterator();
	}
	
	/**
	 * Whether you can write to this bean or not
	 * @return boolean $locked
	 */
	public function isReadOnly() {
		try{
			RedBean_OODB::openBean($this->data, true);
		}
		catch(RedBean_Exception_FailedAccessBean $e){
			return false;	
		}
		return true;
	}

}
/**
* MySQL Database object driver
* @desc performs all redbean actions for MySQL
*
*/
class RedBean_Driver_MySQL implements RedBean_Driver {
 
/**
*
* @var MySQLDatabase instance
*/
private static $me = null;
 
/**
*
* @var int
*/
public $Insert_ID;
 
/**
*
* @var boolean
*/
private $debug = false;
 
/**
*
* @var unknown_type
*/
private $rs = null;
 
/**
* Singleton Design Pattern
* @return DB $DB
*/
private function __construct(){}
 
/**
* Gets an instance of the database object (singleton) and connects to the database
* @return MySQLDatabase $databasewrapper
*/
public static function getInstance( $host, $user, $pass, $dbname ) {
 
if (!self::$me) {
mysql_connect(
 
$host,
$user,
$pass
 
);
 
mysql_selectdb( $dbname );
 
self::$me = new RedBean_Driver_MySQL();
}
return self::$me;
}
 
/**
* Retrieves a record or more using an SQL statement
* @return array $rows
*/
public function GetAll( $sql ) {
 
if ($this->debug) {
echo "<HR>".$sql;
}
 
$rs = mysql_query( $sql );
$this->rs=$rs;
$arr = array();
while( $r = @mysql_fetch_assoc($rs) ) {
$arr[] = $r;
}
 
if ($this->debug) {
 
if (count($arr) > 0) {
echo "<br><b style='color:green'>resultset: ".count($arr)." rows</b>";
}
 
$str = mysql_error();
if ($str!="") {
echo "<br><b style='color:red'>".$str."</b>";
}
}
 
return $arr;
 
}
 
 
/**
* Retrieves a column
* @param $sql
* @return unknown_type
*/
public function GetCol( $sql ) {
 
$rows = $this->GetAll($sql);
$cols = array();
 
foreach( $rows as $row ) {
$cols[] = array_shift( $row );
}
 
return $cols;
 
}
 
/**
* Retrieves a single cell
* @param $sql
* @return unknown_type
*/
public function GetCell( $sql ) {
 
$arr = $this->GetAll( $sql );
 
$row1 = array_shift( $arr );
 
$col1 = array_shift( $row1 );
 
return $col1;
 
}
 
 
/**
* Retrieves a single row
* @param $sql
* @return unknown_type
*/
public function GetRow( $sql ) {
 
$arr = $this->GetAll( $sql );
 
return array_shift( $arr );
 
}
 
/**
* Returns latest error number
* @return unknown_type
*/
public function ErrorNo() {
return mysql_errno();
}
 
/**
* Returns latest error message
* @return unknown_type
*/
public function Errormsg() {
return mysql_error();
}
 
 
 
/**
* Executes an SQL statement and returns the number of
* affected rows.
* @return int $affected
*/
public function Execute( $sql ) {
 
 
if ($this->debug) {
echo "<HR>".$sql;
}
 
$rs = mysql_query( $sql );
$this->rs=$rs;
 
if ($this->debug) {
$str = mysql_error();
if ($str!="") {
echo "<br><b style='color:red'>".$str."</b>";
}
}
 
$this->Insert_ID = $this->GetInsertID();
 
return intval( mysql_affected_rows());
 
}
 
/**
* Prepares a string for usage in SQL code
* @see IDB#esc()
*/
public function Escape( $str ) {
return mysql_real_escape_string( $str );
}
 
 
/**
* Returns the insert id of an insert query
* @see IDB#getInsertID()
*/
public function GetInsertID() {
return intval( mysql_insert_id());
}
 
 
/**
* Return the number of rows affected by the latest query
* @return unknown_type
*/
public function Affected_Rows() {
return mysql_affected_rows();
}
 
public function setDebugMode($tf) {
$this->debug = $tf;
}
 
public function getRaw() {
return $this->rs;
}
 
}
/**
 * PDO Driver
 * @package 		RedBean/PDO.php
 * @description		PDO Driver
 * @author			Desfrenes
 * @license			BSD
 */
class Redbean_Driver_PDO implements RedBean_Driver {

	/**
	 * 
	 * @var unknown_type
	 */
	private static $instance;
    
	/**
	 * 
	 * @var boolean
	 */
    private $debug = false;
    
    /**
     * 
     * @var unknown_type
     */
    private $pdo;
    
    /**
     * 
     * @var unknown_type
     */
    private $affected_rows;
    
    /**
     * 
     * @var unknown_type
     */
    private $rs;
    
    /**
     * 
     * @var unknown_type
     */
    private $exc =0;
    
    /**
     * 
     * @param $dsn
     * @param $user
     * @param $pass
     * @param $dbname
     * @return unknown_type
     */
    public static function getInstance($dsn, $user, $pass, $dbname)
    {
        if(is_null(self::$instance))
        {
            self::$instance = new Redbean_Driver_PDO($dsn, $user, $pass);
        }
        return self::$instance;
    }
    
    /**
     * 
     * @param $dsn
     * @param $user
     * @param $pass
     * @return unknown_type
     */
    public function __construct($dsn, $user, $pass)
    {
        $this->pdo = new PDO(
                $dsn,
                $user,
                $pass,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC)
            );
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetAll()
     */
    public function GetAll( $sql )
    {
    	$this->exc = 0;
    	try{ 
	        if ($this->debug)
	        {
	            echo "<HR>" . $sql;
	        }
	        $rs = $this->pdo->query($sql);
	        $this->rs = $rs;
	        $rows = $rs->fetchAll();
	        if(!$rows)
	        {
	            $rows = array();
	        }
	        
	        if ($this->debug)
	        {
	            if (count($rows) > 0)
	            {
	                echo "<br><b style='color:green'>resultset: " . count($rows) . " rows</b>";
	            }
	            
	        }
    	}
    	catch(Exception $e){ $this->exc = 1; 
    	
    			if ($this->debug){
	           	 $str = $this->Errormsg();
	           	 if ($str != "")
	           	 {
	           	     echo "<br><b style='color:red'>" . $str . "</b>";
	           	 }
    			}
    	return array(); }
        return $rows;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetCol()
     */
    public function GetCol($sql)
    {
    	$this->exc = 0;
    	try{
	        $rows = $this->GetAll($sql);
	        $cols = array();
	 
	        if ($rows && is_array($rows) && count($rows)>0){
		        foreach ($rows as $row)
		        {
		            $cols[] = array_shift($row);
		        }
	        }
	    	
    	}
    	catch(Exception $e){ 
    		$this->exc = 1;
    		return array(); }
        return $cols;
    }
 
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetCell()
     */
    public function GetCell($sql)
    {
    	$this->exc = 0;
    	try{
	        $arr = $this->GetAll($sql);
	        $row1 = array_shift($arr);
	        $col1 = array_shift($row1);
    	}
    	catch(Exception $e){ $this->exc = 1; }
        return $col1;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetRow()
     */
    public function GetRow($sql)
    {
    	$this->exc = 0;
    	try{
        	$arr = $this->GetAll($sql);
    	}
       	catch(Exception $e){ $this->exc = 1; return array(); }
        return array_shift($arr);
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#ErrorNo()
     */
    public function ErrorNo()
    {
    	if (!$this->exc) return 0;
    	$infos = $this->pdo->errorInfo();
        return $infos[1];
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#Errormsg()
     */
    public function Errormsg()
    {
    	if (!$this->exc) return "";
        $infos = $this->pdo->errorInfo();
        return $infos[2];
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#Execute()
     */
    public function Execute( $sql )
    {
    	$this->exc = 0;
    	try{
	        if ($this->debug)
	        {
	            echo "<HR>" . $sql;
	        }
	        $this->affected_rows = $this->pdo->exec($sql);
	       
    	}
    	catch(Exception $e){ $this->exc = 1; 
    	
    	 if ($this->debug)
	        {
	            $str = $this->Errormsg();
	            if ($str != "")
	            {
	                echo "<br><b style='color:red'>" . $str . "</b>";
	            }
	        }
    	return 0; }
        return $this->affected_rows;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#Escape()
     */
    public function Escape( $str )
    {
        return substr(substr($this->pdo->quote($str), 1), 0, -1);
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetInsertID()
     */
    public function GetInsertID()
    {
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#Affected_Rows()
     */
    public function Affected_Rows()
    {
        return (int) $this->affected_rows;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#setDebugMode()
     */
    public function setDebugMode( $tf )
    {
        $this->debug = (bool)$tf;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetRaw()
     */
    public function GetRaw()
    {
        return $this->rs;
    }
}
/**
 * Interface for database drivers
 * @package 		RedBean/Driver.php
 * @description		Describes the API for database classes
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_Driver {

	/**
	 * Implements Singleton
	 * Requests an instance of the database 
	 * @param $host
	 * @param $user
	 * @param $pass
	 * @param $dbname
	 * @return RedBean_Driver $driver
	 */
	public static function getInstance( $host, $user, $pass, $dbname );

	/**
	 * Runs a query and fetches results as a multi dimensional array
	 * @param $sql
	 * @return array $results
	 */
	public function GetAll( $sql );

	/**
	 * Runs a query and fetches results as a column
	 * @param $sql
	 * @return array $results
	 */
	public function GetCol( $sql );

	/**
	 * Runs a query an returns results as a single cell
	 * @param $sql
	 * @return mixed $cellvalue
	 */
	public function GetCell( $sql );

	/**
	 * Runs a query and returns a flat array containing the values of
	 * one row
	 * @param $sql
	 * @return array $row
	 */
	public function GetRow( $sql );

	/**
	 * Returns the error constant of the most
	 * recent error
	 * @return mixed $error
	 */
	public function ErrorNo();

	/**
	 * Returns the error message of the most recent
	 * error
	 * @return string $message
	 */
	public function Errormsg();

	/**
	 * Runs an SQL query
	 * @param $sql
	 * @return void
	 */
	public function Execute( $sql );

	/**
	 * Escapes a value according to the
	 * escape policies of the current database instance
	 * @param $str
	 * @return string $escaped_str
	 */
	public function Escape( $str );

	/**
	 * Returns the latest insert_id value
	 * @return integer $id
	 */
	public function GetInsertID();

	/**
	 * Returns the number of rows affected
	 * by the latest query
	 * @return integer $id
	 */
	public function Affected_Rows();

	/**
	 * Toggles debug mode (printing queries on screen)
	 * @param $tf
	 * @return void
	 */
	public function setDebugMode( $tf );

	/**
	 * Returns the unwrapped version of the database object;
	 * the raw database driver.
	 * @return mixed $database
	 */
	public function GetRaw();
	
}
/**
 * Exception Failed Access
 * Part of the RedBean Exceptions Mechanism
 * @package 		RedBean/Exception
 * @description		Represents a subtype in the RedBean Exception System
 * @author			Gabor de Mooij
 * @license			BSD
 */ 
class RedBean_Exception_FailedAccessBean extends Exception{}
/**
 * Exception Invalid Argument
 * Part of the RedBean Exceptions Mechanism
 * @package 		RedBean/Exception
 * @description		Represents a subtype in the RedBean Exception System
 * @author			Gabor de Mooij
 * @license			BSD
 */ 
class RedBean_Exception_InvalidArgument extends RedBean_Exception {}
/**
 * Exception Invalid Parent Child combination in TREE
 * Part of the RedBean Exceptions Mechanism
 * @package 		RedBean/Exception
 * @description		Represents a subtype in the RedBean Exception System
 * @author			Gabor de Mooij
 * @license			BSD
 */  
class RedBean_Exception_InvalidParentChildCombination extends RedBean_Exception{}
/**
 * Exception Security
 * Part of the RedBean Exceptions Mechanism
 * @package 		RedBean/Exception
 * @description		Represents a subtype in the RedBean Exception System
 * @author			Gabor de Mooij
 * @license			BSD
 */ 
class RedBean_Exception_Security extends RedBean_Exception {}
/**
 * Exception SQL
 * Part of the RedBean Exceptions Mechanism
 * @package 		RedBean/Exception
 * @description		Represents a subtype in the RedBean Exception System
 * @author			Gabor de Mooij
 * @license			BSD
 */ 
class RedBean_Exception_SQL extends RedBean_Exception {};
/**
 * RedBean Exception Base
 * @package 		RedBean/Exception.php
 * @description		Represents the base class
 * 					for RedBean Exceptions
 * @author			Gabor de Mooij
 * @license			BSD
 */
class Redbean_Exception extends Exception{}
/**
 * Observable
 * Base class for Observables
 * @package 		RedBean/Observable.php
 * @description		Part of the observer pattern in RedBean
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Observable {
	/**
	 * 
	 * @var array
	 */
	private $observers = array();
	
	/**
	 * Adds a listener to this instance
	 * @param $eventname
	 * @param $observer
	 * @return unknown_type
	 */
	public function addEventListener( $eventname, RedBean_Observer $observer ) {
		
		if (!isset($this->observers[ $eventname ])) {
			$this->observers[ $eventname ] = array();
		}
		
		$this->observers[ $eventname ][] = $observer;
	}
	
	/**
	 * Sends an event (signal) to the registered listeners
	 * @param $eventname
	 * @return unknown_type
	 */
	public function signal( $eventname ) {
		
		if (!isset($this->observers[ $eventname ])) {
			$this->observers[ $eventname ] = array();
		}
		
		foreach($this->observers[$eventname] as $observer) {
			$observer->onEvent( $eventname, $this );	
		}
		
	}
	
	
}
/**
 * Observer
 * @package 		RedBean/Observer.php
 * @description		Part of the observer pattern in RedBean
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_Observer {
	
	/**
	 * Handles the event send by a RedBean Observable
	 * @param string $eventname
	 * @param RedBean_Observable $observable
	 * @return unknown_type
	 */
	public function onEvent( $eventname, RedBean_Observable $o );
}
/**
 * RedBean OODB (object oriented database)
 * @package 		RedBean/OODB.php
 * @description		Core class for the RedBean ORM pack
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_OODB {

	/**
	 *
	 * @var float
	 */
	private static $version = 0.6;

	/**
	 *
	 * @var string
	 */
	private static $versioninf = "
		RedBean Object Database layer 
		VERSION 0.6
		BY G.J.G.T DE MOOIJ
		LICENSE BSD
		COPYRIGHT 2009
	";

	/**
	 * Indicates how long one can lock an item,
	 * defaults to ten minutes
	 * If a user opens a bean and he or she does not
	 * perform any actions on it others cannot modify the
	 * bean during this time interval.
	 * @var unknown_type
	 */
	private static $locktime = 10;

	/**
	 * a standard adapter for use with RedBean's MYSQL Database wrapper or
	 * ADO library
	 * @var RedBean_DBAdapter
	 */
	public static $db;

	/**
	 * 
	 * @var boolean
	 */
	private static $locking = true;



		/**
		 *
		 * @var string $pkey - a fingerprint for locking
		 */
		public static $pkey = false;

		/**
		 * Indicates that a rollback is required
		 * @var unknown_type
		 */
		private static $rollback = false;
		
		/**
		 * 
		 * @var RedBean_OODB
		 */
		private static $me = null;

		/**
		 * 
		 * Indicates the current engine
		 * @var string
		 */
		private static $engine = "myisam";

		/**
		 * @var boolean $frozen - indicates whether the db may be adjusted or not
		 */
		private static $frozen = false;

		/**
		 * @var QueryWriter
		 */
		private static $writer;
		
		/**
		 * Closes and unlocks the bean
		 * @return unknown_type
		 */
		public function __destruct() {

			self::$db->exec( 
				self::$writer->getQuery("destruct", array("engine"=>self::$engine,"rollback"=>self::$rollback))
			);
			
			
			RedBean_OODB::releaseAllLocks();
			
		}

		/**
		 * Returns the version information of this RedBean instance
		 * @return float
		 */
		public static function getVersionInfo() {
			return self::$versioninf;
		}

		/**
		 * Returns the version number of this RedBean instance
		 * @return unknown_type
		 */
		public static function getVersionNumber() {
			return self::$version;
		}

		/**
		 * Toggles Forward Locking
		 * @param $tf
		 * @return unknown_type
		 */
		public static function setLocking( $tf ) {
			self::$locking = $tf;
		}


		/**
		 * Gets the current locking mode (on or off)
		 * @return unknown_type
		 */
		public static function getLocking() {
			return self::$locking;
		}
	
		
		/**
		 * Toggles optimizer
		 * @param $bool
		 * @return unknown_type
		 */
		public static function setOptimizerActive( $bool ) {
			self::$optimizer = (boolean) $bool;
		}
		
		/**
		 * Returns state of the optimizer
		 * @param $bool
		 * @return unknown_type
		 */
		public static function getOptimizerActive() {
			return self::$optimizer;
		}
		
		/**
		 * Checks whether a bean is valid
		 * @param $bean
		 * @return unknown_type
		 */
		public static function checkBean(OODBBean $bean) {

			foreach($bean as $prop=>$value) {
				$prop = preg_replace('/[^abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_]/',"",$prop);
				if (strlen(trim($prop))===0) {
					throw new RedBean_Exception_Security("Invalid Characters in property");
				}
				else {
					
					$bean->$prop = $value;
				}
			}			
			
			//Is the bean valid? does the bean have an id?
			if (!isset($bean->id)) {
				throw new Exception("Invalid bean, no id");
			}

			//is the id numeric?
			if (!is_numeric($bean->id)) {
				throw new Exception("Invalid bean, id not numeric");
			}

			//does the bean have a type?
			if (!isset($bean->type)) {
				throw new Exception("Invalid bean, no type");
			}

			//is the beantype correct and valid?
			if (!is_string($bean->type) || is_numeric($bean->type) || strlen($bean->type)<3) {
				throw new Exception("Invalid bean, wrong type");
			}

			//is the beantype legal?
			if ($bean->type==="locking" || $bean->type==="dtyp") {
				throw new Exception("Beantype is reserved table");
			}

			//is the beantype allowed?
			if (strpos($bean->type,"_")!==false && ctype_alnum($bean->type)) {
				throw new Exception("Beantype contains illegal characters");
			}


		}

		/**
		 * same as check bean, but does additional checks for associations
		 * @param $bean
		 * @return unknown_type
		 */
		public static function checkBeanForAssoc( $bean ) {

			//check the bean
			self::checkBean($bean);

			//make sure it has already been saved to the database, else we have no id.
			if (intval($bean->id) < 1) {
				//if it's not saved, save it
				$bean->id = self::set( $bean );
			}

			return $bean;

		}

		/**
		 * Returns the current engine
		 * @return unknown_type
		 */
		public static function getEngine() {
			return self::$engine;
		}

		/**
		 * Sets the current engine
		 * @param $engine
		 * @return unknown_type
		 */
		public static function setEngine( $engine ) {

			if ($engine=="myisam" || $engine=="innodb") {
				self::$engine = $engine;
			}
			else {
				throw new Exception("Unsupported database engine");
			}

			return self::$engine;

		}

		/**
		 * Will perform a rollback at the end of the script
		 * @return unknown_type
		 */
		public static function rollback() {
			self::$rollback = true;
		}
		
		/**
		 * Inserts a bean into the database
		 * @param $bean
		 * @return $id
		 */
		public static function set( OODBBean $bean ) {

			self::checkBean($bean);


			$db = self::$db; //I am lazy, I dont want to waste characters...

		
			$table = $db->escape($bean->type); //what table does it want

			//may we adjust the database?
			if (!self::$frozen) {

				//does this table exist?
				$tables = self::showTables();
					
				if (!in_array($table, $tables)) {

					$createtableSQL = self::$writer->getQuery("create_table", array(
						"engine"=>self::$engine,
						"table"=>$table
					));
				
					//get a table for our friend!
					$db->exec( $createtableSQL );
					//jupz, now he has its own table!
					self::addTable( $table );
				}

				//does the table fit?
			/*	$columnsRaw = $db->get( self::$writer->getQuery("describe",array(
            	    "table"=>$table
       			 )) ); 
       			 
       			 */
       			 $columnsRaw = self::$writer->getTableColumns($table, $db) ;
					
				$columns = array();
				foreach($columnsRaw as $r) {
					$columns[$r["Field"]]=$r["Type"];
				}
					
				$insertvalues = array();
				$insertcolumns = array();
				$updatevalues = array();
					
				foreach( $bean as $p=>$v) {
					if ($p!="type" && $p!="id") {
						$p = $db->escape($p);
						$v = $db->escape($v);
						//What kind of property are we dealing with?
						$typeno = self::inferType($v);
						//Is this property represented in the table?
						if (isset($columns[$p])) {
							//yes it is, does it still fit?
							$sqlt = self::getType($columns[$p]);
							//echo "TYPE = $sqlt .... $typeno ";
							if ($typeno > $sqlt) {
								//no, we have to widen the database column type
								$changecolumnSQL = self::$writer->getQuery( "widen_column", array(
									"table" => $table,
									"column" => $p,
									"newtype" => self::$writer->typeno_sqltype[$typeno]
								) ); 
								
								$db->exec( $changecolumnSQL );
							}
						}
						else {
							//no it is not
							$addcolumnSQL = self::$writer->getQuery("add_column",array(
								"table"=>$table,
								"column"=>$p,
								"type"=> self::$writer->typeno_sqltype[$typeno]
							));
							
							$db->exec( $addcolumnSQL );
						}
						//Okay, now we are sure that the property value will fit
						$insertvalues[] = $v;
						$insertcolumns[] = $p;
						$updatevalues[] = array( "property"=>$p, "value"=>$v );
					}
				}

			}
			else {
					
				foreach( $bean as $p=>$v) {
					if ($p!="type" && $p!="id") {
						$p = $db->escape($p);
						$v = $db->escape($v);
						$insertvalues[] = $v;
						$insertcolumns[] = $p;
						$updatevalues[] = array( "property"=>$p, "value"=>$v );
					}
				}
					
			}

			//Does the record exist already?
			if ($bean->id) {
				//echo "<hr>Now trying to open bean....";
				self::openBean($bean, true);
				//yes it exists, update it
				if (count($updatevalues)>0) {
					$updateSQL = self::$writer->getQuery("update", array(
						"table"=>$table,
						"updatevalues"=>$updatevalues,
						"id"=>$bean->id
					)); 
					
					//execute the previously build query
					$db->exec( $updateSQL );
				}
			}
			else {
				//no it does not exist, create it
				if (count($insertvalues)>0) {
					
					$insertSQL = self::$writer->getQuery("insert",array(
						"table"=>$table,
						"insertcolumns"=>$insertcolumns,
						"insertvalues"=>$insertvalues
					));
				
				}
				else {
					$insertSQL = self::$writer->getQuery("create", array("table"=>$table)); 
				}
				//execute the previously build query
				$db->exec( $insertSQL );
				$bean->id = $db->getInsertID();
				self::openBean($bean);
			}

			return $bean->id;
				
		}


		/**
		 * Infers the SQL type of a bean
		 * @param $v
		 * @return $type the SQL type number constant
		 */
		public static function inferType( $v ) {
			
			$db = self::$db;
			$rawv = $v;
			
			$checktypeSQL = self::$writer->getQuery("infertype", array(
				"value"=> self::$db->escape(strval($v))
			));
			
			
			$db->exec( $checktypeSQL );
			$id = $db->getInsertID();
			
			$readtypeSQL = self::$writer->getQuery("readtype",array(
				"id"=>$id
			));
			
			$row=$db->getRow($readtypeSQL);
			
			
			$db->exec( self::$writer->getQuery("reset_dtyp") );
			
			$tp = 0;
			foreach($row as $t=>$tv) {
				if (strval($tv) === strval($rawv)) {
					return $tp;
				}
				$tp++;
			}
			return $tp;
		}

		/**
		 * Returns the RedBean type const for an SQL type
		 * @param $sqlType
		 * @return $typeno
		 */
		public static function getType( $sqlType ) {

			if (in_array($sqlType,self::$writer->sqltype_typeno)) {
				$typeno = self::$writer->sqltype_typeno[$sqlType];
			}
			else {
				$typeno = -1;
			}

			return $typeno;
		}

		/**
		 * Initializes RedBean
		 * @return bool $true
		 */
		public static function init( $querywriter, $dontclose = false ) {

			self::$me = new RedBean_OODB();
			self::$writer = $querywriter;
		

			//prepare database
			if (self::$engine === "innodb") {
				self::$db->exec(self::$writer->getQuery("prepare_innodb"));
				self::$db->exec(self::$writer->getQuery("starttransaction"));
			}
			else if (self::$engine === "myisam"){
				self::$db->exec(self::$writer->getQuery("prepare_myisam"));
			}


			//generate the basic redbean tables
			//Create the RedBean tables we need -- this should only happen once..
			if (!self::$frozen) {
				
				self::$db->exec(self::$writer->getQuery("clear_dtyp"));
					
				self::$db->exec(self::$writer->getQuery("setup_dtyp"));
						
				self::$db->exec(self::$writer->getQuery("setup_locking"));
						
				self::$db->exec(self::$writer->getQuery("setup_tables"));
			}
			
			//generate a key
			if (!self::$pkey) {
				self::$pkey = str_replace(".","",microtime(true)."".mt_rand());
			}

			return true;
		}

		/**
		 * Freezes the database so it won't be changed anymore
		 * @return unknown_type
		 */
		public static function freeze() {
			self::$frozen = true;
		}

		/**
		 * UNFreezes the database so it won't be changed anymore
		 * @return unknown_type
		 */
		public static function unfreeze() {
			self::$frozen = false;
		}

		/**
		 * Returns all redbean tables or all tables in the database
		 * @param $all if set to true this function returns all tables instead of just all rb tables
		 * @return array $listoftables
		 */
		public static function showTables( $all=false ) {

			$db = self::$db;

			if ($all && self::$frozen) {
				$alltables = $db->getCol(self::$writer->getQuery("show_tables"));
				return $alltables;
			}
			else {
				$alltables = $db->getCol(self::$writer->getQuery("show_rtables"));
				return $alltables;
			}

		}

		/**
		 * Registers a table with RedBean
		 * @param $tablename
		 * @return void
		 */
		public static function addTable( $tablename ) {

			$db = self::$db;

			$tablename = $db->escape( $tablename );

			$db->exec(self::$writer->getQuery("register_table",array("table"=>$tablename)));

		}

		/**
		 * UNRegisters a table with RedBean
		 * @param $tablename
		 * @return void
		 */
		public static function dropTable( $tablename ) {

			$db = self::$db;

			$tablename = $db->escape( $tablename );

			$db->exec(self::$writer->getQuery("unregister_table",array("table"=>$tablename)));


		}

		/**
		 * Quick and dirty way to release all locks
		 * @return unknown_type
		 */
		public function releaseAllLocks() {

			self::$db->exec(self::$writer->getQuery("release",array("key"=>self::$pkey)));

		}


		/**
		 * Opens and locks a bean
		 * @param $bean
		 * @return unknown_type
		 */
		public static function openBean( $bean, $mustlock=false) {

			self::checkBean( $bean );
			
			//If locking is turned off, or the bean has no persistance yet (not shared) life is always a success!
			if (!self::$locking || $bean->id === 0) return true;

			$db = self::$db;

			//remove locks that have been expired...
			$removeExpiredSQL = self::$writer->getQuery("remove_expir_lock", array(
				"locktime"=>self::$locktime
			));
			
			$db->exec($removeExpiredSQL);

			$tbl = $db->escape( $bean->type );
			$id = intval( $bean->id );

			//Is the bean already opened for us?
			$checkopenSQL = self::$writer->getQuery("get_lock",array(
				"id"=>$id,
				"table"=>$tbl,
				"key"=>self::$pkey
			));
			
			$row = $db->getRow($checkopenSQL);
			if ($row && is_array($row) && count($row)>0) {
				$updateexpstamp = self::$writer->getQuery("update_expir_lock",array(
					"time"=>time(),
					"id"=>$row["id"]
				));
				$db->exec($updateexpstamp);
				return true; //bean is locked for us!
			}

			//If you must lock a bean then the bean must have been locked by a previous call.
			if ($mustlock) {
				throw new RedBean_Exception_FailedAccessBean("Could not acquire a lock for bean $tbl . $id ");
				return false;
			}

			//try to get acquire lock on the bean
			$openSQL = self::$writer->getQuery("aq_lock", array(
				"table"=>$tbl,
				"id"=>$id,
				"key"=>self::$pkey,
				"time"=>time()
			));
			
			$trials = 0;
			$aff = 0;
			while( $aff < 1 && $trials < 5 ) {
				$db->exec($openSQL);
				$aff = $db->getAffectedRows();
				$trials++;
				if ($aff < 1) usleep(500000); //half a sec
			}

			if ($trials > 4) {
				return false;
			}
			else {
				return true;
			}
		}

		/**
		 * For internal use, synchronizes a block of code
		 * @param $toggle
		 * @return unknown_type
		 */
		private static function sync( $toggle ) {

			$bean = RedBean_OODB::dispense("_syncmethod");
			$bean->id = 0;

			if ($toggle) {
				self::openBean( $bean );
			}
			else {
				self::closeBean( $bean );
			}
		}

		/**
		 * Gets a bean by its primary ID
		 * @param $type
		 * @param $id
		 * @return OODBBean $bean
		 */
		public static function getById($type, $id, $data=false) {

			$bean = self::dispense( $type );
			$db = self::$db;
			$table = $db->escape( $type );
			$id = intval( $id );
			$bean->id = $id;

			//try to open the bean
			self::openBean($bean);

			//load the bean using sql
			if (!$data) {
				
				$getSQL = self::$writer->getQuery("get_bean",array(
					"type"=>$type,
					"id"=>$id
				)); 
				$row = $db->getRow( $getSQL );
			}
			else {
				$row = $data;
			}
			
			if ($row && is_array($row) && count($row)>0) {
				foreach($row as $p=>$v) {
					//populate the bean with the database row
					$bean->$p = $v;
				}
			}
			else {
				throw new RedBean_Exception_FailedAccessBean("bean not found");
			}

			return $bean;

		}

		/**
		 * Checks whether a type-id combination exists
		 * @param $type
		 * @param $id
		 * @return unknown_type
		 */
		public static function exists($type,$id) {

			$db = self::$db;
			$id = intval( $id );
			$type = $db->escape( $type );

			//$alltables = $db->getCol("show tables");
			$alltables = self::showTables();

			if (!in_array($type, $alltables)) {
				return false;
			}
			else {
				$no = $db->getCell( self::$writer->getQuery("bean_exists",array(
					"type"=>$type,
					"id"=>$id
				)) );
				if (intval($no)) {
					return true;
				}
				else {
					return false;
				}
			}
		}

		/**
		 * Counts occurences of  a bean
		 * @param $type
		 * @return integer $i
		 */
		public static function numberof($type) {

			$db = self::$db;
			$type = strtolower( $db->escape( $type ) );

			$alltables = self::showTables();

			if (!in_array($type, $alltables)) {
				return 0;
			}
			else {
				$no = $db->getCell( self::$writer->getQuery("count",array(
					"type"=>$type
				)));
				return $no;
			}
		}
		
		/**
		 * Gets all beans of $type, grouped by $field.
		 *
		 * @param String Object type e.g. "user" (lowercase!)
		 * @param String Field/parameter e.g. "zip"
		 * @return Array list of beans with distinct values of $field. Uses GROUP BY
		 * @author Alan J. Hogan
		 **/
		static function distinct($type, $field)
		{
			//TODO: Consider if GROUP BY (equivalent meaning) is more portable 
			//across DB types?
			$db = self::$db;
			$type = strtolower( $db->escape( $type ) );
			$field = $db->escape( $field );
		
			$alltables = self::showTables();

			if (!in_array($type, $alltables)) {
				return array();
			}
			else {
				$ids = $db->getCol( self::$writer->getQuery("distinct",array(
					"type"=>$type,
					"field"=>$field
				)));
				$beans = array();
				if (is_array($ids) && count($ids)>0) {
					foreach( $ids as $id ) {
						$beans[ $id ] = self::getById( $type, $id , false);
					}
				}
				return $beans;
			}
		}

		/**
		 * Simple statistic
		 * @param $type
		 * @param $field
		 * @return integer $i
		 */
		private static function stat($type,$field,$stat="sum") {

			$db = self::$db;
			$type = strtolower( $db->escape( $type ) );
			$field = strtolower( $db->escape( $field ) );
			$stat = $db->escape( $stat );

			$alltables = self::showTables();

			if (!in_array($type, $alltables)) {
				return 0;
			}
			else {
				$no = $db->getCell(self::$writer->getQuery("stat",array(
					"stat"=>$stat,
					"field"=>$field,
					"type"=>$type
				)));
				return $no;
			}
		}

		/**
		 * Sum
		 * @param $type
		 * @param $field
		 * @return float $i
		 */
		public static function sumof($type,$field) {
			return self::stat( $type, $field, "sum");
		}

		/**
		 * AVG
		 * @param $type
		 * @param $field
		 * @return float $i
		 */
		public static function avgof($type,$field) {
			return self::stat( $type, $field, "avg");
		}

		/**
		 * minimum
		 * @param $type
		 * @param $field
		 * @return float $i
		 */
		public static function minof($type,$field) {
			return self::stat( $type, $field, "min");
		}

		/**
		 * maximum
		 * @param $type
		 * @param $field
		 * @return float $i
		 */
		public static function maxof($type,$field) {
			return self::stat( $type, $field, "max");
		}


		/**
		 * Unlocks everything
		 * @return unknown_type
		 */
		public static function resetAll() {
			$sql = self::$writer->getQuery("releaseall");
			self::$db->exec( $sql );
			return true;
		}

		/**
		 * Fills slots in SQL query
		 * @param $sql
		 * @param $slots
		 * @return unknown_type
		 */
		public static function processQuerySlots($sql, $slots) {
			
			$db = self::$db;
			
			//Just a funny code to identify slots based on randomness
			$code = sha1(rand(1,1000)*time());
			
			//This ensures no one can hack our queries via SQL template injection
			foreach( $slots as $key=>$value ) {
				$sql = str_replace( "{".$key."}", "{".$code.$key."}" ,$sql ); 
			}
			
			//replace the slots inside the SQL template
			foreach( $slots as $key=>$value ) {
				$sql = str_replace( "{".$code.$key."}", self::$writer->getQuote().$db->escape( $value ).self::$writer->getQuote(),$sql ); 
			}
			
			return $sql;
		}
		
		/**
		 * Loads a collection of beans -fast-
		 * @param $type
		 * @param $ids
		 * @return unknown_type
		 */
		public static function fastLoader( $type, $ids ) {
			
			$db = self::$db;
			
			
			$sql = self::$writer->getQuery("fastload", array(
				"type"=>$type,
				"ids"=>$ids
			)); 
			
			return $db->get( $sql );
			
		}
		
		/**
		 * Allows you to fetch an array of beans using plain
		 * old SQL.
		 * @param $rawsql
		 * @param $slots
		 * @param $table
		 * @param $max
		 * @return array $beans
		 */
		public static function getBySQL( $rawsql, $slots, $table, $max=0 ) {
		
			$db = self::$db;
			$sql = $rawsql;
			
			if (is_array($slots)) {
				$sql = self::processQuerySlots( $sql, $slots );
			}
			
			$sql = str_replace('@ifexists:','', $sql);
			$rs = $db->getCol( self::$writer->getQuery("where",array(
				"table"=>$table
			)) . $sql );
			
			$err = $db->getErrorMsg();
			if (!self::$frozen && strpos($err,"Unknown column")!==false && $max<10) {
				$matches = array();
				if (preg_match("/Unknown\scolumn\s'(.*?)'/",$err,$matches)) {
					if (count($matches)==2 && strpos($rawsql,'@ifexists')!==false){
						$rawsql = str_replace('@ifexists:`'.$matches[1].'`','NULL', $rawsql);
						$rawsql = str_replace('@ifexists:'.$matches[1].'','NULL', $rawsql);
						return self::getBySQL( $rawsql, $slots, $table, ++$max);
					}
				}
				return array();
			}
			else {
				if (is_array($rs)) {
					return $rs;
				}
				else {
					return array();
				}
			}
		}
		
		
     /** 
     * Finds a bean using search parameters
     * @param $bean
     * @param $searchoperators
     * @param $start
     * @param $end
     * @param $orderby
     * @return unknown_type
     */
    public static function find(OODBBean $bean, $searchoperators = array(), $start=0, $end=100, $orderby="id ASC", $extraSQL=false) {
 
      self::checkBean( $bean );
      $db = self::$db;
      $tbl = $db->escape( $bean->type );
 
      $findSQL = self::$writer->getQuery("find",array(
      	"searchoperators"=>$searchoperators,
      	"bean"=>$bean,
      	"start"=>$start,
      	"end"=>$end,
      	"orderby"=>$orderby,
      	"extraSQL"=>$extraSQL,
      	"tbl"=>$tbl
      ));
      
      $ids = $db->getCol( $findSQL );
      $beans = array();
 
      if (is_array($ids) && count($ids)>0) {
          foreach( $ids as $id ) {
            $beans[ $id ] = self::getById( $bean->type, $id , false);
        }
      }
      
      return $beans;
      
    }
		
    
		/**
		 * Returns a plain and simple array filled with record data
		 * @param $type
		 * @param $start
		 * @param $end
		 * @param $orderby
		 * @return unknown_type
		 */
		public static function listAll($type, $start=false, $end=false, $orderby="id ASC", $extraSQL = false) {
 
			$db = self::$db;
 
			$listSQL = self::$writer->getQuery("list",array(
				"type"=>$type,
				"start"=>$start,
				"end"=>$end,
				"orderby"=>$orderby,
				"extraSQL"=>$extraSQL
			));
			
			
			return $db->get( $listSQL );
 
		}
		

		/**
		 * Associates two beans
		 * @param $bean1
		 * @param $bean2
		 * @return unknown_type
		 */
		public static function associate( OODBBean $bean1, OODBBean $bean2 ) { //@associate

			//get a database
			$db = self::$db;

			//first we check the beans whether they are valid
			$bean1 = self::checkBeanForAssoc($bean1);
			$bean2 = self::checkBeanForAssoc($bean2);

			self::openBean( $bean1, true );
			self::openBean( $bean2, true );

			//sort the beans
			$tp1 = $bean1->type;
			$tp2 = $bean2->type;
			if ($tp1==$tp2){
				$arr = array( 0=>$bean1, 1 =>$bean2 );
			}
			else {
				$arr = array( $tp1=>$bean1, $tp2 =>$bean2 );
			}
			ksort($arr);
			$bean1 = array_shift( $arr );
			$bean2 = array_shift( $arr );

			$id1 = intval($bean1->id);
			$id2 = intval($bean2->id);

			//infer the association table
			$tables = array();
			array_push( $tables, $db->escape( $bean1->type ) );
			array_push( $tables, $db->escape( $bean2->type ) );
			//sort the table names to make sure we only get one assoc table
			sort($tables);
			$assoctable = $db->escape( implode("_",$tables) );

			//check whether this assoctable already exists
			if (!self::$frozen) {
				$alltables = self::showTables();
				if (!in_array($assoctable, $alltables)) {
					//no assoc table does not exist, create it..
					$t1 = $tables[0];
					$t2 = $tables[1];

					if ($t1==$t2) {
						$t2.="2";
					}

					$assoccreateSQL = self::$writer->getQuery("create_assoc",array(
						"assoctable"=> $assoctable,
						"t1" =>$t1,
						"t2" =>$t2,
						"engine"=>self::$engine
					));
					
					$db->exec( $assoccreateSQL );
					
					//add a unique constraint
					$db->exec( self::$writer->getQuery("add_assoc",array(
						"assoctable"=> $assoctable,
						"t1" =>$t1,
						"t2" =>$t2
					)) );
					
					self::addTable( $assoctable );
				}
			}
				
			//now insert the association record
			$assocSQL = self::$writer->getQuery("add_assoc_now", array(
				"id1"=>$id1,
				"id2"=>$id2,
				"assoctable"=>$assoctable
			));
			
			$db->exec( $assocSQL );
				

		}

		/**
		 * Breaks the association between a pair of beans
		 * @param $bean1
		 * @param $bean2
		 * @return unknown_type
		 */
		public static function unassociate(OODBBean $bean1, OODBBean $bean2) {

			//get a database
			$db = self::$db;

			//first we check the beans whether they are valid
			$bean1 = self::checkBeanForAssoc($bean1);
			$bean2 = self::checkBeanForAssoc($bean2);


			self::openBean( $bean1, true );
			self::openBean( $bean2, true );


			$idx1 = intval($bean1->id);
			$idx2 = intval($bean2->id);

			//sort the beans
			$tp1 = $bean1->type;
			$tp2 = $bean2->type;

			if ($tp1==$tp2){
				$arr = array( 0=>$bean1, 1 =>$bean2 );
			}
			else {
				$arr = array( $tp1=>$bean1, $tp2 =>$bean2 );
			}
				
			ksort($arr);
			$bean1 = array_shift( $arr );
			$bean2 = array_shift( $arr );
				
			$id1 = intval($bean1->id);
			$id2 = intval($bean2->id);
				
			//infer the association table
			$tables = array();
			array_push( $tables, $db->escape( $bean1->type ) );
			array_push( $tables, $db->escape( $bean2->type ) );
			//sort the table names to make sure we only get one assoc table
			sort($tables);
				
				
			$assoctable = $db->escape( implode("_",$tables) );
				
			//check whether this assoctable already exists
			$alltables = self::showTables();
				
			if (in_array($assoctable, $alltables)) {
				$t1 = $tables[0];
				$t2 = $tables[1];
				if ($t1==$t2) {
					$t2.="2";
					$unassocSQL = self::$writer->getQuery("unassoc",array(
					"assoctable"=>$assoctable,
					"t1"=>$t2,
					"t2"=>$t1,
					"id1"=>$id1,
					"id2"=>$id2
					));
					//$unassocSQL = "DELETE FROM `$assoctable` WHERE ".$t2."_id = $id1 AND ".$t1."_id = $id2 ";
					$db->exec($unassocSQL);
				}

				//$unassocSQL = "DELETE FROM `$assoctable` WHERE ".$t1."_id = $id1 AND ".$t2."_id = $id2 ";

				$unassocSQL = self::$writer->getQuery("unassoc",array(
					"assoctable"=>$assoctable,
					"t1"=>$t1,
					"t2"=>$t2,
					"id1"=>$id1,
					"id2"=>$id2
				));
				
				$db->exec($unassocSQL);
			}
			if ($tp1==$tp2) {
				$assoctable2 = "pc_".$db->escape( $bean1->type )."_".$db->escape( $bean1->type );
				//echo $assoctable2;
				//check whether this assoctable already exists
				$alltables = self::showTables();
				if (in_array($assoctable2, $alltables)) {

					//$id1 = intval($bean1->id);
					//$id2 = intval($bean2->id);
					$unassocSQL = self::$writer->getQuery("untree", array(
						"assoctable2"=>$assoctable2,
						"idx1"=>$idx1,
						"idx2"=>$idx2
					));
					
					$db->exec($unassocSQL);
				}
			}
		}

		/**
		 * Fetches all beans of type $targettype assoiciated with $bean
		 * @param $bean
		 * @param $targettype
		 * @return array $beans
		 */
		public static function getAssoc(OODBBean $bean, $targettype) {
			//get a database
			$db = self::$db;
			//first we check the beans whether they are valid
			$bean = self::checkBeanForAssoc($bean);

			$id = intval($bean->id);


			//obtain the table names
			$t1 = $db->escape( strtolower($bean->type) );
			$t2 = $db->escape( $targettype );

			//infer the association table
			$tables = array();
			array_push( $tables, $t1 );
			array_push( $tables, $t2 );
			//sort the table names to make sure we only get one assoc table
			sort($tables);
			$assoctable = $db->escape( implode("_",$tables) );

			//check whether this assoctable exists
			$alltables = self::showTables();
				
			if (!in_array($assoctable, $alltables)) {
				return array(); //nope, so no associations...!
			}
			else {
				if ($t1==$t2) {
					$t2.="2";
				}
				
				$getassocSQL = self::$writer->getQuery("get_assoc",array(
					"t1"=>$t1,
					"t2"=>$t2,
					"assoctable"=>$assoctable,
					"id"=>$id
				));
				
				
				$rows = $db->getCol( $getassocSQL );
				$beans = array();
				if ($rows && is_array($rows) && count($rows)>0) {
					foreach($rows as $i) {
						$beans[$i] = self::getById( $targettype, $i, false);
					}
				}
				return $beans;
			}


		}


		/**
		 * Removes a bean from the database and breaks associations if required
		 * @param $bean
		 * @return unknown_type
		 */
		public static function trash( OODBBean $bean ) {

			self::checkBean( $bean );
			if (intval($bean->id)===0) return;
			self::deleteAllAssoc( $bean );
			self::openBean($bean);
			$table = self::$db->escape($bean->type);
			$id = intval($bean->id);
			self::$db->exec( self::$writer->getQuery("trash",array(
				"table"=>$table,
				"id"=>$id
			)) );

		}
			
		/**
		 * Breaks all associations of a perticular bean $bean
		 * @param $bean
		 * @return unknown_type
		 */
		public static function deleteAllAssoc( $bean ) {

			$db = self::$db;
			$bean = self::checkBeanForAssoc($bean);

			self::openBean( $bean, true );


			$id = intval( $bean->id );

			//get all tables
			$alltables = self::showTables();

			//are there any possible associations?
			$t = $db->escape($bean->type);
			$checktables = array();
			foreach( $alltables as $table ) {
				if (strpos($table,$t."_")!==false || strpos($table,"_".$t)!==false){
					$checktables[] = $table;
				}
			}

			//remove every possible association
			foreach($checktables as $table) {
				if (strpos($table,"pc_")===0){
				
					$db->exec( self::$writer->getQuery("deltree",array(
						"id"=>$id,
						"table"=>$table
					)) );
				}
				else {
					
					$db->exec( self::$writer->getQuery("unassoc_all_t1",array("table"=>$table,"t"=>$t,"id"=>$id)) );
					$db->exec( self::$writer->getQuery("unassoc_all_t2",array("table"=>$table,"t"=>$t,"id"=>$id)) );
				}
					
					
			}
			return true;
		}

		/**
		 * Breaks all associations of a perticular bean $bean
		 * @param $bean
		 * @return unknown_type
		 */
		public static function deleteAllAssocType( $targettype, $bean ) {

			$db = self::$db;
			$bean = self::checkBeanForAssoc($bean);
			self::openBean( $bean, true );

			$id = intval( $bean->id );

			//obtain the table names
			$t1 = $db->escape( strtolower($bean->type) );
			$t2 = $db->escape( $targettype );

			//infer the association table
			$tables = array();
			array_push( $tables, $t1 );
			array_push( $tables, $t2 );
			//sort the table names to make sure we only get one assoc table
			sort($tables);
			$assoctable = $db->escape( implode("_",$tables) );

			if (strpos($assoctable,"pc_")===0){
				$db->exec( self::$writer->getQuery("deltreetype",array(
					"assoctable"=>$assoctable,
					"id"=>$id
				)) );
			}else{
				$db->exec( self::$writer->getQuery("unassoctype1",array(
					"assoctable"=>$assoctable,
					"t1"=>$t1,
					"id"=>$id
				)) );
				$db->exec( self::$writer->getQuery("unassoctype2",array(
					"assoctable"=>$assoctable,
					"t1"=>$t1,
					"id"=>$id
				)) );

			}

			return true;
		}


		/**
		 * Dispenses; creates a new OODB bean of type $type
		 * @param $type
		 * @return OODBBean $bean
		 */
		public static function dispense( $type="StandardBean" ) {

			$oBean = new OODBBean();
			$oBean->type = $type;
			$oBean->id = 0;
			return $oBean;
		}


		/**
		 * Adds a child bean to a parent bean
		 * @param $parent
		 * @param $child
		 * @return unknown_type
		 */
		public static function addChild( OODBBean $parent, OODBBean $child ) {

			//get a database
			$db = self::$db;

			//first we check the beans whether they are valid
			$parent = self::checkBeanForAssoc($parent);
			$child = self::checkBeanForAssoc($child);

			self::openBean( $parent, true );
			self::openBean( $child, true );


			//are parent and child of the same type?
			if ($parent->type !== $child->type) {
				throw new RedBean_Exception_InvalidParentChildCombination();
			}

			$pid = intval($parent->id);
			$cid = intval($child->id);

			//infer the association table
			$assoctable = "pc_".$db->escape($parent->type."_".$parent->type);

			//check whether this assoctable already exists
			if (!self::$frozen) {
				$alltables = self::showTables();
				if (!in_array($assoctable, $alltables)) {
					//no assoc table does not exist, create it..
					$assoccreateSQL = self::$writer->getQuery("create_tree",array(
						"engine"=>self::$engine,
						"assoctable"=>$assoctable
					));
					$db->exec( $assoccreateSQL );
					//add a unique constraint
					$db->exec( self::$writer->getQuery("unique", array(
						"assoctable"=>$assoctable
					)) );
					self::addTable( $assoctable );
				}
			}

			//now insert the association record
			$assocSQL = self::$writer->getQuery("add_child",array(
				"assoctable"=>$assoctable,
				"pid"=>$pid,
				"cid"=>$cid
			));
			$db->exec( $assocSQL );

		}

		/**
		 * Returns all child beans of parent bean $parent
		 * @param $parent
		 * @return array $beans
		 */
		public static function getChildren( OODBBean $parent ) {

			//get a database
			$db = self::$db;

			//first we check the beans whether they are valid
			$parent = self::checkBeanForAssoc($parent);

			$pid = intval($parent->id);

			//infer the association table
			$assoctable = "pc_".$db->escape( $parent->type . "_" . $parent->type );

			//check whether this assoctable exists
			$alltables = self::showTables();
			if (!in_array($assoctable, $alltables)) {
				return array(); //nope, so no children...!
			}
			else {
				$targettype = $parent->type;
				$getassocSQL = self::$writer->getQuery("get_children", array(
					"assoctable"=>$assoctable,
					"pid"=>$pid
				));
				$rows = $db->getCol( $getassocSQL );
				$beans = array();
				if ($rows && is_array($rows) && count($rows)>0) {
					foreach($rows as $i) {
						$beans[$i] = self::getById( $targettype, $i, false);
					}
				}
				return $beans;
			}

		}

		/**
		 * Fetches the parent bean of child bean $child
		 * @param $child
		 * @return OODBBean $parent
		 */
		public static function getParent( OODBBean $child ) {

				
			//get a database
			$db = self::$db;

			//first we check the beans whether they are valid
			$child = self::checkBeanForAssoc($child);

			$cid = intval($child->id);

			//infer the association table
			$assoctable = "pc_".$db->escape( $child->type . "_" . $child->type );
			//check whether this assoctable exists
			$alltables = self::showTables();
			if (!in_array($assoctable, $alltables)) {
				return array(); //nope, so no children...!
			}
			else {
				$targettype = $child->type;
				
				$getassocSQL = self::$writer->getQuery("get_parent", array(
					"assoctable"=>$assoctable,
					"cid"=>$cid
				));
					
				$rows = $db->getCol( $getassocSQL );
				$beans = array();
				if ($rows && is_array($rows) && count($rows)>0) {
					foreach($rows as $i) {
						$beans[$i] = self::getById( $targettype, $i, false);
					}
				}
					
				return $beans;
			}

		}

		/**
		 * Removes a child bean from a parent-child association
		 * @param $parent
		 * @param $child
		 * @return unknown_type
		 */
		public static function removeChild(OODBBean $parent, OODBBean $child) {

			//get a database
			$db = self::$db;

			//first we check the beans whether they are valid
			$parent = self::checkBeanForAssoc($parent);
			$child = self::checkBeanForAssoc($child);

			self::openBean( $parent, true );
			self::openBean( $child, true );


			//are parent and child of the same type?
			if ($parent->type !== $child->type) {
				throw new RedBean_Exception_InvalidParentChildCombination();
			}

			//infer the association table
			$assoctable = "pc_".$db->escape( $parent->type . "_" . $parent->type );

			//check whether this assoctable already exists
			$alltables = self::showTables();
			if (!in_array($assoctable, $alltables)) {
				return true; //no association? then nothing to do!
			}
			else {
				$pid = intval($parent->id);
				$cid = intval($child->id);
				$unassocSQL = self::$writer->getQuery("remove_child", array(
					"assoctable"=>$assoctable,
					"pid"=>$pid,
					"cid"=>$cid
				));
				$db->exec($unassocSQL);
			}
		}
		
		/**
		 * Counts the associations between a type and a bean
		 * @param $type
		 * @param $bean
		 * @return integer $numberOfRelations
		 */
		public static function numofRelated( $type, OODBBean $bean ) {
			
			//get a database
			$db = self::$db;
			
			$t2 = strtolower( $db->escape( $type ) );
						
			//is this bean valid?
			self::checkBean( $bean );
			$t1 = strtolower( $bean->type  );
			$tref = strtolower( $db->escape( $bean->type ) );
			$id = intval( $bean->id );
						
			//infer the association table
			$tables = array();
			array_push( $tables, $t1 );
			array_push( $tables, $t2 );
			
			//sort the table names to make sure we only get one assoc table
			sort($tables);
			$assoctable = $db->escape( implode("_",$tables) );
			
			//get all tables
			$tables = self::showTables();
			
			if ($tables && is_array($tables) && count($tables) > 0) {
				if (in_array( $t1, $tables ) && in_array($t2, $tables)){
					$sqlCountRelations = self::$writer->getQuery(
						"num_related", array(
							"assoctable"=>$assoctable,
							"t1"=>$t1,
							"id"=>$id
						)
					);
					
					return (int) $db->getCell( $sqlCountRelations );
				}
			}
			else {
				return 0;
			}
		}
		
		/**
		 * Accepts a comma separated list of class names and
		 * creates a default model for each classname mentioned in
		 * this list. Note that you should not gen() classes
		 * for which you already created a model (by inheriting
		 * from ReadBean_Decorator).
		 * @param string $classes
		 * @return unknown_type
		 */
		/**
		 * Accepts a comma separated list of class names and
		 * creates a default model for each classname mentioned in
		 * this list. Note that you should not gen() classes
		 * for which you already created a model (by inheriting
		 * from ReadBean_Decorator).
		 * @param string $classes
		 * @return unknown_type
		 */
		public static function gen( $classes ) { 
			$classes = explode(",",$classes);
			foreach($classes as $c) {
				$ns = '';
				$names = explode('\\', $c);
				$className = trim(end($names));
				if(count($names) > 1)
				{
					$ns = 'namespace ' . implode('\\', array_slice($names, 0, -1)) . ";\n";
				}
				if ($c!=="" && $c!=="null" && !class_exists($c) && 
								preg_match("/^\s*[A-Za-z_][A-Za-z0-9_]*\s*$/",$className)){ 
					try{
							$toeval = $ns . " class ".$className." extends RedBean_Decorator {
							private static \$__static_property_type = \"".strtolower($className)."\";
							
							public function __construct(\$id=0, \$lock=false) {
								parent::__construct('".strtolower($className)."',\$id,\$lock);
							}
							
							public static function where( \$sql, \$slots=array() ) {
								return new RedBean_Can( self::\$__static_property_type, RedBean_OODB::getBySQL( \$sql, \$slots, self::\$__static_property_type) );
							}
	
							public static function listAll(\$start=false,\$end=false,\$orderby=' id ASC ',\$sql=false) {
								return RedBean_OODB::listAll(self::\$__static_property_type,\$start,\$end,\$orderby,\$sql);
							}
							
						}";
						eval($toeval);	
						if (!class_exists($c)) return false;
					}
					catch(Exception $e){
						return false;
					}
				}
				else {
					return false;
				}
			}
			return true;
		}
		


		/**
		 * Changes the locktime, this time indicated how long
		 * a user can lock a bean in the database.
		 * @param $timeInSecs
		 * @return unknown_type
		 */
		public static function setLockingTime( $timeInSecs ) {

			if (is_int($timeInSecs) && $timeInSecs >= 0) {
				self::$locktime = $timeInSecs;
			}
			else {
				throw new RedBean_Exception_InvalidArgument( "time must be integer >= 0" );
			}
		}


		
		/**
		 * Cleans the entire redbean database, this will not affect
		 * tables that are not managed by redbean.
		 * @return unknown_type
		 */
		public static function clean() {

			if (self::$frozen) {
				return false;
			}

			$db = self::$db;

			$tables = $db->getCol( self::$writer->getQuery("show_rtables") );

			foreach($tables as $key=>$table) {
				$tables[$key] = self::$writer->getEscape().$table.self::$writer->getEscape();
			}

			$sqlcleandatabase = self::$writer->getQuery("drop_tables",array(
				"tables"=>$tables
			));

			$db->exec( $sqlcleandatabase );

			$db->exec( self::$writer->getQuery("truncate_rtables") );
			self::resetAll();
			return true;

		}
		
	
		/**
		 * Removes all tables from redbean that have
		 * no classes
		 * @return unknown_type
		 */
		public static function removeUnused( ) {

			//oops, we are frozen, so no change..
			if (self::$frozen) {
				return false;
			}

			//get a database
			$db = self::$db;

			//get all tables
			$tables = self::showTables();
			
			foreach($tables as $table) {
				
				//does the class exist?
				$classname = PRFX . $table . SFFX;
				if(!class_exists( $classname , true)) {
					$db->exec( self::$writer->getQuery("drop_tables",array("tables"=>array($table))) );
					$db->exec(self::$writer->getQuery("unregister_table",array("table"=>$table)));
				} 
				
			}
			
		}
		/**
		 * Drops a specific column
		 * @param $table
		 * @param $property
		 * @return unknown_type
		 */
		public static function dropColumn( $table, $property ) {
			
			//oops, we are frozen, so no change..
			if (self::$frozen) {
				return false;
			}

			//get a database
			$db = self::$db;
			
			$db->exec( self::$writer->getQuery("drop_column", array(
				"table"=>$table,
				"property"=>$property
			)) );
			
		}

		/**
	     * Removes all beans of a particular type
	     * @param $type
	     * @return nothing
	     */
	    public static function trashAll($type) {
	        self::$db->exec( self::$writer->getQuery("drop_type",array("type"=>strtolower($type))));
	    }

	    /**
		 * Narrows columns to appropriate size if needed
		 * @return unknown_type
		 */
		public static function keepInShape( $gc = false ,$stdTable=false, $stdCol=false) {
			
			//oops, we are frozen, so no change..
			if (self::$frozen) {
				return false;
			}

			//get a database
			$db = self::$db;

			//get all tables
			$tables = self::showTables();
			
				//pick a random table
				if ($tables && is_array($tables) && count($tables) > 0) {
					if ($gc) self::removeUnused( $tables );
					$table = $tables[array_rand( $tables, 1 )];
				}
				else {
					return; //or return if there are no tables (yet)
				}
			if ($stdTable) $table = $stdTable;

			$table = $db->escape( $table );
			//do not remove columns from association tables
			if (strpos($table,'_')!==false) return;
			//table is still in use? But are all columns in use as well?
			
			$cold = self::$writer->getTableColumns( $table, $db );
			
			//$cols = $db->get( self::$writer->getQuery("describe",array(
			//	"table"=>$table
			//)) );
			//pick a random column
			if (count($cols)<1) return;
				$colr = $cols[array_rand( $cols )];
				$col = $db->escape( $colr["Field"] ); //fetch the name and escape
		        if ($stdCol){
				$exists = false;	
				$col = $stdCol; 
				foreach($cols as $cl){
					if ($cl["Field"]==$col) {
						$exists = $cl;
					}
				}
				if (!$exists) {
					return; 
				}
				else {
					$colr = $exists;
				}
			}
			if ($col=="id" || strpos($col,"_id")!==false) {
				return; //special column, cant slim it down
			}
			
			
			//now we have a table and a column $table and $col
			if ($gc && !intval($db->getCell( self::$writer->getQuery("get_null",array(
				"table"=>$table,
				"col"=>$col
			)
			)))) {
				$db->exec( self::$writer->getQuery("drop_column",array("table"=>$table,"property"=>$col)));
				return;	
			}
			
			//okay so this column is still in use, but maybe its to wide
			//get the field type
			//print_r($colr);
			$currenttype =  self::$writer->sqltype_typeno[$colr["Type"]];
			if ($currenttype > 0) {
				$trytype = rand(0,$currenttype - 1); //try a little smaller
				//add a test column
				$db->exec(self::$writer->getQuery("test_column",array(
					"type"=>self::$writer->typeno_sqltype[$trytype],
					"table"=>$table
				)
				));
				//fill the tinier column with the same values of the original column
				$db->exec(self::$writer->getQuery("update_test",array(
					"table"=>$table,
					"col"=>$col
				)));
				//measure the difference
				$delta = $db->getCell(self::$writer->getQuery("measure",array(
					"table"=>$table,
					"col"=>$col
				)));
				if (intval($delta)===0) {
					//no difference? then change the column to save some space
					$sql = self::$writer->getQuery("remove_test",array(
						"table"=>$table,
						"col"=>$col,
						"type"=>self::$writer->typeno_sqltype[$trytype]
					));
					$db->exec($sql);
				}
				//get rid of the test column..
				$db->exec( self::$writer->getQuery("drop_test",array(
					"table"=>$table
				)) );
			}
		
			//Can we put an index on this column?
			//Is this column worth the trouble?
			if (
				strpos($colr["Type"],"TEXT")!==false ||
				strpos($colr["Type"],"LONGTEXT")!==false
			) {
				return;
			}
			
		
			$variance = $db->getCell(self::$writer->getQuery("variance",array(
				"col"=>$col,
				"table"=>$table
			)));
			$records = $db->getCell(self::$writer->getQuery("count",array("type"=>$table)));
			if ($records) {
				$relvar = intval($variance) / intval($records); //how useful would this index be?
				//if this column describes the table well enough it might be used to
				//improve overall performance.
				$indexname = "reddex_".$col;
				if ($records > 1 && $relvar > 0.85) {
					$sqladdindex=self::$writer->getQuery("index1",array(
						"table"=>$table,
						"indexname"=>$indexname,
						"col"=>$col
					));
					$db->exec( $sqladdindex );
				}
				else {
					$sqldropindex = self::$writer->getQuery("index2",array("table"=>$table,"indexname"=>$indexname));
					$db->exec( $sqldropindex );
				}
			}
			
			return true;
		}
	
}
/**
 * OODBBean (Object Oriented DataBase Bean)
 * @package 		RedBean/OODBBean.php
 * @description		The Bean class used for passing information
 * @author			Gabor de Mooij
 * @license			BSD
 */
class OODBBean {
}
/**
 * Querylogger 
 * @package 		RedBean/QueryLogger.php
 * @description		Simple Audit Logger
 * @author			Gabor de Mooij
 * @license			BSD
 */
class Redbean_Querylogger implements RedBean_Observer
{
 
	/**
	 * @var string
	 */
	private $path = "";
	
	/**
	 * 
	 * @var integer
	 */
	private $userid = 0;
	
	private function getFilename() {
		return $this->path . "audit_".date("m_d_y").".log";
	}
	
	/**
	 * Logs a piece of SQL code
	 * @param $sql
	 * @return void
	 */
	public function logSCQuery( $sql, $db )
    {
		$sql = addslashes($sql);
		$line = "\n".date("H:i:s")."|".$_SERVER["REMOTE_ADDR"]."|UID=".$this->userid."|".$sql;  
		file_put_contents( $this->getFilename(), $line, FILE_APPEND );
		return null;
	}
	
	/**
	 * Inits the logger
	 * @param $path
	 * @param $userid
	 * @return unknown_type
	 */
	public static function init($path="",$userid=0) {
		
		$logger = new self;
		$logger->userid = $userid;
		$logger->path = $path;
		if (!file_exists($logger->getFilename())) {
			file_put_contents($logger->getFilename(),"begin logging");	
		}
		
		RedBean_OODB::$db->addEventListener( "sql_exec", $logger );
	
	}
	
	/**
	 * (non-PHPdoc)
	 * @see RedBean/RedBean_Observer#onEvent()
	 */
	public function onEvent( $event, RedBean_Observable $db ) {
		
		$this->logSCQuery( $db->getSQL(), $db );
	}
	
 
}
/**
 * RedBean MySQLWriter
 * @package 		RedBean/QueryWriter/MySQL.php
 * @description		Writes Queries for MySQL Databases
 * @author			Gabor de Mooij
 * @license			BSD
 */
class QueryWriter_MySQL implements QueryWriter {
	/**
	 * @var array all allowed sql types
	 */
	public $typeno_sqltype = array(
		" TINYINT(3) UNSIGNED ",
		" INT(11) UNSIGNED ",
		" BIGINT(20) ",
		" VARCHAR(255) ",
		" TEXT ",
		" LONGTEXT "
		);

		/**
		 *
		 * @var array all allowed sql types
		 */
		public $sqltype_typeno = array(
		"tinyint(3) unsigned"=>0,
		"int(11) unsigned"=>1,
		"bigint(20)"=>2,
		"varchar(255)"=>3,
		"text"=>4,
		"longtext"=>5
		);

		/**
		 * @var array all dtype types
		 */
		public $dtypes = array(
		"tintyintus","intus","ints","varchar255","text","ltext"
		);

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryCreateTable( $options=array() ) {

			$engine = $options["engine"];
			$table = $options["table"];

			if ($engine=="myisam") {

				//this fellow has no table yet to put his beer on!
				$createtableSQL = "
			 CREATE TABLE `$table` (
			`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
			 PRIMARY KEY ( `id` )
			 ) ENGINE = MYISAM 
			";
			}
			else {
				$createtableSQL = "
			 CREATE TABLE `$table` (
			`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
			 PRIMARY KEY ( `id` )
			 ) ENGINE = InnoDB 
			";
					
			}
			return $createtableSQL;
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryWiden( $options ) {
			extract($options);
			return "ALTER TABLE `$table` CHANGE `$column` `$column` $newtype ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryAddColumn( $options ) {
			extract($options);
			return "ALTER TABLE `$table` ADD `$column` $type ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryUpdate( $options ) {
			extract($options);
			$update = array();
			foreach($updatevalues as $u) {
				$update[] = " `".$u["property"]."` = \"".$u["value"]."\" ";
			}
			return "UPDATE `$table` SET ".implode(",",$update)." WHERE id = ".$id;
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryInsert( $options ) {

			extract($options);

			foreach($insertcolumns as $k=>$v) {
				$insertcolumns[$k] = "`".$v."`";
			}

			foreach($insertvalues as $k=>$v) {
				$insertvalues[$k] = "\"".$v."\"";
			}

			$insertSQL = "INSERT INTO `$table`
					  ( id, ".implode(",",$insertcolumns)." ) 
					  VALUES( null, ".implode(",",$insertvalues)." ) ";
			return $insertSQL;
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryCreate( $options ) {
			extract($options);
			return "INSERT INTO `$table` VALUES(null) ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryInferType( $options ) {
			extract($options);
			$v = "\"".$value."\"";
			$checktypeSQL = "insert into dtyp VALUES(null,$v,$v,$v,$v,$v )";
			return $checktypeSQL;
		}

		/**
		 *
		 * @return string $query
		 */
		private function getQueryResetDTYP() {
			return "truncate table dtyp";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryRegisterTable( $options ) {
			extract( $options );
			return "replace into redbeantables values (null, \"$table\") ";
		}
		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryUnregisterTable( $options ) {
			extract( $options );
			return "delete from redbeantables where tablename = \"$table\" ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryRelease( $options ) {
			extract( $options );
			return "DELETE FROM locking WHERE fingerprint=\"".$key."\" ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryRemoveExpirLock( $options ) {
			extract( $options );
			return "DELETE FROM locking WHERE expire < ".(time()-$locktime);
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryUpdateExpirLock( $options ) {
			extract( $options );
			return "UPDATE locking SET expire=".$time." WHERE id =".$id;
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryAQLock( $options ) {
			extract($options);
			return "INSERT INTO locking VALUES(\"$table\",$id,\"".$key."\",\"".$time."\") ";
		}
		
		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryDistinct($options) {
			extract($options);
			return "SELECT id FROM `$type` GROUP BY $field";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryFastLoad( $options ) {
			extract( $options );
			return "SELECT * FROM `$type` WHERE id IN ( ".implode(",", $ids)." ) ORDER BY FIELD(id,".implode(",", $ids).") ASC		";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryWhere($options) {
			extract($options);
			return "select `$table`.id from $table where ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryFind($options) {

			extract($options);
			$db = RedBean_OODB::$db;
			$findSQL = "SELECT id FROM `$tbl` WHERE ";

	   
			foreach($bean as $p=>$v) {
				if ($p === "type" || $p === "id") continue;
				$p = $db->escape($p);
				$v = $db->escape($v);
				if (isset($searchoperators[$p])) {
					if ($searchoperators[$p]==="LIKE") {
						$part[] = " `$p`LIKE \"%$v%\" ";
					}
					else {
						$part[] = " `$p` ".$searchoperators[$p]." \"$v\" ";
					}
				}
				else {
				}
			}
			if ($extraSQL) {
				$findSQL .= @implode(" AND ",$part) . $extraSQL;
			}
			else {
				$findSQL .= @implode(" AND ",$part) . " ORDER BY $orderby LIMIT $start, $end ";
			}
			return $findSQL;
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryList($options) {
			extract($options);
			$db = RedBean_OODB::$db;
			if ($extraSQL) {
				$listSQL = "SELECT * FROM ".$db->escape($type)." ".$extraSQL;
			}
			else {
				$listSQL = "SELECT * FROM ".$db->escape($type)."
			ORDER BY ".$orderby;
				if ($end !== false && $start===false) {
					$listSQL .= " LIMIT ".intval($end);
				}
				if ($start !== false && $end !== false) {
					$listSQL .= " LIMIT ".intval($start).", ".intval($end);
				}
				if ($start !== false && $end===false) {
					$listSQL .= " LIMIT ".intval($start).", 18446744073709551615 ";
				}
			}
			return $listSQL;
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryAddAssocNow( $options ) {
			extract($options);
			return "REPLACE INTO `$assoctable` VALUES(null,$id1,$id2) ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryUnassoc( $options ) {
			extract($options);
			return "DELETE FROM `$assoctable` WHERE ".$t1."_id = $id1 AND ".$t2."_id = $id2 ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryCreateAssoc($options) {

			extract($options);

			return "
			 CREATE TABLE `$assoctable` (
			`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
			`".$t1."_id` INT( 11 ) UNSIGNED NOT NULL,
			`".$t2."_id` INT( 11 ) UNSIGNED NOT NULL,
			 PRIMARY KEY ( `id` )
			 ) ENGINE = ".$engine."; 
			";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryUntree( $options ) {
			extract($options);
			return "DELETE FROM `$assoctable2` WHERE
				(parent_id = $idx1 AND child_id = $idx2) OR
				(parent_id = $idx2 AND child_id = $idx1) ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryAddAssoc($options) {
			extract( $options );
			return "ALTER TABLE `$assoctable` ADD UNIQUE INDEX `u_$assoctable` (`".$t1."_id`, `".$t2."_id` ) ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryDeltreeType($options) {
			extract( $options );
			return "DELETE FROM $assoctable WHERE parent_id = $id  OR child_id = $id ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryCreateTree( $options ) {
			extract( $options );
			return "
				 CREATE TABLE `$assoctable` (
				`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
				`parent_id` INT( 11 ) UNSIGNED NOT NULL,
				`child_id` INT( 11 ) UNSIGNED NOT NULL,
				 PRIMARY KEY ( `id` )
				 ) ENGINE = ".$engine."; 
				";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryUnique( $options ) {
			extract( $options );
			return "ALTER TABLE `$assoctable` ADD UNIQUE INDEX `u_$assoctable` (`parent_id`, `child_id` ) ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryAddChild( $options ) {
			extract( $options );
			return "REPLACE INTO `$assoctable` VALUES(null,$pid,$cid) ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryRemoveChild( $options ) {
			extract( $options );
			return "DELETE FROM `$assoctable` WHERE
				( parent_id = $pid AND child_id = $cid ) ";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryDescribe( $options ) {
			extract( $options );
			return "describe `$table`";
		}
		
		
		/**
	     * Returns an array of table Columns
	     * @return array
	     */
	    public function getTableColumns($tbl, RedBean_DBAdapter $db) {
	        $rs = $db->get($this->getQuery("describe",array(
	            "table"=>$tbl
	        )));
	    
	        return $rs;
	    } 
		

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryDropTables( $options ) {
			extract($options);
			return "drop tables ".implode(",",$tables);
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryDropColumn( $options ) {
			extract($options);
			return "ALTER TABLE `$table` DROP `$property`";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryTestColumn( $options ) {
			extract($options);
			return "alter table `$table` add __test  ".$type;
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryUpdateTest( $options ) {
			extract($options);
			return "update `$table` set __test=`$col`";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryMeasure( $options ) {
			extract($options);
			return "select count(*) as df from `$table` where
				strcmp(`$col`,__test) != 0 AND `$col` IS NOT NULL";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryRemoveTest($options) {
			extract($options);
			return "alter table `$table` change `$col` `$col` ".$type;
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getQueryDropTest($options) {
			extract($options);
			return "alter table `$table` drop __test";
		}

		
		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getIndex1($options) {
			extract($options);
			return "ALTER IGNORE TABLE `$table` ADD INDEX $indexname (`$col`)";
		}

		/**
		 *
		 * @param $options
		 * @return string $query
		 */
		private function getIndex2($options) {
			extract($options);
			return "ALTER IGNORE TABLE `$table` DROP INDEX $indexname";
		}
	
		/**
		 * Get SQL for destructors
		 * @param $options
		 * @return string $query
		 */
		private function getDestruct($options) {
			extract($options);
			if ($rollback) return;
			if ($engine=="innodb") return "COMMIT"; else return "";
		}

		/**
		 * Gets a basic SQL query
		 * @param array $options
		 * @param string $sql_type 
		 * @return string $sql
		 */
		private function getBasicQuery( $options, $sql_type="SELECT" ) {
			extract($options);
			if (isset($fields)){
				$sqlfields = array();
				foreach($fields as $field) {
					$sqlfields[] = " `$field` ";
				}
				$field = implode(",", $fields);
			}
			if (!isset($field)) $field="";
			$sql = "$sql_type ".$field." FROM `$table` ";
			if (isset($where)) {
				if (is_array($where)) {
					$crit = array();
					foreach($where as $w=>$v) {
						$crit[] = " `$w` = \"".$v."\"";						
					}
					$sql .= " WHERE ".implode(" AND ",$crit);	
				}
				else {
					$sql .= " WHERE ".$where;
				}	
			}
			return $sql;
		}


		/**
		 * (non-PHPdoc)
		 * @see RedBean/QueryWriter#getQuery()
		 */
		public function getQuery( $queryname, $params=array() ) {
			//echo "<br><b style='color:yellow'>$queryname</b>";
			switch($queryname) {
				case "create_table":
					return $this->getQueryCreateTable($params);
					break;
				case "widen_column":
					return $this->getQueryWiden($params);
					break;
				case "add_column":
					return $this->getQueryAddColumn($params);
					break;
				case "update":
					return $this->getQueryUpdate($params);
					break;
				case "insert":
					return $this->getQueryInsert($params);
					break;
				case "create":
					return $this->getQueryCreate($params);
					break;
				case "infertype":
					return $this->getQueryInferType($params);
					break;
				case "readtype":
		 			return $this->getBasicQuery(
		 				array("fields"=>array("tinyintus","intus","ints","varchar255","text"),
		 					"table" =>"dtyp",
		 					"where"=>array("id"=>$params["id"])));
		 			break;
				case "reset_dtyp":
					return $this->getQueryResetDTYP();
					break;
				case "prepare_innodb":
					return "SET autocommit=0";
					break;
				case "prepare_myisam":
					return "SET autocommit=1";
					break;
				case "starttransaction":
					return "START TRANSACTION";
					break;
				case "setup_dtyp":
					return "
				CREATE TABLE IF NOT EXISTS `dtyp` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `tinyintus` tinyint(3) unsigned NOT NULL,
				  `intus` int(11) unsigned NOT NULL,
				  `ints` bigint(20) NOT NULL,
				  `varchar255` varchar(255) NOT NULL,
				  `text` text NOT NULL,
				  PRIMARY KEY  (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
				";
					break;
				case "clear_dtyp":
					return "drop tables dtyp";
					break;
				case "setup_locking":
					return "
				CREATE TABLE IF NOT EXISTS `locking` (
				  `tbl` varchar(255) NOT NULL,
				  `id` bigint(20) NOT NULL,
				  `fingerprint` varchar(255) NOT NULL,
				  `expire` int(11) NOT NULL,
				  UNIQUE KEY `tbl` (`tbl`,`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=latin1;
				";
					break;
				case "setup_tables":
					return "
				 CREATE TABLE IF NOT EXISTS `redbeantables` (
				 `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
				 `tablename` VARCHAR( 255 ) NOT NULL ,
				 PRIMARY KEY ( `id` ),
				 UNIQUE KEY `tablename` (`tablename`)
				 ) ENGINE = MYISAM 
				";
					break;
				case "show_tables":
					return "show tables";
					break;
				case "show_rtables":
					return "select tablename from redbeantables";
					break;
				case "register_table":
					return $this->getQueryRegisterTable( $params );
					break;
				case "unregister_table":
					return $this->getQueryUnregisterTable( $params );
					break;
				case "release":
					return $this->getQueryRelease( $params );
					break;
				case "remove_expir_lock":
					return $this->getQueryRemoveExpirLock( $params );
					break;
				case "update_expir_lock":
					return $this->getQueryUpdateExpirLock( $params );
					break;
				case "aq_lock":
					return $this->getQueryAQLock( $params );
					break;
				case "get_lock":
					return $this->getBasicQuery(array("fields"=>array("id"),"table"=>"locking","where"=>array("id"=>$params["id"],"tbl"=>$params["table"],"fingerprint"=>$params["key"])));
					break;
				case "get_bean":
					return $this->getBasicQuery(array("field"=>"*","table"=>$params["type"],"where"=>array("id"=>$params["id"])));
					break;
				case "bean_exists":
					return $this->getBasicQuery(array("field"=>"count(*)","table"=>$params["type"],"where"=>array("id"=>$params["id"])));
					break;
				case "count":
					return $this->getBasicQuery(array("field"=>"count(*)","table"=>$params["type"]));
					break;
				case "distinct":
					return $this->getQueryDistinct($params);
					break;
				case "stat":
					return $this->getBasicQuery(array("field"=>$params["stat"]."(`".$params["field"]."`)","table"=>$params["type"]));
					break;
				case "releaseall":
					return "TRUNCATE locking";
					break;
				case "fastload":
					return $this->getQueryFastLoad($params);
					break;
				case "where":
					return $this->getQueryWhere($params);
					break;
				case "find":
					return $this->getQueryFind( $params);
					break;
				case "list":
					return $this->getQueryList( $params);
					break;
				case "create_assoc":
					return $this->getQueryCreateAssoc( $params );
					break;
				case "add_assoc":
					return $this->getQueryAddAssoc( $params );
					break;
				case "add_assoc_now":
					return $this->getQueryAddAssocNow( $params );
					break;
				case "unassoc":
					return $this->getQueryUnassoc( $params );
					break;
				case "untree":
					return $this->getQueryUntree( $params );
					break;
				case "get_assoc":
					$col = $params["t1"]."_id";
					return $this->getBasicQuery(array(
						"table"=>$params["assoctable"],
						"fields"=>array( $params["t2"]."_id" ),
						"where"=>array( $col=>$params["id"])
					));
					break;
				case "trash":
					return $this->getBasicQuery(array("table"=>$params["table"],"where"=>array("id"=>$params["id"])),"DELETE");
					break;
				case "deltree":
					return $this->getBasicQuery(array("table"=>$params["table"],"where"=>" parent_id = ".$params["id"]." OR child_id = ".$params["id"]),"DELETE");
					break;
				case "unassoc_all_t1":
					$col = $params["t"]."_id";
					return $this->getBasicQuery(array("table"=>$params["table"],"where"=>array($col=>$params["id"])),"DELETE");				
					break;
				case "unassoc_all_t2":
					$col = $params["t"]."2_id";
					return $this->getBasicQuery(array("table"=>$params["table"],"where"=>array($col=>$params["id"])),"DELETE");
					break;
				case "deltreetype":
					return $this->getQueryDeltreeType( $params );
					break;
				case "unassoctype1":
					$col = $params["t1"]."_id";
					$r = $this->getBasicQuery(array("table"=>$params["assoctable"],"where"=>array($col=>$params["id"])),"DELETE");
					//echo "<hr>$r";
					return $r;
					break;
				case "unassoctype2":
					$col = $params["t1"]."2_id";
					$r =$this->getBasicQuery(array("table"=>$params["assoctable"],"where"=>array($col=>$params["id"])),"DELETE");
					//echo "<hr>$r";
					return $r;		
					break;
				case "create_tree":
					return $this->getQueryCreateTree( $params );
					break;
				case "unique":
					return $this->getQueryUnique( $params );
					break;
				case "add_child":
					return $this->getQueryAddChild( $params );
					break;
				case "get_children":
					return $this->getBasicQuery(array("table"=>$params["assoctable"],"fields"=>array("child_id"),
						"where"=>array("parent_id"=>$params["pid"])));
					break;
				case "get_parent":
					return $this->getBasicQuery(array( "where"=>array("child_id"=>$params["cid"]),"fields"=>array("parent_id"),"table"=>$params["assoctable"]	));
					break;
				case "remove_child":
					return $this->getQueryRemoveChild( $params );
					break;
				case "num_related":
					$col = $params["t1"]."_id";
					return $this->getBasicQuery(array("field"=>"COUNT(1)","table"=>$params["assoctable"],"where"=>array($col=>$params["id"])));
					break;
				case "drop_tables":
					return $this->getQueryDropTables( $params );
					break;
				case "truncate_rtables":
					return "truncate redbeantables";
					break;
				case "drop_column":
					return $this->getQueryDropColumn( $params );
					break;
				case "describe":
					return $this->getQueryDescribe( $params );
					break;
				case "get_null":
					return $this->getBasicQuery(array("field"=>"count(*)","table"=>$params["table"],"where"=>" `".$params["col"]."` IS NOT NULL "));
					return $this->getQueryGetNull( $params );
					break;
				case "test_column":
					return $this->getQueryTestColumn( $params );
					break;
				case "update_test":
					return $this->getQueryUpdateTest( $params );
					break;
				case "measure":
					return $this->getQueryMeasure( $params );
					break;
				case "remove_test":
					return $this->getQueryRemoveTest($params);
					break;
				case "drop_test":
					return $this->getQueryDropTest($params);
					break;
				case "variance":
					return $this->getBasicQuery(array("field"=>"count(distinct `".$params["col"]."`)","table"=>$params["table"]));
					break;
				case "index1":
					return $this->getIndex1($params);
					break;
				case "index2":
					return $this->getIndex2($params);
					break;
				case "drop_type":
					return $this->getBasicQuery(array("table"=>$params["type"]),"DELETE");				
					break;
				case "destruct":
					return $this->getDestruct($params);
					break;
				default:
					throw new Exception("QueryWriter has no support for Query:".$queryname);
			}
		}

		/**
		 * @return string $query
		 */
		public function getQuote() {
			return "\"";
		}

		/**
		 * @return string $query
		 */
		public function getEscape() {
			return "`";
		}
}
/**
 * QueryWriter
 * Interface for QueryWriters
 * @package 		RedBean/QueryWriter.php
 * @description		Describes the API for a QueryWriter
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface QueryWriter {
	
	/**
	 * Returns the requested query if the writer has any
	 * @param $queryname
	 * @param $params
	 * @return mixed $sql_query
	 */
	public function getQuery( $queryname, $params=array() );
	
	/**
	 * Gets the quote-escape symbol of this writer
	 * @return unknown_type
	 */
	public function getQuote();

	/**
	 * Gets the backtick for this writer
	 * @return unknown_type
	 */
	public function getEscape();
	
	public function getTableColumns( $tbl, RedBean_DBAdapter $db );

}
//For framework intergration if you define $db you can specify a class prefix for models
if (!isset($db)) define("PRFX","");
if (!isset($db)) define("SFFX","");

/**
 * RedBean Setup
 * Helper class to quickly setup RedBean for you
 * @package 		RedBean/Setup.php
 * @description		Helper class to quickly setup RedBean for you
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Setup { 
	
	/**
	 * Kickstarts RedBean :)
	 * @param $dsn
	 * @param $username
	 * @param $password
	 * @param $freeze
	 * @param $engine
	 * @param $debugmode
	 * @param $unlockall
	 * @return unknown_type
	 */
	public static function kickstart( $dsn="mysql:host=localhost;dbname=oodb", 
									  $username='root', 
									  $password='', 
									  $freeze=false, 
  									  $engine="innodb", 
									  $debugmode=false, 
									  $unlockall=false) {
		
		//This is no longer configurable							  		
		eval("
			class R extends RedBean_OODB { }
		");
		
		eval("
			class RD extends RedBean_Decorator { }
		");
		

		//get an instance of the MySQL database
		
		if (strpos($dsn,"embmysql")===0) {

			//try to parse emb string
			$dsn .= ';';
			$matches = array();
			preg_match('/host=(.+?);/',$dsn,$matches);
			$matches2 = array();
			preg_match('/dbname=(.+?);/',$dsn,$matches2);
			if (count($matches)==2 && count($matches2)==2) {
				$db = RedBean_Driver_MySQL::getInstance( $matches[1], $username, $password, $matches2[1] );
			}
			else {
				throw new Exception("Could not parse MySQL DSN");
			}
		}
		else{
			$db = Redbean_Driver_PDO::getInstance( $dsn, $username, $password, null );
		}
		
		if ($debugmode) {
			$db->setDebugMode(1);
		}
	
		RedBean_OODB::$db = new RedBean_DBAdapter($db); //Wrap ADO in RedBean's adapter
		RedBean_OODB::setEngine($engine); //select a database driver
		RedBean_OODB::init( new QueryWriter_MySQL() ); //Init RedBean
	
		if ($unlockall) {
			RedBean_OODB::resetAll(); //Release all locks
		}
	
		if ($freeze) {
			RedBean_OODB::freeze(); //Decide whether to freeze the database
		}
	}
	
	/**
	 * Kickstarter for development phase
	 * @param $dsn
	 * @param $username
	 * @param $password
	 * @param $gen
	 * @return unknown_type
	 */
	public static function kickstartDev( $gen, $dsn, $username="root", $password="" ) {
		
		//kickstart for development
		self::kickstart( $dsn, $username, $password, false, "innodb", false, false);
		
		//generate classes
		R::gen( $gen );
	}
	
	/**
	 * Kickstarter for deployment phase and testing
	 * @param $dsn
	 * @param $username
	 * @param $password
	 * @param $gen
	 * @return unknown_type
	 */
	public static function kickstartFrozen( $gen, $dsn, $username="root", $password="" ) {
		
		//kickstart for development
		self::kickstart( $dsn, $username, $password, true, "innodb", false, false);
		
		//generate classes
		R::gen( $gen );
	}
		
	
}
/**
 * Sieve
 * @package 		RedBean/Sieve.php
 * @description		Filters a bean
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Sieve {
	
	/**
	 * 
	 * @var array
	 */
	private $vals;
	
	/**
	 * 
	 * @var array
	 */
	private $report = array();
	
	/**
	 * 
	 * @var boolean
	 */
	private $succes = true;
	
	/**
	 * 
	 * @param $validations
	 * @return unknown_type
	 */
	public static function make( $validations ) {
		
		$sieve = new self;
		$sieve->vals = $validations;
		return $sieve;
			
	}
	
	/**
	 * 
	 * @param $deco
	 * @return unknown_type
	 */
	public function valid( RedBean_Decorator $deco ) {
	
		foreach($this->vals as $p => $v) {
			if (class_exists($v)) {
				$validator = new $v( $deco, $report );
				if ($validator instanceof RedBean_Validator) { 
					$message = $validator->check( $deco->$p );
					if ($message !== true) {
						$this->succes = false;
					}
					if (!isset($this->report[$v])) {
						$this->report[$v]=array();
					}
					$this->report[ $v ][ $p ] = $message;
						
				}
			}
		}
		return $this->succes;	
	}
	
	/**
	 * 
	 * @param $deco
	 * @param $key
	 * @return unknown_type
	 */
	public function validAndReport( RedBean_Decorator $deco, $key=false ) {
		$this->valid( $deco );
		if ($key) {
			if (isset($this->report[$key])) {
				return $this->report[$key];
			}
		}
		return $this->report;
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	public function getReport() {
		return $this->report;
	}
	
	
}
/**
 * RedBean Tools
 * Tool Collection for RedBean
 * @package 		RedBean/Tools.php
 * @description		A series of Tools of RedBean
 * @author			Desfrenes
 * @license			BSD
 */
class RedBean_Tools
{
	/**
	 * 
	 * @var unknown_type
	 */
    private static $class_definitions;
    
    /**
     * 
     * @var unknown_type
     */
    private static $remove_whitespaces;
    
    /**
     * 
     * @param $root
     * @param $callback
     * @param $recursive
     * @return unknown_type
     */
    public static function walk_dir( $root, $callback, $recursive = true )
    {
        $root = realpath($root);
        $dh   = @opendir( $root );
        if( false === $dh )
        {
            return false;
        }
        while(false !==  ($file = readdir($dh)))
        {
            if( "." == $file || ".." == $file )
            {
                continue;
            }
            call_user_func( $callback, "{$root}/{$file}" );
            if( false !== $recursive && is_dir( "{$root}/{$file}" ))
            {
                Redbean_Tools::walk_dir( "{$root}/{$file}", $callback, $recursive );
            }
        }
        closedir($dh);
        return true;
    }
 
    /**
     * 
     * @param $file
     * @param $removeWhiteSpaces
     * @return unknown_type
     */
    public static function compile($file = '', $removeWhiteSpaces = true)
    {
        self::$remove_whitespaces = $removeWhiteSpaces;
        self::$class_definitions = '';
        $base = dirname(__FILE__) . '/';
        self::walk_dir($base,'Redbean_Tools::stripClassDefinition');
        $content = str_replace("\r\n","\n", ' ' . "\n" . file_get_contents($base . 'license.txt') . "\n" . self::$class_definitions);
        if(!empty($file))
        {
            file_put_contents($file, $content);
        }
        return $content;
    }
 
    /**
     * 
     * @param $file
     * @return unknown_type
     */
    private static function stripClassDefinition($file)
    {
        if(is_file($file) && substr($file, -4) == '.php')
        {
            if(self::$remove_whitespaces)
            {
                self::$class_definitions .= "\n" . trim(str_replace('', '', php_strip_whitespace($file)));
            }
            else
            {
                self::$class_definitions .= "\n" . trim(str_replace('', '', trim(file_get_contents($file))));
            }
        }
    }
}
/**
 * RedBean Validator Alphanumeric
 * @package 		RedBean/Validator/AlphaNumeric.php
 * @description		Checks whether a value is alpha numeric
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Validator_AlphaNumeric implements RedBean_Validator {
	/**
	 * (non-PHPdoc)
	 * @see RedBean/RedBean_Validator#check()
	 */
	public function check( $v ) {
		return (bool) preg_match('/^[A-Za-z0-9]+$/', $v);
	}
}
/**
 * RedBean Validator
 * @package 		RedBean/Validator.php
 * @description		API for Validators
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_Validator {
	/**
	 * 
	 * @param $property
	 * @return unknown_type
	 */
	public function check( $property );
}