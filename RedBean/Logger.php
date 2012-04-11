<?php
/**
 * RedBean class for Logging
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
class RedBean_Logger implements RedBean_ILogger {

  /**
   * Default logger method logging to STDOUT
   *
   * @param ...
   */
  public function log() {
    if (func_num_args() > 0) {
      foreach (func_get_args() as $argument) {
        if (is_array($argument)) echo print_r($argument,true); else echo $argument;
		echo "<br>\n";
      }
    }
  }
}


