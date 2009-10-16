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

	/**
	 *
	 * @var RedBean_OODB
	 */
    private $oodb;

	/**
	 *
	 * @var RedBean_QueryWriter
	 */
    private $writer;

	/**
	 *
	 * @var RedBean_DBAdapter
	 */
    private $adapter;

	/**
	 * Constructor
	 * @param RedBean_OODB $oodb
	 * @param RedBean_DBAdapter $adapter
	 * @param RedBean_QueryWriter $writer
	 * return RedBean_ToolBox $toolbox
	 */
    public function __construct( RedBean_OODB $oodb, RedBean_DBAdapter $adapter, RedBean_QueryWriter $writer ) {
        $this->oodb = $oodb;
        $this->adapter = $adapter;
        $this->writer = $writer;
		return $this;
    }

	/**
	 * Returns the QueryWriter
	 * @return RedBean_QueryWriter $writer
	 */
    public function getWriter() {
        return $this->writer;
    }

	/**
	 * Retruns the RedBean OODB Core object
	 * @return RedBean_OODB $oodb
	 */
    public function getRedBean() {
        return $this->oodb;
    }

	/**
	 * Returns the adapter
	 * @return RedBean_DBAdapter $adapter
	 */
    public function getDatabaseAdapter() {
        return $this->adapter;
    }
}