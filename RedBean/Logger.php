<?php
/**
 * RedBean Logging
 * 
 * @file			RedBean/Logging.php
 * @description		Logging interface for RedBeanPHP ORM,
 *					provides a uniform and convenient logging 
 *					interface throughout RedBeanPHP.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
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
