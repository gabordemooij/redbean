<?php
/**
 * RedBean ChangeLogger
 * Shields you from race conditions automatically.
 * @package 		RedBean/ChangeLogger.php
 * @description		Shields you from race conditions automatically.
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_ChangeLogger implements RedBean_Observer {

    /**
     * @var RedBean_DBAdapter
     */
    private $writer;

	/**
	 *
	 * @var array
	 */
	private $stash = array();

	/**
	 * Constructor, requires a writer
	 * @param RedBean_QueryWriter $writer
	 */
    public function __construct(RedBean_QueryWriter $writer) {
        $this->writer = $writer;
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
			//echo "<br>opened: ".print_r($item, 1);
        }
        if ($event=="update" || $event=="delete") {
            if (($item->getMeta("opened"))) $oldid = $item->getMeta("opened"); else $oldid=0;
            $newid = $this->writer->checkChanges($type,$id, $oldid);
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
		$insertid = $this->writer->insertRecord("__log",array("action","tbl","itemid"),
           array(array(1,  '__no_type__', 0))); //Write a multi opened record
		$values = array();
		foreach($ids as $id) { //The returned Ids will be stored in a stash buffer
			$this->stash[$id]=$insertid; //the onEvent OPEN will empty this stash
			$values[] = array(1, $type, $id); //by using up the ids in it.

		}
		$this->writer->insertRecord("__log",array("action","tbl","itemid"), $values);
	}

	/**
	 * For testing only, dont use.
	 * @return array $stash
	 */
	public function testingOnly_getStash() {
		return $this->stash;
	}
}