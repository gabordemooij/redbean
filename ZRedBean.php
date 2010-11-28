<?php
		
/**
 * ZRedBean 
 * @file 			ZRedBean.php
 * @description		A Zend Resource Plugin for RedBean
 * @author			Gabor de Mooij
 * @license			BSD
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 * Put this file in a directory together with the all-in-one pack
 * from the website. In your configuration file, point to this file
 * as a resource plugin. This class will bootstrap RedBean for you and make
 * configuration options available in the .ini file.
 *
 * In your application.ini put:
 *
 * pluginPaths.redbean = "red"; -- or another directory or symlink in /library
 * resources.ZRedBean.dsn = "mysql:host=localhost;dbname=database;"
 * resources.ZRedBean.password = ""
 * resources.ZRedBean.username = "root"
 * resources.ZRedBean.freeze = false
 *
 *
 */
class RedBean_ZRedBean extends Zend_Application_Resource_ResourceAbstract
{
	/**
	* Holds the DSN string for database connectivity.
	* @var string 
	*/
	protected $dsn = null;
	
	/**
	* Holds the username ID string for database connectivity.
	* @var string 
	*/
	protected $username = null;
	
	/**
	* Holds the password string for database connectivity.
	* @var string 
	*/
	protected $password = null;
	
	/**
	* Holds the freeze-flag. If TRUE RedBean will run in FROZEN
	* mode. If FALSE (default) RedBean will run in FLUID mode.
	* @var boolean 
	*/
	protected $freeze = false;
	
	/**
	 * Plugin setter for use in application.ini
	 * To set this value in config use:
	 * resources.ZRedBean.<this item> = <value>
	 * @param string $value
	 */
	public function setDsn( $dsn ) {
		$this->dsn = $dsn;
	}

	/**
	 * Plugin setter for use in application.ini
	 * To set this value in config use:
	 * resources.ZRedBean.<this item> = <value>
	 * @param string $value
	 */
	public function setUsername( $username ) {
		$this->username = $username;
	}

	/**
	 * Plugin setter for use in application.ini
	 * To set this value in config use:
	 * resources.ZRedBean.<this item> = <value>
	 * @param string $value
	 */
	public function setPassword( $password ) {
		$this->password = $password;
	}

	/**
	 * Plugin setter for use in application.ini
	 * To set this value in config use:
	 * resources.ZRedBean.<this item> = <value>
	 * @param string $value
	 */
	public function setFreeze( $tf ) {
		$this->freeze = $tf;		
	}

	/**
	 * Because we are in initialization phase we rather show 
	 * errors than throw exceptions.
	 * @param string $message
	 */
	public function showErrorMessage( $message ) {
		die("
			<h1 style='color:red'>Oops, we ran into a problem..</h1><br/>
			$message 
		");
	}

	/**
	 * Initializes the Resource Plugin for RedBean.
	 * This method should be invoked after the settings have been  
	 * processed.
	 */
    public function init() {
    	
    	//Check the current PHP version
		if (version_compare(PHP_VERSION, '5.3', '<')) {
			$this->showErrorMessage("RedBean requires PHP 5.3 or higher.");
		}
		
		if (!$this->dsn) {
			R::setup();
		}
		else {
			if (!$this->username) {
				$this->showErrorMessage("No username given. 
				Please provide a resources.ZRedBean.username in application.ini! ");
			}
			R::setup($this->dsn,$this->username,$this->password);
		}
		
		//Toggle Freeze
		if ($this->freeze) R::freeze();
		
		//Add objects to registry
		Zend_Registry::set('redbean_oodb',R::$redbean);
		Zend_Registry::set('redbean_querywriter',R::$writer);
		Zend_Registry::set('redbean_adapter',R::$adapter);
	}
	
	/**
	 * Loads the RedBean All-in-one pack.
	 * Note that this plugin is only handy for the all-in-one pack.
	 * If you use the 'unpacked' version of RedBeanPHP you could also just
	 * add the namespace to the Zend Autoloader and add your own bootstrap method.
	 */
	public static function load() {
		
		//Find the current directory
		$dir = dirname( __FILE__ );
		
		//Assemble the filename
		$filename = $dir.'/rb.php';
		$filenameB = $dir.'/rb.pack.php';
		
		//Does the file exist?
		if (file_exists($filenameB)) {
			return $filenameB;
		}
		elseif(file_exists($filename)) {
			return $filename;
		}
		else{
			$this->showErrorMessage("Could not find rb.php, 
				make sure it is in the same folder as ZRedBean.php, 
				looking in: $dir");
		}
	
	}
    
}


//Try to include the RedBean All in one pack
require_once( RedBean_ZRedBean::load() );



