RedBean
===========

Current Build Status:
[![Build Status](https://secure.travis-ci.org/gabordemooij/redbean.png)](http://travis-ci.org/gabordemooij/redbean)

RedBeanPHP is an easy to use ORM tool that stores beans directly in the
database and creates all tables and columns required on the fly.
On the other hand it allows plain SQL to search the database. In fact
RedBean is some sort of combination between document oriented database
tools like mongoDB or couchDB and traditional relational database systems
like MySQL. It offers the best of both worlds: SQL and no-SQL. You work
with no-SQL if you interact with objects will you simply turn the switch
and work with SQL if you want to do some typical database tasks like
searching or quickly grabbing something out of the data store with
specially crafted SQL. RedBean also has excellent performance because it
can freeze the database schema which means it no longer scans schemas.

Databases Supported
-------------------

RedBean supports MySQL (InnoDB), PostgreSQL, SQLite3, CUBRID and Oracle.

Quick Example
-------------

How we store a book object with RedBean:
```php
$book = R::dispense("book");
$book->author = "Santa Claus";
$book->title = "Secrets of Christmas";
$id = R::store( $book );
```

Yep, it's that simple.

Install with Composer
------------------------
You can use RedBeanPHP with [Composer](http://getcomposer.org/).

Create `composer.json` in project root:
```json
{
  "require": {
    "gabordemooij/redbean": "dev-master"
  }
}
```

Install via composer:
```bash
php composer.phar install
```

Autoloading:
```php
require 'vendor/autoload.php';
use RedBean_Facade as R;
```

More information
----------------

For more information about RedBeanPHP please consult
the RedBeanPHP online manual at:

http://www.redbeanphp.com/
