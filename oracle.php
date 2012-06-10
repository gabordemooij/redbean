<?php
/**
create sequence myseq; 
 
create trigger mytrigger 
before insert on book for each row 
begin 
  select myseq.nextval into :new.id from dual; 
end; 


**/
require("rb.php");
echo "we are here!";


//$conn = oci_connect('xdev', 'xdev', 'localhost/XE');
//$conn = new PDO("oci:dbname=localhost/XE","xdev","xdev");


class RedBean_Driver_OCI implements RedBean_Driver { 

	private $dsn;

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

    private $autocommit = true;
    
    /* Hold the statement for the last query */
    private $statement;


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
            self::$instance = new RedBean_Driver_OCI($dbname, $user, $pass);
            
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
    public function __construct($db, $user, $pass)
    {
	echo "$user, $pass, $db";
	$conn = oci_connect($user, $pass, $db);
	$this->connection = $conn;


    }

    public function setAutoCommit( $toggle ) {
       $this->autocommit = (bool) $toggle;
    }	

    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetAll()
     */
    public function GetAll( $sql, $aValues=array() )
    {
	$this->Execute($sql, $aValues);
        $rows = array();
	while($rs=oci_fetch_assoc($this->statement)) $rows[]=$rs;
        return $rows;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetCol()
     */
    public function GetCol($sql, $aValues=array())
    {
    	
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
    	
       	$arr = $this->GetAll($sql, $aValues);
        return array_shift($arr);
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#ErrorNo()
     */
    public function ErrorNo()
    {
    	
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#Errormsg()
     */
    public function Errormsg()
    {
    	
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#Execute()
     */
    public function Execute( $sql, $aValues=array() )
    {
	echo $sql;
        foreach($aValues as $key=>$value)
        {
             $sql = preg_replace('/\?/', ' :SLOT'.$key.' ', $sql, 1); 
        }

        $stid = oci_parse($this->connection, $sql);

	foreach($aValues as $key=>$value) {
             ${'SLOT'.$key} = $value;
            oci_bind_by_name($stid, ':SLOT'.$key, ${'SLOT'.$key});
	}


	if (!$this->autocommit)
            oci_execute($stid, OCI_NO_AUTO_COMMIT);  // data not committed
	else 
            oci_execute($stid);
        
        $this->statement = $stid;
        
		
    }

    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#Escape()
     */
    public function Escape( $str )
    {
        return $str;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetInsertID()
     */
    public function GetInsertID()
    {
        return 0;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#Affected_Rows()
     */
    public function Affected_Rows()
    {
        return 0;
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#setDebugMode()
     */
    public function setDebugMode( $tf )
    {
        
    }
    
    /**
     * (non-PHPdoc)
     * @see RedBean/RedBean_Driver#GetRaw()
     */
    public function GetRaw()
    {
        //return $this->rs;
    }


	/**
	 * Starts a transaction.
	 */
	public function StartTrans() {
		
	}

	/**
	 * Commits a transaction.
	 */
	public function CommitTrans() {
		oci_commit($this->connection);
	}


	/**
	 * Rolls back a transaction.
	 */
	public function FailTrans() {
		oci_rollback($this->connection);
	}

	/**
	 * Returns the name of the database type/brand: i.e. mysql, db2 etc.
	 * @return string $typeName
	 */
	public function getDatabaseType() {
		return "OCI";
	}

	/**
	 * Returns the version number of the database.
	 * @return mixed $version 
	 */
	public function getDatabaseVersion() {
		return "8";
	}

}

class RedBean_QueryWriter_OCI extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter{

	protected $adapter;

	public function __construct(RedBean_Adapter $a) {
		$this->adapter = $a;
	}

	public function addUniqueIndex( $table, $columns ){
	}

	public function getTables(){
	 throw new RedBean_Exception_SQL("This extension is for frozen mode only.");
	}
		

public function createTable( $table ){ 
throw new RedBean_Exception_SQL("This extension is for frozen mode only.");
 }
    public function getColumns( $table ){ return array();
throw new RedBean_Exception_SQL("This extension is for frozen mode only.");
}

    public function addColumn( $table, $column, $type ){
		throw new RedBean_Exception_SQL("This extension is for frozen mode only.");
	}

	
    public function code( $typedescription ){ throw new RedBean_Exception_SQL("This extension is for frozen mode only.");
	}
    public function widenColumn( $table, $column, $type ){
		throw new RedBean_Exception_SQL("This extension is for frozen mode only.");
	}

    public function insertRecord( $table, $insertcolumns, $insertvalues ){
               
		foreach($insertcolumns as $key=>$column) {
			$insertcolumns[$key]=$this->noKW($column);
		}
		$columns = implode(",", $insertcolumns);	

		foreach($insertvalues as $iv) {
			$table = $this->noKW($table);	
			
			$this->adapter->exec(" INSERT INTO $table ($columns) VALUES(".implode(",",array_fill(0,count($iv),"?")).") ",$iv);
		}

	}

	public function deleteRecord( $table, $id){
		$this->deleteRecordArguments = array($table, "id", $id);
		return $this->returnDeleteRecord;
	}
/*
    public function checkChanges($type, $id, $logid){
		$this->checkChangesArguments = array($type, $id, $logid);
		return $this->returnCheckChanges;
	}
	public function addUniqueIndex( $table,$columns ){
		$this->addUniqueIndexArguments=array($table,$columns);
		return $this->returnAddUniqueIndex;
	}
*/
	public function selectByCrit( $select, $table, $column, $value, $withUnion=false ){
		$this->selectByCritArguments=array($select, $table, $column, $value, $withUnion);
		return $this->returnSelectByCrit;
	}

	public function deleteByCrit( $table, $crits ){
		$this->deleteByCrit=array($table, $crits );
		return $this->returnDeleteByCrit;
	}


	public function getIDField( $type ) { return "id"; }

	public function noKW($str){
		return preg_replace("/\W/","",$str);
	}

	public function sqlStateIn($state,$list) { return true; }
        
	/**
	 * Returns the Column Type Code (integer) that corresponds
	 * to the given value type. This method is used to determine the minimum
	 * column type required to represent the given value.
	 *
	 * @param string $value value
	 *
	 * @return integer $type type
	 */
	public function scanType($value, $alsoScanSpecialForTypes=false){
            
        }      

	
}

$driver = RedBean_Driver_OCI::getInstance("oci","hr","hr","");
$adapter = new RedBean_Adapter_DBAdapter( $driver );
$writer = new RedBean_QueryWriter_OCI( $adapter );
$redbean = new RedBean_OODB( $writer );
$toolbox = new RedBean_ToolBox( $redbean, $adapter, $writer );

//regular table read/write
$redbean->freeze( true );
$book = $redbean->dispense("book");

$book->title = "Harry Potter";
$book->rating = 3;
$redbean->store( $book );

$book = $redbean->load('book',1);


//$village = $redbean->dispense('village');
//$building1 = $redbean->dispense('building');
//$building2 = $redbean->dispense('building');
//
//$village->ownBuilding = array($building1,$building2); //replaces entire list
//$redbean->store($village); 





















