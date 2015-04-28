RedBeanPHP 4
============

![Build Status](https://travis-ci.org/gabordemooij/redbean.svg?branch=master)

RedBeanPHP is an easy to use ORM tool for PHP.

* Automatically creates tables and columns as you go
* No configuration, just fire and forget
* No complicated package tools, no autoloaders, just ONE file

Quick Example
-------------

How we store a book object with RedBeanPHP:
```php
$book = R::dispense("book");
$book->author = "Santa Claus";
$book->title = "Secrets of Christmas";
$id = R::store( $book );
```

Yep, it's that simple.


More information
----------------

For more information about RedBeanPHP please consult
the RedBeanPHP website:

http://www.redbeanphp.com/
