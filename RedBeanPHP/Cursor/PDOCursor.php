<?php

namespace RedBeanPHP\Cursor;

use RedBeanPHP\ICursor as ICursor;

/**
 * PDO Database Cursor
 * Implementation of PDO Database Cursor.
 * Used by the BeanCollection to fetch one bean at a time.
 *
 * @file    RedBeanPHP/Cursor/PDOCursor.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class PDOCursor implements ICursor
{
	/**
	 * @var \PDOStatement
	 */
	protected $res;

	/**
	 * @var string
	 */
	protected $fetchStyle;

	/**
	 * Constructor, creates a new instance of a PDO Database Cursor.
	 *
	 * @param \PDOStatement  $res        the PDO statement
	 * @param string        $fetchStyle fetch style constant to use
	 *
	 */
	public function __construct( \PDOStatement $res, $fetchStyle )
	{
		$this->res = $res;
		$this->fetchStyle = $fetchStyle;
	}

	/**
	 * @see ICursor::getNextItem
	 */
	public function getNextItem()
	{
		return $this->res->fetch();
	}

	/**
	 * @see ICursor::close
	 */
	public function close()
	{
		$this->res->closeCursor();
	}
}
