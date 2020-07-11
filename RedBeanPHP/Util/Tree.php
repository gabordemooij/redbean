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
	 * Usage:
	 *
	 * <code>
	 * $newsArticles = R::children( $newsPage, ' ORDER BY title ASC ' ) 
	 * $newsArticles = R::children( $newsPage, ' WHERE title = ? ', [ $t ] );
	 * $newsArticles = R::children( $newsPage, ' WHERE title = :t ', [ ':t' => $t ] );
	 * </code>
	 *
	 * Note:
	 * You are allowed to use named parameter bindings as well as
	 * numeric parameter bindings (using the question mark notation).
	 * However, you can not mix. Also, if using named parameter bindings,
	 * parameter binding key ':slot0' is reserved for the ID of the bean
	 * and used in the query.
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
	 * <code>
	 * $newsPages = R::parents( $newsArticle, ' ORDER BY title ASC ' );
	 * $newsPages = R::parents( $newsArticle, ' WHERE title = ? ', [ $t ] );
	 * $newsPages = R::parents( $newsArticle, ' WHERE title = :t ', [ ':t' => $t ] );
	 * </code>
	 *
	 * Note:
	 * You are allowed to use named parameter bindings as well as
	 * numeric parameter bindings (using the question mark notation).
	 * However, you can not mix. Also, if using named parameter bindings,
	 * parameter binding key ':slot0' is reserved for the ID of the bean
	 * and used in the query.
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

	/**
	 * Counts all children beans associates with the specified
	 * bean in a tree structure.
	 *
	 * @note this only works for databases that support
	 * recusrive common table expressions.
	 *
	 * <code>
	 * $count = R::countChildren( $newsArticle );
	 * $count = R::countChildren( $newsArticle, ' WHERE title = ? ', [ $t ] );
	 * $count = R::countChildren( $newsArticle, ' WHERE title = :t ', [ ':t' => $t ] );
	 * </code>
	 *
	 * @note:
	 * You are allowed to use named parameter bindings as well as
	 * numeric parameter bindings (using the question mark notation).
	 * However, you can not mix. Also, if using named parameter bindings,
	 * parameter binding key ':slot0' is reserved for the ID of the bean
	 * and used in the query.
	 *
	 * @note:
	 * By default, if no SQL or select is given or select=TRUE this method will subtract 1 of
	 * the total count to omit the starting bean. If you provide your own select,
	 * this method assumes you take control of the resulting total yourself since
	 * it cannot 'predict' what or how you are trying to 'count'.
	 *
	 * @param OODBBean       $bean     reference bean to find children of
	 * @param string         $sql      optional SQL snippet
	 * @param array          $bindings optional parameter bindings for SQL snippet
	 * @param string|boolean $select   select snippet to use (advanced, optional, see QueryWriter::queryRecursiveCommonTableExpression)
	 *
	 * @return integer
	 */
	public function countChildren( OODBBean $bean, $sql = NULL, $bindings = array(), $select = TRUE ) {
		$type = $bean->getMeta('type');
		$id   = $bean->id;
		$rows = $this->writer->queryRecursiveCommonTableExpression( $type, $id, FALSE, $sql, $bindings, $select );
		$first = reset($rows);
		$cell  = reset($first);
		return (intval($cell) - (($select === TRUE && is_null($sql)) ? 1 : 0));
	}

	/**
	 * Counts all parent beans associates with the specified
	 * bean in a tree structure.
	 *
	 * @note this only works for databases that support
	 * recusrive common table expressions.
	 *
	 * <code>
	 * $count = R::countParents( $newsArticle );
	 * $count = R::countParents( $newsArticle, ' WHERE title = ? ', [ $t ] );
	 * $count = R::countParents( $newsArticle, ' WHERE title = :t ', [ ':t' => $t ] );
	 * </code>
	 *
	 * Note:
	 * You are allowed to use named parameter bindings as well as
	 * numeric parameter bindings (using the question mark notation).
	 * However, you can not mix. Also, if using named parameter bindings,
	 * parameter binding key ':slot0' is reserved for the ID of the bean
	 * and used in the query.
	 *
	 * Note:
	 * By default, if no SQL or select is given or select=TRUE this method will subtract 1 of
	 * the total count to omit the starting bean. If you provide your own select,
	 * this method assumes you take control of the resulting total yourself since
	 * it cannot 'predict' what or how you are trying to 'count'.
	 *
	 * @param OODBBean $bean     reference bean to find parents of
	 * @param string   $sql      optional SQL snippet
	 * @param array    $bindings optional parameter bindings for SQL snippet
	 * @param string|boolean $select   select snippet to use (advanced, optional, see QueryWriter::queryRecursiveCommonTableExpression)
	 *
	 * @return integer
	 */
	public function countParents( OODBBean $bean, $sql = NULL, $bindings = array(), $select = TRUE ) {
		$type = $bean->getMeta('type');
		$id   = $bean->id;
		$rows = $this->writer->queryRecursiveCommonTableExpression( $type, $id, TRUE, $sql, $bindings, $select );
		$first = reset($rows);
		$cell  = reset($first);
		return (intval($cell) - (($select === TRUE && is_null($sql)) ? 1 : 0));
	}
}
