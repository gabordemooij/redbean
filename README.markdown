RedBeanPHP 4
============

![Build Status](https://travis-ci.org/gabordemooij/redbean.svg?branch=master)

RedBeanPHP is an easy to use ORM tool for PHP.

* Automatically creates tables and columns as you go
* No configuration, just fire and forget
* No complicated package tools, no autoloaders, just ONE file

Installation via Composer
-------------------------

Just open your composer.json file and add the package name ```(e.g. "gabordemooij/redbean": "dev-master")``` in your require list.

```json
{
    "require": {
        "gabordemooij/redbean": "dev-master"
    }
}
```

**NOTE**: 
You will find many examples on the redbean website make use of RedBean's `R` class. Because of namespaced autoloading in composer, this class will be available as `\RedbeanPHP\R` instead of `R`. If you desire to use the much shorter `R` class, you can utilize PHP's `class_alias()` function to take care of the job for you as so:

```php
class_alias('\RedBeanPHP\R','\R');
```

If you are not using composer then [try it.](http://redbeanphp.com/install)


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
