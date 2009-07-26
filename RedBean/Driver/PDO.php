<?php 

/**
 * PDO support driver
 * the PDO driver has been written by Desfrenes.
 */
class Redbean_Driver_PDO implements RedBean_Driver {
    private static $instance;
    
    private $debug = false;
    private $pdo;
    private $affected_rows;
    private $rs;
    private $exc =0;
    
    public static function getInstance($dsn, $user, $pass, $dbname)
    {
        if(is_null(self::$instance))
        {
            self::$instance = new Redbean_Driver_PDO($dsn, $user, $pass);
        }
        return self::$instance;
    }
    
    public function __construct($dsn, $user, $pass)
    {
        $this->pdo = new PDO(
                $dsn,
                $user,
                $pass,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC)
            );
    }
    
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
    
    public function GetRow($sql)
    {
    	$this->exc = 0;
    	try{
        	$arr = $this->GetAll($sql);
    	}
       	catch(Exception $e){ $this->exc = 1; return array(); }
        return array_shift($arr);
    }
    
    public function ErrorNo()
    {
    	if (!$this->exc) return 0;
    	$infos = $this->pdo->errorInfo();
        return $infos[1];
    }
    public function Errormsg()
    {
    	if (!$this->exc) return "";
        $infos = $this->pdo->errorInfo();
        return $infos[2];
    }
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
    public function Escape( $str )
    {
        return substr(substr($this->pdo->quote($str), 1), 0, -1);
    }
    public function GetInsertID()
    {
        return (int) $this->pdo->lastInsertId();
    }
    public function Affected_Rows()
    {
        return (int) $this->affected_rows;
    }
    public function setDebugMode( $tf )
    {
        $this->debug = (bool)$tf;
    }
    public function GetRaw()
    {
        return $this->rs;
    }
}
