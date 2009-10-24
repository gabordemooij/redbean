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
    public function deleteRecord( $table, $column, $value);
    public function checkChanges($type, $id, $logid);
	public function addUniqueIndex( $table,$columns );
	
}