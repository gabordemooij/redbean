<?php
/**
 * RedBean Logging
 *
 * @file    RedBean/Logging.php
 * @desc    Logging interface for RedBeanPHP ORM
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * Provides a uniform and convenient logging
 * interface throughout RedBeanPHP.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_Logger
{

	/**
	 * Logs a message specified in first argument.
	 *
	 * @param string $message the message to log. (optional)
	 *
	 * @return void
	 */
	public function log();
}
