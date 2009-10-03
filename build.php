<?php
/**
 * Build Script
 * @package 		build.php
 * @description		A small build script for RedBean
 * @author			Desfrenes
 * @license			BSD
 */
echo "\n RedBean Builder v1.0 ";
echo "\n Written by Desfrenes ";
echo "\n Building..... ";
define('BASE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
set_include_path('.' . PATH_SEPARATOR . BASE_DIR);
function __autoload($class)
{
    include_once(str_replace('_', '/', $class) . '.php');
}
RedBean_Tools::compile(BASE_DIR . 'allinone.php', false);
RedBean_Tools::compile(BASE_DIR . 'allinone-compressed.php', true);
echo "\n done.. \n\n";