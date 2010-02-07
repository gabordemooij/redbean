<?php 
/**
 * PDO Driver
 * @file			RedBean/PDO.php
 * @description		PDO Driver
 *					This Driver implements the RedBean Driver API
 * @author			Desfrenes
 * @license			BSD
 */
class RedBean_Driver_PDO implements RedBean_Driver {

	/**
	 * 
	 * @var unknown_type
	 */
	private static $instance;
    
	/**
	 * 
	 * @var boolean
	 */
    private $debug = false;
    
    /**
     * 
     * @var unknown_type
     */
    private $pdo;
    
    /**
     * 
     * @var unknown_type
     */
    private $affected_rows;
    
    /**
     * 
     * @var unknown_type
     */
    private $rs;
    
    /**
     * 
     * @var unknown_type
     */
    private $exc =0;


    /**
     * Returns an instance of the PDO Driver.
     * @param $dsn
     * @param $user
     * @param $pass
     * @param $dbname
     * @return unknown_type
     */
    public static function getInstance($dsn, $user, $pass, $dbname)
    {
        if(is_null(self::$instance))
        {
            self::$instance = new RedBean_Driver_PDO($dsn, $user, $pass);
            
        }
        return self::$instance;
    }
    
    /**
     * Constructor.
     * @param $dsn
     * @param $user
     * @param $pass
     * @return unknown_type
     */
    public function __construct($dsn, $user, $pass)
    {
    	//PDO::MYSQL_ATTR_INIT_COMMAND
        $this->pdo = new PDO(
                $dsn,
                $user,
                $pass,
                
                array(1002 => 'SET NAMES utf8',
                      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC)
            );
    }

    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetAll()
     */
    public function GetAll( $sql, $aValues=array() )
    {
		
    	$this->exc = 0;
		if ($this->debug) {
			echo "<HR>" . $sql.print_r($aValues,1);
		}
		$s = $this->pdo->prepare($sql);

		try {
		$s->execute($aValues);
		$this->rs = $s->fetchAll();
		$rows = $this->rs;
		}catch(PDOException $e) {
			//Unfortunately the code field is supposed to be int by default (php)
			//So we need a property to convey the SQL State code.
			if (version_compare(PHP_VERSION, '5.3.0', '<')) {
				$x = new RedBean_Exception_SQL( $e->getMessage(), 0);
			}
			else {
				$x = new RedBean_Exception_SQL( $e->getMessage(), 0, $e );
			}
			$x->setSQLState( $e->getCode() );
			throw $x;
		}

		if(!$rows) {
			$rows = array();
		}

		if ($this->debug) {
			if (count($rows) > 0) {
				echo "<br><b style='color:green'>resultset: " . count($rows) . " rows</b>";
			}
		}
        return $rows;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetCol()
     */
    public function GetCol($sql, $aValues=array())
    {
    	$this->exc = 0;
		$rows = $this->GetAll($sql,$aValues);
		$cols = array();

		if ($rows && is_array($rows) && count($rows)>0){
			foreach ($rows as $row)
			{
				$cols[] = array_shift($row);
			}
		}

        return $cols;
    }
 
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetCell()
     */
    public function GetCell($sql, $aValues=array())
    {
    	$this->exc = 0;
        $arr = $this->GetAll($sql,$aValues);
	    $row1 = array_shift($arr);
	    $col1 = array_shift($row1);
        return $col1;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetRow()
     */
    public function GetRow($sql, $aValues=array())
    {
    	$this->exc = 0;
       	$arr = $this->GetAll($sql, $aValues);
        return array_shift($arr);
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#ErrorNo()
     */
    public function ErrorNo()
    {
    	if (!$this->exc) return 0;
    	$infos = $this->pdo->errorInfo();
        return $infos[1];
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#Errormsg()
     */
    public function Errormsg()
    {
    	if (!$this->exc) return "";
        $infos = $this->pdo->errorInfo();
        return $infos[2];
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#Execute()
     */
    public function Execute( $sql, $aValues=array() )
    {
    	$this->exc = 0;
    	    if ($this->debug)
	        {
	            echo "<HR>" . $sql.print_r($aValues,1);
	        }
			try {
			$s = $this->pdo->prepare($sql);
			$s->execute($aValues);
			$this->affected_rows=$s->rowCount();
			return $this->affected_rows;
			}
			catch(PDOException $e) {
				//Unfortunately the code field is supposed to be int by default (php)
				//So we need a property to convey the SQL State code.
				if (version_compare(PHP_VERSION, '5.3.0', '<')) {
					$x = new RedBean_Exception_SQL( $e->getMessage(), 0);
				}
				else {
					$x = new RedBean_Exception_SQL( $e->getMessage(), 0, $e );
				}
				
				$x->setSQLState( $e->getCode() );
				throw $x;
				
			}
		//	
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#Escape()
     */
    public function Escape( $str )
    {
        return substr(substr($this->pdo->quote($str), 1), 0, -1);
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetInsertID()
     */
    public function GetInsertID()
    {
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#Affected_Rows()
     */
    public function Affected_Rows()
    {
        return (int) $this->affected_rows;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#setDebugMode()
     */
    public function setDebugMode( $tf )
    {
        $this->debug = (bool)$tf;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetRaw()
     */
    public function GetRaw()
    {
        return $this->rs;
    }


	/**
	 * Starts a transaction.
	 */
	public function StartTrans() {
		$this->pdo->beginTransaction();
	}

	/**
	 * Commits a transaction.
	 */
	public function CommitTrans() {
		$this->pdo->commit();
	}


	/**
	 * Rolls back a transaction.
	 */
	public function FailTrans() {
		$this->pdo->rollback();
	}

	/**
	 * Returns the name of the database type/brand: i.e. mysql, db2 etc.
	 * @return string $typeName
	 */
	public function getDatabaseType() {
		return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * Returns the version number of the database.
	 * @return mixed $version 
	 */
	public function getDatabaseVersion() {
		return $this->pdo->getAttribute(PDO::ATTR_CLIENT_VERSION);
	}

}
