<?php
/**
 * ToolBox
 * Contains most important redbean tools
 * @package 		RedBean/ToolBox.php
 * @description		The ToolBox acts as a resource locator for RedBean but can
 *					be integrated in larger resource locators (nested).
 *					It does not do anything more than just store the three most
 *					important RedBean resources (tools): the database adapter,
 *					the redbean core class (oodb) and the query writer.
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_ToolBox {

    private $oodb;
    private $writer;
    private $adapter;


    public function __construct( RedBean_OODB $oodb, RedBean_DBAdapter $adapter, RedBean_QueryWriter $writer ) {

        $this->oodb = $oodb;
        $this->adapter = $adapter;
        $this->writer = $writer;


    }

    public function getWriter() {
        return $this->writer;
    }

    public function getRedBean() {
        return $this->oodb;
    }

    public function getDatabaseAdapter() {
        return $this->adapter;
    }
}