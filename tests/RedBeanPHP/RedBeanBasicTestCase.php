<?php

namespace RedBeanPHP;

use RedBeanPHP\Facade as R;

abstract class RedBeanBasicTestCase extends \PHPUnit_Framework_TestCase
{

    protected $dsn;

    public function setup()
    {
        R::setup($this->dsn);
    }

    public function tearDown()
    {
        R::nuke();
    }

    protected function setGet($value)
    {
        $bean = R::dispense( "page" );
        $bean->prop = $value;
        $id = R::store( $bean );
        $bean = R::load( "page", $id );

        return $bean->prop;
    }

}
