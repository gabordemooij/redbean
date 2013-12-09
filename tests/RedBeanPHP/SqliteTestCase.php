<?php

namespace RedBeanPHP;

use RedBeanPHP\RedBeanBasicTestCase;

abstract class SqliteTestCase extends RedBeanBasicTestCase
{

    protected $dsn = 'sqlite:/tmp/oodb.db';

}
