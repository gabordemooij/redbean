<?php

namespace RedBeanPHP\Util;

use RedBeanPHP\ToolBox;
use RedBeanPHP\OODBBean;

/**
 * Tree
 *
 * Given a bean, finds it children or parents
 * in a hierchical structure.
 *
 * @experimental feature
 *
 * @file    RedBeanPHP/Util/Tree.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Tree {

	/**
	 * @var ToolBox
	 */
	protected $toolbox;

	/**
	 * @var QueryWriter
	 */
	protected $writer;

	/**
	 * @var OODB
	 */
	protected $oodb;

	/**
	 * Constructor, creates a new instance of
	 * the Tree.
	 *
	 * @param ToolBox $toolbox toolbox
	 */
	public function __construct( ToolBox $toolbox )
	{
		$this->toolbox = $toolbox;
		$this->writer  = $toolbox->getWriter();
		$this->oodb    = $toolbox->getRedBean();
	}

	/**
	 * Returns all child beans associates with the specified
	 * bean in a tree structure.
	 *
	 * @note this only works for databases that support
	 * recusrive common table expressions.
	 *
	 * @param OODBBean $bean     reference bean to find children of
	 * @param string   $sql      optional SQL snippet
	 * @param array    $bindings optional parameter bindings for SQL snippet
	 *
	 * @return array
	 */
	public function children( OODBBean $bean, $sql = NULL, $bindings = array() )
	{
		$type = $bean->getMeta('type');
		$id   = $bean->id;

		$rows = $this->writer->queryRecursiveCommonTableExpression( $type, $id, FALSE, $sql, $bindings );

		return $this->oodb->convertToBeans( $type, $rows );
	}
	
	/**
	 * Returns all parent beans associates with the specified
	 * bean in a tree structure.
	 *
	 * @note this only works for databases that support
	 * recusrive common table expressions.
	 *
	 * @param OODBBean $bean     reference bean to find parents of
	 * @param string   $sql      optional SQL snippet
	 * @param array    $bindings optional parameter bindings for SQL snippet
	 *
	 * @return array
	 */
	public function parents( OODBBean $bean, $sql = NULL, $bindings = array() )
	{
		$type = $bean->getMeta('type');
		$id   = $bean->id;

		$rows = $this->writer->queryRecursiveCommonTableExpression( $type, $id, TRUE, $sql, $bindings );

		return $this->oodb->convertToBeans( $type, $rows );		
	}	
}
