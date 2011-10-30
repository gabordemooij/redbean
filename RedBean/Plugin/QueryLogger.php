<?php
/**
 * Query Logger
 *
 * @file 			RedBean/Plugin/QueryLogger.php
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedBean_Plugin_QueryLogger implements RedBean_Observer {

	/**
	 * @var array
	 * contains log messages
	 */
	protected $logs = array();

	/**
	 * Creates a new instance of the Query Logger and attaches
	 * this logger to the adapter.
	 *
	 * @static
	 * @param RedBean_Observable $adapter the adapter you want to attach to
	 *
	 * @return RedBean_Plugin_QueryLogger $querylogger instance of the Query Logger
	 */
	public static function getInstanceAndAttach( RedBean_Observable $adapter ) {
		$queryLog = new RedBean_Plugin_QueryLogger;
		$adapter->addEventListener( "sql_exec", $queryLog );
		return $queryLog;
	}

	/**
	 * Singleton pattern
	 * Constructor - private
	 */
	private function __construct(){}

	/**
	 * Implementation of the onEvent() method for Observer interface.
	 * If a query gets executed this method gets invoked because the
	 * adapter will send a signal to the attached logger.
	 *
	 * @param  string $eventName          ID of the event (name)
	 * @param  RedBean_DBAdapter $adapter adapter that sends the signal
	 *
	 * @return void
	 */
	public function onEvent( $eventName, $adapter ) {
		if ($eventName=="sql_exec") {
			$sql = $adapter->getSQL();
			$this->logs[] = $sql;
		}
	}

	/**
	 * Searches the logs for the given word and returns the entries found in
	 * the log container.
	 *
	 * @param  string $word word to look for
	 *
	 * @return array $entries entries that contain the keyword
	 */
	public function grep( $word ) {
		$found = array();
		foreach($this->logs as $log) {
			if (strpos($log,$word)!==false) {
				$found[] = $log;
			}
		}
		return $found;
	}

	/**
	 * Returns all the logs.
	 *
	 * @return array $logs logs
	 */
	public function getLogs() {
		return $this->logs;
	}

	/**
	 * Clears the logs.
	 *
	 * @return void
	 */
	public function clear() {
		$this->logs = array();
	}
}
