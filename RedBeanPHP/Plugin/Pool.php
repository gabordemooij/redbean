<?php
 
namespace RedBeanPHP\Plugin;
 
use RedBeanPHP\ToolBox;
use RedBeanPHP\OODBBean;

/**
 * @experimental
 *
 * PoolDB
 *
 * Experimental plugin that makes bean automatically connect
 * to the database they come from. 
 */

/**
 * NonStaticBeanHelper
 *
 * The NonStaticBeanHelper is used by the database pool class PoolDB.
 */
class NonStaticBeanHelper extends RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper {

	/**
	 * Returns the extracted toolbox.
	 *
	 * @return ToolBox
	 */
	public function getExtractedToolbox()
	{
		$toolbox = $this->toolbox;
		return array( $toolbox->getRedbean(), $toolbox->getDatabaseAdapter(), $toolbox->getWriter(), $toolbox );
	}

	/**
	 * Constructor
	 * 
	 * Creates a new instance of the NonStaticBeanHelper.
	 * The NonStaticBeanHelper is used by the database pool class PoolDB.
	 */
	public function __construct($toolbox)
	{
		$this->toolbox = $toolbox;
	}
}

/**
 * PoolDB
 *
 * Represents a pool of databases that will have persisting
 * associations with the beans they dispense. Saving a bean from
 * a pooled database will make sure that the bean will be stored
 * in the database it originated from instead of the currently
 * selected database.
 *
 * @experimental
 * This is an experimental plugin, added for testing purposes
 * only.
 *
 * Usage:
 * 
 * <code>
 * // Let's add some databases
 * R::addPoolDatabase( 'db1', 'sqlite:/tmp/db1.txt' );
 * R::nuke();
 * R::addPoolDatabase( 'db2', 'sqlite:/tmp/db2.txt' );
 * R::nuke();
 * R::addPoolDatabase( 'db3', 'sqlite:/tmp/db3.txt' );
 * R::nuke();
 *
 * // create a book and page in db1
 * R::selectDatabase('db1');
 * $book = R::dispense(array(
 *	'_type' => 'book',
 *	'title' => 'Databases for Beans',
 *	'ownPageList' => array(
 *		0 => array(
 *			'_type' => 'page',
 *			'content' => 'Lorem Ipsum'
 *		)
 *	)
 * ));
 * R::store($book);
 *
 * //switch to db2
 * R::selectDatabase( 'db2' );
 * //obtain pages (from db1)
 * $pages = count($book->ownPageList);
 * echo "I found {$pages} pages in db1.\n";
 *
 * $pages = R::count('page');
 * echo "There are {$pages} pages in db2.\n";
 *
 * // create pizza in db 2
 * $pizza = R::dispense('pizza');
 *
 * // switch to db3 
 * R::selectDatabase( 'db3' );
 *
 * // store pizza in db2
 * $pizza->pepperoni = true;
 * R::store($pizza);
 *
 * $pizzas = R::count('pizza');
 * echo "There are {$pizzas} in pizzas db3.\n";
 * R::selectDatabase('db2');
 * $pizzas = R::count('pizza');
 * echo "There are {$pizzas} pizzas in db2.\n";
 * </code>
 *
 * @file    RedBeanPHP/Plugin/Pool.php
 * @author  RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class PoolDB extends \RedBeanPHP\OODB {

	/**
	 * @var array
	 */
	private static $pool = array();
	
	/**
	 * @var ToolBox
	 */
	private $toolbox;

	/**
	 * @var OODB
	 */
	private $oodb;

	/**
	 * @var string
	 */
	private $key;

	/**
	 * @var NonStaticBeanHelper
	 */
	private $beanHelper;

	/**
	 * Constructor
	 *
	 * creates a new instance of the database pool.
	 *
	 * @param string $key  key
	 * @param OODB   $oodb oodb instance
	 */
	public function __construct( $key, $oodb)
	{
		self::$pool[$key] = $oodb;
		$this->oodb = $oodb;
		$this->key = $key;
		parent::__construct( $oodb->writer, $oodb->isFrozen );
	}
	

	/**
	 * Sets the toolbox to be used by the database pool.
	 *
	 * @param ToolBox $toolbox toolbox
	 *
	 * @return void
	 */
	public function setToolBox( $toolbox )
	{
		$this->toolbox = $toolbox;
		$this->beanHelper = new NonStaticBeanHelper( $this->toolbox );
		$this->beanHelper->key = $this->key;
		$this->oodb->setBeanHelper( $this->beanHelper );
	}
	
	/**
	 * Returns the bean helper of the database pool.
	 * 
	 * @return BeanHelper
	 */
	public function getBeanHelper()
	{
		return $this->beanHelper;
	}

	/**
	 * Implements the find operation.
	 *
	 * @see OODB::find
	 */
	public function find( $type, $conditions=array(), $sql=NULL, $bindings=array())
	{
		return parent::find($type, $conditions, $sql, $bindings);
	}

	/**
	 * Dispenses a new bean from the database pool.
	 * A bean that has been dispensed by the pool will have a special
	 * meta attribute called sys.source containing the key identifying
	 * the database in the pool it originated from.
	 *
	 * @see OODB::dispense
	 */
	public function dispense( $type, $number = 1, $alwaysReturnArray = FALSE )
	{
		$bean = $this->oodb->dispense( $type, $number, $alwaysReturnArray );
		foreach( self::$pool as $key => $db ) {
			if ( $this->oodb === $db ) {
				$bean->setMeta( 'sys.source',$key );
			}
		}
		return $bean;
	}

	/**
	 * Stores the specified bean in the database in the pool
	 * it originated from by looking up the sys.source attribute.
	 *
	 * @see OODB::store
	 */
	public function store(  $bean )
	{
		$dataSource = $bean->getMeta('sys.source');
		if ( !is_null( $dataSource ) ) {
			$result = self::$pool[$dataSource]->store( $bean );
		} else {
			$result = parent::store( $bean );
		}
		return $result;
	}
}


R::ext( 'addPoolDatabase', function( $dbName, $dsn, $user=NULL, $pass=NULL ) {
	R::addDatabase( $dbName, $dsn, $user, $pass );
	R::selectDatabase( $dbName );
	list($oodb, $adapter, $writer, ) = R::getExtractedToolbox();
	$poolDB = new PoolDB( $dbName, $oodb );
	$toolbox = new ToolBox( $poolDB, $adapter, $writer );
	$poolDB->setToolBox( $toolbox );
	R::$toolboxes[$dbName]=$toolbox;
	R::selectDatabase( $dbName, TRUE );
} );

