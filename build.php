<?php
/**
 * Build Script
 * @package 		build.php
 * @description		A small build script for RedBean
 * @author			Desfrenes
 * @license			BSD
 */

echo "\n ===================================== ";
echo "\n BUILDSCRIPT FOR REDBEAN ";
echo "\n ===================================== ";
echo "\n";

define('BASE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
set_include_path('.' . PATH_SEPARATOR . BASE_DIR);
function __autoload($class)
{
	
    include_once(str_replace('_', '/', $class) . '.php');
}
echo "\n\n\n Now building dev version... \n\n";
RedBean_Tools::compile(BASE_DIR . 'rb.php', false);
echo "\n\n\n Now building compressed version... \n\n";
RedBean_Tools::compile(BASE_DIR . 'rb.pack.php', true);
echo "\n Done... \n\n";