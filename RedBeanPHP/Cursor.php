<?php

namespace RedBeanPHP;

/**
 * Database Cursor Interface
 *
 * @file    RedBeanPHP/Cursor.php
 * @desc    Represents a simple database cursor.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface Cursor {
	/**
	 * Retrieves the next row from the result set.
	 *
	 * @return array
	 */
	public function getNextItem();

	/**
	 * Closes the database cursor.
	 * Some databases require a cursor to be closed before executing
	 * another statement/opening a new cursor.
	 *
	 * @return void
	 */
	public function close();
}
