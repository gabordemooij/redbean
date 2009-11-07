<?php 
/**
 * QueryWriter
 * Interface for QueryWriters
 * @package 		RedBean/QueryWriter.php
 * @description		Describes the API for a QueryWriter
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_QueryWriter {

	const C_DATATYPE_BOOL = 0;
	const C_DATATYPE_UINT8 = 1;
	const C_DATATYPE_UINT32 = 2;
	const C_DATATYPE_DOUBLE = 3;
	const C_DATATYPE_TEXT8 = 4;
	const C_DATATYPE_TEXT16 = 5;
	const C_DATATYPE_TEXT32 = 6;
	const C_DATATYPE_SPECIFIED = 99;


	public function getTables();
    public function createTable( $table );
    public function getColumns( $table );
	public function scanType( $value );
    public function addColumn( $table, $column, $type );
    public function code( $typedescription );
    public function widenColumn( $table, $column, $type );
    public function updateRecord( $table, $updatevalues, $id);
    public function insertRecord( $table, $insertcolumns, $insertvalues );
    public function selectRecord($type, $ids);
    public function deleteRecord( $table, $id );
    
	public function addUniqueIndex( $table,$columns );
	
}