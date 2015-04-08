<?php

namespace RedBeanPHP\Cursor;

use RedBeanPHP\ICursor as ICursor;

/**
 * NULL Database Cursor
 * Implementation of the NULL Cursor.
 * Used for an empty BeanCollection.
 *
 * @file    RedBeanPHP/Cursor/NULLCursor.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class NullCursor implements ICursor
{
	/**
	 * @see ICursor::getNextItem
	 */
	public function getNextItem()
	{
		return NULL;
	}

	/**
	 * @see ICursor::close
	 */
	public function close()
	{
		return NULL;
	}
}
