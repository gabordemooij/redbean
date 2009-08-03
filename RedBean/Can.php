<?php

/**
 * RedBean Can
 * @desc   a Can contains beans and acts as n iterator, it also enables you to work with
 * 		   large collections while remaining light-weight
 * @author gabordemooij
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

