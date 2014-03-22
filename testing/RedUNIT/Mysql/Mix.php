<?php 

namespace RedUNIT\Mysql;

use RedUNIT\Mysql as Mysql;
use RedBeanPHP\Facade as R;
use RedBeanPHP\SQLHelper as SQLHelper; 

/**
 * RedUNIT_Mysql_Mix
 *
 * @file    RedUNIT/Mysql/Mix.php
 * @desc    Tests mixing SQL with PHP, SQLHelper class.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Mix extends Mysql
{
	
	/**
	 * Test query building.
	 * 
	 * @return void
	 */
	public function testQueryBuilderMix()
	{
		$toolbox = R::getToolBox();

		$adapter = $toolbox->getDatabaseAdapter();

		$mixer = new SQLHelper( $adapter );

		$now = $mixer->now();

		asrt( is_string( $now ), TRUE );

		asrt( ( strlen( $now ) > 5 ), TRUE );

		$bean = R::dispense( 'bean' );

		$bean->field1 = 'a';
		$bean->field2 = 'b';

		R::store( $bean );

		$data = $mixer->begin()->select( '*' )->from( 'bean' )
			->where( ' field1 = ? ' )->put( 'a' )->get();

		asrt( is_array( $data ), TRUE );

		$row = array_pop( $data );

		asrt( is_array( $row ), TRUE );

		asrt( $row['field1'], 'a' );
		asrt( $row['field2'], 'b' );

		$row = $mixer->begin()->select( 'field1', 'field2' )->from( 'bean' )
			->where( ' 1 ' )->limit( '1' )->get( 'row' );

		asrt( is_array( $row ), TRUE );

		asrt( $row['field1'], 'a' );
		asrt( $row['field2'], 'b' );

		$cell = $mixer->begin()->select( 'field1' )->from( 'bean' )
			->get( 'cell' );

		asrt( $cell, 'a' );

		$cell = $mixer->begin()->select_field1_from( 'bean' )
			->get( 'cell' );

		asrt( $cell, 'a' );

		// Now switch back to non-capture mode (issue #142)
		$value = $mixer->now();

		asrt( is_object( $value ), FALSE );
		asrt( is_scalar( $value ), TRUE );

		asrt( $value > 0, TRUE );

		$mixer->begin()->select_field1_from( 'bean' );

		$mixer->clear();

		$value = $mixer->now();

		asrt( is_scalar( $value ), TRUE );

		// Test open and close block commands
		$bean = R::dispense( 'bean' );

		$bean->num = 2;

		R::store( $bean );

		$value = $mixer->begin()
			->select( 'num' )->from( 'bean' )->where( 'num IN' )
			->open()
			->addSQL( '2' )
			->close()
			->get( 'cell' );

		asrt( ( $value == 2 ), TRUE );

		// Test nesting
		$bean = R::dispense( 'bean' );

		$bean->num = 2;

		R::store( $bean );

		$value = $mixer->begin()
			->select( 'num' )->from( 'bean' )->where( 'num IN' )
			->nest( $mixer->getNew()->begin()->addSQL( ' ( 2 ) ' ) )
			->get( 'cell' );

		asrt( ( $value == 2 ), TRUE );
	}

	/**
	 * Test the __toString method of the SQLHelper. 
	 */
	public function testToString()
	{
		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();	
		$sqlHelper = new SQLHelper( $adapter );
		$sqlHelper->begin()
				  ->select( '*' )
				  ->from( 'table' )
				  ->where( 'name = ?' )
				  ->put( 'name' );
		$str = (string) $sqlHelper;
		asrt( ( strpos( $str, 'query' ) !== false ), true );
		asrt( ( strpos( $str, 'select * from table where name = ?' ) !== false ), true );
		asrt( ( strpos( $str, '=> Array') !== false ), true );
		asrt( ( strpos( $str, 'params') !== false ), true );
	}
}




