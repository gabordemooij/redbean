<?php
/**
 * @name RedBean View Manager
 * @file RedBean
 * @author Gabor de Mooij and the RedBean Team
 * @copyright Gabor de Mooij (c)
 * @license BSD
 *
 * The ViewManager creates on the fly views for easy querying.
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedBean_ViewManager {



	/**
	 * @var RedBean_OODB
	 */
	protected $oodb;

	/**
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;

	/**
	 * @var RedBean_QueryWriter
	 */
	protected $writer;


	/**
	 * Constructor
	 *
	 * @param RedBean_ToolBox $tools toolbox
	 */
	public function __construct( RedBean_ToolBox $tools ) {
		$this->oodb = $tools->getRedBean();
		$this->adapter = $tools->getDatabaseAdapter();
		$this->writer = $tools->getWriter();
	}

	/**
	 * Creates a view with name $viewID based on $refType bean type
	 * and then left-joining the specified types in $types in the given
	 * order.
	 *
	 * @param  string $viewID  desired name of the view
	 * @param  string $refType first bean type to be used as base
	 * @param  array  $types   array with types to be left-joined in view
	 *
	 * @return boolean $success whether we created a new view (false if already exists)
	 */
	public function createView( $viewID, $refType, $types ) {
		if ($this->oodb->isFrozen()) return false;
		$history = array();
		$tables = array_flip( $this->writer->getTables() );
		$refTable = $refType; //$this->writer->safeTable($refType, true);
		$currentTable = $refTable;
		$history[$refType] = $refType;
		foreach($types as $t) {
			if (!isset($history[$t])){
				$history[$t] = $t;
				$connection = array($t,$currentTable);
				sort($connection);
				$connection = implode("_", $connection);
				$connectionTable = $this->writer->safeTable($connection,true);
				if (isset($tables[$connectionTable])) {
					//this connection exists
					$srcPoint = $this->writer->safeTable($connection).".".$this->writer->safeColumn($currentTable."_id"); //i.e. partic_project.project_id
					$dstPoint = $this->writer->safeTable($currentTable).".".$this->writer->safeColumn($this->writer->getIDField($currentTable)); //i.e. project.id
					$joins[$connection] = array($srcPoint,$dstPoint);
					//now join the type
					$srcPoint = $this->writer->safeTable($connection).".".$this->writer->safeColumn($t."_id");
					$dstPoint = $this->writer->safeTable($t).".".$this->writer->safeColumn($this->writer->getIDField($t));
					$joins[$t] = array($srcPoint,$dstPoint);
				}
				else {
					//this connection does not exist
					$srcPoint = $this->writer->safeTable($t).".".$this->writer->safeColumn($currentTable."_id");
					$dstPoint = $this->writer->safeTable($currentTable).".".$this->writer->safeColumn($this->writer->getIDField($currentTable));
					$joins[$t] = array($srcPoint,$dstPoint);
				}
			}
			//now set the new refTable
			$currentTable=$t;
		}
		try{
			$rs = (boolean) $this->writer->createView($refType,$joins,$viewID);
		}
		catch(Exception $e) {
			throw new RedBean_Exception_SQL('Could not create view, types does not seem related (yet)..');
		}
		return $rs;
	}
}
