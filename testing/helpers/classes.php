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
	public function update()
	{
		//print_r($this->sharedTaste);
	}
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

	public function taste()
	{
		return 'A bit too salty';
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
	public function addConstraintForTypes( $sourceType, $destType )
	{
		$exception = new \RedBeanPHP\RedException\SQL;
		$exception->setSQLState( $this->sqlState );
		throw $exception;
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
