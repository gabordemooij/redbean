<?php
/**
 * TimeLine 
 *
 * @file 			RedBean/Plugin/TimeLine.php
 * @desc			Monitors schema changes to ease deployment.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_TimeLine extends RedBean_Plugin_QueryLogger implements RedBean_Plugin {
	/**
	 * Path to file to write SQL and comments to.
	 * 
	 * @var string 
	 */
	protected $file;
	/**
	 * Constructor.
	 * Requires a path to an existing and writable file.
	 * 
	 * @param string $outputPath path to file to write schema changes to. 
	 */
	public function __construct($outputPath) {
		if (!file_exists($outputPath) || !is_writable($outputPath)) 
			throw new RedBean_Exception_Security('Cannot write to file: '.$outputPath);
		$this->file = $outputPath;
	}
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
	public function onEvent($eventName, $adapter) {
		if ($eventName == 'sql_exec') {
			$sql = $adapter->getSQL();
			$this->logs[] = $sql;
			if (strpos($sql, 'ALTER') === 0) {
				$write = "-- ".date('Y-m-d H:i')." | Altering table. \n";
				$write .= $sql;
				$write .= "\n\n";
			}
			if (strpos($sql, 'CREATE') === 0) {
				$write = "-- ".date('Y-m-d H:i')." | Creating new table. \n";
				$write .= $sql;
				$write .= "\n\n";
			}
			if (isset($write)) {
				file_put_contents($this->file, $write, FILE_APPEND);
			}
		}
	}
}