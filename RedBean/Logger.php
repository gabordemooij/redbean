<?php
/**
 * RedBean Logging
 * 
 * @file			RedBean/Logging.php
 * @desc			Logging interface for RedBeanPHP ORM
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * Provides a uniform and convenient logging 
 * interface throughout RedBeanPHP.
 * 
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
interface RedBean_Logger {

  /**
   * Method used to log messages.
   * Writes the specified message to the log document whatever
   * that may be (files, database etc). Provides a uniform
   * interface for logging throughout RedBeanPHP.
   *
   * @param string $message the message to log. (optional)
   */
  public function log();

}
