<?php

spl_autoload_register(function($className)
{
    $className = ltrim($className, '\\');
    $fileName  = '';
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

	 $path = 'phar://rb.phar/'.$fileName;
	 
    if (file_exists($path)) {
		 require $path;
	 }
});

//Allow users to mount the plugin folder.
if ( defined( 'REDBEANPHP_EXTRA' ) ) {
    Phar::mount( 'RedBeanPHP/Extra', REDBEANPHP_EXTRA );
} 

//make some classes available for backward compatibility
class RedBean_SimpleModel extends \RedBeanPHP\SimpleModel {};
class RedBean_DependencyInjector extends \RedBeanPHP\DependencyInjector{};
class R extends \RedBeanPHP\Facade{};

