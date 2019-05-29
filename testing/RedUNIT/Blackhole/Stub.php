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
		$writer = new \DiagnosticCUBRIDWriter( $mockdapter );
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
		$mockdapter->answerGetCol = array();
		$writer->callMethod( 'buildFK', $type, $targetType, $property, $targetProperty, $isDep = FALSE );
		pass();
		$mockdapter->errorExec = new \RedBeanPHP\RedException\SQL('Test Exception');
		$writer->callMethod( 'buildFK', $type, $targetType, $property, $targetProperty, $isDep = FALSE );
		pass();
		$mockdapter->errorExec = NULL;
		$mockdapter->answerGetSQL = array(
			array(
				'CREATE TABLE' => 'CONSTRAINT [key] FOREIGN KEY ([bean]) REFERENCES [bean] ON DELETE CASCADE ON UPDATE RESTRICT'
			)
		);
		$writer->addFK( $type, $targetType, $property, $targetProperty, $isDependent = FALSE );
		pass();
		$writer->callMethod( 'getKeyMapForType', 'bean' );
		pass();
		$writer->inferFetchType( $type, $property );
		pass();
		$writer->getTypeForID();
		pass();
		$writer->getTables();
		pass();
		$writer->createTable( $table );
		pass();
		$mockdapter->answerGetSQL = array(array('Field'=>'title','Type'=>'STRING'));
		$writer->getColumns( $table );
		pass();
		asrt( $writer->scanType( 123, $flagSpecial = FALSE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_INTEGER );
		asrt( $writer->scanType( 12.3, $flagSpecial = FALSE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_DOUBLE );
		asrt( $writer->scanType( '0001', $flagSpecial = FALSE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_STRING );
		asrt( $writer->scanType( '1001', $flagSpecial = FALSE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_INTEGER );
		asrt( $writer->scanType( NULL, $flagSpecial = FALSE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_INTEGER );
		asrt( $writer->scanType( '2019-01-01', $flagSpecial = FALSE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_STRING );
		asrt( $writer->scanType( '2019-01-01 10:00:00', $flagSpecial = FALSE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_STRING );
		asrt( $writer->scanType( '2019-01-01', $flagSpecial = TRUE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_SPECIAL_DATE );
		asrt( $writer->scanType( '2019-01-01 10:00:00', $flagSpecial = TRUE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_SPECIAL_DATETIME );
		pass();
		$writer->code( $typedescription, $includeSpecials = FALSE );
		$writer->code( $typedescription, $includeSpecials = TRUE );
		asrt( $writer->code( 'INTEGER', FALSE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_INTEGER );
		asrt( $writer->code( 'DOUBLE', FALSE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_DOUBLE );
		asrt( $writer->code( 'STRING', FALSE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_STRING );
		asrt( $writer->code( 'DATE', FALSE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_SPECIFIED );
		asrt( $writer->code( 'DATETIME', FALSE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_SPECIFIED );
		asrt( $writer->code( 'INTEGER', TRUE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_INTEGER );
		asrt( $writer->code( 'DOUBLE', TRUE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_DOUBLE );
		asrt( $writer->code( 'STRING', TRUE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_STRING );
		asrt( $writer->code( 'DATE', TRUE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_SPECIAL_DATE );
		asrt( $writer->code( 'DATETIME', TRUE ), \RedBeanPHP\QueryWriter\CUBRID::C_DATATYPE_SPECIAL_DATETIME );
		pass();
		$writer->addColumn( $type, $column, $field );
		pass();
		$writer->addUniqueConstraint( $type, $properties );
		$mockdapter->errorExec = new \RedBeanPHP\RedException\SQL('Test Exception');
		$writer->addUniqueConstraint( $type, $properties );
		pass();
		asrt( $writer->sqlStateIn( 'HY000', array() ), FALSE );
		asrt( $writer->sqlStateIn( 'HY000', array(\RedBeanPHP\QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION) ), TRUE );
		pass();
		$writer->addIndex( $type, $name, $column );
		pass();
		$mockdapter->errorExec = NULL;
		$writer->addIndex( $type, $name, $column );
		pass();
		$writer->wipeAll();
		pass();
		$mockdapter->answerGetCol = array( 'table1' );
		$mockdapter->answerGetSQL = array(
			array(
				'CREATE TABLE' => 'CONSTRAINT [key] FOREIGN KEY ([bean]) REFERENCES [bean] ON DELETE CASCADE ON UPDATE RESTRICT'
			)
		);
		$writer->wipeAll();
		pass();
		$writer->esc( $dbStructure, $noQuotes = FALSE );
		pass();
	}

	/**
	 * Stub test for SSL-connect.
	 * 
	 * @return void
	 */
	 public function testSSL()
	 {
		$pdo = R::getPDO();
		$mock = new \MockPDO;
		R::getDatabaseAdapter()->getDatabase()->setPDO( $mock );
		R::addToolBoxWithKey( 'stub', new \RedBeanPHP\ToolBox( R::getRedBean(), R::getDatabaseAdapter(), R::getWriter()) );
		R::useMysqlSSL( 'key.pem','cert.pem','cacert.pem', 'stub' );
		R::getDatabaseAdapter()->getDatabase()->setPDO( $pdo );
		$prop = \PDO::MYSQL_ATTR_SSL_KEY;
		asrt( $mock->getDiagAttribute($prop), 'key.pem' );
		$prop = \PDO::MYSQL_ATTR_SSL_CERT;
		asrt( $mock->getDiagAttribute($prop), 'cert.pem' );
		$prop = \PDO::MYSQL_ATTR_SSL_CA;
		asrt( $mock->getDiagAttribute($prop), 'cacert.pem' );
	 }
}


