<?php

namespace RedBeanPHP\Cursor;

use RedBeanPHP\Cursor as Cursor;

/**
 * NULL Database Cursor
 *
 * @file    RedBeanPHP/Cursor/NULLCursor.php
 * @desc    Implementation of the NULL Cursor
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class NullCursor implements Cursor {
	/**
	 * @see Cursor::getNextItem
	 */
	public function getNextItem()
	{
		return NULL;
	}

	/**
	 * @see Cursor::close
	 */
	public function close()
	{
		return NULL;
	}
}
