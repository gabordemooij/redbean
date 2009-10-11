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

    
    /*public function trash( RedBean_OODBBean $bean ) {
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

    */

    public function generate( $classes, $prefix = false, $suffix = false ) {
        return $this->toolbox->getClassGenerator()->generate($classes,$prefix,$suffix);
    }


/*
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
*/


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


