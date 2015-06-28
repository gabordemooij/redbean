<?php

/**
 * RedUNIT Shared Test Classes / Mock Objects
 * This file contains a collection of test classes that can be used by
 * and shared by tests.
 */

/**
 * Observable Mock
 * This is just for testing
 */
class ObservableMock extends \RedBeanPHP\Observable
{
	/**
	 * @param $eventname
	 * @param $info
	 */
	public function test( $eventname, $info )
	{
		$this->signal( $eventname, $info );
	}
}

/**
 * Observer Mock
 * This is just for testing
 */
class ObserverMock implements \RedBeanPHP\Observer
{
	/**
	 * @var bool
	 */
	public $event = FALSE;

	/**
	 * @var bool
	 */
	public $info = FALSE;

	/**
	 * @param string $event
	 * @param        $info
	 */
	public function onEvent( $event, $info )
	{
		$this->event = $event;
		$this->info  = $info;
	}
}

/**
 * Shared helper class for tests.
 * A test model to test FUSE functions.
 */
class Model_Band extends RedBeanPHP\SimpleModel
{
	public function after_update() { }

	private $notes = array();

	/**
	 * @throws Exception
	 */
	public function update()
	{
		if ( count( $this->ownBandmember ) > 4 ) {
			throw new Exception( 'too many!' );
		}
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return 'bigband';
	}

	/**
	 * @param $prop
	 * @param $value
	 */
	public function setProperty( $prop, $value )
	{
		$this->$prop = $value;
	}

	/**
	 * @param $prop
	 *
	 * @return bool
	 */
	public function checkProperty( $prop )
	{
		return isset( $this->$prop );
	}

	/**
	 * Sets a note.
	 *
	 * @param string $note
	 *
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function setNote( $note, $value )
	{
		$this->notes[ $note ] = $value;
	}

	/**
	 * Returns the value of a note.
	 *
	 * @param string $note
	 *
	 * @return string
	 */
	public function getNote( $note )
	{
		return $this->notes[ $note ];
	}
}

/**
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_Box extends RedBeanPHP\SimpleModel
{
	public function delete() { $a = $this->bean->ownBottle; }
}

/**
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_Cocoa extends RedBeanPHP\SimpleModel
{
	public function update(){}
}

/**
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_Taste extends RedBeanPHP\SimpleModel
{
	public function after_update()
	{
		asrt( count( $this->bean->ownCocoa ), 0 );
	}
}

/**
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_Coffee extends RedBeanPHP\SimpleModel
{
	public function update()
	{
		while ( count( $this->bean->ownSugar ) > 3 ) {
			array_pop( $this->bean->ownSugar );
		}
	}
}

/**
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_Test extends RedBeanPHP\SimpleModel
{
	public function update()
	{
		if ( $this->bean->item->val ) {
			$this->bean->item->val        = 'Test2';
			$can                          = R::dispense( 'can' );
			$can->name                    = 'can for bean';
			$s                            = reset( $this->bean->sharedSpoon );
			$s->name                      = "S2";
			$this->bean->item->deep->name = '123';
			$this->bean->ownCan[]         = $can;
			$this->bean->sharedPeas       = R::dispense( 'peas', 10 );
			$this->bean->ownChip          = R::dispense( 'chip', 9 );
		}
	}
}

global $lifeCycle;

/**
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_Bandmember extends RedBeanPHP\SimpleModel
{
	public function open()
	{
		global $lifeCycle;

		$lifeCycle .= "\n called open: " . $this->id;
	}

	public function dispense()
	{
		global $lifeCycle;

		$lifeCycle .= "\n called dispense() " . $this->bean;
	}

	public function update()
	{
		global $lifeCycle;

		$lifeCycle .= "\n called update() " . $this->bean;
	}

	public function after_update()
	{
		global $lifeCycle;

		$lifeCycle .= "\n called after_update() " . $this->bean;
	}

	public function delete()
	{
		global $lifeCycle;

		$lifeCycle .= "\n called delete() " . $this->bean;
	}

	public function after_delete()
	{
		global $lifeCycle;

		$lifeCycle .= "\n called after_delete() " . $this->bean;
	}
}

/**
 * A model to box soup models :)
 */
class Model_Soup extends \RedBeanPHP\SimpleModel
{
	private $flavour = '';

	public function taste()
	{
		return 'A bit too salty';
	}

	public function setFlavour( $flavour )
	{
		$this->flavour = $flavour;
	}

	public function getFlavour()
	{
		return $this->flavour;
	}
}

