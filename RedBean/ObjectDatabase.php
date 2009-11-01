<?php

interface ObjectDatabase {

	public function load( $type, $id );
	public function store( RedBean_OODBBean $bean );
	public function trash( RedBean_OODBBean $bean );
	public function batch( $type, $ids );
	public function dispense( $type );

}