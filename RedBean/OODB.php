<?php 
/**
 * @name RedBean OODB
 * @package RedBean
 * @author Gabor de Mooij and the RedBean Team
 * @copyright Gabor de Mooij (c)
 * @license BSD
 *
 * The RedBean OODB Class is the main class of RedBean.
 * It takes RedBean_OODBBean objects and stores them to and loads them from the
 * database as well as providing other CRUD functions. This class acts as a
 * object database.
 * 
 */
class RedBean_OODB extends RedBean_Observable implements ObjectDatabase {

	private $stash = NULL;

    /**
     *
     * @var RedBean_DBAdapter
     */
    private $writer;
    /**
     *
     * @var boolean
     */
    private $isFrozen = false;
    /**
     * Constructor, requires a DBAadapter (dependency inversion)
     * @param RedBean_DBAdapter $adapter
     */
    public function __construct( RedBean_QueryWriter $writer ) {
        $this->writer = $writer;
    }
	/**
	 * Toggles fluid or frozen mode. In fluid mode the database
	 * structure is adjusted to accomodate your objects. In frozen mode
	 * this is not the case.
	 * @param boolean $tf
	 */
    public function freeze( $tf ) {
        $this->isFrozen = (bool) $tf;
    }
    /**
     * Dispenses a OODBBean
     * @param string $type
     * @return RedBean_OODBBean $bean
     */
    public function dispense($type ) {
		
        $bean = new RedBean_OODBBean();
        $bean->setMeta("type", $type );
        $bean->id = 0;
		$this->signal( "dispense", $bean );
        $this->check( $bean );
        return $bean;
    }
    /**
     * Checks whether a bean is valid
     * @param RedBean_OODBBean $bean
     */
    public function check( RedBean_OODBBean $bean ) {
	    //Is all meta information present?
        if (!isset($bean->id) || !($bean->getMeta("type"))) {
            throw new RedBean_Exception_Security("Bean has incomplete Meta Information");
        }
        //Pattern of allowed characters
        $pattern = '/[^abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_]/';
        //Does the type contain invalid characters?
        if (preg_match($pattern,$bean->getMeta("type"))) {
            throw new RedBean_Exception_Security("Bean Type is invalid");
        }
        //Are the properties and values valid?
        foreach($bean as $prop=>$value) {
            if (
                is_array($value) ||
                is_object($value) ||
                strlen($prop)<1 ||
                preg_match($pattern,$prop)
            ) {
                throw new RedBean_Exception_Security("Invalid Bean: property $prop  ");
            }
        }
    }
	
	/**
	 * Stores a Bean in the database. If the bean contains other beans,
	 * these will get stored as well.
	 * @param RedBean_OODBBean $bean
	 * @return integer $newid
	 */
    public function store( RedBean_OODBBean $bean ) {
		$this->signal( "update", $bean );
        $this->check($bean);
        //what table does it want
        $table = $bean->getMeta("type");
	        //does this table exist?
            $tables = $this->writer->getTables();
            //If not, create
            if (!$this->isFrozen && !in_array($table, $tables)) {
                $this->writer->createTable( $table );
            }
            $columns = $this->writer->getColumns($table) ;
            //does the table fit?
            $insertvalues = array();
            $insertcolumns = array();
            $updatevalues = array();
            foreach( $bean as $p=>$v) {
                if ($p!="id") {
					 if (!$this->isFrozen) {
						//What kind of property are we dealing with?
						$typeno = $this->writer->scanType($v);
						//Is this property represented in the table?
						if (isset($columns[$p])) {
							//yes it is, does it still fit?
							$sqlt = $this->writer->code($columns[$p]);
							if ($typeno > $sqlt) {
							//no, we have to widen the database column type
								$this->writer->widenColumn( $table, $p, $typeno );
							}
						}
						else {
						//no it is not
							$this->writer->addColumn($table, $p, $typeno);
						}
					}
                    //Okay, now we are sure that the property value will fit
                    $insertvalues[] = $v;
                    $insertcolumns[] = $p;
                    $updatevalues[] = array( "property"=>$p, "value"=>$v );
                }
            }
            if (!$this->isFrozen && ($uniques = $bean->getMeta("buildcommand.unique"))) {
					foreach($uniques as $unique){
						$this->writer->addUniqueIndex( $table, $unique );
					}
             }
            if ($bean->id) {
                if (count($updatevalues)>0) {
                    $this->writer->updateRecord( $table, $updatevalues, $bean->id );
                }
                return (int) $bean->id;
            }
            else {
                $id = $this->writer->insertRecord( $table, $insertcolumns, array($insertvalues) );
                $bean->id = $id;
                return (int) $id;
            }
    }
	/**
	 * Loads a bean using its primary Key and its Type
	 * @param string $type
	 * @param integer $id
	 * @return RedBean_OODBBean $bean
	 */
    public function load($type, $id) {
		$id = intval( $id );
		if ($id < 0) throw new RedBean_Exception_Security("Id less than zero not allowed");
		$bean = $this->dispense( $type );
		if ($this->stash && $this->stash[$id]) {
			$row = $this->stash[$id];
		}
		else {
			try{ $rows = $this->writer->selectRecord($type,array($id));	}catch(RedBean_Exception_SQL $e){
				if ($e->getSQLState()=="42S02" || $e->getSQLState()=="42S22") {
					$rows = 0;
					if ($this->isFrozen) throw $e; //only throw if frozen;
				}
				else throw $e;
			}
			if (!$rows) return $this->dispense($type);
			$row = array_pop($rows);
		}
		foreach($row as $p=>$v) {
				 //populate the bean with the database row
                $bean->$p = $v;
        }
        $this->signal( "open", $bean );
		return $bean;
    }
	/**
	 * Deletes a bean from the database
	 * @param RedBean_OODBBean $bean
	 */
    public function trash( RedBean_OODBBean $bean ) {
		$this->signal( "delete", $bean );
        $this->check( $bean );
		try{
        $this->writer->deleteRecord( $bean->getMeta("type"), "id",$bean->id );
		}catch(RedBean_Exception_SQL $e){
			if ($e->getSQLState()!="42S02" && $e->getSQLState()!="42S22") throw $e;
		}
    }
	/**
	 * Loads a Batch of Beans at once
	 * @param string $type
	 * @param array $ids
	 * @return array $beans
	 */
    public function batch( $type, $ids ) {
		if (!$ids) return array();
		$collection = array();

		try{
		$rows = $this->writer->selectRecord($type,$ids);
		}catch(RedBean_Exception_SQL $e){
			if ($e->getSQLState()!="42S02" && $e->getSQLState()!="42S22") throw $e;
			$rows = false;
		}
		$this->stash = array();
		if (!$rows) return array();
		foreach($rows as $row) {
			$this->stash[$row["id"]] = $row;
		}
        foreach($ids as $id) {
            $collection[ $id ] = $this->load( $type, $id );
        }
		$this->stash = NULL;
        return $collection;
    }
	
}


