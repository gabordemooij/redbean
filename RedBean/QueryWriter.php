<?php 

interface QueryWriter {
	
	public function getQuery( $queryname, $params=array() );
	
}