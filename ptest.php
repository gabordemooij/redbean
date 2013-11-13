<?php



require 'rb.phar';
use \RedBeanPHP\Facade as R;

R::setup();
print_r(R::dispense('a'));


//\RedBeanPHP\Facade::setup();
