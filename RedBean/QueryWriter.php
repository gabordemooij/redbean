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
	
	/**
	 * Returns the requested query if the writer has any
	 * @param $queryname
	 * @param $params
	 * @return mixed $sql_query
	 */
	public function getQuery( $queryname, $params=array() );
	
	/**
	 * Gets the quote-escape symbol of this writer
	 * @return unknown_type
	 */
	public function getQuote();

	/**
	 * Gets the backtick for this writer
	 * @return unknown_type
	 */
	public function getEscape();
	
	
	/**
	 * 
	 * @param string $tbl
	 * @param RedBean_DBAdapter $db
	 * @return array $arr( array('Field'=>$string, 'Type'=>$string) )
	 */
	public function getTableColumns( $tbl, RedBean_DBAdapter $db );

}