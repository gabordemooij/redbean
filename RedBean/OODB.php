<?php
/**
 * RedBean Object Oriented DataBase
 * 
 * @name 		RedBean OODB
 * @file 		RedBean/OODB.php
 * @author 		Gabor de Mooij and the RedBean Team
 * @copyright 	Gabor de Mooij (c)
 * @license 	BSD
 *
 * The RedBean OODB Class is the main class of RedBeanPHP.
 * It takes RedBean_OODBBean objects and stores them to and loads them from the
 * database as well as providing other CRUD functions. This class acts as a
 * object database.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_OODB extends RedBean_Observable {
	
	private $dep = array();

	/**
	 * Secret stash. Used for batch loading.
	 * @var array
	 */
	private $stash = NULL;

	/**
	 * Contains the writer for OODB.
	 * @var RedBean_Adapter_DBAdapter
	 */
	private $writer;
	/**
	 * Whether this instance of OODB is frozen or not.
	 * In frozen mode the schema will not de modified, in fluid mode
	 * the schema can be adjusted to meet the needs of the developer.
	 * @var boolean
	 */
	private $isFrozen = false;

	/**
	 * Bean Helper. The bean helper to give to the beans. Bean Helpers
	 * assist beans in getting hold of a toolbox.
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
	 * 
	 * @return boolean $yesNo TRUE if frozen, FALSE otherwise
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
	 * 
	 * @param string $type type of bean you want to dispense
	 * 
	 * @return RedBean_OODBBean $bean the new bean instance
	 */
	public function dispense($type ) {
		$this->signal( 'before_dispense', $type );
		$bean = new RedBean_OODBBean();
		$bean->setBeanHelper($this->beanhelper);
		$bean->setMeta('type',$type );
		$bean->setMeta('sys.id','id');
		$bean->id = 0;
		if (!$this->isFrozen) $this->check( $bean );
		$bean->setMeta('tainted',true);
		$this->signal('dispense',$bean );
		return $bean;
	}

	/**
	 * Sets bean helper to be given to beans.
	 * Bean helpers assist beans in getting a reference to a toolbox.
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
	 * 
	 * @param RedBean_OODBBean $bean the bean that needs to be checked
	 * 
	 * @return void
	 */
	public function check( RedBean_OODBBean $bean ) {
		//Is all meta information present?
		if (!isset($bean->id) ) {
			throw new RedBean_Exception_Security("Bean has incomplete Meta Information id ");
		}
		if (!($bean->getMeta("type"))) {
			throw new RedBean_Exception_Security('Bean has incomplete Meta Information II');
		}
		//Pattern of allowed characters
		$pattern = '/[^abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_]/';
		//Does the type contain invalid characters?
		if (preg_match($pattern,$bean->getMeta('type'))) {
			throw new RedBean_Exception_Security('Bean Type is invalid');
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
	 * Searches the database for a bean that matches conditions $conditions and sql $addSQL
	 * and returns an array containing all the beans that have been found.
	 * 
	 * Conditions need to take form:
	 * 
	 * array(
	 * 		'PROPERTY' => array( POSSIBLE VALUES... 'John','Steve' )
	 * 		'PROPERTY' => array( POSSIBLE VALUES... )
	 * );
	 * 
	 * All conditions are glued together using the AND-operator, while all value lists
	 * are glued using IN-operators thus acting as OR-conditions.
	 * 
	 * Note that you can use property names; the columns will be extracted using the
	 * appropriate bean formatter.
	 * 
	 * @throws RedBean_Exception_SQL 
	 * 
	 * @param string $type       type of beans you are looking for
	 * @param array  $conditions list of conditions
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
	 * 
	 * @param string $table table name (not type!)
	 * 
	 * @return boolean $exists whether the given table exists within this database.
	 */
	public function tableExists($table) {
		//does this table exist?
		$tables = $this->writer->getTables();
		return in_array(($table), $tables);
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
		if ($inx = ($bean->getMeta('buildcommand.indexes'))) {
			if (isset($inx[$property])) $this->writer->addIndex($table,$inx[$property],$property);
		}
	}



	/**
	 * Process groups. Internal function. Processes different kind of groups for
	 * storage function. Given a list of original beans and a list of current beans,
	 * this function calculates which beans remain in the list (residue), which
	 * have been deleted (are in the trashcan) and which beans have been added
	 * (additions). 
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
	 * 
	 * @param RedBean_OODBBean $bean bean to store
	 *
	 * @return integer $newid resulting ID of the new bean
	 */
	public function store( RedBean_OODBBean $bean ) {

		$processLists = false;

		foreach($bean as $k=>$v) {
			if (is_array($v) || is_object($v)) { $processLists = true; break; }
		}

		if (!$processLists && !$bean->getMeta('tainted')) return $bean->getID();
		$this->signal('update', $bean );
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
					if (!$v->id || $v->getMeta('tainted')) {
						$this->store($v);
					}
					$beanID = $v->id;
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
		if ($bean->getMeta('tainted')) {
			//Does table exist? If not, create
			if (!$this->isFrozen && !$this->tableExists($table)) {
				$this->writer->createTable( $table );
				$bean->setMeta('buildreport.flags.created',true);
			}
			if (!$this->isFrozen) {
				$columns = $this->writer->getColumns($table) ;
			}
			//does the table fit?
			$insertvalues = array();
			$insertcolumns = array();
			$updatevalues = array();
			foreach( $bean as $p=>$v ) {
				$origV = $v;
				if ($p!='id') {
					if (!$this->isFrozen) {
						//Does the user want to specify the type?
						if ($bean->getMeta("cast.$p",-1)!==-1) {
							$cast = $bean->getMeta("cast.$p");
							if ($cast=='string') {
								$typeno = $this->writer->scanType('STRING');
							}
							elseif ($cast=='id') {
								$typeno = $this->writer->getTypeForID();
							}
							elseif(isset($this->writer->sqltype_typeno[$cast])) {
								$typeno = $this->writer->sqltype_typeno[$cast];
							}
							else {
								throw new RedBean_Exception('Invalid Cast');
							}
						}
						else {
							$cast = false;		
							//What kind of property are we dealing with?
							$typeno = $this->writer->scanType($v,true);
							$v = $this->writer->getValue();
						}
						//Is this property represented in the table?
						if (isset($columns[$p])) {
							//rescan
							$v = $origV;
							if (!$cast) $typeno = $this->writer->scanType($v,false);
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
			$rs = $this->writer->updateRecord( $table, $updatevalues, $bean->id );
			$bean->id = $rs;
			$bean->setMeta("tainted",false);
		}

		if ($processLists) {
			foreach($embeddedBeans as $linkField=>$embeddedBean) {
				if (!$this->isFrozen) {
					$this->writer->addIndex($bean->getMeta('type'),
								'index_foreignkey_'.$embeddedBean->getMeta('type'),
								 $linkField);
					$this->writer->addFK($bean->getMeta('type'),$embeddedBean->getMeta('type'),$linkField,'id');
	
				}
			}
	
			$myFieldLink = $bean->getMeta('type').'_id';
			//Handle related beans
			foreach($ownTrashcan as $trash) {
			if (isset($this->dep[$trash->getMeta('type')]) && in_array($bean->getMeta('type'),$this->dep[$trash->getMeta('type')])) {


					   $this->trash($trash);
			   }
			   else {
					   $trash->$myFieldLink = null;
					   $this->store($trash);
			   }
			}
			foreach($ownAdditions as $addition) {
				if ($addition instanceof RedBean_OODBBean) {
					$addition->$myFieldLink = $bean->id;
					$addition->setMeta('cast.'.$myFieldLink,'id');
					$this->store($addition);
					if (!$this->isFrozen) {
						$this->writer->addIndex($addition->getMeta('type'),
							'index_foreignkey_'.$bean->getMeta('type'),
							 $myFieldLink);
						$this->writer->addFK($addition->getMeta('type'),$bean->getMeta('type'),$myFieldLink,'id');
					}
				}
				else {
					throw new RedBean_Exception_Security('Array may only contain RedBean_OODBBeans');
				}
			}
			foreach($ownresidue as $residue) {
				if ($residue->getMeta('tainted')) {
					$this->store($residue);
				}
			}
			foreach($sharedTrashcan as $trash) {
				$this->assocManager->unassociate($trash,$bean);
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
				$this->store($residue);
			}
		}
		$this->signal('after_update',$bean);
		return (int) $bean->id;
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
	 * 
	 * Important note:
	 * If the bean cannot be found in the database a new bean of
	 * the specified type will be generated and returned.
	 * 
	 * @param string  $type type of bean you want to load
	 * @param integer $id   ID of the bean you want to load
	 * 
	 * @return RedBean_OODBBean $bean loaded bean
	 */
	public function load($type,$id) {
		$this->signal('before_open',array('type'=>$type,'id'=>$id));
		$bean = $this->dispense( $type );
		if ($this->stash && isset($this->stash[$id])) {
			$row = $this->stash[$id];
		}
		else {
			try {
				$rows = $this->writer->selectRecord($type,array('id'=>array($id)));
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
			}
			if (!$rows) return $bean; // $this->dispense($type); -- no need...
			$row = array_pop($rows);
		}
		foreach($row as $p=>$v) {
			//populate the bean with the database row
			$bean->$p = $v;
		}
		$this->signal('open',$bean );
		$bean->setMeta('tainted',false);
		return $bean;
	}

	/**
	 * Removes a bean from the database.
	 * This function will remove the specified RedBean_OODBBean
	 * Bean Object from the database.
	 * 
	 * @throws RedBean_Exception_Security $exception
	 * 
	 * @param RedBean_OODBBean $bean bean you want to remove from database
	 */
	public function trash( RedBean_OODBBean $bean ) {
		$this->signal('delete',$bean);
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
			$this->writer->selectRecord($bean->getMeta('type'),
				array('id' => array( $bean->id) ),null,true );
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
		}
		$bean->id = 0;
		$this->signal('after_delete', $bean );
	}

	/**
	 * Returns an array of beans. Pass a type and a series of ids and
	 * this method will bring you the correspondig beans.
	 * 
	 * important note: Because this method loads beans using the load()
	 * function (but faster) it will return empty beans with ID 0 for 
	 * every bean that could not be located. The resulting beans will have the
	 * passed IDs as their keys.
	 *
	 * @param string $type type of beans 
	 * @param array  $ids  ids to load
	 *
	 * @return array $beans resulting beans (may include empty ones)
	 */
	public function batch( $type, $ids ) {
		if (!$ids) return array();
		$collection = array();
		try {
			$rows = $this->writer->selectRecord($type,array('id'=>$ids));
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
			$this->stash[$row['id']] = $row;
		}
		foreach($ids as $id) {
			$collection[ $id ] = $this->load( $type, $id );
		}
		$this->stash = NULL;
		return $collection;
	}

	/**
	 * This is a convenience method; it converts database rows
	 * (arrays) into beans. Given a type and a set of rows this method
	 * will return an array of beans of the specified type loaded with
	 * the data fields provided by the result set from the database.
	 * 
	 * @param string $type type of beans you would like to have
	 * @param array  $rows rows from the database result
	 * 
	 * @return array $collectionOfBeans collection of beans
	 */
	public function convertToBeans($type, $rows) {
		$collection = array();
		$this->stash = array();
		foreach($rows as $row) {
			$id = $row['id'];
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
			return false;
		}
	}

	/**
	 * Returns an Association Manager for use with OODB.
	 * A simple getter function to obtain a reference to the association manager used for
	 * storage and more.
	 *
	 * @throws Exception
	 * @return RedBean_AssociationManager $assoc Association Manager
	 */
	public function getAssociationManager() {
		if (!isset($this->assocManager)) throw new Exception('No association manager available.');
		return $this->assocManager;
	}

	/**
	 * Sets the association manager instance to be used by this OODB.
	 * A simple setter function to set the association manager to be used for storage and
	 * more.
	 * 
	 * @param RedBean_AssociationManager $assoc sets the association manager to be used
	 * 
	 * @return void
	 */
	public function setAssociationManager(RedBean_AssociationManager $assoc) {
		$this->assocManager = $assoc;
	}
	
	
	public function setDepList($dep) {
		$this->dep = $dep;
	}

}


