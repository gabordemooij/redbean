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
|Xwisdom

======================================================
|						       RedBean is Licensed BSD
------------------------------------------------------
|RedBean is a OOP Database Simulation Middleware layer
|for php.
------------------------------------------------------
|Loosely based on an idea by Erik Roelofs - thanks man

VERSION 0.6

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
	 * 
	 * @var RedBean_OODB
	 */
	private $provider = null;
	
	/**
	 * Constructor
	 * @param $type
	 * @param $collection
	 * @return RedBean_Can $instance
	 */
	public function __construct( RedBean_ToolBox_ModHub $provider, $type="", $collection = array() ) {
		
		$this->provider=$provider;
		$this->collectionIDs = $collection;
		$this->type = $type;
		$this->num = count( $this->collectionIDs );
	}
	
	/**
	 * Wraps an RedBean_OODBBean in a RedBean_Decorator
	 * @param RedBean_OODBBean $bean
	 * @return RedBean_Decorator $deco
	 */
	public function wrap( $bean, $prefix=false, $suffix=false ) {

		if (!$prefix) {
			$prefix = RedBean_Setup_Namespace_PRFX;
		}  
		
		if (!$suffix) {
			$suffix = RedBean_Setup_Namespace_SFFX;
		}
		$dclass = $prefix.$this->type.$suffix;
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

                $rows = $this->provider->getBeanStore()->fastloader( $this->type, $this->collectionIDs );
		$beans = array();
		if (is_array($rows)) {
			foreach( $rows as $row ) {
				//Use the fastloader for optimal performance (takes row as data)
				$beans[] = $this->wrap( $this->provider->getBeanStore()->get( $this->type, $row["id"] , $row) );
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
			return $this->wrap( $this->provider->getBeanStore()->get( $this->type, $id ) );
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
		return $this;
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
			return $this->wrap( $this->provider->getBeanStore()->get( $this->type, $id ) );
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
    	$this->collectionIDs = array_reverse($this->collectionIDs, false);
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
class RedBean_DBAdapter extends RedBean_Observable implements RedBean_Tool {

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
	 * @var RedBean_OODBBean
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
	 * @var RedBean_OODB
	 */
	protected $provider = null;


	/**
	 * Constructor, loads directly from main table
	 * @param $type
	 * @param $id
	 * @return unknown_type
	 */
	public function __construct( RedBean_OODB $provider, $type=false, $id=0) {

		$this->provider = $provider;
                
               
		$id = floatval( $id );
		if (!$type) {
			throw new Exception("Undefined bean type");
		}
		else {
                	$this->type = $this->provider->getToolBox()->getFilter()->table($type);
                        //echo $this->type;
			if ($id > 0) { //if the id is higher than 0 load data
				$this->data = $this->provider->getToolBox()->getBeanStore()->get( $this->type, $id);
			}
			else { //otherwise, dispense a regular empty RedBean_OODBBean
				$this->data = $this->provider->dispense( $this->type );
			}
		}
	}
	
	/**
	 * This is a service for static convenience methods that
	 * have no object context but still need a provider.
	 * @return unknown_type
	 */
	private static function getStaticProvider() {
		return RedBean_OODB::getInstance();
	}

	/**
	 * Free memory of a class, drop column in db
	 * @param $property
	 * @return unknown_type
	 */
	public function free( $property ) {
		$this->signal("deco_free", $this);
		$this->provider->dropColumn( $this->type, $property );
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
		$name = $this->provider->getToolBox()->getFilter()->property($name, true);
		return isset($this->data->$name) ? $this->data->$name : null;
	}

	/**
	 * Magic setter. Another way to handle accessors
	 */
	public function __set( $name, $value ) {
		$this->signal("deco_set", $this);
		$name = $this->provider->getToolBox()->getFilter()->property($name);
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
			$prop = $this->provider->getToolBox()->getFilter()->property($prop);
			$this->$prop = $arguments[0];
			return $this;

		}
		elseif (strpos($method,"getRelated")===0)	{
			$this->signal("deco_get", $this);
			$prop = $this->provider->getToolBox()->getFilter()->table( substr( $method, 10 ) );
			$beans = $this->provider->getAssoc( $this->data, $prop );
			$decos = array();
			$dclass = RedBean_Setup_Namespace_PRFX.$prop.RedBean_Setup_Namespace_SFFX;

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
                        $prop = $this->provider->getToolBox()->getFilter()->property($prop, true);
			return $this->$prop;
		}
		elseif (strpos( $method, "is" ) === 0) {
			$prop = $this->provider->getToolBox()->getFilter()->property( substr( $method, 2 ) );
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
			$this->provider->associate($this->data, $bean);
			return $this;
		}
		else if (strpos($method,"remove")===0) {
			$this->signal("deco_remove",$this);
			$deco = $arguments[0];
			$bean = $deco->getData();
			$this->provider->unassociate($this->data, $bean);
			return $this;
		}
		else if (strpos($method,"attach")===0) {
			$this->signal("deco_attach",$this);
			$deco = $arguments[0];
			$bean = $deco->getData();
			$this->provider->addChild($this->data, $bean);
			return $this;
		}
		else if (strpos($method,"clearRelated")===0) {
			$this->signal("deco_clearrelated",$this); 
			$type = $this->provider->getToolBox()->getFilter()->table( substr( $method, 12 ) );
			$this->provider->deleteAllAssocType($type, $this->data);
			return $this;
		}
		else if (strpos($method,"numof")===0) {
			$this->signal("deco_numof",$this);
			$type = $this->provider->getToolBox()->getFilter()->table( substr( $method, 5 ) );
			return $this->provider->numOfRelated($type, $this->data);
				
		}
	}

	/**
	 * Enforces an n-to-1 relationship
	 * @param $deco
	 * @return unknown_type
	 */
	public function belongsTo( $deco ) {
		$this->signal("deco_belongsto", $this);
		$this->provider->deleteAllAssocType($deco->getType(), $this->data);
		$this->provider->associate($this->data, $deco->getData());
	}

	/**
	 * Enforces an 1-to-n relationship
	 * @param $deco
	 * @return unknown_type
	 */
	public function exclusiveAdd( $deco ) {
		$this->signal("deco_exclusiveadd", $this);
		$this->provider->deleteAllAssocType($this->type,$deco->getData());
		$this->provider->associate($deco->getData(), $this->data);
	}

	/**
	 * Returns the parent object of the current object if any
	 * @return RedBean_Decorator $oBean
	 */
	public function parent() {
		$this->signal("deco_parent", $this);
		$beans = $this->provider->getParent( $this->data );
		if (count($beans) > 0 ) $bean = array_pop($beans); else return null;
		$dclass = RedBean_Setup_Namespace_PRFX.$this->type.RedBean_Setup_Namespace_SFFX;
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
		$beans = $this->provider->getParent( $this->data );
		if (count($beans) > 0 ) {
			$bean = array_pop($beans);
		}
		else {
			return null;
		}
		$beans = $this->provider->getChildren( $bean );
		$decos = array();
		$dclass = RedBean_Setup_Namespace_PRFX.$this->type.RedBean_Setup_Namespace_SFFX;
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
		$beans = $this->provider->getChildren( $this->data );
		$decos = array();
		$dclass = RedBean_Setup_Namespace_PRFX.$this->type.RedBean_Setup_Namespace_SFFX;
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
		$clone = new self( $this->provider, $this->type, 0 );
		$clone->setData( $this->getData() );
		return $clone;
	}

	/**
	 * Clears all associations
	 * @return unknown_type
	 */
	public function clearAllRelations() {
		$this->signal("deco_clearrelations", $this);
		$this->provider->deleteAllAssoc( $this->getData() );
	}

	/**
	 * Gets data directly
	 * @return RedBean_OODBBean
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
		return $this->provider->getToolBox()->getBeanStore()->set( $this->data );
	}

	/**
	 * Deletes the bean
	 * @param $deco
	 * @return unknown_type
	 */
	public static function delete( $deco ) {
		self::getStaticProvider()->trash( $deco->getData() );
	}

        /**
         * Same as delete() but not static
         */
        public function destroy() {
                $this->provider->trash( $this->getData() );
        }


	/**
	 * Explicitly forward-locks a decorated bean
	 * @return unknown_type
	 */
	public function lock() {
		$this->provider->getLockManager()->openBean($this->getData());
	}

	/**
	 * Explicitly unlocks a decorated bean
	 * @return unknown_type
	 */
	public function unlock() {
		$this->provider->closeBean( $this->getData());
	}


	/**
	 * Closes and unlocks the bean
	 * @param $deco
	 * @return unknown_type
	 */
	public static function close( $deco ) {
		self::getStaticProvider()->closeBean( $deco->getData() );
	}

	/**
	 * Creates a redbean decorator for a specified type
	 * @param $type
	 * @param $id
	 * @return unknown_type
	 */
	public static function make( $type="", $id ){
		return new RedBean_Decorator( self::getStaticProvider(), $type, $id );
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


                $rf = new RedBean_Mod_Filter_Strict;

		if (!is_array($filter)) {
			return array();
		}

		if (count($filter)<1) {
			return array();
		}

		//make all keys of the filter lowercase
		$filters = array();
		foreach($filter as $key=>$f) {
			$filters[$rf->property($key)] =$f;

			if (!in_array($f,array("=","!=","<",">","<=",">=","like","LIKE"))) {
				throw new ExceptionInvalidFindOperator();
			}

		}

		$beans = self::getStaticProvider()->find( $deco->getData(), $filters, $start, $end, $orderby, $extraSQL );

		$decos = array();
		$dclass = RedBean_Setup_Namespace_PRFX.$deco->type.RedBean_Setup_Namespace_SFFX;
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
			$this->provider->getToolBox()->getLockManager()->openBean($this->data, true);
		}
		catch(RedBean_Exception_FailedAccessBean $e){
			return true;
		}
		return false;
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
 * RedBean Exception Base
 * @package 		RedBean/Exception.php
 * @description		Represents the base class
 * 					for RedBean Exceptions
 * @author			Gabor de Mooij
 * @license			BSD
 */
class Redbean_Exception extends Exception{}
abstract class RedBean_Mod implements RedBean_Tool  {

    protected $provider;

    public function __construct(RedBean_ToolBox $provider) {
        $this->provider = $provider;
    }

}
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
 * @name RedBean OODB
 * @package RedBean
 * @author Gabor de Mooij and the RedBean Team
 * @copyright Gabor de Mooij (c) 
 * @license BSD
 * 
 * The RedBean OODB Class acts as a facade; it connects the
 * user models to internal modules and hides the various modules
 * behind a coherent group of methods. 
 */
class RedBean_OODB {

    /**
     *
     * @var string
     */
    public $pkey = false;

    /**
     *
     * @var boolean
     */
    private $rollback = false;

    private static $me = null;

    private $engine = "myisam";

    private $frozen = false;

    private $toolbox = null;

    public function initWithToolBox( RedBean_ToolBox_ModHub $toolbox ) {
        $this->toolbox = $toolbox;
        $db = $this->toolbox->getDatabase();
        $writer = $this->toolbox->getWriter();
        //prepare database
        if ($this->engine === "innodb") {
            $db->exec($writer->getQuery("prepare_innodb"));
            $db->exec($writer->getQuery("starttransaction"));
        }
        else if ($this->engine === "myisam") {
                $db->exec($writer->getQuery("prepare_myisam"));
        }
        //generate the basic redbean tables
        //Create the RedBean tables we need -- this should only happen once..
        if (!$this->frozen) {
            $db->exec($writer->getQuery("clear_dtyp"));
            $db->exec($writer->getQuery("setup_dtyp"));
            $db->exec($writer->getQuery("setup_locking"));
            $db->exec($writer->getQuery("setup_tables"));
        }
        //generate a key
        if (!$this->pkey) {
            $this->pkey = str_replace(".","",microtime(true)."".mt_rand());
        }
        return true;
    }

   
    public function __destruct() {

        $this->getToolBox()->getLockManager()->unlockAll();

        $this->toolbox->getDatabase()->exec(
            $this->toolbox->getWriter()->getQuery("destruct", array("engine"=>$this->engine,"rollback"=>$this->rollback))
        );
    }
    
    public function isFrozen() {
        return (boolean) $this->frozen;
    }

    public static function getInstance( RedBean_ToolBox_ModHub $toolbox = NULL ) {
        if (self::$me === null) {
            self::$me = new RedBean_OODB;
        }
        if ($toolbox) self::$me->initWithToolBox( $toolbox );
        return self::$me;
    }

    public function getToolBox() {
        return $this->toolbox;
    }

    public function getEngine() {
        return $this->engine;
    }

    public function setEngine( $engine ) {

        if ($engine=="myisam" || $engine=="innodb") {
            $this->engine = $engine;
        }
        else {
            throw new Exception("Unsupported database engine");
        }

        return $this->engine;

    }

    public static function rollback() {
        $this->rollback = true;
    }


    public function freeze() {
        $this->frozen = true;
    }

    public function unfreeze() {
        $this->frozen = false;
    }

   // public function exists($type,$id) {
   //     return $this->toolbox->getBeanStore()->exists($type, $id);
   // }

    public function numberof($type) {
        return $this->toolbox->getBeanStore()->numberof( $type );
    }

    public function distinct($type, $field) {
        return $this->toolbox->getLister()->distinct( $type, $field );
    }

    private function stat($type,$field,$stat="sum") {
        return $this->toolbox->getLister()->stat( $type, $field, $stat);
    }

    public function sumof($type,$field) {
        return $this->stat( $type, $field, "sum");
    }

    public function avgof($type,$field) {
        return $this->stat( $type, $field, "avg");
    }

    public function minof($type,$field) {
        return $this->stat( $type, $field, "min");
    }

    public function maxof($type,$field) {
        return $this->stat( $type, $field, "max");
    }

    public function resetAll() {
        $sql = $this->toolbox->getWriter()->getQuery("releaseall");
        $this->toolbox->getDatabase()->exec( $sql );
        return true;
    }

    public function getBySQL( $rawsql, $slots, $table, $max=0 ) {
        return $this->toolbox->getSearch()->sql( $rawsql, $slots, $table, $max );
    }

    public function find(RedBean_OODBBean $bean, $searchoperators = array(), $start=0, $end=100, $orderby="id ASC", $extraSQL=false) {
        return $this->toolbox->getFinder()->find($bean, $searchoperators, $start, $end, $orderby, $extraSQL);

    }
    public function listAll($type, $start=false, $end=false, $orderby="id ASC", $extraSQL = false) {
        return $this->toolbox->getLister()->get($type, $start, $end, $orderby,$extraSQL);
    }
    public function associate( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 ) { //@associate
        return $this->toolbox->getAssociation()->link( $bean1, $bean2 );
    }
    public function unassociate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
        return $this->toolbox->getAssociation()->breakLink( $bean1, $bean2 );
    }
    public function getAssoc(RedBean_OODBBean $bean, $targettype) {
        return $this->toolbox->getAssociation()->get( $bean, $targettype );
    }
    public function trash( RedBean_OODBBean $bean ) {
        return $this->toolbox->getBeanStore()->trash( $bean );
    }
    public function deleteAllAssoc( $bean ) {
        return $this->toolbox->getAssociation()->deleteAllAssoc( $bean );
    }
    public function deleteAllAssocType( $targettype, $bean ) {
        return $this->toolbox->getAssociation()->deleteAllAssocType( $targettype, $bean );
    }
    public function dispense( $type="StandardBean" ) {
        return $this->toolbox->getDispenser()->dispense( $type );
    }
    public function addChild( RedBean_OODBBean $parent, RedBean_OODBBean $child ) {
        return $this->toolbox->getTree()->add( $parent, $child );
    }

    public function getChildren( RedBean_OODBBean $parent ) {
        return $this->toolbox->getTree()->getChildren($parent);
    }

    public function getParent( RedBean_OODBBean $child ) {
        return $this->toolbox->getTree()->getParent($child);
    }

    public function removeChild(RedBean_OODBBean $parent, RedBean_OODBBean $child) {
        return $this->toolbox->getTree()->removeChild( $parent, $child );
    }

    public function numofRelated( $type, RedBean_OODBBean $bean ) {
        return $this->toolbox->getAssociation()->numOfRelated( $type, $bean );

    }
    public function generate( $classes, $prefix = false, $suffix = false ) {
        return $this->toolbox->getClassGenerator()->generate($classes,$prefix,$suffix);
    }

    public function clean() {
        return $this->toolbox->getGC()->clean();
    }

    public function removeUnused( ) {
        return $this->toolbox->getGC()->removeUnused( $this, $this->toolbox->getDatabase(), $this->toolbox->getWriter() );
    }
    
    public function dropColumn( $table, $property ) {
        return $this->toolbox->getGC()->dropColumn($table,$property);
    }

    public function trashAll($type) {
        $this->toolbox->getDatabase()->exec( $this->toolbox->getWriter()->getQuery("drop_type",array("type"=>$this->toolbox->getFilter()->table($type))));
    }

    public static function gen($arg, $prefix = false, $suffix = false) {
        return self::getInstance()->generate($arg, $prefix, $suffix);
    }

    public static function keepInShape($gc = false ,$stdTable=false, $stdCol=false) {
        return self::getInstance()->getToolBox()->getOptimizer()->run($gc, $stdTable, $stdCol);
    }

    public static function kickstartDev( $gen, $dsn, $username="root", $password="", $debug=false ) {
        return RedBean_Setup::kickstartDev( $gen, $dsn, $username, $password, $debug ); 
    }

    public static function kickstartFrozen( $gen, $dsn, $username="root", $password="" ) {
        return RedBean_Setip::kickstartFrozen( $gen, $dsn, $username, $password);
    }
}
/**
 * RedBean_OODBBean (Object Oriented DataBase Bean)
 * @package 		RedBean/RedBean_OODBBean.php
 * @description		The Bean class used for passing information
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_OODBBean {
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
		
		RedBean_OODB::getInstance()->getToolBox()->getDatabase()->addEventListener( "sql_exec", $logger );
	
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
 * QueryWriter
 * Interface for QueryWriters
 * @package 		RedBean/QueryWriter.php
 * @description		Describes the API for a QueryWriter
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_QueryWriter {
	
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
	
	
	/**
	 * 
	 * @param string $tbl
	 * @param RedBean_DBAdapter $db
	 * @return array $arr( array('Field'=>$string, 'Type'=>$string) )
	 */
	public function getTableColumns( $tbl, RedBean_DBAdapter $db );

}
//For framework intergration if you can specify a class prefix for models
if (!defined("RedBean_Setup_Namespace_PRFX")) define("RedBean_Setup_Namespace_PRFX","");
if (!defined("RedBean_Setup_Namespace_SFFX")) define("RedBean_Setup_Namespace_SFFX","");

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
		if (!class_exists("R")) {
			eval("
				class R extends RedBean_OODB { }
			");
			
			eval("
				class RD extends RedBean_Decorator { }
			");
		}
		
		
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
			$db = new Redbean_Driver_PDO( $dsn, $username, $password, null );
		}
		
		if ($debugmode) {
			$db->setDebugMode(1);
		}

                $conn = new RedBean_DBAdapter($db);//Wrap ADO in RedBean's adapter
		$writer = new QueryWriter_MySQL();
                //assemble a toolbox
                $toolbox = new RedBean_ToolBox_ModHub();
                $toolbox->add("database", $conn);
                $toolbox->add("writer", $writer);
                $toolbox->add("filter",new RedBean_Mod_Filter_Strict($toolbox));
                $toolbox->add("beanchecker",new RedBean_Mod_BeanChecker($toolbox));
                $toolbox->add("gc",new RedBean_Mod_GarbageCollector($toolbox));
                $toolbox->add("classgenerator",new RedBean_Mod_ClassGenerator($toolbox));
                $toolbox->add("search",new RedBean_Mod_Search($toolbox));
                $toolbox->add("optimizer",new RedBean_Mod_Optimizer($toolbox));
                $toolbox->add("beanstore",new RedBean_Mod_BeanStore($toolbox));
                $toolbox->add("association",new RedBean_Mod_Association($toolbox));
                $toolbox->add("lockmanager",new RedBean_Mod_LockManager($toolbox));
                $toolbox->add("tree",new RedBean_Mod_Tree($toolbox));
                $toolbox->add("tableregister",new RedBean_Mod_TableRegister($toolbox));
                $toolbox->add("finder",new RedBean_Mod_Finder($toolbox));
                $toolbox->add("dispenser",new RedBean_Mod_Dispenser($toolbox));
                $toolbox->add("scanner",new RedBean_Mod_Scanner($toolbox));
                $toolbox->add("lister",new RedBean_Mod_Lister($toolbox));
               
                
                $redbean = RedBean_OODB::getInstance( $toolbox );
                $toolbox->setFacade( $redbean );
                //$oldconn = RedBean_OODB::getInstance()->getInstance()->getDatabase();
                $redbean->setEngine($engine);


                  



                

                //RedBean_OODB::getInstance()->setDatabase( $conn );
		
		
		//RedBean_OODB::getInstance()->setEngine($engine); //select a database driver
		//RedBean_OODB::getInstance()->init(  ); //Init RedBean
	
		if ($unlockall) {
			
	 
			//RedBean_OODB::getInstance()->resetAll(); //Release all locks
                        $redbean->resetAll();
		}
	
		if ($freeze) {
			//RedBean_OODB::getInstance()->freeze(); //Decide whether to freeze the database
                        $redbean->freeze();
		}
	
		return $redbean;
	}
	
	/**
	 * Kickstarter for development phase
	 * @param $gen
	 * @param $dsn
	 * @param $username
	 * @param $password
	 * @param $debug
	 * @return unknown_type
	 */
	public static function kickstartDev( $gen, $dsn, $username="root", $password="", $debug=false ) {
		
		//kickstart for development
		return self::kickstart( $dsn, $username, $password, false, "innodb", $debug, false);
		
		//generate classes
		//RedBean_OODB::getInstance()->gen( $gen );
                //return RedBean_OODB::getInstance();
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
		return self::kickstart( $dsn, $username, $password, true, "innodb", false, false);
		
		//generate classes
		//RedBean_OODB::getInstance()->gen( $gen );
                //return RedBean_OODB::getInstance();
	}
	
	
	public static function reconnect( RedBean_DBAdapter $newDatabase ) {
		
                $oldToolBox = RedBean_OODB::getInstance()->getToolBox();
                $oldDatabase = $oldToolBox->getDatabase();
                $oldToolBox->add("database", $newDatabase);
                return $oldDatabase;



                //$old = RedBean_OODB::getInstance()->getInstance()->getDatabase();
		//RedBean_OODB::getInstance()->getInstance()->setDatabase( $new );
		//return $old;
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
interface RedBean_Tool {

    

}
abstract class RedBean_ToolBox {


    private $tools = array();



    protected function give( $toolname ) {
        if ($this->has($toolname)) {
            return $this->tools[$toolname];
        }
        else {
            throw new Exception("Module or tool $toolname has not been installed.");
        }
    }


    public function has( $toolname ) {
        return (isset($this->tools[$toolname]));
    }

    public function add( $label, RedBean_Tool $tool ) {
        $this->tools[$label] = $tool;
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
    private static $class_definitions = array();
    
    /**
     * 
     * @var unknown_type
     */
    private static $remove_whitespaces;


    private static $count = 0;
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
        self::$class_definitions = array();
        $base = dirname(__FILE__) . '/';
        self::walk_dir($base,'Redbean_Tools::stripClassDefinition');
        $str ='';
        ksort(self::$class_definitions);
        foreach( self::$class_definitions as $k=>$v){
            //echo "\n".$k.' - '.$file;
            $str .= $v;
        }
        
        $content = str_replace("\r\n","\n", ' ' . "\n" . file_get_contents($base . 'license.txt') . "\n" . $str);
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
          
            $index = (substr_count($file, "/") * 1000) + (++self::$count);

            if(self::$remove_whitespaces)
            {
                self::$class_definitions[$index] = "\n" . trim(str_replace('', '', php_strip_whitespace($file)));
            }
            else
            {
                self::$class_definitions[$index] = "\n" . trim(str_replace('', '', trim(file_get_contents($file))));
            }
        }
    }
}
/**
 * Interface RedBean Validator
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

	public function GetRaw() {
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
    	//PDO::MYSQL_ATTR_INIT_COMMAND
        $this->pdo = new PDO(
                $dsn,
                $user,
                $pass,
                
                array(1002 => 'SET NAMES utf8',
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
class RedBean_Mod_Association extends RedBean_Mod {

    public function link( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 ) {
    //get a database
        $db = $this->provider->getDatabase();

        //first we check the beans whether they are valid
        $bean1 = $this->provider->getBeanChecker()->checkBeanForAssoc($bean1);
        $bean2 = $this->provider->getBeanChecker()->checkBeanForAssoc($bean2);

        $this->provider->getLockManager()->openBean( $bean1, true );
        $this->provider->getLockManager()->openBean( $bean2, true );

        //sort the beans
        $tp1 = $bean1->type;
        $tp2 = $bean2->type;
        if ($tp1==$tp2) {
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
        if (!$this->provider->isFrozen()) {
            $alltables = $this->provider->getTableRegister()->getTables();
            if (!in_array($assoctable, $alltables)) {
            //no assoc table does not exist, create it..
                $t1 = $tables[0];
                $t2 = $tables[1];

                if ($t1==$t2) {
                    $t2.="2";
                }

                $assoccreateSQL = $this->provider->getWriter()->getQuery("create_assoc",array(
                    "assoctable"=> $assoctable,
                    "t1" =>$t1,
                    "t2" =>$t2,
                    "engine"=>$this->provider->getEngine()
                ));

                $db->exec( $assoccreateSQL );

                //add a unique constraint
                $db->exec( $this->provider->getWriter()->getQuery("add_assoc",array(
                    "assoctable"=> $assoctable,
                    "t1" =>$t1,
                    "t2" =>$t2
                    )) );

                $this->provider->getTableRegister()->register( $assoctable );
            }
        }

        //now insert the association record
        $assocSQL = $this->provider->getWriter()->getQuery("add_assoc_now", array(
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
    public function breakLink(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
    //get a database
        $db = $this->provider->getDatabase();

        //first we check the beans whether they are valid
        $bean1 = $this->provider->getBeanChecker()->checkBeanForAssoc($bean1);
        $bean2 = $this->provider->getBeanChecker()->checkBeanForAssoc($bean2);


        $this->provider->getLockManager()->openBean( $bean1, true );
        $this->provider->getLockManager()->openBean( $bean2, true );


        $idx1 = intval($bean1->id);
        $idx2 = intval($bean2->id);

        //sort the beans
        $tp1 = $bean1->type;
        $tp2 = $bean2->type;

        if ($tp1==$tp2) {
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
        $alltables = $this->provider->getTableRegister()->getTables();

        if (in_array($assoctable, $alltables)) {
            $t1 = $tables[0];
            $t2 = $tables[1];
            if ($t1==$t2) {
                $t2.="2";
                $unassocSQL = $this->provider->getWriter()->getQuery("unassoc",array(
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

            $unassocSQL = $this->provider->getWriter()->getQuery("unassoc",array(
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
            $alltables = $this->provider->getTableRegister()->getTables();
            if (in_array($assoctable2, $alltables)) {

            //$id1 = intval($bean1->id);
            //$id2 = intval($bean2->id);
                $unassocSQL = $this->provider->getWriter()->getQuery("untree", array(
                    "assoctable2"=>$assoctable2,
                    "idx1"=>$idx1,
                    "idx2"=>$idx2
                ));

                $db->exec($unassocSQL);
            }
        }
    }




    public function get( RedBean_OODBBean $bean, $targettype ) {
    //get a database
        $db = $this->provider->getDatabase();
        //first we check the beans whether they are valid
        $bean = $this->provider->getBeanChecker()->checkBeanForAssoc($bean);

        $id = intval($bean->id);


        //obtain the table names
        $t1 = $db->escape( $this->provider->getToolBox()->getFilter()->table($bean->type) );
        $t2 = $db->escape( $targettype );

        //infer the association table
        $tables = array();
        array_push( $tables, $t1 );
        array_push( $tables, $t2 );
        //sort the table names to make sure we only get one assoc table
        sort($tables);
        $assoctable = $db->escape( implode("_",$tables) );

        //check whether this assoctable exists
        $alltables = $this->provider->getTableRegister()->getTables();

        if (!in_array($assoctable, $alltables)) {
            return array(); //nope, so no associations...!
        }
        else {
            if ($t1==$t2) {
                $t2.="2";
            }

            $getassocSQL = $this->provider->getWriter()->getQuery("get_assoc",array(
                "t1"=>$t1,
                "t2"=>$t2,
                "assoctable"=>$assoctable,
                "id"=>$id
            ));


            $rows = $db->getCol( $getassocSQL );
            $beans = array();
            if ($rows && is_array($rows) && count($rows)>0) {
                foreach($rows as $i) {
                    $beans[$i] = $this->provider->getBeanStore()->get( $targettype, $i, false);
                }
            }
            return $beans;
        }


    }

    public function deleteAllAssoc( $bean ) {

        $db = $this->provider->getDatabase();
        $bean = $this->provider->getBeanChecker()->checkBeanForAssoc($bean);

        $this->provider->getLockManager()->openBean( $bean, true );


        $id = intval( $bean->id );

        //get all tables
        $alltables = $this->provider->getTableRegister()->getTables();

        //are there any possible associations?
        $t = $db->escape($bean->type);
        $checktables = array();
        foreach( $alltables as $table ) {
            if (strpos($table,$t."_")!==false || strpos($table,"_".$t)!==false) {
                $checktables[] = $table;
            }
        }

        //remove every possible association
        foreach($checktables as $table) {
            if (strpos($table,"pc_")===0) {

                $db->exec( $this->provider->getWriter()->getQuery("deltree",array(
                    "id"=>$id,
                    "table"=>$table
                    )) );
            }
            else {

                $db->exec( $this->provider->getWriter()->getQuery("unassoc_all_t1",array("table"=>$table,"t"=>$t,"id"=>$id)) );
                $db->exec( $this->provider->getWriter()->getQuery("unassoc_all_t2",array("table"=>$table,"t"=>$t,"id"=>$id)) );
            }


        }
        return true;
    }


    public function deleteAllAssocType( $targettype, $bean ) {
        $db = $this->provider->getDatabase();
        $bean = $this->provider->getBeanChecker()->checkBeanForAssoc($bean);
        $this->provider->getLockManager()->openBean( $bean, true );

        $id = intval( $bean->id );

        //obtain the table names
        $t1 = $db->escape( $this->provider->getToolBox()->getFilter()->table($bean->type) );
        $t2 = $db->escape( $targettype );

        //infer the association table
        $tables = array();
        array_push( $tables, $t1 );
        array_push( $tables, $t2 );
        //sort the table names to make sure we only get one assoc table
        sort($tables);
        $assoctable = $db->escape( implode("_",$tables) );

        $availabletables = $this->provider->getTableRegister()->getTables();


        if (in_array('pc_'.$assoctable,$availabletables)) {
            $db->exec( $this->provider->getWriter()->getQuery("deltreetype",array(
                "assoctable"=>'pc_'.$assoctable,
                "id"=>$id
                )) );
        }
        if (in_array($assoctable,$availabletables)) {
            $db->exec( $this->provider->getWriter()->getQuery("unassoctype1",array(
                "assoctable"=>$assoctable,
                "t1"=>$t1,
                "id"=>$id
                )) );
            $db->exec( $this->provider->getWriter()->getQuery("unassoctype2",array(
                "assoctable"=>$assoctable,
                "t1"=>$t1,
                "id"=>$id
                )) );

        }

        return true;
    }

    public function numOfRelated( $type, RedBean_OODBBean $bean ) {

    			$db = $this->provider->getDatabase();

			$t2 = $this->provider->getToolBox()->getFilter()->table( $db->escape( $type ) );

			//is this bean valid?
			$this->provider->getBeanChecker()->check( $bean );
			$t1 = $this->provider->getToolBox()->getFilter()->table( $bean->type  );
			$tref = $this->provider->getToolBox()->getFilter()->table( $db->escape( $bean->type ) );
			$id = intval( $bean->id );

			//infer the association table
			$tables = array();
			array_push( $tables, $t1 );
			array_push( $tables, $t2 );

			//sort the table names to make sure we only get one assoc table
			sort($tables);
			$assoctable = $db->escape( implode("_",$tables) );

			//get all tables
			$tables = $this->provider->getTableRegister()->getTables();

			if ($tables && is_array($tables) && count($tables) > 0) {
				if (in_array( $t1, $tables ) && in_array($t2, $tables)){
					$sqlCountRelations = $this->provider->getWriter()->getQuery(
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

}
class RedBean_Mod_BeanChecker extends RedBean_Mod {

    
    public function check( RedBean_OODBBean $bean ) {
        if (!$this->provider->getDatabase()) {
            throw new RedBean_Exception_Security("No database object. Have you used kickstart to initialize RedBean?");
        }
        foreach($bean as $prop=>$value) {
            
            if (preg_match('/[^abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_]/',$prop)) {
                throw new RedBean_Exception_Security("Invalid Characters in property $prop ");
            }

            $prop = preg_replace('/[^abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_]/',"",$prop);
            if (strlen(trim($prop))===0) {
                throw new RedBean_Exception_Security("Invalid Characters in property");
            }
            else {
                if (is_array($value)) {
                    throw new RedBean_Exception_Security("Cannot store an array, use composition instead or serialize first.");
                }
                if (is_object($value)) {
                    throw new RedBean_Exception_Security("Cannot store an object, use composition instead or serialize first.");
                }
                $bean->$prop = $value;
            }
        }

        //Is the bean valid? does the bean have an id?
        if (!isset($bean->id)) {
            throw new RedBean_Exception_Security("Invalid bean, no id");
        }

        //is the id numeric?
        if (!is_numeric($bean->id) || $bean->id < 0 || (round($bean->id)!=$bean->id)) {
            throw new RedBean_Exception_Security("Invalid bean, id not numeric");
        }

        //does the bean have a type?
        if (!isset($bean->type)) {
            throw new RedBean_Exception_Security("Invalid bean, no type");
        }

        //is the beantype correct and valid?
        if (!is_string($bean->type) || is_numeric($bean->type) || strlen($bean->type)<3) {
            throw new RedBean_Exception_Security("Invalid bean, wrong type");
        }

        //is the beantype legal?
        if ($bean->type==="locking" || $bean->type==="dtyp" || $bean->type==="redbeantables") {
            throw new RedBean_Exception_Security("Beantype is reserved table");
        }

        //is the beantype allowed?
        if (strpos($bean->type,"_")!==false && ctype_alnum($bean->type)) {
            throw new RedBean_Exception_Security("Beantype contains illegal characters");
        }

    }

    public function checkBeanForAssoc( $bean ) {

    //check the bean
        $this->check($bean);

        //make sure it has already been saved to the database, else we have no id.
        if (intval($bean->id) < 1) {
        //if it's not saved, save it
            $bean->id = $this->provider->getBeanStore()->set( $bean );
        }

        return $bean;

    }


}
/**
 * @class BeanStore
 * @desc The BeanStore is responsible for storing, retrieving, updating and deleting beans.
 * It performs the BASIC CRUD operations on bean objects.
 * 
 */
class RedBean_Mod_BeanStore extends RedBean_Mod {

    /**
     * Inserts a bean into the database
     * @param $bean
     * @return $id
     */
    public function set( RedBean_OODBBean $bean ) {

        $this->provider->getBeanChecker()->check($bean);


        $db = $this->provider->getDatabase(); //I am lazy, I dont want to waste characters...


        $table = $db->escape($bean->type); //what table does it want

        //may we adjust the database?
        if (!$this->provider->isFrozen()) {

        //does this table exist?
            $tables = $this->provider->getTableRegister()->getTables();

            if (!in_array($table, $tables)) {

                $createtableSQL = $this->provider->getWriter()->getQuery("create_table", array(
                    "engine"=>$this->provider->getEngine(),
                    "table"=>$table
                ));

                //get a table for our friend!
                $db->exec( $createtableSQL );
                //jupz, now he has its own table!...
                $this->provider->getTableRegister()->register( $table );
            }

            //does the table fit?
            $columnsRaw = $this->provider->getWriter()->getTableColumns($table, $db) ;

            $columns = array();
            foreach($columnsRaw as $r) {
                $columns[$r["Field"]]=$r["Type"];
            }

            $insertvalues = array();
            $insertcolumns = array();
            $updatevalues = array();

            //@todo: move this logic to a table manager
            foreach( $bean as $p=>$v) {
                if ($p!="type" && $p!="id") {
                    $p = $db->escape($p);
                    $v = $db->escape($v);
                    //What kind of property are we dealing with?
                    $typeno = $this->provider->getScanner()->type($v);
                    //Is this property represented in the table?
                    if (isset($columns[$p])) {
                    //yes it is, does it still fit?
                        $sqlt = $this->provider->getScanner()->code($columns[$p]);
                        //echo "TYPE = $sqlt .... $typeno ";
                        if ($typeno > $sqlt) {
                        //no, we have to widen the database column type
                            $changecolumnSQL = $this->provider->getWriter()->getQuery( "widen_column", array(
                                "table" => $table,
                                "column" => $p,
                                "newtype" => $this->provider->getWriter()->typeno_sqltype[$typeno]
                                ) );

                            $db->exec( $changecolumnSQL );
                        }
                    }
                    else {
                    //no it is not
                        $addcolumnSQL = $this->provider->getWriter()->getQuery("add_column",array(
                            "table"=>$table,
                            "column"=>$p,
                            "type"=> $this->provider->getWriter()->typeno_sqltype[$typeno]
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
            $this->provider->getLockManager()->openBean($bean, true);
            //yes it exists, update it
            if (count($updatevalues)>0) {
                $updateSQL = $this->provider->getWriter()->getQuery("update", array(
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

                $insertSQL = $this->provider->getWriter()->getQuery("insert",array(
                    "table"=>$table,
                    "insertcolumns"=>$insertcolumns,
                    "insertvalues"=>$insertvalues
                ));

            }
            else {
                $insertSQL = $this->provider->getWriter()->getQuery("create", array("table"=>$table));
            }
            //execute the previously build query
            $db->exec( $insertSQL );
            $bean->id = $db->getInsertID();
            $this->provider->getLockManager()->openBean($bean);
        }

        return $bean->id;

    }

   
    public function get($type, $id, $data=false) {
        $bean = $this->provider->dispense( $type );
        $db = $this->provider->getDatabase();
        $table = $db->escape( $type );
        $id = abs( intval( $id ) );
        $bean->id = $id;

        //try to open the bean
        $this->provider->getLockManager()->openBean($bean);

        //load the bean using sql
        if (!$data) {
                $getSQL = $this->provider->getWriter()->getQuery("get_bean",array(
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

    //@todo tested?
    public function trash( RedBean_OODBBean $bean ) {
        $this->provider->getBeanChecker()->check( $bean );
	if (intval($bean->id)===0) return;
	$this->provider->deleteAllAssoc( $bean );
	$this->provider->getLockManager()->openBean($bean);
	$table = $this->provider->getDatabase()->escape($bean->type);
	$id = intval($bean->id);
	$this->provider->getDatabase()->exec( $this->provider->getWriter()->getQuery("trash",array(
		"table"=>$table,
		"id"=>$id
	)) );
    }

    public function exists($type,$id) {

    	$db = $this->provider->getDatabase();
			$id = intval( $id );
			$type = $db->escape( $type );

			//$alltables = $db->getCol("show tables");
			$alltables = $this->provider->getTableRegister()->getTables();

			if (!in_array($type, $alltables)) {
				return false;
			}
			else {
				$no = $db->getCell( $this->provider->getWriter()->getQuery("bean_exists",array(
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

           public function numberof($type) {
                	$db = $this->provider->getDatabase();
			$type = $this->provider->getToolBox()->getFilter()->table( $db->escape( $type ) );

			$alltables = $this->provider->getTableRegister()->getTables();

			if (!in_array($type, $alltables)) {
				return 0;
			}
			else {
				$no = $db->getCell( $this->provider->getWriter()->getQuery("count",array(
					"type"=>$type
				)));
				return intval( $no );
			}
           }

           public function fastloader($type, $ids) {

                $db = $this->provider->getDatabase();
                $sql = $this->provider->getWriter()->getQuery("fastload", array(
                    "type"=>$type,
                    "ids"=>$ids
                ));
                return $db->get( $sql );

           }


}
class RedBean_Mod_ClassGenerator extends RedBean_Mod {

   /**
    *
    * @param <type> $classes
    * @param <type> $prefix
    * @param <type> $suffix
    * @return <type>
    */
    public function generate( $classes, $prefix = false, $suffix = false ) {

        if (!$prefix) {
                $prefix = RedBean_Setup_Namespace_PRFX;
        }

        if (!$suffix) {
                $suffix = RedBean_Setup_Namespace_SFFX;
        }

        $classes = explode(",",$classes);
        foreach($classes as $c) { // echo $c;
                $ns = '';
                $names = explode('\\', $c);
                $className = trim(end($names));
                if(count($names) > 1)
                {
                        $namespacestring = implode('\\', array_slice($names, 0, -1));
                        $ns = 'namespace ' . $namespacestring . " { ";
                }
                if ($c!=="" && $c!=="null" && !class_exists($c) &&
                                        preg_match("/^\s*[A-Za-z_][A-Za-z0-9_]*\s*$/",$className)){
                                        $tablename = $className;
                                        $fullname = $prefix.$className.$suffix;
                                        $toeval = $ns . " class ".$fullname." extends ". (($ns=='') ? '' : '\\' ) . "RedBean_Decorator {
                                        private static \$__static_property_type = \"".$this->provider->getToolBox()->getFilter()->table($tablename)."\";

                                        public function __construct(\$id=0, \$lock=false) {

                                                parent::__construct( RedBean_OODB::getInstance(), '".$this->provider->getToolBox()->getFilter()->table($tablename)."',\$id,\$lock);
                                        }

                                        public static function where( \$sql, \$slots=array() ) {
                                                return new RedBean_Can( RedBean_OODB::getInstance()->getToolBox(), self::\$__static_property_type, RedBean_OODB::getInstance()->getBySQL( \$sql, \$slots, self::\$__static_property_type) );
                                        }

                                        public static function listAll(\$start=false,\$end=false,\$orderby=' id ASC ',\$sql=false) {
                                                return RedBean_OODB::getInstance()->listAll(self::\$__static_property_type,\$start,\$end,\$orderby,\$sql);
                                        }

                                        public static function getReadOnly(\$id) {
                                                RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLocking( false );
                                                \$me = new self( \$id );
                                                RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLocking( true );
                                                return \$me;
                                        }

                                        public static function exists( \$id ) {
                                            return  RedBean_OODB::getInstance()->getToolBox()->getBeanStore()->exists(self::\$__static_property_type, \$id);
                                        }

                                       

                                }";

                                if(count($names) > 1) {
                                        $toeval .= "}";
                                }

                                $teststring = (($ns!="") ? '\\'.$namespacestring.'\\'.$fullname : $fullname);
                                eval($toeval);
                                if (!class_exists( $teststring )) {
                                        throw new Exception("Failed to generate class");
                                }

                }
                else {
                        return false;
                }
        }
        return true;

    }


}
class RedBean_Mod_Dispenser extends RedBean_Mod {

        public function dispense($type ) {
                $oBean = new RedBean_OODBBean();
		$oBean->type = $type;
		$oBean->id = 0;
		return $oBean;
        }


}
interface RedBean_Mod_Filter {

    public function property( $name, $forReading = false );
    public function table( $name );
}
class RedBean_Mod_Finder extends RedBean_Mod {

        /**
     * Finds a bean using search parameters
     * @param $bean
     * @param $searchoperators
     * @param $start
     * @param $end
     * @param $orderby
     * @return unknown_type
     */
    public function find(RedBean_OODBBean $bean, $searchoperators = array(), $start=0, $end=100, $orderby="id ASC", $extraSQL=false) {
      $this->provider->getBeanChecker()->check( $bean );
      $db = $this->provider->getDatabase();
      $tbl = $db->escape( $bean->type );

      $findSQL = $this->provider->getWriter()->getQuery("find",array(
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
            $beans[ $id ] = $this->provider->getBeanStore()->get( $bean->type, $id , false);
        }
      }

      return $beans;

    }





}
class RedBean_Mod_GarbageCollector extends RedBean_Mod {

 
    
    public function removeUnused( RedBean_OODB $oodb, RedBean_DBAdapter $db, RedBean_QueryWriter $writer ) {
            if ($this->provider->isFrozen()) return;
            //get all tables
            $tables = $this->provider->getTableRegister()->getTables();
            foreach($tables as $table) {
                    if (strpos($table,"_")!==false) {
                            //associative table
                            $tables = explode("_", $table);
                            //both classes need to exist in order to keep this table
                            $classname1 = RedBean_Setup_Namespace_PRFX . $tables[0] . RedBean_Setup_Namespace_SFFX;
                            $classname2 = RedBean_Setup_Namespace_PRFX . $tables[1] . RedBean_Setup_Namespace_SFFX;
                            if(!class_exists( $classname1 , true) || !class_exists( $classname2 , true)) {
                                    $db->exec( $writer->getQuery("drop_tables",array("tables"=>array($table))) );
                                    $db->exec($writer->getQuery("unregister_table",array("table"=>$table)));
                            }
                    }
                    else {
                            //does the class exist?
                            $classname = RedBean_Setup_Namespace_PRFX . $table . RedBean_Setup_Namespace_SFFX;
                            if(!class_exists( $classname , true)) {
                                    $db->exec( $writer->getQuery("drop_tables",array("tables"=>array($table))) );
                                    $db->exec($writer->getQuery("unregister_table",array("table"=>$table)));
                            }
                    }

            }
    }


    public function dropColumn($table,$property) {
        	//oops, we are frozen, so no change..
			if ($this->provider->isFrozen()) {
				return false;
			}

			//get a database
			$db = $this->provider->getDatabase();

			$db->exec( $this->provider->getWriter()->getQuery("drop_column", array(
				"table"=>$table,
				"property"=>$property
			)) );

		}

    public function clean() {

			if ($this->provider->isFrozen()) {
				return false;
			}

			$db = $this->provider->getDatabase();

			$tables = $db->getCol( $this->provider->getWriter()->getQuery("show_rtables") );

			foreach($tables as $key=>$table) {
				$tables[$key] = $this->provider->getWriter()->getEscape().$table.$this->provider->getWriter()->getEscape();
			}

			$sqlcleandatabase = $this->provider->getWriter()->getQuery("drop_tables",array(
				"tables"=>$tables
			));

			$db->exec( $sqlcleandatabase );

			$db->exec( $this->provider->getWriter()->getQuery("truncate_rtables") );
			$this->provider->resetAll();
			return true;

		
    }
    
}
class RedBean_Mod_Lister extends RedBean_Mod {


        public function get($type, $start=false, $end=false, $orderby="id ASC", $extraSQL = false) {

    	$db = $this->provider->getDatabase();

			$listSQL = $this->provider->getWriter()->getQuery("list",array(
				"type"=>$type,
				"start"=>$start,
				"end"=>$end,
				"orderby"=>$orderby,
				"extraSQL"=>$extraSQL
			));


			return $db->get( $listSQL );

    }



    public function distinct($type,$field){
        //TODO: Consider if GROUP BY (equivalent meaning) is more portable
			//across DB types?
			$db = $this->provider->getDatabase();
			$type = $this->provider->getToolBox()->getFilter()->table( $db->escape( $type ) );
			$field = $db->escape( $field );

			$alltables = $this->provider->getTableRegister()->getTables();

			if (!in_array($type, $alltables)) {
				return array();
			}
			else {
				$ids = $db->getCol( $this->provider->getWriter()->getQuery("distinct",array(
					"type"=>$type,
					"field"=>$field
				)));
				$beans = array();
				if (is_array($ids) && count($ids)>0) {
					foreach( $ids as $id ) {
						$beans[ $id ] = $this->provider->getBeanStore()->get( $type, $id , false);
					}
				}
				return $beans;
			}
		
    }


    public function stat( $type, $field, $stat) {
        $db = $this->provider->getDatabase();
			$type = $this->provider->getToolBox()->getFilter()->table( $db->escape( $type ) );
			$field = $this->provider->getToolBox()->getFilter()->property( $db->escape( $field ) );
			$stat = $db->escape( $stat );

			$alltables = $this->provider->getTableRegister()->getTables();

			if (!in_array($type, $alltables)) {
				return 0;
			}
			else {
				$no = $db->getCell($this->provider->getWriter()->getQuery("stat",array(
					"stat"=>$stat,
					"field"=>$field,
					"type"=>$type
				)));
				return floatval( $no );
			}
		}
  
}
class RedBean_Mod_LockManager extends RedBean_Mod {

    private $locking = true;
    private $locktime = 10;


    public function getLockingTime() { return $this->locktime; }
     public function setLockingTime( $timeInSecs ) {

        if (is_int($timeInSecs) && $timeInSecs >= 0) {
            $this->locktime = $timeInSecs;
        }
        else {
            throw new RedBean_Exception_InvalidArgument( "time must be integer >= 0" );
        }
    }
    public function openBean($bean, $mustlock = false) {

                        $this->provider->getBeanChecker()->check( $bean);
			//If locking is turned off, or the bean has no persistance yet (not shared) life is always a success!
			if (!$this->provider->getToolBox()->getLockManager()->getLocking() || $bean->id === 0) return true;
                        $db = $this->provider->getDatabase();

			//remove locks that have been expired...
			$removeExpiredSQL = $this->provider->getWriter()->getQuery("remove_expir_lock", array(
				"locktime"=>$this->provider->getToolBox()->getLockManager()->getLockingTime()
			));

			$db->exec($removeExpiredSQL);

			$tbl = $db->escape( $bean->type );
			$id = intval( $bean->id );

			//Is the bean already opened for us?
			$checkopenSQL = $this->provider->getWriter()->getQuery("get_lock",array(
				"id"=>$id,
				"table"=>$tbl,
				"key"=>$this->provider->pkey
			));

			$row = $db->getRow($checkopenSQL);
			if ($row && is_array($row) && count($row)>0) {
				$updateexpstamp = $this->provider->getWriter()->getQuery("update_expir_lock",array(
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
			$openSQL = $this->provider->getWriter()->getQuery("aq_lock", array(
				"table"=>$tbl,
				"id"=>$id,
				"key"=>$this->provider->pkey,
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


    public function setLocking( $tf ) {
        $this->locking = $tf;
    }



    public function getLocking() {
        return $this->locking;
    }

    public function unlockAll() {
          $this->provider->getDatabase()->exec($this->provider->getWriter()->getQuery("release",array("key"=>$this->provider->pkey)));
    }

}
class RedBean_Mod_Optimizer extends RedBean_Mod {

    /**
     * Narrows columns to appropriate size if needed
     * @return unknown_type
     */
    public function run( $gc = false ,$stdTable=false, $stdCol=false) {

    //oops, we are frozen, so no change..
        if ($this->provider->isFrozen()) {
            return false;
        }

        //get a database
        $db = $this->provider->getDatabase();

        //get all tables
        $tables = $this->provider->getTableRegister()->getTables();

        //pick a random table
        if ($tables && is_array($tables) && count($tables) > 0) {
            if ($gc) $this->provider->removeUnused( $tables );
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

        $cols = $this->provider->getWriter()->getTableColumns( $table, $db );

        //$cols = $db->get( $this->provider->getWriter()->getQuery("describe",array(
        //	"table"=>$table
        //)) );
        //pick a random column
        if (count($cols)<1) return;
        $colr = $cols[array_rand( $cols )];
        $col = $db->escape( $colr["Field"] ); //fetch the name and escape
        if ($stdCol) {
            $exists = false;
            $col = $stdCol;
            foreach($cols as $cl) {
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
        if ($gc && !intval($db->getCell( $this->provider->getWriter()->getQuery("get_null",array(
            "table"=>$table,
            "col"=>$col
            )
        )))) {
            $db->exec( $this->provider->getWriter()->getQuery("drop_column",array("table"=>$table,"property"=>$col)));
            return;
        }

        //okay so this column is still in use, but maybe its to wide
        //get the field type
        //print_r($colr);
        $currenttype =  $this->provider->getWriter()->sqltype_typeno[$colr["Type"]];
        if ($currenttype > 0) {
            $trytype = rand(0,$currenttype - 1); //try a little smaller
            //add a test column
            $db->exec($this->provider->getWriter()->getQuery("test_column",array(
                "type"=>$this->provider->getWriter()->typeno_sqltype[$trytype],
                "table"=>$table
                )
            ));
            //fill the tinier column with the same values of the original column
            $db->exec($this->provider->getWriter()->getQuery("update_test",array(
                "table"=>$table,
                "col"=>$col
            )));
            //measure the difference
            $delta = $db->getCell($this->provider->getWriter()->getQuery("measure",array(
                "table"=>$table,
                "col"=>$col
            )));
            if (intval($delta)===0) {
            //no difference? then change the column to save some space
                $sql = $this->provider->getWriter()->getQuery("remove_test",array(
                    "table"=>$table,
                    "col"=>$col,
                    "type"=>$this->provider->getWriter()->typeno_sqltype[$trytype]
                ));
                $db->exec($sql);
            }
            //get rid of the test column..
            $db->exec( $this->provider->getWriter()->getQuery("drop_test",array(
                "table"=>$table
                )) );
        }

        //@todo -> querywriter!
        //Can we put an index on this column?
        //Is this column worth the trouble?
        if (
        strpos($colr["Type"],"TEXT")!==false ||
            strpos($colr["Type"],"LONGTEXT")!==false
        ) {
            return;
        }


        $variance = $db->getCell($this->provider->getWriter()->getQuery("variance",array(
            "col"=>$col,
            "table"=>$table
        )));
        $records = $db->getCell($this->provider->getWriter()->getQuery("count",array("type"=>$table)));
        if ($records) {
            $relvar = intval($variance) / intval($records); //how useful would this index be?
            //if this column describes the table well enough it might be used to
            //improve overall performance.
            $indexname = "reddex_".$col;
            if ($records > 1 && $relvar > 0.85) {
                $sqladdindex=$this->provider->getWriter()->getQuery("index1",array(
                    "table"=>$table,
                    "indexname"=>$indexname,
                    "col"=>$col
                ));
                $db->exec( $sqladdindex );
            }
            else {
                $sqldropindex = $this->provider->getWriter()->getQuery("index2",array("table"=>$table,"indexname"=>$indexname));
                $db->exec( $sqldropindex );
            }
        }

        return true;
    }


}
class RedBean_Mod_Scanner extends RedBean_Mod {



    public function type( $value ) {
        $v = $value;
        $db = $this->provider->getDatabase();
        $rawv = $v;

			$checktypeSQL = $this->provider->getWriter()->getQuery("infertype", array(
				"value"=> $db->escape(strval($v))
			));


			$db->exec( $checktypeSQL );
			$id = $db->getInsertID();

			$readtypeSQL = $this->provider->getWriter()->getQuery("readtype",array(
				"id"=>$id
			));

			$row=$db->getRow($readtypeSQL);


			$db->exec( $this->provider->getWriter()->getQuery("reset_dtyp") );

			$tp = 0;
			foreach($row as $t=>$tv) {
				if (strval($tv) === strval($rawv)) {
					return $tp;
				}
				$tp++;
			}
			return $tp;
		}

    public function code( $sqlType ) {
        		if (in_array($sqlType,$this->provider->getWriter()->sqltype_typeno)) {
				$typeno = $this->provider->getWriter()->sqltype_typeno[$sqlType];
			}
			else {
				$typeno = -1;
			}

			return $typeno;

    }

}
class RedBean_Mod_Search extends RedBean_Mod {

    /**
     * Fills slots in SQL query
     * @param $sql
     * @param $slots
     * @return unknown_type
     */
    public function processQuerySlots($sql, $slots) {

        $db = $this->provider->getDatabase();

        //Just a funny code to identify slots based on randomness
        $code = sha1(rand(1,1000)*time());

        //This ensures no one can hack our queries via SQL template injection
        foreach( $slots as $key=>$value ) {
            $sql = str_replace( "{".$key."}", "{".$code.$key."}" ,$sql );
        }

        //replace the slots inside the SQL template
        foreach( $slots as $key=>$value ) {
            $sql = str_replace( "{".$code.$key."}", $this->provider->getWriter()->getQuote().$db->escape( $value ).$this->provider->getWriter()->getQuote(),$sql );
        }

        return $sql;
    }



    public function sql($rawsql, $slots, $table, $max=0) {

        $db = $this->provider->getDatabase();
        
        $sql = $rawsql;

        if (is_array($slots)) {
            $sql = $this->processQuerySlots( $sql, $slots );
        }

        $sql = str_replace('@ifexists:','', $sql);
        $rs = $db->getCol( $this->provider->getWriter()->getQuery("where",array(
            "table"=>$table
            )) . $sql );

        $err = $db->getErrorMsg();
        if (!$this->provider->isFrozen() && strpos($err,"Unknown column")!==false && $max<10) {
            $matches = array();
            if (preg_match("/Unknown\scolumn\s'(.*?)'/",$err,$matches)) {
                if (count($matches)==2 && strpos($rawsql,'@ifexists')!==false) {
                    $rawsql = str_replace('@ifexists:`'.$matches[1].'`','NULL', $rawsql);
                    $rawsql = str_replace('@ifexists:'.$matches[1].'','NULL', $rawsql);
                    return $this->sql( $rawsql, $slots, $table, ++$max);
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
}
class RedBean_Mod_TableRegister extends RedBean_Mod {

    public function getTables( $all=false ) {
        $db = $this->provider->getDatabase();

        if ($all && $this->provider->isFrozen()) {
            $alltables = $db->getCol($this->provider->getWriter()->getQuery("show_tables"));
            return $alltables;
        }
        else {
            $alltables = $db->getCol($this->provider->getWriter()->getQuery("show_rtables"));
            return $alltables;
        }

    }


    public function register( $tablename ) {
        
        $db = $this->provider->getDatabase();

        $tablename = $db->escape( $tablename );

        $db->exec($this->provider->getWriter()->getQuery("register_table",array("table"=>$tablename)));

    }


    public function unregister( $tablename ) {
        $db = $this->provider->getDatabase();
        $tablename = $db->escape( $tablename );

        $db->exec($this->provider->getWriter()->getQuery("unregister_table",array("table"=>$tablename)));


    }



}
class RedBean_Mod_Tree extends RedBean_Mod {


    public function add( RedBean_OODBBean $parent, RedBean_OODBBean $child ) {
                	//get a database
			$db = $this->provider->getDatabase();

			//first we check the beans whether they are valid
			$parent = $this->provider->getBeanChecker()->checkBeanForAssoc($parent);
			$child = $this->provider->getBeanChecker()->checkBeanForAssoc($child);

			$this->provider->getLockManager()->openBean( $parent, true );
			$this->provider->getLockManager()->openBean( $child, true );


			//are parent and child of the same type?
			if ($parent->type !== $child->type) {
				throw new RedBean_Exception_InvalidParentChildCombination();
			}

			$pid = intval($parent->id);
			$cid = intval($child->id);

			//infer the association table
			$assoctable = "pc_".$db->escape($parent->type."_".$parent->type);

			//check whether this assoctable already exists
			if (!$this->provider->isFrozen()) {
				$alltables = $this->provider->getTableRegister()->getTables();
				if (!in_array($assoctable, $alltables)) {
					//no assoc table does not exist, create it..
					$assoccreateSQL = $this->provider->getWriter()->getQuery("create_tree",array(
						"engine"=>$this->provider->getEngine(),
						"assoctable"=>$assoctable
					));
					$db->exec( $assoccreateSQL );
					//add a unique constraint
					$db->exec( $this->provider->getWriter()->getQuery("unique", array(
						"assoctable"=>$assoctable
					)) );
					$this->provider->getTableRegister()->register( $assoctable );
				}
			}

			//now insert the association record
			$assocSQL = $this->provider->getWriter()->getQuery("add_child",array(
				"assoctable"=>$assoctable,
				"pid"=>$pid,
				"cid"=>$cid
			));
			$db->exec( $assocSQL );

		}

        public function getChildren( RedBean_OODBBean $parent ) {

			//get a database
			$db = $this->provider->getDatabase();

			//first we check the beans whether they are valid
			$parent = $this->provider->getBeanChecker()->checkBeanForAssoc($parent);

			$pid = intval($parent->id);

			//infer the association table
			$assoctable = "pc_".$db->escape( $parent->type . "_" . $parent->type );

			//check whether this assoctable exists
			$alltables = $this->provider->getTableRegister()->getTables();
			if (!in_array($assoctable, $alltables)) {
				return array(); //nope, so no children...!
			}
			else {
				$targettype = $parent->type;
				$getassocSQL = $this->provider->getWriter()->getQuery("get_children", array(
					"assoctable"=>$assoctable,
					"pid"=>$pid
				));
				$rows = $db->getCol( $getassocSQL );
				$beans = array();
				if ($rows && is_array($rows) && count($rows)>0) {
					foreach($rows as $i) {
						$beans[$i] = $this->provider->getBeanStore()->get( $targettype, $i, false);
					}
				}
				return $beans;
			}

		}



                public function getParent( RedBean_OODBBean $child ) {
            		//get a database
			$db = $this->provider->getDatabase();

			//first we check the beans whether they are valid
			$child = $this->provider->getBeanChecker()->checkBeanForAssoc($child);

			$cid = intval($child->id);

			//infer the association table
			$assoctable = "pc_".$db->escape( $child->type . "_" . $child->type );
			//check whether this assoctable exists
			$alltables = $this->provider->getTableRegister()->getTables();
			if (!in_array($assoctable, $alltables)) {
				return array(); //nope, so no children...!
			}
			else {
				$targettype = $child->type;

				$getassocSQL = $this->provider->getWriter()->getQuery("get_parent", array(
					"assoctable"=>$assoctable,
					"cid"=>$cid
				));

				$rows = $db->getCol( $getassocSQL );
				$beans = array();
				if ($rows && is_array($rows) && count($rows)>0) {
					foreach($rows as $i) {
						$beans[$i] = $this->provider->getBeanStore()->get( $targettype, $i, false);
					}
				}

				return $beans;
			}

		}


                public function removeChild(RedBean_OODBBean $parent, RedBean_OODBBean $child) {
                    	$db = $this->provider->getDatabase();

			//first we check the beans whether they are valid
			$parent = $this->provider->getBeanChecker()->checkBeanForAssoc($parent);
			$child = $this->provider->getBeanChecker()->checkBeanForAssoc($child);

			$this->provider->getLockManager()->openBean( $parent, true );
			$this->provider->getLockManager()->openBean( $child, true );


			//are parent and child of the same type?
			if ($parent->type !== $child->type) {
				throw new RedBean_Exception_InvalidParentChildCombination();
			}

			//infer the association table
			$assoctable = "pc_".$db->escape( $parent->type . "_" . $parent->type );

			//check whether this assoctable already exists
			$alltables = $this->provider->getTableRegister()->getTables();
			if (!in_array($assoctable, $alltables)) {
				return true; //no association? then nothing to do!
			}
			else {
				$pid = intval($parent->id);
				$cid = intval($child->id);
				$unassocSQL = $this->provider->getWriter()->getQuery("remove_child", array(
					"assoctable"=>$assoctable,
					"pid"=>$pid,
					"cid"=>$cid
				));
				$db->exec($unassocSQL);
			}
		}



}
/**
 * RedBean MySQLWriter
 * @package 		RedBean/QueryWriter/MySQL.php
 * @description		Writes Queries for MySQL Databases
 * @author			Gabor de Mooij
 * @license			BSD
 */
class QueryWriter_MySQL implements RedBean_QueryWriter, RedBean_Tool {
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
			 ) ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			";
			}
			else {
				$createtableSQL = "
			 CREATE TABLE `$table` (
			`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
			 PRIMARY KEY ( `id` )
			 ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
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
			return "INSERT INTO `$table` (id) VALUES(null) ";
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
			return "DELETE FROM locking WHERE expire <= ".(time()-$locktime);
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
			$db = RedBean_OODB::getInstance()->getToolBox()->getDatabase();
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
			$db = RedBean_OODB::getInstance()->getToolBox()->getDatabase();
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
					$engine = RedBean_OODB::getInstance()->getEngine();
					return "
				CREATE TABLE IF NOT EXISTS `dtyp` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `tinyintus` tinyint(3) unsigned NOT NULL,
				  `intus` int(11) unsigned NOT NULL,
				  `ints` bigint(20) NOT NULL,
				  `varchar255` varchar(255) NOT NULL,
				  `text` text NOT NULL,
				  PRIMARY KEY  (`id`)
				) ENGINE=$engine DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
				";
					break;
				case "clear_dtyp":
					return "drop tables dtyp";
					break;
				case "setup_locking":
					$engine = RedBean_OODB::getInstance()->getEngine();
					return "
				CREATE TABLE IF NOT EXISTS `locking` (
				  `tbl` varchar(255) NOT NULL,
				  `id` bigint(20) NOT NULL,
				  `fingerprint` varchar(255) NOT NULL,
				  `expire` int(11) NOT NULL,
				  UNIQUE KEY `tbl` (`tbl`,`id`)
				) ENGINE=$engine DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
				";
					break;
				case "setup_tables":
					$engine = RedBean_OODB::getInstance()->getEngine();
					return "
				 CREATE TABLE IF NOT EXISTS `redbeantables` (
				 `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
				 `tablename` VARCHAR( 255 ) NOT NULL ,
				 PRIMARY KEY ( `id` ),
				 UNIQUE KEY `tablename` (`tablename`)
				 ) ENGINE = $engine DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
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
class RedBean_ToolBox_ModHub extends RedBean_ToolBox {

    private $facade;

  

    public function getDatabase() {
        return $this->give("database");
    }

    public function getWriter() {
        return $this->give("writer");
    }

    public function getFilter() {
        return $this->give("filter");
    }

    public function setFacade( $facade ) {
        $this->facade = $facade;
    }
    
    public function __call( $who, $args=array() ) {

        $tool = strtolower(substr($who,3));
        if ($this->has($tool)) {
            return $this->give( $tool );
        }
        else {
            return call_user_func_array( array($this->facade,$who), $args );
        }
        
    }

    public function __get($v) {
       return $this->facade->$v;
    }

    public function __set($v,$i) {
        $this->facade->$v = $i;
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
class RedBean_Mod_Filter_NullFilter extends RedBean_Mod implements RedBean_Mod_Filter {

    public function __construct(){}

    public function property( $name, $forReading = false ) {
        return $name;
    }

    public function table( $name ) {
          return $name;
    }

}
class RedBean_Mod_Filter_Strict extends RedBean_Mod implements RedBean_Mod_Filter {

    public function __construct(){}
    
    public function property( $name, $forReading = false ) {
        $name = strtolower($name);
          if (!$forReading) {
            if ($name=="type") {
                    throw new RedBean_Exception_Security("type is a reserved property to identify the table, pleae use another name for this property.");
            }
            if ($name=="id") {
                    throw new RedBean_Exception_Security("id is a reserved property to identify the record, pleae use another name for this property.");
            }
        }
        $name =  trim(preg_replace("/[^abcdefghijklmnopqrstuvwxyz0123456789]/","",$name));
        if (strlen($name)===0) {
            throw new RedBean_Exception_Security("Empty property is not allowed");
        }
        return $name;
    }

    public function table( $name ) {
          $name =  strtolower(trim(preg_replace("/[^abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789]/","",$name)));
          if (strlen($name)===0) {
            throw new RedBean_Exception_Security("Empty property is not allowed");
          }
          return $name;

    }

}