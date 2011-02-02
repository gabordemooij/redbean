<?php
/**
 * RedBean BeanMachine
 *
 * @file			RedBean/BeanMachine.php
 * @description		Query Building System for Bean Machinery
 * @author			Gabor de Mooij
 * @license			BSD
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_BeanMachine implements RedBean_Plugin {

	/**
	 * @var RedBean_Plugin_BeanMachine_Group
	 */
	protected $groups = null;

	/**
	 * @var RedBean_Plugin_BeanMachine_Group
	 */
	protected $selected = null;

	/**
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * @var array
	 */
	protected $bookmarks = array();
	    

	/**
	 * Initializes the Bean Machine
	 * @return void
	 */
	private function init() {
		if (!class_exists("RedBean_Plugin_BeanMachine_Group")) {
			//create inner classes, we dont want to add files for each class,
			//just makes a mess. this one will only be used by BeanMachine.
			RedBean_Plugin_BeanMachine_InnerClasses();
		}
	}

	/**
	 * Private - use getInstance() instead, NOT a SINGLETON.
	 * Constructor bootstraps its own classes.
	 *
	 * @return void
	 */
	private function __construct() {

		$this->groups = new RedBean_Plugin_BeanMachine_Group;
		$this->groups->setTemplate("","");
		$this->groups->setGlue(" \n ");
		$this->selected = $this->groups;
		$this->root = $this->groups;

	}

	/**
	 * Gets an instance of the BeanMachine.
	 *
	 * @return RedBean_Plugin_BeanMachine $machine the Bean Machine.
	 */
	public function getInstance() {

		//Bootstrap own classes
		self::init();

		$inst = new self();
		return $inst;

	}

	/**
	 * Binds a value to a key.
	 * 
	 * @throws Exception
	 *
	 * @param  string $key
	 * @param  mixed $value
	 *
	 * @return void
	 */
	public function bind( $key, $value ) {
		if (isset($this->parameters[$key])) {
			throw new Exception("Parameter set already!");
		}
		$this->parameters[$key] = $Value;
	}


	/**
	 * Finds the group in the Query and selects it, opens it.
	 *
	 * Usage:
	 * 	$q = RedBean_Plugin_BeanMachine::getInstance();
	 *  $q->addGroup("SELECT-CLAUSE", " SELECT @ ", ",");
	 * 	... do all kind of stuff...
	 *  $q->openGroup("SELECT-CLAUSE");
	 *
	 *
	 * @throws Exception
	 * @param  $key
	 * @return RedBean_Plugin_BeanMachine
	 */
	public function openGroup( $key ) {

		foreach($this->bookmarks as $m=>$bookmark) {
			if ($m==$key) {
				$this->selected = $bookmark;
				return $this;
			}
		}

		throw new Exception("No Such Group");

		
	}

	/**
	 * Adds a new Group to the Query.
	 * Usage:
	 *
	 * //Example 1: creating a where clause
	 * $q->addGroup("WHERE-CLAUSE", " WHERE @ ", " AND ");
	 * $q->add(" color = :color ");
	 * $q->add(" smell = :smell ");
	 * (Outputs: WHERE color = :color AND smell = :smell )
	 *
	 * //Example 2: creating a group in the where-clause
	 * $q->openGroup("WHERE-CLAUSE");
	 * $q->addGroup("ROSES", " (@) ", " OR ");
	 * $q->add(" title = 'roses' ");
	 * $q->add(" description = 'roses' ");
	 *
	 *
	 * @param  string $key ID to assign to this part of the Query
	 * @param  string $template Template to use for this part of the Query, '@' is placeholder for SQL
	 * @param  string $glue string to use to glue together SQL parts in group
	 *
	 * @return RedBean_Plugin_BeanMachine $bm Chainable
	 */
	public function addGroup( $key, $template, $glue ) {


		$this->selected = $this->selected->addGroup( $key );
		$this->bookmarks[$key]= $this->selected;
		$this->selected->setGlue($glue);
		$templateSnippets = explode("@", $template);
		$this->selected->setTemplate($templateSnippets[0], $templateSnippets[1]);
		return $this;
	}

	/**
	 * Resets, re-selects the root group of the query.
	 * @return RedBean_Plugin_BeanMachine $bm Chainable
	 */
	public function reset() {
		$this->selected = $this->root;
		return $this;
	}

	/**
	 * Adds a statement to the current part of the query.
	 * 
	 * @throws Exception
	 *
	 * @param  string $statement statement to add
	 *
	 * @return RedBean_Plugin_BeanMachine $bm Chainable
	 */
	public function add( $statement ) {
		if ($this->selected instanceof RedBean_Plugin_BeanMachine_Group) {
			$this->selected->add( $statement );
		}
		else {
			throw new Exception("No Group has been opened. Please open a group first.");
		}
		return $this;
	}

	/**
	 * Builds the Query, returns the string.
	 *
	 * @return string $querySQL SQL code
	 */
	public function __toString() {
		return (string) $this->groups;
	}


}

function RedBean_Plugin_BeanMachine_InnerClasses() {
	class RedBean_Plugin_BeanMachine_Group {

			private $parent = null;
			private $glueChar = ",";
			private $before = " ( ";
			private $after = " ) ";
			private $statements = array();

			public function __construct( $parent = null ) {
				$this->parent = $parent;
			}

			public function getParent() {
				if ($this->parent) return $this->parent; else return $this;
			}

			public function setGlue( $glueChar ) {
				$this->glueChar = $glueChar;
			}
			public function add( $statement = "" ) {
				$this->statements[] = $statement;
			}
			public function setTemplate($before, $after) {
				$this->before = $before;
				$this->after = $after;
			}
			public function __toString() {
				$gluedStatements = implode($this->glueChar, $this->statements);
				return (string) $this->before . $gluedStatements . $this->after;
			}
			public function addGroup($key) {
				$g = new self($this);
				$this->statements[] = $g;
				return $g;
			}

		}
		return RedBean_Plugin_BeanMachine_Group;

}