<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Stab tests for VMs without CUBRID database
 *
 * Tests CUBRID driver but without DB.
 * For full tests see CUBRID test folder.
 *
 * @file    RedUNIT/Base/Stub.php
 * @desc    Tests CUBRID without actual DB (mock adapter)
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Stub extends Base
{
	/**
	 * Test CUBRID.
	 */
	public function testCUBRID()
	{
		$mockdapter = new \Mockdapter();
		$writer = new DiagnosticCUBRIDWriter( $mockdapter );
		pass();
		$type = 'bean';
		$targetType = 'other';
		$property = 'property';
		$targetProperty = 'other';
		$value = 'value';
		$properties = array( 'property','other' );
		$table = 'bean';
		$column = 'field';
		$list = array();
		$typedescription = ' STRING ';
		$state = '';
		$field = 'field';
		$dbStructure = 'test';
		$name = 'name';
		$writer->callMethod( 'buildFK', $type, $targetType, $property, $targetProperty, $isDep = FALSE );
		pass();
		$writer->callMethod( 'getKeyMapForType', 'CONSTRAINT key FOREIGN KEY (bean) REFERENCES [bean] ON DELETE CASCADE ON UPDATE RESTRICT' );
		

		pass();
		$writer->getTypeForID();
		pass();
		$writer->getTables();
		pass();
		$writer->createTable( $table );
		pass();
		$writer->getColumns( $table );
		pass();
		$writer->scanType( $value, $flagSpecial = FALSE );
		pass();
		$writer->code( $typedescription, $includeSpecials = FALSE );
		pass();
		$writer->addColumn( $type, $column, $field );
		pass();
		$writer->addUniqueConstraint( $type, $properties );
		pass();
		$writer->sqlStateIn( $state, $list, $extraDriverDetails = array() );
		pass();
		$writer->addIndex( $type, $name, $column );
		pass();
		$writer->addFK( $type, $targetType, $property, $targetProperty, $isDependent = FALSE );
		pass();
		$writer->wipeAll();
		pass();
		$writer->esc( $dbStructure, $noQuotes = FALSE );
		pass();
		$writer->inferFetchType( $type, $property );
		pass();
	}

}

class DiagnosticCUBRIDWriter extends \RedBeanPHP\QueryWriter\CUBRID {
	
	public function callMethod( $method, $arg1 = NULL, $arg2 = NULL, $arg3 = NULL, $arg4 = NULL, $arg5 = NULL ) {
		return $this->$method( $arg1, $arg2, $arg3, $arg4, $arg5 );
	}
}
