<?php
/**
 * RedBean Exception OCI
 *
 * @file    RedBean/Exception/OCI.php
 * @desc    Represents an OCI database exception, for use with Oracle driver.
 * @author  Stephane Gerber
 * @license BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Exception_OCI extends RedBean_Exception_SQL
{

	/**
	 * Converts the exception object to a string,
	 * including the exception message.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return '[RedBean_Exception_OCI]:' . $this->getMessage();
	}
}
