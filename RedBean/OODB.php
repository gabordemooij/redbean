<?php 
/**
 * RedBean OODB (object oriented database)
 * @package 		RedBean/OODB.php
 * @description		Core class for the RedBean ORM pack
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_OODB {


    /**
     * Indicates how long one can lock an item,
     * defaults to ten minutes
     * If a user opens a bean and he or she does not
     * perform any actions on it others cannot modify the
     * bean during this time interval.
     * @var unknown_type
     */
    private $locktime = 10;

    /**
     *
     * @var boolean
     */
    private $locking = true;
    /**
     *
     * @var string $pkey - a fingerprint for locking
     */
    public $pkey = false;

    /**
     * Indicates that a rollback is required
     * @var unknown_type
     */
    private $rollback = false;

    /**
     *
     * @var $this
     */
    private static $me = null;

    /**
     *
     * Indicates the current engine
     * @var string
     */
    private $engine = "myisam";

    /**
     * @var boolean $frozen - indicates whether the db may be adjusted or not
     */
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



    public function getFilter() {
        return $this->toolbox->getFilter();
    }

    public function setFilter( RedBean_Mod_Filter $filter ) {
        $this->toolbox->add("filter",$filter);
    }

    public function getWriter() {
        return $this->toolbox->getWriter();
    }

    public function isFrozen() {
        return (boolean) $this->frozen;
    }

    /**
     * Closes and unlocks the bean
     * @return unknown_type
     */
    public function __destruct() {

        $this->releaseAllLocks();

        $this->toolbox->getDatabase()->exec(
            $this->toolbox->getWriter()->getQuery("destruct", array("engine"=>$this->engine,"rollback"=>$this->rollback))
        );

    }




    /**
     * Toggles Forward Locking
     * @param $tf
     * @return unknown_type
     */
    public function setLocking( $tf ) {
        $this->locking = $tf;
    }

    public function getDatabase() {
        return $this->toolbox->getDatabase();
    }

    public function setDatabase( RedBean_DBAdapter $db ) {
        $this->toolbox->add("database",$db);
    }

    /**
     * Gets the current locking mode (on or off)
     * @return unknown_type
     */
    public function getLocking() {
        return $this->locking;
    }


    /**
     * Toggles optimizer
     * @param $bool
     * @return unknown_type
     */
    public function setOptimizerActive( $bool ) {
        $this->optimizer = (boolean) $bool;
    }

    /**
     * Returns state of the optimizer
     * @param $bool
     * @return unknown_type
     */
    public function getOptimizerActive() {
        return $this->optimizer;
    }

   
    /**
     * Singleton
     * @return unknown_type
     */
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

    /**
     * Checks whether a bean is valid
     * @param $bean
     * @return unknown_type
     */
    public function checkBean(RedBean_OODBBean $bean) {
        if (!$this->toolbox->getDatabase()) {
            throw new RedBean_Exception_Security("No database object. Have you used kickstart to initialize RedBean?");
        }
        return $this->toolbox->getBeanChecker()->check( $bean );
    }

    /**
     * same as check bean, but does additional checks for associations
     * @param $bean
     * @return unknown_type
     */
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

    /**
     * Returns the current engine
     * @return unknown_type
     */
    public function getEngine() {
        return $this->engine;
    }

    /**
     * Sets the current engine
     * @param $engine
     * @return unknown_type
     */
    public function setEngine( $engine ) {

        if ($engine=="myisam" || $engine=="innodb") {
            $this->engine = $engine;
        }
        else {
            throw new Exception("Unsupported database engine");
        }

        return $this->engine;

    }

    /**
     * Will perform a rollback at the end of the script
     * @return unknown_type
     */
    public function rollback() {
        $this->rollback = true;
    }

    public function set( RedBean_OODBBean $bean ) {
        return $this->toolbox->getBeanStore()->set($bean);
    }


    /**
     * Infers the SQL type of a bean
     * @param $v
     * @return $type the SQL type number constant
     */
    public function inferType( $v ) {
        return $this->toolbox->getScanner()->type( $v );
    }


    /**
     * Returns the RedBean type const for an SQL type
     * @param $sqlType
     * @return $typeno
     */
    public function getType( $sqlType ) {
        return $this->toolbox->getScanner()->code( $sqlType );
    }

    
    /**
     * Freezes the database so it won't be changed anymore
     * @return unknown_type
     */
    public function freeze() {
        $this->frozen = true;
    }

    /**
     * UNFreezes the database so it won't be changed anymore
     * @return unknown_type
     */
    public function unfreeze() {
        $this->frozen = false;
    }

    /**
     * Returns all redbean tables or all tables in the database
     * @param $all if set to true this function returns all tables instead of just all rb tables
     * @return array $listoftables
     */
    public function showTables( $all=false ) {
        return $this->toolbox->getTableRegister()->getTables($all);
    }

    /**
     * Registers a table with RedBean
     * @param $tablename
     * @return void
     */
    public function addTable( $tablename ) {
        return $this->toolbox->getTableRegister()->register( $tablename);
    }

    /**
     * UNRegisters a table with RedBean
     * @param $tablename
     * @return void
     */
    public function dropTable( $tablename ) {
        return $this->toolbox->getTableRegister()->unregister( $tablename );
    }

    /**
     * Quick and dirty way to release all locks
     * @return unknown_type
     */
    public function releaseAllLocks() {
        $this->toolbox->getDatabase()->exec($this->toolbox->getWriter()->getQuery("release",array("key"=>$this->pkey)));
    }

    /**
     * Opens and locks a bean
     * @param $bean
     * @return unknown_type
     */
    public function openBean( $bean, $mustlock=false) {
        $this->checkBean( $bean );
        $this->toolbox->getLockManager()->openBean( $bean, $mustlock );
    }


    /**
     * Gets a bean by its primary ID
     * @param $type
     * @param $id
     * @return RedBean_OODBBean $bean
     */
    public function getById($type, $id, $data=false) {
        return $this->toolbox->getBeanStore()->get($type,$id,$data);
    }

    /**
     * Checks whether a type-id combination exists
     * @param $type
     * @param $id
     * @return unknown_type
     */
    public function exists($type,$id) {
        return $this->toolbox->getBeanStore()->exists($type, $id);
    }



    /**
     * Counts occurences of  a bean
     * @param $type
     * @return integer $i
     */
    public function numberof($type) {
        return $this->toolbox->getBeanStore()->numberof( $type );
    }

    /**
     * Gets all beans of $type, grouped by $field.
     *
     * @param String Object type e.g. "user" (lowercase!)
     * @param String Field/parameter e.g. "zip"
     * @return Array list of beans with distinct values of $field. Uses GROUP BY
     * @author Alan J. Hogan
     **/
    function distinct($type, $field) {
        return $this->toolbox->getLister()->distinct( $type, $field );
    }


    /**
     * Simple statistic
     * @param $type
     * @param $field
     * @return integer $i
     */
    private function stat($type,$field,$stat="sum") {
        return $this->toolbox->getLister()->stat( $type, $field, $stat);
    }



    /**
     * Sum
     * @param $type
     * @param $field
     * @return float $i
     */
    public function sumof($type,$field) {
        return $this->stat( $type, $field, "sum");
    }

    /**
     * AVG
     * @param $type
     * @param $field
     * @return float $i
     */
    public function avgof($type,$field) {
        return $this->stat( $type, $field, "avg");
    }

    /**
     * minimum
     * @param $type
     * @param $field
     * @return float $i
     */
    public function minof($type,$field) {
        return $this->stat( $type, $field, "min");
    }

    /**
     * maximum
     * @param $type
     * @param $field
     * @return float $i
     */
    public function maxof($type,$field) {
        return $this->stat( $type, $field, "max");
    }


    /**
     * Unlocks everything
     * @return unknown_type
     */
    public function resetAll() {
        $sql = $this->toolbox->getWriter()->getQuery("releaseall");
        $this->toolbox->getDatabase()->exec( $sql );
        return true;
    }

    /**
     * Loads a collection of beans -fast-
     * @param $type
     * @param $ids
     * @return unknown_type
     */
    public function fastLoader( $type, $ids ) {

        $db = $this->toolbox->getDatabase();


        $sql = $this->toolbox->getWriter()->getQuery("fastload", array(
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
    public function getBySQL( $rawsql, $slots, $table, $max=0 ) {

        return $this->toolbox->getSearch()->sql( $rawsql, $slots, $table, $max );

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
    public function find(RedBean_OODBBean $bean, $searchoperators = array(), $start=0, $end=100, $orderby="id ASC", $extraSQL=false) {
        return $this->toolbox->getFinder()->find($bean, $searchoperators, $start, $end, $orderby, $extraSQL);

    }


    /**
     * Returns a plain and simple array filled with record data
     * @param $type
     * @param $start
     * @param $end
     * @param $orderby
     * @return unknown_type
     */
    public function listAll($type, $start=false, $end=false, $orderby="id ASC", $extraSQL = false) {
        return $this->toolbox->getLister()->get($type, $start, $end, $orderby,$extraSQL);
    }


    /**
     * Associates two beans
     * @param $bean1
     * @param $bean2
     * @return unknown_type
     */
    public function associate( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 ) { //@associate
        return $this->toolbox->getAssociation()->link( $bean1, $bean2 );
    }

    /**
     * Breaks the association between a pair of beans
     * @param $bean1
     * @param $bean2
     * @return unknown_type
     */
    public function unassociate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
        return $this->toolbox->getAssociation()->breakLink( $bean1, $bean2 );
    }

    /**
     * Fetches all beans of type $targettype assoiciated with $bean
     * @param $bean
     * @param $targettype
     * @return array $beans
     */
    public function getAssoc(RedBean_OODBBean $bean, $targettype) {
        return $this->toolbox->getAssociation()->get( $bean, $targettype );
    }


    /**
     * Removes a bean from the database and breaks associations if required
     * @param $bean
     * @return unknown_type
     */
    public function trash( RedBean_OODBBean $bean ) {
        return $this->toolbox->getBeanStore()->trash( $bean );
    }


    /**
     * Breaks all associations of a perticular bean $bean
     * @param $bean
     * @return unknown_type
     */
    public function deleteAllAssoc( $bean ) {
        return $this->toolbox->getAssociation()->deleteAllAssoc( $bean );
    }

    /**
     * Breaks all associations of a perticular bean $bean
     * @param $bean
     * @return unknown_type
     */
    public function deleteAllAssocType( $targettype, $bean ) {
        return $this->toolbox->getAssociation()->deleteAllAssocType( $targettype, $bean );
    }


    /**
     * Dispenses; creates a new OODB bean of type $type
     * @param $type
     * @return RedBean_OODBBean $bean
     */
    public function dispense( $type="StandardBean" ) {
        return $this->toolbox->getDispenser()->dispense( $type );
    }


    /**
     * Adds a child bean to a parent bean
     * @param $parent
     * @param $child
     * @return unknown_type
     */
    public function addChild( RedBean_OODBBean $parent, RedBean_OODBBean $child ) {
        return $this->toolbox->getTree()->add( $parent, $child );
    }

    /**
     * Returns all child beans of parent bean $parent
     * @param $parent
     * @return array $beans
     */
    public function getChildren( RedBean_OODBBean $parent ) {
        return $this->toolbox->getTree()->getChildren($parent);
    }

    /**
     * Fetches the parent bean of child bean $child
     * @param $child
     * @return RedBean_OODBBean $parent
     */
    public function getParent( RedBean_OODBBean $child ) {
        return $this->toolbox->getTree()->getParent($child);
    }

    /**
     * Removes a child bean from a parent-child association
     * @param $parent
     * @param $child
     * @return unknown_type
     */
    public function removeChild(RedBean_OODBBean $parent, RedBean_OODBBean $child) {
        return $this->toolbox->getTree()->removeChild( $parent, $child );
    }

    /**
     * Counts the associations between a type and a bean
     * @param $type
     * @param $bean
     * @return integer $numberOfRelations
     */
    public function numofRelated( $type, RedBean_OODBBean $bean ) {
        return $this->toolbox->getAssociation()->numOfRelated( $type, $bean );

    }

    /**
     * Accepts a comma separated list of class names and
     * creates a default model for each classname mentioned in
     * this list. Note that you should not gen() classes
     * for which you already created a model (by inheriting
     * from ReadBean_Decorator).
     * @param string $classes
     * @param string $prefix prefix for framework integration (optional, constant is used otherwise)
     * @param string $suffix suffix for framework integration (optional, constant is used otherwise)
     * @return unknown_type
     */

    public function generate( $classes, $prefix = false, $suffix = false ) {
        return $this->toolbox->getClassGenerator()->generate($classes,$prefix,$suffix);
    }


    

    /**
     * Changes the locktime, this time indicated how long
     * a user can lock a bean in the database.
     * @param $timeInSecs
     * @return unknown_type
     */
    public function setLockingTime( $timeInSecs ) {

        if (is_int($timeInSecs) && $timeInSecs >= 0) {
            $this->locktime = $timeInSecs;
        }
        else {
            throw new RedBean_Exception_InvalidArgument( "time must be integer >= 0" );
        }
    }

    public function getLockingTime() { return $this->locktime; }



    /**
     * Cleans the entire redbean database, this will not affect
     * tables that are not managed by redbean.
     * @return unknown_type
     */
    public function clean() {
        return $this->toolbox->getGC()->clean();
    }


    /**
     * Removes all tables from redbean that have
     * no classes
     * @return unknown_type
     */
    public function removeUnused( ) {
    //oops, we are frozen, so no change..
        if ($this->frozen) {
            return false;
        }
        return $this->toolbox->getGC()->removeUnused( $this, $this->toolbox->getDatabase(), $this->toolbox->getWriter() );


    }
    /**
     * Drops a specific column
     * @param $table
     * @param $property
     * @return unknown_type
     */
    public function dropColumn( $table, $property ) {
        return $this->toolbox->getGC()->dropColumn($table,$property);
    }

    /**
     * Removes all beans of a particular type
     * @param $type
     * @return nothing
     */
    public function trashAll($type) {
        $this->toolbox->getDatabase()->exec( $this->toolbox->getWriter()->getQuery("drop_type",array("type"=>$this->toolbox->getFilter()->table($type))));
    }

    public static function gen($arg, $prefix = false, $suffix = false) {
        return self::getInstance()->generate($arg, $prefix, $suffix);
    }

    public static function keepInShape($gc = false ,$stdTable=false, $stdCol=false) {
        return self::getInstance()->getToolBox()->getOptimizer()->run($gc, $stdTable, $stdCol);
    }

    public function getInstOf( $className, $id=0 ) {
        if (!class_exists($className)) throw new Exception("Class does not Exist");
        $object = new $className($id);
        return $object;
    }
}


