<?php
/**
 * CompatManager (Compatibility Management)
 * @file 		RedBean/CompatManager.php
 * @description	Offers easy to use tools to check for database compatibility.
 * @author		Gabor de Mooij
 * @license		BSD
 */
class RedBean_CompatManager {

	/**
	 * List of Database constants to be used
	 * for version detection.
	 */
	const C_SYSTEM_MYSQL		= "mysql";
	const C_SYSTEM_SQLITE		= "sqlite";
	const C_SYSTEM_DB2			= "db2";
	const C_SYSTEM_POSTGRESQL	= "postgresql";
	const C_SYSTEM_ORACLE		= "oracle";
	const C_SYSTEM_MSSQL		= "mssql";
	const C_SYSTEM_HYPERTABLE	= "hypertable";
	const C_SYSTEM_INFORMIX		= "informix";
	const C_SYSTEM_SYBASE		= "sybase";
	const C_SYSTEM_FOXPRO		= "foxpro";

	/**
	 *
	 * @var boolean $ignoreWarning
	 */
	private static $ignoreVersion = false;

	/**
	 *
	 * @var string $messageUnsupported
	 */
	protected $messageUnsupported = "
Unfortunately ##YOU## is not supported by this module or class.
Supported System(s): ##DBS##.
To suppress this Exception use: RedBean_CompatManager::ignore(TRUE); ";

	/**
	 *
	 * @var array $supportedSystems
	 */
	protected $supportedSystems = array();

	/**
	 * This method toggles the exception system globally.
	 * If you set this to true exceptions will not be thrown. Use this
	 * if you think the version specification of a module is incorrect
	 * or too narrow.
	 * @param bool $ignore
	 */
	public static function ignore( $tf = TRUE ) {
		self::$ignoreVersion = (bool) $tf;
	}

	/**
	 * Scans the toolbox to determine whether the database adapter
	 * is compatible with the current class, plugin or module.
	 * @throws RedBean_Exception_UnsupportedDatabase $exception
	 * @param RedBean_ToolBox $toolbox
	 * @return bool $compatible
	 */
	public function scanToolBox( RedBean_ToolBox $toolbox ) {

	//obtain the database system
		$brand = strtolower(trim($toolbox->getDatabaseAdapter()->getDatabase()->getDatabaseType()));

		//obtain version number
		$version = $toolbox->getDatabaseAdapter()->getDatabase()->getDatabaseVersion();

		if (!is_numeric($version)) {
			$version = 999; //No version number? Ignore!
		}

		//compare database
		if (isset($this->supportedSystems[$brand])
			&& ((float)$this->supportedSystems[$brand] <= (float) $version)
		) {
			return true;
		}
		else {
			if (!self::$ignoreVersion) {
				$this->messageUnsupported = str_replace("##YOU##",$brand." v".$version,$this->messageUnsupported);
				$list = array();
				foreach($this->supportedSystems as $supported=>$version) {
					$list[] = " ".$supported . " v".$version."+";
				}
				$this->messageUnsupported = str_replace("##DBS##",implode(",",$list),$this->messageUnsupported);
				throw new RedBean_Exception_UnsupportedDatabase($this->messageUnsupported);
			}
			else {
				return false;
			}
		}


	}


	/**
	 * Static Variant
	 * Scans the toolbox to determine whether the database adapter
	 * is compatible with the current class, plugin or module.
	 * @throws RedBean_Exception_UnsupportedDatabase $exception
	 * @param RedBean_ToolBox $toolbox
	 * @param array $listOfSystemsSupported
	 * @return bool $compatible
	 */
	public static function scanDirect( RedBean_ToolBox $toolbox, $list = array() ) {
		$compat = new RedBean_CompatManager();
		$compat->supportedSystems = $list;
		return $compat->scanToolBox($toolbox);
	}

}