/**
 * A custom BeanHelper to test custom FUSE operations.
 */
class SoupBeanHelper extends \RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper
{
	/**
	 * Returns a model for a bean based on its type.
	 *
	 * @param OODBBean $bean
	 *
	 * @return object
	 */
	public function getModelForBean( \RedBeanPHP\OODBBean $bean )
	{
			if ( $bean->getMeta( 'type' ) === 'meal' ) {
				$model = new Model_Soup;
				$model->loadBean( $bean );
				return $model;
			} else {
				return parent::getModelForBean( $bean );
			}
	}
}

/**
 * Test Model.
 */
class Model_Boxedbean extends \RedBeanPHP\SimpleModel
{
}

/**
 * Mock class for testing purposes.
 */
class Model_Ghost_House extends \RedBeanPHP\SimpleModel
{
	public static $deleted = FALSE;

	public function delete()
	{
		self::$deleted = TRUE;
	}
}

/**
 * Mock class for testing purposes.
 */
class Model_Ghost_Ghost extends \RedBeanPHP\SimpleModel
{
	public static $deleted = FALSE;

	public function delete()
	{
		self::$deleted = TRUE;
	}
}

/**
 * Mock class for testing purposes.
 */
class FaultyWriter extends \RedBeanPHP\QueryWriter\MySQL
{

	protected $sqlState;

	/**
	 * Mock method.
	 *
	 * @param string $sqlState sql state
	 */
	public function setSQLState( $sqlState )
	{
		$this->sqlState = $sqlState;
	}

	/**
	 * Mock method
	 *
	 * @param string $sourceType destination type
	 * @param string $destType   source type
	 *
	 * @throws SQL
	 */
	public function addUniqueConstraint( $sourceType, $destType )
	{
		$exception = new \RedBeanPHP\RedException\SQL;
		$exception->setSQLState( $this->sqlState );
		throw $exception;
	}

	/**
	 * Mock method.
	 * Generates an exception when trying to invoke
	 * getKeyMapForType via getForeignKeyForTypeProperty.
	 *
	 * @return void
	 */
	protected function getKeyMapForType( $type )
	{
		throw new \RedBeanPHP\RedException\SQL;
	}
}

/**
 * Mock class to test default implementations in AQueryWriter.
 */
class NullWriter extends \RedBeanPHP\QueryWriter\AQueryWriter {
}

class ProxyWriter extends \RedBeanPHP\QueryWriter\AQueryWriter {

	public static function callMethod( $object, $method, $arg1 = NULL, $arg2 = NULL, $arg3 = NULL ) {
		return $object->$method( $arg1, $arg2, $arg3 );
	}

}

/**
 * Mock class to test proper model name
 * beautificattion for link table beans in FUSE.
 */
class Model_PageWidget extends RedBean_SimpleModel {
	/**
	 * @var string
	 */
	private static $test = '';

	/**
	 * Returns the test flag.
	 *
	 * @return string
	 */
	public static function getTestReport()
	{
		return self::$test;
	}

	/**
	 * Update method to set the flag.
	 */
	public function update()
	{
		self::$test = 'didSave';
	}
}

/**
 * Mock class to test proper model name
 * beautificattion for link table beans in FUSE.
 */
class Model_Gadget_Page extends RedBean_SimpleModel {
	/**
	 * @var string
	 */
	private static $test = '';

	/**
	 * Returns the test flag.
	 *
	 * @return string
	 */
	public static function getTestReport()
	{
		return self::$test;
	}

	/**
	 * Update method to set the flag.
	 */
	public function update()
	{
		self::$test = 'didSave';
	}
}

/**
 * Mock class to test proper model name
 * beautificattion for link table beans in FUSE.
 */
class Model_A_B_C extends RedBean_SimpleModel {
	/**
	 * @var string
	 */
	private static $test = '';

	/**
	 * Returns the test flag.
	 *
	 * @return string
	 */
	public static function getTestReport()
	{
		return self::$test;
	}

	/**
	 * Update method to set the flag.
	 */
	public function update()
	{
		self::$test = 'didSave';
	}
}

class Model_BookBook extends \RedBean_SimpleModel {
	public function delete() {
		asrt($this->bean->shelf, 'x13');
	}
}

class Model_Feed extends \RedbeanPHP\SimpleModel {
    public function update() {
        $this->bean->post = json_encode($this->bean->post);
    }

    public function open() {
        $this->bean->post = json_decode($this->bean->post, true);
    }
}

