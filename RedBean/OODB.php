<?php 

class RedBean_OODB {

    public $pkey = false;

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

        $this->releaseAllLocks();

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

    public function checkBean(RedBean_OODBBean $bean) {
        if (!$this->toolbox->getDatabase()) {
            throw new RedBean_Exception_Security("No database object. Have you used kickstart to initialize RedBean?");
        }
        return $this->toolbox->getBeanChecker()->check( $bean );
    }

    public function checkBeanForAssoc( $bean ) {

    //check the bean
        $this->checkBean($bean);

        //make sure it has already been saved to the database, else we have no id.
        if (intval($bean->id) < 1) {
        //if it's not saved, save it
            $bean->id = $this->set( $bean );
        }

        return $bean;

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

    public function set( RedBean_OODBBean $bean ) {
        return $this->toolbox->getBeanStore()->set($bean);
    }

    public function inferType( $v ) {
        return $this->toolbox->getScanner()->type( $v );
    }

    public function getType( $sqlType ) {
        return $this->toolbox->getScanner()->code( $sqlType );
    }

    public function freeze() {
        $this->frozen = true;
    }

    public function unfreeze() {
        $this->frozen = false;
    }

    public function showTables( $all=false ) {
        return $this->toolbox->getTableRegister()->getTables($all);
    }

    public function addTable( $tablename ) {
        return $this->toolbox->getTableRegister()->register( $tablename);
    }

    public function dropTable( $tablename ) {
        return $this->toolbox->getTableRegister()->unregister( $tablename );
    }

    public function releaseAllLocks() {
        $this->toolbox->getDatabase()->exec($this->toolbox->getWriter()->getQuery("release",array("key"=>$this->pkey)));
    }

    public function openBean( $bean, $mustlock=false) {
        $this->checkBean( $bean );
        $this->toolbox->getLockManager()->openBean( $bean, $mustlock );
    }

    public function getById($type, $id, $data=false) {
        return $this->toolbox->getBeanStore()->get($type,$id,$data);
    }

    public function exists($type,$id) {
        return $this->toolbox->getBeanStore()->exists($type, $id);
    }

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
    //oops, we are frozen, so no change..
        if ($this->frozen) {
            return false;
        }
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


