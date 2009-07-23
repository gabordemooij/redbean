<?php
define('BASE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
set_include_path('.' . PATH_SEPARATOR . BASE_DIR);
function __autoload($class)
{
    include_once(str_replace('_', '/', $class) . '.php');
}
Redbean_Tools::compile(BASE_DIR . 'allinone.php', false);
Redbean_Tools::compile(BASE_DIR . 'allinone-compressed.php', true);