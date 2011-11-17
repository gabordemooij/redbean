<?php
/**
 * @name RedBean OODB
 * @file RedBean
 * @author Gabor de Mooij and the RedBean Team
 * @copyright Gabor de Mooij (c)
 * @license BSD
 *
 * The RedBean OODB Class is the main class of RedBean.
 * It takes RedBean_OODBBean objects and stores them to and loads them from the
 * database as well as providing other CRUD functions. This class acts as a
 * object database.
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_OODB extends RedBean_Observable {

	/**
	 *
	 * @var array
	 */
	private $stash = NULL;

	/**
	 *
	 * @var RedBean_Adapter_DBAdapter
	 */
	private $writer;
	/**
	 *
	 * @var boolean
	 */
	private $isFrozen = false;

	/**
	 * @var null|\RedBean_BeanHelperFacade
	 */
	private $beanhelper = null;

	/**
	 * The RedBean OODB Class is the main class of RedBean.
	 * It takes RedBean_OODBBean objects and stores them to and loads them from the
	 * database as well as providing other CRUD functions. This class acts as a
	 * object database.
	 * Constructor, requires a DBAadapter (dependency inversion)
	 * @param RedBean_Adapter_DBAdapter $adapter
	 */
	public function __construct( $writer ) {

		if ($writer instanceof RedBean_QueryWriter) {
			$this->writer = $writer;
		}

		$this->beanhelper = new RedBean_BeanHelperFacade();


	}

	/**
	 * Toggles fluid or frozen mode. In fluid mode the database
	 * structure is adjusted to accomodate your objects. In frozen mode
	 * this is not the case.
	 * @param boolean $trueFalse
	 */
	public function freeze( $tf ) {
		$this->isFrozen = (bool) $tf;
	}


	/**
	 * Returns the current mode of operation of RedBean.
	 * In fluid mode the database
	 * structure is adjusted to accomodate your objects.
	 * In frozen mode
	 * this is not the case.
	 * @return <type>
	 */
	public function isFrozen() {
		return (bool) $this->isFrozen;
	}

	/**
	 * Dispenses a new bean (a RedBean_OODBBean Bean Object)
	 * of the specified type. Always
	 * use this function to get an empty bean object. Never
	 * instantiate a RedBean_OODBBean yourself because it needs
	 * to be configured before you can use it with RedBean. This
	 * function applies the appropriate initialization /
	 * configuration for you.
	 * @param string $type
	 * @return RedBean_OODBBean $bean
	 */
	public function dispense($type ) {
		$this->signal( "before_dispense", $type );
		$bean = new RedBean_OODBBean();
		$bean->setBeanHelper($this->beanhelper);
		$bean->setMeta("type", $type );
		$idfield = $this->writer->getIDField($bean->getMeta("type"));
		$bean->setMeta("sys.idfield",$idfield);
		//$bean->setMeta("sys.oodb",$this);
		$bean->$idfield = 0;
		if (!$this->isFrozen) $this->check( $bean );
		$bean->setMeta("tainted",true);
		$this->signal( "dispense", $bean );
		return $bean;
	}

	/**
	 * Sets bean helper
	 *
	 * @param RedBean_IBeanHelper $beanhelper helper
	 *
	 * @return void
	 */
	public function setBeanHelper( RedBean_IBeanHelper $beanhelper) {
		$this->beanhelper = $beanhelper;
	}


	/**
	 * Checks whether a RedBean_OODBBean bean is valid.
	 * If the type is not valid or the ID is not valid it will
	 * throw an exception: RedBean_Exception_Security.
	 * @throws RedBean_Exception_Security $exception
	 * @param RedBean_OODBBean $bean
	 */
	public function check( RedBean_OODBBean $bean ) {
		$idfield = $this->writer->getIDField($bean->getMeta("type"));
		//Is all meta information present?

		if (!isset($bean->$idfield) ) {
			throw new RedBean_Exception_Security("Bean has incomplete Meta Information $idfield ");
		}
		if (!($bean->getMeta("type"))) {
			throw new RedBean_Exception_Security("Bean has incomplete Meta Information II");
		}
		//Pattern of allowed characters
		$pattern = '/[^abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_]/';
		//Does the type contain invalid characters?
		if (preg_match($pattern,$bean->getMeta("type"))) {
			throw new RedBean_Exception_Security("Bean Type is invalid");
		}
		//Are the properties and values valid?
		foreach($bean as $prop=>$value) {
			if (
					  is_array($value) ||
					  (is_object($value)) ||
					  strlen($prop)<1 ||
					  preg_match($pattern,$prop)
			) {
				throw new RedBean_Exception_Security("Invalid Bean: property $prop  ");
			}
		}
	}


	/**
	 * @param  $type
	 * @param array $conditions
	 * @param null $addSQL
	 * @return array
	 */
	public function find($type,$conditions=array(),$addSQL=null) {
		try {
			$beans = $this->convertToBeans($type,$this->writer->selectRecord($type,$conditions,$addSQL));
			return $beans;
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN)
			)) throw $e;
		}
		return array();
	}


	/**
	 * Checks whether the specified table already exists in the database.
	 * Not part of the Object Database interface!
	 * @param string $table
	 * @return boolean $exists
	 */
	public function tableExists($table) {
		//does this table exist?
		$tables = $this->writer->getTables();
		return in_array($this->writer->getFormattedTableName($table), $tables);
	}


	/**
	 * Processes all column based build commands.
	 * A build command is an additional instruction for the Query Writer. It is processed only when
	 * a column gets created. The build command is often used to instruct the writer to write some
	 * extra SQL to create indexes or constraints. Build commands are stored in meta data of the bean.
	 * They are only for internal use, try to refrain from using them in your code directly.
	 *
	 * @param  string           $table    name of the table to process build commands for
	 * @param  string           $property name of the property to process build commands for
	 * @param  RedBean_OODBBean $bean     bean that contains the build commands
	 *
	 * @return void
	 */
	protected function processBuildCommands($table, $property, RedBean_OODBBean $bean) {
		if ($inx = ($bean->getMeta("buildcommand.indexes"))) {
			if (isset($inx[$property])) $this->writer->addIndex($table,$inx[$property],$property);
		}
	}



	/**
	 * Process groups
	 *
	 * @param  array $originals originals
	 * @param  array $current   the current beans
	 * @param  array $additions beans that have been added
	 * @param  array $trashcan  beans that have been deleted
	 * @param  array $residue   beans that have been left untouched
	 *
	 * @return array $result 	new relational state
	 */
	protected function processGroups( $originals, $current, $additions, $trashcan, $residue ) {
		return array(
			array_merge($additions,array_diff($current,$originals)),
			array_merge($trashcan,array_diff($originals,$current)),
			array_merge($residue,array_intersect($current,$originals))
		);
	}

	/**
	 * Stores a bean in the database. This function takes a
	 * RedBean_OODBBean Bean Object $bean and stores it
	 * in the database. If the database schema is not compatible
	 * with this bean and RedBean runs in fluid mode the schema
	 * will be altered to store the bean correctly.
	 * If the database schema is not compatible with this bean and
	 * RedBean runs in frozen mode it will throw an exception.
	 * This function returns the primary key ID of the inserted
	 * bean.
	 *
	 * @throws RedBean_Exception_Security $exception
	 * @param RedBean_OODBBean $bean bean to store
	 *
	 * @return integer $newid
	 */
	public function store( RedBean_OODBBean $bean ) {

		$processLists = false;

		foreach($bean as $k=>$v) {
			if (is_array($v) || is_object($v)) { $processLists = true; break; }
		}

		if (!$processLists && !$bean->getMeta('tainted')) return $bean->getID();
	
		
		$this->signal( "update", $bean );

		foreach($bean as $k=>$v) {
			if (is_array($v) || is_object($v)) { $processLists = true; break; }
		}

		
		if ($processLists) {
		//Define groups
		$sharedAdditions = $sharedTrashcan = $sharedresidue = $sharedItems = array();
		$ownAdditions = $ownTrashcan = $ownresidue = array();
		$tmpCollectionStore = array();
		$embeddedBeans = array();
		foreach($bean as $p=>$v) {



			if ($v instanceof RedBean_OODBBean) {
				$embtype = $v->getMeta('type');
				$idfield = $this->writer->getIDField($embtype);
				if (!$v->$idfield || $v->getMeta('tainted')) {
					$this->store($v);
				}
				$beanID = $v->$idfield;
				$linkField = $p.'_id';
				$bean->$linkField = $beanID;
				$bean->setMeta('cast.'.$linkField,'id');
				$embeddedBeans[$linkField] = $v;
				$tmpCollectionStore[$p]=$bean->$p;
				$bean->removeProperty($p);
			}
			if (is_array($v)) {
				$originals = $bean->getMeta('sys.shadow.'.$p);
				if (!$originals) $originals = array();
				if (strpos($p,'own')===0) {
					list($ownAdditions,$ownTrashcan,$ownresidue)=$this->processGroups($originals,$v,$ownAdditions,$ownTrashcan,$ownresidue);
					$bean->removeProperty($p);
				}
				elseif (strpos($p,'shared')===0) {
					list($sharedAdditions,$sharedTrashcan,$sharedresidue)=$this->processGroups($originals,$v,$sharedAdditions,$sharedTrashcan,$sharedresidue);
					$bean->removeProperty($p);

				}
				else {
				}
			}
		}
		}

		if (!$this->isFrozen) $this->check($bean);
		//what table does it want
		$table = $bean->getMeta("type");
		$idfield = $this->writer->getIDField($table);

		if ($bean->getMeta('tainted')) {
		//Does table exist? If not, create
		if (!$this->isFrozen && !$this->tableExists($table)) {
			$this->writer->createTable( $table );
			$bean->setMeta("buildreport.flags.created",true);
		}
		if (!$this->isFrozen) {
			$columns = $this->writer->getColumns($table) ;
		}
		//does the table fit?
		$insertvalues = array();
		$insertcolumns = array();
		$updatevalues = array();
		foreach( $bean as $p=>$v ) {
			if ($p!=$idfield) {
				if (!$this->isFrozen) {
					//Does the user want to specify the type?
					if ($bean->getMeta("cast.$p",-1)!==-1) {
						$cast = $bean->getMeta("cast.$p");
						if ($cast=="string") {
							$typeno = $this->writer->scanType("STRING");
						}
						elseif ($cast=="id") {
							$typeno = $this->writer->getTypeForID();
						}
						else {
							throw new RedBean_Exception("Invalid Cast");
						}
					}
					else {
						//What kind of property are we dealing with?
						$typeno = $this->writer->scanType($v);
					}

					//Is this property represented in the table?
					if (isset($columns[$p])) {
						//yes it is, does it still fit?
						$sqlt = $this->writer->code($columns[$p]);
						if ($typeno > $sqlt) {
							//no, we have to widen the database column type
							$this->writer->widenColumn( $table, $p, $typeno );
							$bean->setMeta("buildreport.flags.widen",true);
						}
					}
					else {
						//no it is not
						$this->writer->addColumn($table, $p, $typeno);
						$bean->setMeta("buildreport.flags.addcolumn",true);
						//@todo: move build commands here... more practical
						$this->processBuildCommands($table,$p,$bean);
					}
				}
				//Okay, now we are sure that the property value will fit
				$insertvalues[] = $v;
				$insertcolumns[] = $p;
				$updatevalues[] = array( "property"=>$p, "value"=>$v );
			}
		}

		if (!$this->isFrozen && ($uniques = $bean->getMeta("buildcommand.unique"))) {
			foreach($uniques as $unique) {
				$this->writer->addUniqueIndex( $table, $unique );
			}
		}

		$rs = $this->writer->updateRecord( $table, $updatevalues, $bean->$idfield );
		$bean->$idfield = $rs;


		$bean->setMeta("tainted",false);
		}




		if ($processLists) {

		foreach($embeddedBeans as $linkField=>$embeddedBean) {
			if (!$this->isFrozen) {

				$this->writer->addIndex($bean->getMeta('type'),
							'index_foreignkey_'.$embeddedBean->getMeta('type'),
							 $linkField);
				$this->writer->addFK($bean->getMeta('type'),$embeddedBean->getMeta('type'),$linkField,$this->writer->getIDField($embeddedBean->getMeta('type')));

			}
		}

		$myFieldLink = $bean->getMeta('type').'_id';
		//Handle related beans
		foreach($ownTrashcan as $trash) {
			if ($trash instanceof RedBean_OODBBean) {
				$trash->$myFieldLink = null;
				$this->store($trash);
			}
			else {
				throw new RedBean_Exception_Security('Array may only contain RedBean_OODBBeans');
			}
		}
		foreach($ownAdditions as $addition) {
			if ($addition instanceof RedBean_OODBBean) {
				$addition->$myFieldLink = $bean->$idfield;
				$addition->setMeta('cast.'.$myFieldLink,'id');
				$this->store($addition);
				if (!$this->isFrozen) {
					$this->writer->addIndex($addition->getMeta('type'),
						'index_foreignkey_'.$bean->getMeta('type'),
						 $myFieldLink);
					$this->writer->addFK($addition->getMeta('type'),$bean->getMeta('type'),$myFieldLink,$idfield);
				}
			}
			else {
				throw new RedBean_Exception_Security('Array may only contain RedBean_OODBBeans');
			}
		}
		foreach($ownresidue as $residue) {
			if ($residue instanceof RedBean_OODBBean) {
				if ($residue->getMeta('tainted')) {
					$this->store($residue);
				}
			}
			else {
				throw new RedBean_Exception_Security('Array may only contain RedBean_OODBBeans');
			}
		}
		foreach($sharedTrashcan as $trash) {
			if ($trash instanceof RedBean_OODBBean) {
				$this->assocManager->unassociate($trash,$bean);
			}
			else {
				throw new RedBean_Exception_Security('Array may only contain RedBean_OODBBeans');
			}
		}
		foreach($sharedAdditions as $addition) {
			if ($addition instanceof RedBean_OODBBean) {
				$this->assocManager->associate($addition,$bean);
			}
			else {
				throw new RedBean_Exception_Security('Array may only contain RedBean_OODBBeans');
			}
		}
		foreach($sharedresidue as $residue) {
			if ($residue instanceof RedBean_OODBBean) {
				$this->store($residue);
			}
			else {
				throw new RedBean_Exception_Security('Array may only contain RedBean_OODBBeans');
			}
		}
		}
		$this->signal( "after_update", $bean );
		return (int) $bean->$idfield;
	}

	/**
	 * Loads a bean from the object database.
	 * It searches for a RedBean_OODBBean Bean Object in the
	 * database. It does not matter how this bean has been stored.
	 * RedBean uses the primary key ID $id and the string $type
	 * to find the bean. The $type specifies what kind of bean you
	 * are looking for; this is the same type as used with the
	 * dispense() function. If RedBean finds the bean it will return
	 * the RedBean_OODB Bean object; if it cannot find the bean
	 * RedBean will return a new bean of type $type and with
	 * primary key ID 0. In the latter case it acts basically the
	 * same as dispense().
	 * If the bean cannot be found in the database a new bean of
	 * the specified type will be generated and returned.
	 * @param string $type
	 * @param integer $id
	 * @return RedBean_OODBBean $bean
	 */
	public function load($type, $id) {
		$this->signal("before_open",array("type"=>$type,"id"=>$id));

		$bean = $this->dispense( $type );
		if ($this->stash && isset($this->stash[$id])) {
			$row = $this->stash[$id];
		}
		else {
			try {
				$idfield = $this->writer->getIDField($type);
				$rows = $this->writer->selectRecord($type,array($idfield=>array($id)));

			}catch(RedBean_Exception_SQL $e ) {
				if (
				$this->writer->sqlStateIn($e->getSQLState(),
				array(
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
				)

				) {
					$rows = 0;
					if ($this->isFrozen) throw $e; //only throw if frozen;
				}
				else throw $e;
			}
			if (!$rows) return $bean; // $this->dispense($type); -- no need...
			$row = array_pop($rows);
		}

		foreach($row as $p=>$v) {
			//populate the bean with the database row
			$bean->$p = $v;
		}
		$this->signal( "open", $bean );
		$bean->setMeta("tainted",false);

		return $bean;
	}

	/**
	 * Removes a bean from the database.
	 * This function will remove the specified RedBean_OODBBean
	 * Bean Object from the database.
	 * @throws RedBean_Exception_Security $exception
	 * @param RedBean_OODBBean $bean
	 */
	public function trash( RedBean_OODBBean $bean ) {
		$idfield = $this->writer->getIDField($bean->getMeta("type"));

		$this->signal( "delete", $bean );

		foreach($bean as $p=>$v) {



			if ($v instanceof RedBean_OODBBean) {
				$bean->removeProperty($p);
			}
			if (is_array($v)) {
				if (strpos($p,'own')===0) {
					$bean->removeProperty($p);
				}
				elseif (strpos($p,'shared')===0) {
					$bean->removeProperty($p);
				}
			}
		}

		if (!$this->isFrozen) $this->check( $bean );
		try {
			$this->writer->selectRecord($bean->getMeta("type"),
				array($idfield => array( $bean->$idfield) ),null,true );
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
		}
		$bean->$idfield = 0;

		$this->signal( "after_delete", $bean );

	}

	/**
	 * Loads and returns a series of beans of type $type.
	 * The beans are loaded all at once.
	 * The beans are retrieved using their primary key IDs
	 * specified in the second argument.
	 * @throws RedBean_Exception_Security $exception
	 * @param string $type
	 * @param array $ids
	 * @return array $beans
	 */
	public function batch( $type, $ids ) {
		if (!$ids) return array();
		$collection = array();
		try {
			$idfield = $this->writer->getIDField($type);
			$rows = $this->writer->selectRecord($type,array($idfield=>$ids));
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;

			$rows = false;
		}
		$this->stash = array();
		if (!$rows) return array();
		foreach($rows as $row) {
			$this->stash[$row[$this->writer->getIDField($type)]] = $row;
		}
		foreach($ids as $id) {
			$collection[ $id ] = $this->load( $type, $id );
		}
		$this->stash = NULL;
		return $collection;
	}

	/**
	 * This is a convenience method; it converts database rows
	 * (arrays) into beans.
	 * @param string $type
	 * @param array $rows
	 * @return array $collectionOfBeans
	 */
	public function convertToBeans($type, $rows) {
		$collection = array();
		$this->stash = array();
		foreach($rows as $row) {
			$id = $row[$this->writer->getIDField($type)];
			$this->stash[$id] = $row;
			$collection[ $id ] = $this->load( $type, $id );

		}
		$this->stash = NULL;
		return $collection;
	}

	/**
	 * Returns the number of beans we have in DB of a given type.
	 *
	 * @param string $type type of bean we are looking for
	 *
	 * @return integer $num number of beans found
	 */
	public function count($type) {
		try {
			return (int) $this->writer->count($type);
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
		}
		return 0;
	}

	/**
	 * Trash all beans of a given type.
	 *
	 * @param string $type type
	 *
	 * @return boolean $yesNo whether we actually did some work or not..
	 */
	public function wipe($type) {
		try {
			$this->writer->wipe($type);
			return true;
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
		}
		return false;
	}


	/**
	 * Returns an Association Manager for use with OODB.
	 *
	 * @throws Exception
	 * @return RedBean_AssociationManager $assoc Assoction Manager
	 */
	public function getAssociationManager() {
		if (!isset($this->assocManager)) throw new Exception("No association manager available.");
		return $this->assocManager;
	}

	/**
	 * @param RedBean_AssociationManager $assoc
	 * @return void
	 */
	public function setAssociationManager(RedBean_AssociationManager $assoc) {
		$this->assocManager = $assoc;
	}

}


