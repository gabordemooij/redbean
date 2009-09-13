<?php 
/**
 * PDO Driver
 * @package 		RedBean/PDO.php
 * @description		PDO Driver
 * @author			Desfrenes
 * @license			BSD
 */
class Redbean_Driver_PDO implements RedBean_Driver {

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
     * 
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
            self::$instance = new Redbean_Driver_PDO($dsn, $user, $pass);
            
        }
        return self::$instance;
    }
    
    /**
     * 
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
    public function GetAll( $sql )
    {
    	$this->exc = 0;
    	try{ 
	        if ($this->debug)
	        {
	            echo "<HR>" . $sql;
	        }
	        $rs = $this->pdo->query($sql);
	        $this->rs = $rs;
	        $rows = $rs->fetchAll();
	        if(!$rows)
	        {
	            $rows = array();
	        }
	        
	        if ($this->debug)
	        {
	            if (count($rows) > 0)
	            {
	                echo "<br><b style='color:green'>resultset: " . count($rows) . " rows</b>";
	            }
	            
	        }
    	}
    	catch(Exception $e){ $this->exc = 1; 
    	
    			if ($this->debug){
	           	 $str = $this->Errormsg();
	           	 if ($str != "")
	           	 {
	           	     echo "<br><b style='color:red'>" . $str . "</b>";
	           	 }
    			}
    	return array(); }
        return $rows;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetCol()
     */
    public function GetCol($sql)
    {
    	$this->exc = 0;
    	try{
	        $rows = $this->GetAll($sql);
	        $cols = array();
	 
	        if ($rows && is_array($rows) && count($rows)>0){
		        foreach ($rows as $row)
		        {
		            $cols[] = array_shift($row);
		        }
	        }
	    	
    	}
    	catch(Exception $e){ 
    		$this->exc = 1;
    		return array(); }
        return $cols;
    }
 
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetCell()
     */
    public function GetCell($sql)
    {
    	$this->exc = 0;
    	try{
	        $arr = $this->GetAll($sql);
	        $row1 = array_shift($arr);
	        $col1 = array_shift($row1);
    	}
    	catch(Exception $e){ $this->exc = 1; }
        return $col1;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetRow()
     */
    public function GetRow($sql)
    {
    	$this->exc = 0;
    	try{
        	$arr = $this->GetAll($sql);
    	}
       	catch(Exception $e){ $this->exc = 1; return array(); }
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
    public function Execute( $sql )
    {
    	$this->exc = 0;
    	try{
	        if ($this->debug)
	        {
	            echo "<HR>" . $sql;
	        }
	        $this->affected_rows = $this->pdo->exec($sql);
	       
    	}
    	catch(Exception $e){ $this->exc = 1; 
    	
    	 if ($this->debug)
	        {
	            $str = $this->Errormsg();
	            if ($str != "")
	            {
	                echo "<br><b style='color:red'>" . $str . "</b>";
	            }
	        }
    	return 0; }
        return $this->affected_rows;
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
    
}
