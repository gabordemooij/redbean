<?php
/**
 * RedBean Object Oriented DataBase
 * 
 * @file			RedBean/OODB.php
 * @desc			RedBean OODB
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 * The RedBean OODB Class is the main class of RedBeanPHP.
 * It takes RedBean_OODBBean objects and stores them to and loads them from the
 * database as well as providing other CRUD functions. This class acts as a
 * object database.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_OODB extends RedBean_Observable {
	/**
	 * @var array 
	 */
	protected $chillList = array();
	/**
	 * @var array
	 */
	protected $dep = array();
	/**
	 * @var array
	 */
	protected $stash = NULL;
	/*
	 * @var integer
	 * Keeps track of the nesting level of the OODB object
	 */
	protected $nesting = 0;
	/**
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $writer;
	/**
	 * @var boolean
	 */
	protected $isFrozen = false;
	/**
	 * @var null|\RedBean_BeanHelperFacade
	 */
	protected $beanhelper = null;
	/**
	 * @var RedBean_AssociationManager
	 */
	protected $assocManager = null;
	/**
	 * Constructor, requires a query writer.
	 *
	 * @param RedBean_QueryWriter $writer
	 */
	public function __construct(RedBean_QueryWriter $writer) {
		if ($writer instanceof RedBean_QueryWriter) $this->writer = $writer;
		$this->beanhelper = new RedBean_BeanHelper_Facade();
	}
	/**
	 * Toggles fluid or frozen mode. In fluid mode the database
	 * structure is adjusted to accomodate your objects. In frozen mode
	 * this is not the case.
	 * 
	 * You can also pass an array containing a selection of frozen types.
	 * Let's call this chilly mode, it's just like fluid mode except that
	 * certain types (i.e. tables) aren't touched.
	 * 
	 * @param boolean|array $trueFalse
	 */
	public function freeze($tf) {
		if (is_array($tf)) {
			$this->chillList = $tf;
			$this->isFrozen = false;
		} else $this->isFrozen = (boolean) $tf;
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
	 * @param string $type   type of bean you want to dispense
	 * @param string $number number of beans you would like to get
	 * 
	 * @return RedBean_OODBBean $bean the new bean instance
	 */
	public function dispense($type, $number = 1) {
		$beans = array();
		for($i = 0; $i < $number; $i++){
			$bean = new RedBean_OODBBean;
			$bean->setBeanHelper($this->beanhelper);
			$bean->setMeta('type', $type );
			$bean->setMeta('sys.id', 'id');
			$bean->id = 0;
			if (!$this->isFrozen) $this->check($bean);
			$bean->setMeta('tainted', true);
			$bean->setMeta('sys.orig', array('id' => 0));
			$this->signal('dispense', $bean );
			$beans[] = $bean;
		}
		return (count($beans) === 1) ? array_pop($beans) : $beans; 
	}
	/**
	 * Sets bean helper to be given to beans.
	 * Bean helpers assist beans in getting a reference to a toolbox.
	 *
	 * @param RedBean_IBeanHelper $beanhelper helper
	 *
	 * @return void
	 */
	public function setBeanHelper(RedBean_BeanHelper $beanhelper) {
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
	public function check(RedBean_OODBBean $bean) {
		//Is all meta information present?
		if (!isset($bean->id) ) {
			throw new RedBean_Exception_Security('Bean has incomplete Meta Information id ');
		}
		if (!is_numeric($bean->id)) {
			throw new RedBean_Exception_Security('ID must be numeric.');
		}
		if (!($bean->getMeta('type'))) {
			throw new RedBean_Exception_Security('Bean has incomplete Meta Information II');
		}
		//Pattern of allowed characters
		$pattern = '/[^a-z0-9_]/i';
		//Does the type contain invalid characters?
		if (preg_match($pattern, $bean->getMeta('type'))) {
			throw new RedBean_Exception_Security('Bean Type is invalid');
		}
		//Are the properties and values valid?
		foreach($bean as $prop => $value) {
			if (
					  is_array($value) ||
					  (is_object($value)) ||
					  strlen($prop) < 1 ||
					  preg_match($pattern, $prop)
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
	 * 		'PROPERTY' => array( POSSIBLE VALUES... 'John', 'Steve' )
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
	 * @param string  $type       type of beans you are looking for
	 * @param array   $conditions list of conditions
	 * @param string  $addSQL	  SQL to be used in query
	 * @param boolean $all        whether you prefer to use a WHERE clause or not (TRUE = not)
	 * 
	 * @return array $beans		  resulting beans
	 */
	public function find($type, $conditions = array(), $addSQL = null, $all = false) {
		try {
			$beans = $this->convertToBeans($type, $this->writer->selectRecord($type, $conditions, $addSQL, false, false, $all));
			return $beans;
		} catch(RedBean_Exception_SQL $e) {
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
			if (isset($inx[$property])) $this->writer->addIndex($table, $inx[$property], $property);
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
	private function processGroups($originals, $current, $additions, $trashcan, $residue) {
		return array(
			array_merge($additions, array_diff($current, $originals)),
			array_merge($trashcan, array_diff($originals, $current)),
			array_merge($residue, array_intersect($current, $originals))
		);
	}
	/**
	 * Figures out the desired type given the cast string ID.
	 * 
	 * @param string $cast cast identifier
	 * 
	 * @return integer $typeno 
	 */
	private function getTypeFromCast($cast) {
		if ($cast == 'string') {
			$typeno = $this->writer->scanType('STRING');
		} elseif ($cast == 'id') {
			$typeno = $this->writer->getTypeForID();
		} elseif(isset($this->writer->sqltype_typeno[$cast])) {
			$typeno = $this->writer->sqltype_typeno[$cast];
		} else {
			throw new RedBean_Exception('Invalid Cast');
		}
		return $typeno;
	}
	/**
	 * Processes an embedded bean. First the bean gets unboxed if possible.
	 * Then, the bean is stored if needed and finally the ID of the bean
	 * will be returned.
	 * 
	 * @param RedBean_OODBBean|Model $v the bean or model
	 * 
	 * @return  integer $id
	 */
	private function prepareEmbeddedBean($v) {
		if (!$v->id || $v->getMeta('tainted')) {
			$this->store($v);
		}
		return $v->id;
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
	 * @param RedBean_OODBBean | RedBean_SimpleModel $bean bean to store
	 *
	 * @return integer $newid resulting ID of the new bean
	 */
	public function store($bean) { 
		if ($bean instanceof RedBean_SimpleModel) $bean = $bean->unbox();
		if (!($bean instanceof RedBean_OODBBean)) throw new RedBean_Exception_Security('OODB Store requires a bean, got: '.gettype($bean));
		$processLists = false;
		foreach($bean as $k => $v) if (is_array($v) || is_object($v)) { $processLists = true; break; }
		if (!$processLists && !$bean->getMeta('tainted')) return $bean->getID();
		$this->signal('update', $bean );
		foreach($bean as $k => $v) if (is_array($v) || is_object($v)) { $processLists = true; break; }
		if ($processLists) {
			//Define groups
			$sharedAdditions = $sharedTrashcan = $sharedresidue = $sharedItems = array();
			$ownAdditions = $ownTrashcan = $ownresidue = array();
			$tmpCollectionStore = array();
			$embeddedBeans = array();
			foreach($bean as $p => $v) {
				if ($v instanceof RedBean_SimpleModel) $v = $v->unbox(); 
				if ($v instanceof RedBean_OODBBean) {
					$linkField = $p.'_id';
					$bean->$linkField = $this->prepareEmbeddedBean($v);
					$bean->setMeta('cast.'.$linkField, 'id');
					$embeddedBeans[$linkField] = $v;
					$tmpCollectionStore[$p] = $bean->$p;
					$bean->removeProperty($p);
				}
				if (is_array($v)) {
					$originals = $bean->getMeta('sys.shadow.'.$p);
					if (!$originals) $originals = array();
					if (strpos($p, 'own') === 0) {
						list($ownAdditions, $ownTrashcan, $ownresidue) = $this->processGroups($originals, $v, $ownAdditions, $ownTrashcan, $ownresidue);
						$bean->removeProperty($p);
					} elseif (strpos($p, 'shared') === 0) {
						list($sharedAdditions, $sharedTrashcan, $sharedresidue) = $this->processGroups($originals, $v, $sharedAdditions, $sharedTrashcan, $sharedresidue);
						$bean->removeProperty($p);
					} else {}
				}
			}
		}
		$this->storeBean($bean);
		if ($processLists) {
			$this->processEmbeddedBeans($bean, $embeddedBeans);
			$myFieldLink = $bean->getMeta('type').'_id';
			$this->processTrashcan($bean, $ownTrashcan);
			$this->processAdditions($bean, $ownAdditions);
			$this->processResidue($ownresidue);
			foreach($sharedTrashcan as $trash) $this->assocManager->unassociate($trash, $bean);
			$this->processSharedAdditions($bean, $sharedAdditions);
			foreach($sharedresidue as $residue) $this->store($residue);
		}
		$this->signal('after_update', $bean);
		return (int) $bean->id;
	}
	/**
	 * Stores a cleaned bean; i.e. only scalar values. This is the core of the store()
	 * method. When all lists and embedded beans (parent objects) have been processed and
	 * removed from the original bean the bean is passed to this method to be stored
	 * in the database.
	 * 
	 * @param RedBean_OODBBean $bean the clean bean 
	 */
	private function storeBean(RedBean_OODBBean $bean) {
		if (!$this->isFrozen) $this->check($bean);
		//what table does it want
		$table = $bean->getMeta('type');
		if ($bean->getMeta('tainted')) {
			//Does table exist? If not, create
			if (!$this->isFrozen && !$this->tableExists($this->writer->esc($table, true))) {
				$this->writer->createTable($table);
				$bean->setMeta('buildreport.flags.created', true);
			}
			if (!$this->isFrozen) {
				$columns = $this->writer->getColumns($table) ;
			}
			//does the table fit?
			$insertvalues = array();
			$insertcolumns = array();
			$updatevalues = array();
			foreach($bean as $p => $v) {
				$origV = $v;
				if ($p !== 'id') {
					if (!$this->isFrozen) {
						//Not in the chill list?
						if (!in_array($bean->getMeta('type'), $this->chillList)) {
							//Does the user want to specify the type?
							if ($bean->getMeta("cast.$p", -1) !== -1) {
								$cast = $bean->getMeta("cast.$p");
								$typeno = $this->getTypeFromCast($cast);
							} else {
								$cast = false;		
								//What kind of property are we dealing with?
								$typeno = $this->writer->scanType($v, true);
							}
							//Is this property represented in the table?
							if (isset($columns[$this->writer->esc($p, true)])) {
								//rescan
								$v = $origV;
								if (!$cast) $typeno = $this->writer->scanType($v, false);
								//yes it is, does it still fit?
								$sqlt = $this->writer->code($columns[$this->writer->esc($p, true)]);
								if ($typeno > $sqlt) {
									//no, we have to widen the database column type
									$this->writer->widenColumn($table, $p, $typeno);
									$bean->setMeta('buildreport.flags.widen', true);
								}
							} else {
								//no it is not
								$this->writer->addColumn($table, $p, $typeno);
								$bean->setMeta('buildreport.flags.addcolumn', true);
								//@todo: move build commands here... more practical
								$this->processBuildCommands($table, $p, $bean);
							}
						}
					}
					//Okay, now we are sure that the property value will fit
					$insertvalues[] = $v;
					$insertcolumns[] = $p;
					$updatevalues[] = array('property' => $p, 'value' => $v);
				}
			}
			if (!$this->isFrozen && ($uniques = $bean->getMeta('buildcommand.unique'))) {
				foreach($uniques as $unique) $this->writer->addUniqueIndex($table, $unique);
			}
			$rs = $this->writer->updateRecord($table, $updatevalues, $bean->id);
			$bean->id = $rs;
			$bean->setMeta('tainted', false);
		}
	}
	/**
	 * Processes a list of beans from a bean. A bean may contain lists. This
	 * method handles shared addition lists; i.e. the $bean->sharedObject properties.
	 * 
	 * @param RedBean_OODBBean $bean             the bean
	 * @param array            $sharedAdditions  list with shared additions
	 */
	private function processSharedAdditions($bean, $sharedAdditions) {
		foreach($sharedAdditions as $addition) {
			if ($addition instanceof RedBean_OODBBean) {
				$this->assocManager->associate($addition, $bean);
			} else {
				throw new RedBean_Exception_Security('Array may only contain RedBean_OODBBeans');
			}
		}
	}
	/**
	 * Processes a list of beans from a bean. A bean may contain lists. This
	 * method handles own lists; i.e. the $bean->ownObject properties.
	 * A residue is a bean in an own-list that stays where it is. This method
	 * checks if there have been any modification to this bean, in that case
	 * the bean is stored once again, otherwise the bean will be left untouched.
	 *  
	 * @param RedBean_OODBBean $bean       the bean
	 * @param array            $ownresidue list 
	 */
	private function processResidue($ownresidue) {
		foreach($ownresidue as $residue) {
			if ($residue->getMeta('tainted')) {
				$this->store($residue);
			}
		}
	}
	/**
	 * Processes a list of beans from a bean. A bean may contain lists. This
	 * method handles own lists; i.e. the $bean->ownObject properties.
	 * A trash can bean is a bean in an own-list that has been removed 
	 * (when checked with the shadow). This method
	 * checks if the bean is also in the dependency list. If it is the bean will be removed.
	 * If not, the connection between the bean and the owner bean will be broken by
	 * setting the ID to NULL.
	 *  
	 * @param RedBean_OODBBean $bean        the bean
	 * @param array            $ownTrashcan list 
	 */
	private function processTrashcan($bean, $ownTrashcan) {
		$myFieldLink = $bean->getMeta('type').'_id';
		if (is_array($ownTrashcan) && count($ownTrashcan)>0) {
			$first = reset($ownTrashcan);
			if ($first instanceof RedBean_OODBBean) {
				$alias = $bean->getMeta('sys.alias.'.$first->getMeta('type'));
				if ($alias) $myFieldLink = $alias.'_id';
			}
		}
		foreach($ownTrashcan as $trash) {
			if (isset($this->dep[$trash->getMeta('type')]) && in_array($bean->getMeta('type'), $this->dep[$trash->getMeta('type')])) {
				$this->trash($trash);
			} else {
				$trash->$myFieldLink = null;
				$this->store($trash);
			}
		}
	}
	/**
	 * Processes embedded beans.
	 * Each embedded bean will be indexed and foreign keys will
	 * be created if the bean is in the dependency list.
	 * 
	 * @param RedBean_OODBBean $bean          bean
	 * @param array            $embeddedBeans embedded beans
	 */
	private function processEmbeddedBeans($bean, $embeddedBeans) {
		foreach($embeddedBeans as $linkField => $embeddedBean) {
			if (!$this->isFrozen) {
				$this->writer->addIndex($bean->getMeta('type'),
							'index_foreignkey_'.$bean->getMeta('type').'_'.$embeddedBean->getMeta('type'),
							 $linkField);
				$isDep = $this->isDependentOn($bean->getMeta('type'), $embeddedBean->getMeta('type'));
				$this->writer->addFK($bean->getMeta('type'), $embeddedBean->getMeta('type'), $linkField, 'id', $isDep);
			}
		}	
	}
	/**
	 * Part of the store() functionality.
	 * Handles all new additions after the bean has been saved.
	 * Stores addition bean in own-list, extracts the id and
	 * adds a foreign key. Also adds a constraint in case the type is
	 * in the dependent list.
	 * 
	 * @param RedBean_OODBBean $bean         bean
	 * @param array            $ownAdditions list of addition beans in own-list 
	 */
	private function processAdditions($bean, $ownAdditions) {
		$myFieldLink = $bean->getMeta('type').'_id';
		if ($bean && count($ownAdditions)>0) {
			$first = reset($ownAdditions);
			if ($first instanceof RedBean_OODBBean) {
				$alias = $bean->getMeta('sys.alias.'.$first->getMeta('type'));
				if ($alias) $myFieldLink = $alias.'_id';
			}
		}
		foreach($ownAdditions as $addition) {
			if ($addition instanceof RedBean_OODBBean) {  
				$addition->$myFieldLink = $bean->id;
				$addition->setMeta('cast.'.$myFieldLink, 'id');
				$this->store($addition);
				if (!$this->isFrozen) {
					$this->writer->addIndex($addition->getMeta('type'),
						'index_foreignkey_'.$addition->getMeta('type').'_'.$bean->getMeta('type'),
						 $myFieldLink);
					$isDep = $this->isDependentOn($addition->getMeta('type'), $bean->getMeta('type'));
					$this->writer->addFK($addition->getMeta('type'), $bean->getMeta('type'), $myFieldLink, 'id', $isDep);
				}
			} else {
				throw new RedBean_Exception_Security('Array may only contain RedBean_OODBBeans');
			}
		}
	}
	/**
	 * Checks whether reference type has been marked as dependent on target type.
	 * This is the result of setting reference type as a key in R::dependencies() and
	 * putting target type in its array. 
	 * 
	 * @param string $refType   reference type
	 * @param string $otherType other type / target type
	 * 
	 * @return boolean 
	 */
	protected function isDependentOn($refType, $otherType) {
		return (boolean) (isset($this->dep[$refType]) && in_array($otherType, $this->dep[$refType]));
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
	public function load($type, $id) {
		$bean = $this->dispense($type);
		if (isset($this->stash[$this->nesting][$id])) {
			$row = $this->stash[$this->nesting][$id];
		} else {
			try {
				$rows = $this->writer->selectRecord($type, array('id' => array($id)));
			} catch(RedBean_Exception_SQL $e ) {
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
			if (empty($rows)) return $bean;
			$row = array_pop($rows);
		}
		$bean->setMeta('sys.orig', $row);
		foreach($row as $p => $v) {
			$bean->$p = $v;
		}
		$this->nesting ++;
		$this->signal('open', $bean);
		$this->nesting --;
		$bean->setMeta('tainted', false);
		return $bean;
	}
	/**
	 * Removes a bean from the database.
	 * This function will remove the specified RedBean_OODBBean
	 * Bean Object from the database.
	 * 
	 * @throws RedBean_Exception_Security $exception
	 * 
	 * @param RedBean_OODBBean|RedBean_SimpleModel $bean bean you want to remove from database
	 */
	public function trash($bean) {
		if ($bean instanceof RedBean_SimpleModel) $bean = $bean->unbox();
		if (!($bean instanceof RedBean_OODBBean)) throw new RedBean_Exception_Security('OODB Store requires a bean, got: '.gettype($bean));
		$this->signal('delete', $bean);
		foreach($bean as $p => $v) {
			if ($v instanceof RedBean_OODBBean) {
				$bean->removeProperty($p);
			}
			if (is_array($v)) {
				if (strpos($p, 'own') === 0) {
					$bean->removeProperty($p);
				} elseif (strpos($p, 'shared') === 0) {
					$bean->removeProperty($p);
				}
			}
		}
		if (!$this->isFrozen) $this->check($bean);
		try {
			$this->writer->selectRecord($bean->getMeta('type'),
				array('id' => array($bean->id)), null, true );
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
		}
		$bean->id = 0;
		$this->signal('after_delete', $bean);
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
	public function batch($type, $ids) {
		if (!$ids) return array();
		$collection = array();
		try {
			$rows = $this->writer->selectRecord($type, array('id' => $ids));
		} catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
			$rows = false;
		}
		$this->stash[$this->nesting] = array();
		if (!$rows) return array();
		foreach($rows as $row) {
			$this->stash[$this->nesting][$row['id']] = $row;
		}
		foreach($ids as $id) {
			$collection[$id] = $this->load($type, $id);
		}
		$this->stash[$this->nesting] = NULL;
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
		$this->stash[$this->nesting] = array();
		foreach($rows as $row) {
			$id = $row['id'];
			$this->stash[$this->nesting][$id] = $row;
			$collection[$id] = $this->load($type, $id);
		}
		$this->stash[$this->nesting] = NULL;
		return $collection;
	}
	/**
	 * Returns the number of beans we have in DB of a given type.
	 *
	 * @param string $type   type of bean we are looking for
	 * @param string $addSQL additional SQL snippet
	 * @param array  $params parameters to bind to SQL
	 *
	 * @return integer $num number of beans found
	 */
	public function count($type, $addSQL = '', $params = array()) {
		try {
			return (int) $this->writer->count($type, $addSQL, $params);
		} catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),array(RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE))) throw $e;
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
		} catch(RedBean_Exception_SQL $e) {
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
	/**
	 * Sets a dependency list. Dependencies can be used to make
	 * certain beans depend on others. This causes dependent beans to get removed
	 * once the bean they depend on has been removed as well.
	 * A dependency takes the form:
	 * 
	 * $me => depends on array( $bean1, $bean2 )
	 * 
	 * For instance a to inform RedBeanPHP about the fact that a page
	 * depends on a book:
	 * 
	 * 'page' => array('book')
	 * 
	 * A bean can depend on multiple other beans.
	 * 
	 * A dependency does two things:
	 * 
	 * 1. Adds a ON CASCADE DELETE 
	 * 2. trashes the depending bean if the entry in the ownList is removed 
	 * 
	 * @param array $dep 
	 */
	public function setDepList($dep) {
		$this->dep = $dep;
	}
	/**
	 * Preloads certain properties for beans.
	 * Understands aliases.
	 * 
	 * Usage: $redbean->preload($books, array('coauthor'=>'author'));
	 * 
	 * Usage for nested beans:
	 * 
	 * $redbean->preload($texts, array('page', 'page.book', 'page.book.author'));
	 * 
	 * preloads pages, books and authors.
	 * You may also use a shortcut here: 
	 * 
	 * $redbean->preload($texts, array('page', '*.book', '*.author'));
	 * 
	 * Can also load preload lists:
	 * 
	 * $redbean->preload($books, array('ownPage'=>'page', '*.ownText'=>'text', 'sharedTag'=>'tag'));
	 * 
	 * @param array $beans beans
	 * @param array $types types to load
	 */
	public function preload($beans, $types, $closure = null) {
		if (is_string($types)) $types = explode(',', $types);
		$oldFields = array(); $i = 0; $retrievals = array(); $oldField = '';
		foreach($types as $key => $typeInfo) {
			list($type,$sqlObj) = (is_array($typeInfo) ? $typeInfo : array($typeInfo, null));
			$map = $ids = $retrievals[$i] = array();
			$field = (is_numeric($key)) ? $type : $key;//use an alias?
			if (strpos($field, '*') !== false) { 
				$oldFields[]= $oldField; 
				$field = str_replace('*', implode('.', $oldFields), $field);
			}
			if (strpos($field, '&') !== false) $field = str_replace('&', implode('.', $oldFields), $field);
			$filteredBeans = $beans;
			$counterID = 0;
			foreach($filteredBeans as $bean) {
				$bean->setMeta('sys.input-bean-id', array($counterID => $counterID));
				$counterID++;
			}
			while($p = strpos($field, '.')) { //filtering: find the right beans in the path
				$nesting = substr($field, 0, $p);
				$filtered = array();
				foreach($filteredBeans as $bean) {
					$inputBeanID = $bean->getMeta('sys.input-bean-id');
					if (is_array($bean->$nesting)) {
						$nestedBeans = $bean->$nesting;
						foreach($nestedBeans as $nestedBean) {
							$currentInputBeanIDs = $nestedBean->getMeta('sys.input-bean-id'); 
							if (!is_array($currentInputBeanIDs)) $currentInputBeanIDs = array();
							$addInputIDs = $bean->getMeta('sys.input-bean-id');
							foreach($addInputIDs as $addInputID) $currentInputBeanIDs[$addInputID] = $addInputID;
							$nestedBean->setMeta('sys.input-bean-id', $currentInputBeanIDs);
						}
						$filtered = array_merge($filtered, $nestedBeans);
					} elseif (!is_null($bean->$nesting)) {
						$nestedBean = $bean->$nesting;
						$currentInputBeanIDs = $nestedBean->getMeta('sys.input-bean-id'); 
						if (!is_array($currentInputBeanIDs)) $currentInputBeanIDs = array();
						$addInputIDs = $bean->getMeta('sys.input-bean-id');
						foreach($addInputIDs as $addInputID) $currentInputBeanIDs[$addInputID] = $addInputID;
						$nestedBean->setMeta('sys.input-bean-id', $currentInputBeanIDs);	
						$filtered[] = $bean->$nesting;
					}
				}
				$filteredBeans = $filtered;
				$field = substr($field, $p+1);
			}
			$oldField = $field;
			if (strpos($type, '.')) $type = $field;
			if (strpos($field, 'shared') !== 0) {
				foreach($filteredBeans as $bean) { //gather ids to load the desired bean collections
					if (strpos($field, 'own') === 0) { //based on bean->id for ownlist
						$id = $bean->id; $ids[$id] = $id;
					} elseif($id = $bean->{$field.'_id'}){ //based on bean_id for parent
						$ids[$id] = $id; 
						if (!isset($map[$id])) $map[$id] = array();
						$map[$id][] = $bean;
					}
				}
			}
			if (strpos($field, 'shared') === 0) {
				$bean = reset($filteredBeans);
				$link = $bean->getMeta('type').'_id';
				$keys = $this->assocManager->related($filteredBeans, $type, true);
				$linkTable = $this->assocManager->getTable(array($type, $bean->getMeta('type')));
				$linkBeans = $this->batch($linkTable, $keys);
				$linked = $targetIDs = array();
				$targetIDField = $type.'_id';
				foreach($linkBeans as $linkBean) {
					$linkID = $linkBean->$link;
					if (!isset($linked[$linkID])) $linked[$linkID] = array();
					$linked[$linkID][] = $linkBean;
					$targetIDs[$linkBean->$targetIDField] = $linkBean->$targetIDField;
				}
				$sharedBeans = $this->find($type, array('id' => $targetIDs), $sqlObj, (!is_null($sqlObj)));
				foreach($filteredBeans as $filteredBean) {
					$list = array();
					if (isset($linked[$filteredBean->id])) {
						foreach($linked[$filteredBean->id] as $linkBean) {
							foreach($sharedBeans as $sharedBean) {
								if ($sharedBean->id == $linkBean->$targetIDField) $list[$sharedBean->id] = $sharedBean; 
							}
						}
					}
					$filteredBean->setProperty($field, $list);
					$inputBeanIDs = $filteredBean->getMeta('sys.input-bean-id');
					foreach($inputBeanIDs as $inputBeanID) {
						if (!isset($retrievals[$i][$inputBeanID])) $retrievals[$i][$inputBeanID] = array();
						foreach($list as $listKey => $listBean) $retrievals[$i][$inputBeanID][$listKey] = $listBean;
					}
				}
			} elseif (strpos($field, 'own') === 0) {//preload for own-list using find
				$link = $bean->getMeta('type').'_id';
				$children = $this->find($type, array($link => $ids), $sqlObj, (!is_null($sqlObj)));
				foreach($filteredBeans as $filteredBean) {
					$list = array();
					foreach($children as $child) if ($child->$link == $filteredBean->id) $list[$child->id] = $child;
					$filteredBean->setProperty($field, $list);
					$inputBeanIDs = $filteredBean->getMeta('sys.input-bean-id');
					foreach($inputBeanIDs as $inputBeanID) {
						if (!isset($retrievals[$i][$inputBeanID])) $retrievals[$i][$inputBeanID] = array();
						foreach($list as $listKey => $listBean) $retrievals[$i][$inputBeanID][$listKey] = $listBean;
					}
				}
			} else { //preload for parent objects using batch()
				foreach($this->batch($type, $ids) as $parent) {
					foreach($map[$parent->id] as $childBean) {
						$childBean->setProperty($field, $parent);
						$inputBeanIDs = $childBean->getMeta('sys.input-bean-id');
						foreach($inputBeanIDs as $inputBeanID) $retrievals[$i][$inputBeanID] = $parent;
					}
				}
			}
			$i++;
		}
		if ($closure) {
			$key = 0; 
			foreach($beans as $bean) {
				$params = array();
				foreach($retrievals as $r) $params[] = (isset($r[$key])) ? $r[$key] : null; 
				array_unshift($params, $bean);
				call_user_func_array($closure, $params);
				$key ++;
			}
		}
	}
}