<?php
/**
 * RedBean interface for Logging
 * 
 * @name    RedBean ILogger
 * @file    RedBean/ILogger.php
 * @author    Gabor de Mooij
 * @license   BSD
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_ILogger {

  /**
   * Redbean will call this method to log your data
   *
   * @param ...
   */
  public function log();


}