/**
 * UUID QueryWriter for MySQL for testing purposes.
 */
class UUIDWriterMySQL extends \RedBeanPHP\QueryWriter\MySQL {

	protected $defaultValue = '@uuid';
	const C_DATATYPE_SPECIAL_UUID  = 97;

	public function __construct( \RedBeanPHP\Adapter $adapter )
	{
		parent::__construct( $adapter );
		$this->addDataType( self::C_DATATYPE_SPECIAL_UUID, 'char(36)'  );
	}

	public function createTable( $table )
	{
		$table = $this->esc( $table );
		$sql   = "
			CREATE TABLE {$table} (
			id char(36) NOT NULL,
			PRIMARY KEY ( id ))
			ENGINE = InnoDB DEFAULT
			CHARSET=utf8mb4
			COLLATE=utf8mb4_unicode_ci ";
		$this->adapter->exec( $sql );
	}

	public function updateRecord($table, $updateValues, $id = NULL)
	{
		$flagNeedsReturnID = (!$id);
		if ($flagNeedsReturnID) R::exec('SET @uuid = uuid() ');
		$id = parent::updateRecord( $table, $updateValues, $id );
		if ( $flagNeedsReturnID ) $id = R::getCell('SELECT @uuid');
		return $id;
	}

	public function getTypeForID()
	{
		return self::C_DATATYPE_SPECIAL_UUID;
	}
}

/**
 * UUID QueryWriter for PostgreSQL for testing purposes.
 */
class UUIDWriterPostgres extends \RedBeanPHP\QueryWriter\PostgreSQL {

	protected $defaultValue = 'uuid_generate_v4()';
	const C_DATATYPE_SPECIAL_UUID  = 97;

	public function __construct( \RedBeanPHP\Adapter $adapter )
	{
		parent::__construct( $adapter );
		$this->addDataType( self::C_DATATYPE_SPECIAL_UUID, 'uuid'  );
	}

	public function createTable( $table )
	{
		$table = $this->esc( $table );
		$this->adapter->exec( " CREATE TABLE $table (id uuid PRIMARY KEY); " );
	}

	public function getTypeForID()
	{
		return self::C_DATATYPE_SPECIAL_UUID;
	}
}

class DiagnosticBean extends \RedBeanPHP\OODBBean {

	/**
	 * Returns current status of modification flags.
	 *
	 * @return string
	 */
	public function getModFlags()
	{
		$modFlags = '';
		if ($this->aliasName !== NULL) $modFlags .= 'a';
		if ($this->fetchType !== NULL) $modFlags .= 'f';
		if ($this->noLoad === TRUE) $modFlags .= 'n';
		if ($this->all === TRUE) $modFlags .= 'r';
		if ($this->withSql !== '') $modFlags .= 'w';

		return $modFlags;
	}

}

class DiagnosticModel extends \RedBeanPHP\SimpleModel
{

	private $logs = array();

	public function open()
	{
		$this->logs[] = array(
			'action' => 'open',
			'data'   => array(
				'id' => $this->id
			)
		);
	}

	public function dispense()
	{
		$this->logs[] = array(
			'action' => 'dispense',
			'data'   => array(
				'bean' => $this->bean
			)
		);
	}

	public function update()
	{
		$this->logs[] = array(
			'action' => 'update',
			'data'   => array(
				'bean' => $this->bean
			)
		);
	}

	public function after_update()
	{
		$this->logs[] = array(
			'action' => 'after_update',
			'data'   => array(
				'bean' => $this->bean
			)
		);
	}

	public function delete()
	{
		$this->logs[] = array(
			'action' => 'delete',
			'data'   => array(
				'bean' => $this->bean
			)
		);
	}

	public function after_delete()
	{
		$this->logs[] = array(
			'action' => 'after_delete',
			'data'   => array(
				'bean' => $this->bean
			)
		);
	}

	public function getLogs()
	{
		return $this->logs;
	}

	public function getLogActionCount( $action = NULL )
	{
		if ( is_null( $action ) ) return count( $this->logs );
		$counter = 0;
		foreach( $this->logs as $log ) {
			if ( $log['action'] == $action ) $counter ++;
		}
		return $counter;
	}

	public function clearLog()
	{
		return $this->logs = array();
	}

	public function getDataFromLog( $logIndex = 0, $property )
	{
		return $this->logs[$logIndex]['data'][$property];
	}

}

class Model_Probe extends DiagnosticModel {};

define('REDBEAN_OODBBEAN_CLASS', '\DiagnosticBean');
