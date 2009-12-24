<?php
/**
 * RedBean ChangeLogger
 * Shields you from race conditions automatically.
 * @file 		RedBean/ChangeLogger.php
 * @description		Shields you from race conditions automatically.
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Plugin_ChangeLogger extends RedBean_CompatManager implements RedBean_Plugin,RedBean_Observer {

	/**
	 * Specify what database systems are supported by this class.
	 * @var array $databaseSpecs
	 */
	protected $supportedSystems = array(
		RedBean_CompatManager::C_SYSTEM_MYSQL => "5"
	);


    /**
     * @var RedBean_Adapter_DBAdapter
     */
    private $writer;

	/**
	 *
	 * @var RedBean_Adapter
	 */
	private $adapter;


	/**
	 *
	 * @var array
	 */
	private $stash = array();

	/**
	 *
	 * @var RedBean_OODB
	 */
	private $redbean;

	/**
	 * Constructor, requires a writer
	 * @param RedBean_QueryWriter $writer
	 */
    public function __construct(RedBean_ToolBox $toolbox) {

		//Do a compatibility check, using the Compatibility Management System
		$this->scanToolBox( $toolbox );

        $this->writer = $toolbox->getWriter();
		$this->adapter = $toolbox->getDatabaseAdapter();
		$this->redbean = $toolbox->getRedBean();
		if (!$this->redbean->isFrozen()) {
			$this->adapter->exec("
						CREATE TABLE IF NOT EXISTS `__log` (
						`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`tbl` VARCHAR( 255 ) NOT NULL ,
						`action` TINYINT( 2 ) NOT NULL ,
						`itemid` INT( 11 ) NOT NULL
						) ENGINE = MYISAM ;
				"); //Must be MyISAM! else you run in trouble if you use transactions!
		}
		$maxid = $this->adapter->getCell("SELECT MAX(id) FROM __log");
		$this->adapter->exec("DELETE FROM __log WHERE id < $maxid - 200 ");
    }

	/**
	 * Throws an exception if information in the bean has been changed
	 * by another process or bean. This is actually the same as journaling
	 * using timestamps however with timestamps you risk race conditions
	 * when the measurements are not fine-grained enough; with
	 * auto-incremented primary key ids we dont have this risk.
	 * @param string $event
	 * @param RedBean_OODBBean $item
	 */
    public function onEvent( $event, $item ) { 
        $id = $item->id;
        if (! ((int) $id)) $event="open";


        $type = $item->getMeta("type");
        if ($event=="open") {
			if (isset($this->stash[$id])) {
				$insertid = $this->stash[$id];
				unset($this->stash[$id]);
				return $insertid;
			}
			$insertid = $this->writer->insertRecord("__log",array("action","tbl","itemid"),
            array(array(1,  $type, $id)));
			$item->setMeta("opened",$insertid);
			
        }
        if ($event=="update" || $event=="delete") {
            if (($item->getMeta("opened"))) $oldid = $item->getMeta("opened"); else $oldid=0;
            $newid = $this->checkChanges($type,$id, $oldid);
	        $item->setMeta("opened",$newid);
        }
    }


	/**
	 * Facilitates preloading. If you want to load multiple beans at once
	 * these beans can be locked individually; given N beans this means approx.
	 * N*3 queries which is quite a lot. This method allows you to pre-lock or pre-open
	 * multiple entries at once. All beans will get an opened stamp that correspond to
	 * the first bean opened. This means this approach is conservative; it might
	 * produce a higher rate of false alarms but it does not compromise
	 * concurrency security.
	 * @param string $type
	 * @param array $ids
	 */
	public function preLoad( $type, $ids ) {
		$this->adapter->exec("INSERT INTO __log (id,action,tbl,itemid)
		VALUES(NULL, :action,:tbl,:id)",array(":action"=>1,":tbl"=>"__no_type__",":id"=>0)); //Write a multi opened record
		$insertid = $this->adapter->getInsertID();
		$values = array();
		foreach($ids as $id) { //The returned Ids will be stored in a stash buffer
			$this->stash[$id]=$insertid; //the onEvent OPEN will empty this stash
			$values[] = array(1, $type, $id); //by using up the ids in it.

		}
		$this->writer->insertRecord("__log",array("action","tbl","itemid"), $values); //use extend. insert if possible
	}

	/**
	 * For testing only, dont use.
	 * @return array $stash
	 */
	public function testingOnly_getStash() {
		return $this->stash;
	}

	/**
	 * Gets information about changed records using a type and id and a logid.
	 * RedBean Locking shields you from race conditions by comparing the latest
	 * cached insert id with a the highest insert id associated with a write action
	 * on the same table. If there is any id between these two the record has
	 * been changed and RedBean will throw an exception. This function checks for changes.
	 * If changes have occurred it will throw an exception. If no changes have occurred
	 * it will insert a new change record and return the new change id.
	 * This method locks the log table exclusively.
	 * @param  string $type
	 * @param  integer $id
	 * @param  integer $logid
	 * @return integer $newchangeid
	 */
    public function checkChanges($type, $id, $logid) {
		$type = $this->writer->check($type);
		$id = (int) $id;
		$logid = (int) $logid;
		$num = $this->adapter->getCell("
        SELECT count(*) FROM __log WHERE tbl=\"$type\" AND itemid=$id AND action=2 AND id > $logid");
        if ($num) {
			throw new RedBean_Exception_FailedAccessBean("Locked, failed to access (type:$type, id:$id)");
		}
		$this->adapter->exec("INSERT INTO __log (id,action,tbl,itemid) VALUES(NULL, 2,:tbl,:id)",array(":tbl"=>$type, ":id"=>$id));
		$newid = $this->adapter->getInsertID();
	    if ($this->adapter->getCell("select id from __log where tbl=:tbl AND id < $newid and id > $logid and action=2 and itemid=$id ",
			array(":tbl"=>$type))){
			throw new RedBean_Exception_FailedAccessBean("Locked, failed to access II (type:$type, id:$id)");
		}
		return $newid;
	}
}