<?php
/**
 * RedUNIT Shared Test Classes / Mock Objects
 * This file contains a collection of test classes that can be used by
 * the test suite. None of these classes should be used by users of
 * the RedBeanPHP library, they are meant for internal use only!
 * These classes are most of the time, single purpose classes, that are
 * only used once or twice. They are written down in a compact format
 * because the overview of their limited functionality is handy wereas
 * documentation per method is not very useful in this case.
 */

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Observable Mock
 */
class ObservableMock extends \RedBeanPHP\Observable
{
	public function test( $eventname, $info ){ $this->signal( $eventname, $info ); }
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Observer Mock
 */
class ObserverMock implements \RedBeanPHP\Observer
{
	public $event = FALSE;
	public $info = FALSE;
	public function onEvent( $event, $info ){ $this->event = $event; $this->info  = $info; }
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Shared helper class for tests.
 * A test model to test FUSE functions.
 */
class Model_Band extends RedBeanPHP\SimpleModel
{
	public function after_update() { }
	private $notes = array();
	public function update()
	{
		if ( count( $this->ownBandmember ) > 4 ) throw new Exception( 'too many!' );
	}
	public function __toString(){ return 'bigband'; }
	public function setProperty( $prop, $value ) { $this->$prop = $value; }
	public function checkProperty( $prop ) { return isset( $this->$prop ); }
	public function setNote( $note, $value ){ $this->notes[ $note ] = $value; }
	public function getNote( $note ) { return $this->notes[ $note ]; }
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_Box extends RedBeanPHP\SimpleModel
{
	public function delete() { $a = $this->bean->ownBottle; }
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_Cocoa extends RedBeanPHP\SimpleModel
{
	public function update(){}
}
/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
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
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 */
class Model_Coffee extends RedBeanPHP\SimpleModel
{
	public static $defaults = array();

	public function dispense()
	{
		if ( count( self::$defaults ) && !$this->bean->id ) {
			foreach (self::$defaults as $key => $value) {
				$this->{$key} = $value;
			}
		}
	}

	public function __jsonSerialize()
	{
		return array_merge(
			$this->bean->export(),
			array( 'description' => "{$this->bean->variant}.{$this->bean->strength}" )
		);
	}

	public function update()
	{
		while ( count( $this->bean->ownSugar ) > 3 ) {
			array_pop( $this->bean->ownSugar );
		}
	}
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
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

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Shared helper class for tests.
 * A Model class for testing Models/FUSE and related features.
 * Used in Blackhole/Export.
 */
global $lifeCycle;
class Model_Bandmember extends RedBeanPHP\SimpleModel
{
	public function open(){ global $lifeCycle; $lifeCycle .= "\n called open: " . $this->id; }
	public function dispense(){ global $lifeCycle; $lifeCycle .= "\n called dispense() " . $this->bean; }
	public function update() { global $lifeCycle; $lifeCycle .= "\n called update() " . $this->bean; }
	public function after_update(){ global $lifeCycle; $lifeCycle .= "\n called after_update() " . $this->bean; }
	public function delete(){ global $lifeCycle; $lifeCycle .= "\n called delete() " . $this->bean; }
	public function after_delete(){ global $lifeCycle; $lifeCycle .= "\n called after_delete() " . $this->bean; }
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * A custom BeanHelper to test custom FUSE operations in
 * Blackhole/Fusebox
 */
class Model_Soup extends \RedBeanPHP\SimpleModel
{
	private $flavour = '';
	public function taste() { return 'A bit too salty'; }
	public function setFlavour( $flavour ) { $this->flavour = $flavour; }
	public function getFlavour(){ return $this->flavour; }
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * A custom BeanHelper to test custom FUSE operations in
 * Base/Fuse.
 */
class SoupBeanHelper extends \RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper
{
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
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Used in Base/Boxing and Base/Misc to test boxing of beans.
 * Just a plain model for use with a bean with nothing in it.
 */
class Model_Boxedbean extends \RedBeanPHP\SimpleModel{}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Used in Mysql/Uuid, Postgres/Uuid and Base/Association. Meant
 * to be a versatile, generic test model.
 */
class Model_Ghost_House extends \RedBeanPHP\SimpleModel
{
	public static $deleted = FALSE;
	public function delete() { self::$deleted = TRUE; }
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Used in Mysql/Uuid, Postgres/Uuid and Base/Association. Meant
 * to be a versatile, generic test model for N-M relations.
 */
class Model_Ghost_Ghost extends \RedBeanPHP\SimpleModel
{
	public static $deleted = FALSE;
	public function delete() { self::$deleted = TRUE; }
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Mock class for testing purposes. Used in Base/Association and
 * Base/Foreignkeys to emit errors to test handling of errors
 * originating from the Query Writer.
 */
class FaultyWriter extends \RedBeanPHP\QueryWriter\MySQL
{
	protected $sqlState;
	public function setSQLState( $sqlState ){ $this->sqlState = $sqlState; }
	public function addUniqueConstraint( $sourceType, $destType ){
		$exception = new \RedBeanPHP\RedException\SQL;
		$exception->setSQLState( $this->sqlState );
		throw $exception;
	}
	protected function getKeyMapForType( $type ){throw new \RedBeanPHP\RedException\SQL;}
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Mock class to test default implementations in AQueryWriter.
 */
class NullWriter extends \RedBeanPHP\QueryWriter\AQueryWriter {}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Used in Base/Foreignkeys (testFKInspect) to test foreign keys.
*/
class ProxyWriter extends \RedBeanPHP\QueryWriter\AQueryWriter {
	public static function callMethod( $object, $method, $arg1 = NULL, $arg2 = NULL, $arg3 = NULL ) {
		return $object->$method( $arg1, $arg2, $arg3 );
	}
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Mock class to test proper model name
 * beautificattion for link table beans in FUSE.
 */
class Model_PageWidget extends RedBean_SimpleModel {
	private static $test = '';
	public static function getTestReport(){ return self::$test; }
	public function update(){ self::$test = 'didSave'; }
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Mock class to test proper model name
 * beautificattion for link table beans in FUSE.
 */
class Model_Gadget_Page extends RedBean_SimpleModel {
	private static $test = '';
	public static function getTestReport(){ return self::$test;}
	public function update(){ self::$test = 'didSave'; }
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Mock class to test proper model name
 * beautificattion for link table beans in FUSE.
 */
class Model_A_B_C extends RedBean_SimpleModel {
	private static $test = '';
	public static function getTestReport(){ return self::$test; }
	public function update() { self::$test = 'didSave'; }
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Used in Base/Update to test SQL filters with links
 */
class Model_BookBook extends \RedBean_SimpleModel {
	public function delete() {
		asrt($this->bean->shelf, 'x13');
	}
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Used in Base/Fuse (error handling in Fuse) and
 * Base/Issue408 (export issue).
 */
class Model_Feed extends \RedbeanPHP\SimpleModel {
	public function update() { $this->bean->post = json_encode( $this->bean->post );}
	public function open() { $this->bean->post = json_decode( $this->bean->post, TRUE );}
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * UUID QueryWriter for MySQL for testing purposes.
 * Used in Mysql/Uuid to test if RedBeanPHP can be used with a
 * UUID-strategy. While UUID keys are not part of the RedBeanPHP core,
 * examples are given on the website and this test makes sure those examples
 * are working as expected.
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
	public function getTypeForID(){return self::C_DATATYPE_SPECIAL_UUID;}
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * UUID QueryWriter for PostgreSQL for testing purposes.
 * Used in Postgres/Uuid to test if RedBeanPHP can be used with a
 * UUID-strategy. While UUID keys are not part of the RedBeanPHP core,
 * examples are given on the website and this test makes sure those examples
 * are working as expected.
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

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * This diagnostic bean class adds a method to read the current
 * status of the modifier flags. Used to test interactions with
 * beans and monitor the effect on the internal flags.
 */
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

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * This is diagnostic class that allows access to otherwise
 * protected methods.Used to test FUSE hooks in Base/Fuse.php
 * Subclassed by Model_Probe.
 */
class DiagnosticModel extends \RedBeanPHP\SimpleModel
{

	private $logs = array();
	public function open() { $this->logs[] = array('action' => 'open','data'=> array('id' => $this->id));}
	public function dispense(){$this->logs[] = array('action' => 'dispense','data' => array('bean' => $this->bean));}
	public function update(){$this->logs[] = array('action' => 'update','data' => array('bean' => $this->bean));}
	public function after_update(){$this->logs[] = array('action' => 'after_update','data'=> array('bean' => $this->bean));}
	public function delete(){$this->logs[] = array('action' => 'delete','data'=> array('bean' => $this->bean));}
	public function after_delete(){$this->logs[] = array('action' => 'after_delete','data'   => array('bean' => $this->bean));}
	public function getLogs(){return $this->logs;}
	public function getLogActionCount( $action = NULL )
	{
		if ( is_null( $action ) ) return count( $this->logs );
		$counter = 0;
		foreach( $this->logs as $log ) if ( $log['action'] == $action ) $counter ++;
		return $counter;
	}
	public function clearLog(){return $this->logs = array();}
	public function getDataFromLog( $logIndex = 0, $property ){return $this->logs[$logIndex]['data'][$property];}
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Used in Base/Database (testDatabaseCapabilityChecker) to check
 * database capabilities.
*/
class DatabaseCapabilityChecker extends \RedBeanPHP\Driver\RPDO {

	public function __construct( \PDO $pdo )
	{
		$this->pdo = $pdo;
	}

	public function checkCapability( $capID )
	{
		return $this->hasCap( $capID );
	}
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Used in Test Suite Base/Bean (testToStringOverride)
 * to test string overrides.
 */
class Model_String extends \RedBeanPHP\SimpleModel {
	public function __toString() {
		return base64_encode( $this->bean->text );
	}
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * This is diagnostic class that allows access to otherwise
 * protected methods.Used to test FUSE hooks in Base/Fuse.php
 */
class Model_Probe extends DiagnosticModel {};

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Class to mock adapter.
 * Inspects behavior of classes interacting with the adapter class
 * by capturing the method invocations.
 */
class Mockdapter implements \RedBeanPHP\Adapter {

	public function answer( $id )
	{
		$error = "error{$id}";
		$property = "answer{$id}";
		if (isset($this->$error)) throw $this->$error;
		if (isset($this->$property)) return $this->$property;
	}

	public function getSQL(){}
	public function exec( $sql, $bindings = array(), $noevent = FALSE ){ return $this->answer('Exec'); }
	public function get( $sql, $bindings = array() ){ return $this->answer('GetSQL'); }
	public function getRow( $sql, $bindings = array() ){ return array(); }
	public function getCol( $sql, $bindings = array() ){ return $this->answer('GetCol'); }
	public function getCell( $sql, $bindings = array() ){ return ''; }
	public function getAssoc( $sql, $bindings = array() ){ return array();  }
	public function getAssocRow( $sql, $bindings = array() ){ return array(); }
	public function getInsertID(){}
	public function getAffectedRows(){}
	public function getCursor( $sql, $bindings = array() ){}
	public function getDatabase(){}
	public function startTransaction(){}
	public function commit(){}
	public function rollback(){}
	public function close(){}
	public function setOption( $optionKey, $optionValue ){}
	public function getDatabaseServerVersion(){ return 'Mock'; }
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Custom Logger class.
 */
class CustomLogger extends \RedBeanPHP\Logger\RDefault
{

	private $log;
	public function getLogMessage(){ return $this->log; }
	public function log() { $this->log = func_get_args(); }
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * This is diagnostic class that allows access to otherwise
 * protected methods.
 * Class to test protected method hasCap in RPDO.
 */
class TestRPO extends \RedBeanPHP\Driver\RPDO {
	public function testCap( $cap ) {
		return $this->hasCap( $cap );
	}
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * Class to mock PDO behavior.
 */
class MockPDO extends \PDO {
	public $attributes = array();
	public function __construct() { }
	public function setAttribute( $att, $val = NULL ){ $this->attributes[ $att ] = $val; }
	public function getDiagAttribute( $att ){ return $this->attributes[ $att ]; }
	public function getAttribute( $att ) {
		if ($att == \PDO::ATTR_SERVER_VERSION) return '5.5.3';
		return 'x';
	}
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * DiagnosticCUBRIDWriter
 * Class for stub test for CUBRID database support.
 */
class DiagnosticCUBRIDWriter extends \RedBeanPHP\QueryWriter\CUBRID {
	public function callMethod( $method, $arg1 = NULL, $arg2 = NULL, $arg3 = NULL, $arg4 = NULL, $arg5 = NULL ) {
		return $this->$method( $arg1, $arg2, $arg3, $arg4, $arg5 );
	}
}

/**
 * Test utility class.
 * This class is meant for testing purposes only and should
 * never be used for anything else than RedBeanPHP Unit Testing.
 * This is an error class that allows RedBeanPHP Unit Tests to
 * test error handling.
 * Test Model that throws an exception upon update().
 */
class Model_Brokentoy extends \RedbeanPHP\SimpleModel {
	public function update(){
		throw new \Exception('Totally on purpose.');
	}
}

/**
 * To test Dynamic BeanHelpers, resolving models with different prefixes and/or
 * namespaces.
 */
class Prefix1_Bean extends \RedbeanPHP\SimpleModel { }
class Prefix2_Bean extends \RedbeanPHP\SimpleModel { }

define('REDBEAN_OODBBEAN_CLASS', '\DiagnosticBean');