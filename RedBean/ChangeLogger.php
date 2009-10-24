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

	private $ids = array();

	/**
	 * Constructor, requires a writer
	 * @param RedBean_QueryWriter $writer
	 */
    public function __construct(RedBean_QueryWriter $writer) {
        $this->writer = $writer;
    }

	/**
	 * Throws an exception if information in the bean has been changed
	 * by another process or bean.
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



	public function preLoad( $type, $ids ) {
		$insertid = $this->writer->insertRecord("__log",array("action","tbl","itemid"),
           array(array(1,  '__no_type__', 0)));
		$values = array();
		foreach($ids as $id) {
			$this->stash[$id]=$insertid;
			$values[] = array(1, $type, $id);

		}
		$this->writer->insertRecord("__log",array("action","tbl","itemid"), $values);
	}
}