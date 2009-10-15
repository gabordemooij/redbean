<?php 
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
class RedBean_OODB extends RedBean_Observable {


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

    public function freeze( $tf ) {
        $isFrozen = (bool) $tf;
    }


    /**
     * Dispenses a OODBBean
     * @param string $type
     * @return RedBean_OODBBean $bean
     */
    public function dispense($type ) {


        $bean = new RedBean_OODBBean();
        $bean->__info = array( "type"=>$type );
        $bean->id = 0;

        $this->check( $bean );

        return $bean;
    }


    /**
     * Checks whether a bean is valid
     * @param RedBean_OODBBean $bean
     */
    public function check( RedBean_OODBBean $bean ) {

    //Bean should contain a hidden information section
        if (!isset($bean->__info)) {
            throw new RedBean_Exception_Security("Bean has no Meta Information");
        }

        //Is all meta information present?
        if (!isset($bean->id) || !isset($bean->__info["type"])) {
            throw new RedBean_Exception_Security("Bean has incomplete Meta Information");
        }

        //Pattern of allowed characters
        $pattern = '/[^abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_]/';

        //Does the type contain invalid characters?
        if (preg_match($pattern,$bean->__info["type"])) {
            throw new RedBean_Exception_Security("Bean Type is invalid");
        }

        //Are the properties and values valid?
        foreach($bean as $prop=>$value) {
            if (
            ($prop != "__info") &&
                (
                is_array($value) ||
                is_object($value) ||
                strlen($prop)<1 ||
                preg_match($pattern,$prop)
            )
            ) {
                throw new RedBean_Exception_Security("Invalid Bean: property $prop OR value $value ");
            }
        }
    }

    public function store( RedBean_OODBBean $bean ) {

        $this->signal( "update", $bean );
        $this->check($bean);

       

        //what table does it want
        $table = $this->writer->escape($bean->__info["type"]);

        //may we adjust the database?
        if (!$this->isFrozen) {

        //does this table exist?
            $tables = $this->writer->getTables();

            //If not, create
            if (!in_array($table, $tables)) {
                $this->writer->createTable( $table );
            }

            $columns = $this->writer->getColumns($table) ;

            //does the table fit?
            $insertvalues = array();
            $insertcolumns = array();
            $updatevalues = array();

            foreach( $bean as $p=>$v) {
                if ($p!="__info" && $p!="id") {
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
                    //Okay, now we are sure that the property value will fit
                    $insertvalues[] = $v;
                    $insertcolumns[] = $p;
                    $updatevalues[] = array( "property"=>$p, "value"=>$v );
                }
            }

             if (isset($bean->__info["unique"])) {
                    $this->writer->addUniqueIndex( $table, $bean->__info["unique"][0],$bean->__info["unique"][1] );
             }

            if ($bean->id) {
                if (count($updatevalues)>0) {
                    $this->writer->updateRecord( $table, $updatevalues, $bean->id );
                }
                return (int) $bean->id;
            }
            else {
                $id = $this->writer->insertRecord( $table, $insertcolumns, $insertvalues );
                $bean->id = $id;
                return (int) $id;
            }



        }
    }


    public function load($type, $id) {


        $bean = $this->dispense( $type );
        
        $row =  $this->writer->selectRecord($type,$id);

        foreach($row as $p=>$v) {
            //populate the bean with the database row
         
                $bean->$p = $v;
        }

        $this->signal( "open", $bean );
        return $bean;


    }

    public function trash( $bean ) {
        $this->check( $bean );
        $this->writer->deleteRecord( $bean->__info["type"], "id",$bean->id );
    }

  
    public function batch( $type, $ids ) {
        $collection = array();
        foreach($ids as $id) {
            $collection[ $id ] = $this->load( $type, $id );
        }
        return $collection;
    }

}


