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
        if (! ((int) $id)) return;
        $type = $item->getMeta("type");
        if ($event=="open") {
            $insertid = $this->writer->insertRecord("__log",array("action","tbl","itemid"),
            array(1,  $type, $id));
            $item->setMeta("opened",$insertid);
			//echo "<br>opened: ".print_r($item, 1);
        }
        if ($event=="update") {
            if (($item->getMeta("opened"))) $oldid = $item->getMeta("opened"); else $oldid=0;
            $newid = $this->writer->checkChanges($type,$id, $oldid);
	        $item->setMeta("opened",$newid);
        }
    }
}