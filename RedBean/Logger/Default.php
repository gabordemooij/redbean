<?php
/**
 * Logger. Provides a basic logging function for RedBeanPHP.
 *
 * @file    RedBean/Logger.php
 * @desc    Logger
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * Provides a basic logging function for RedBeanPHP.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Logger_Default implements RedBean_Logger
{

	/**
	 * Default logger method logging to STDOUT.
	 * This is the default/reference implementation of a logger.
	 * This method will write the message value to STDOUT (screen).
	 *
	 * @param $message (optional)
	 *
	 * @return void
	 */
	public function log()
	{
		if ( func_num_args() < 1 ) return;

		foreach ( func_get_args() as $argument ) {
			if ( is_array( $argument ) ) {
				echo print_r( $argument, TRUE );
			} else {
				echo $argument;
			}

			echo "<br>\n";
		}
	}
}
