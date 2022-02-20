RedBeanPHP 5
============

[![Build Status](https://travis-ci.org/gabordemooij/redbean.svg?branch=master)](https://travis-ci.org/gabordemooij/redbean)

RedBeanPHP is an easy to use ORM tool for PHP.

* Automatically creates tables and columns as you go
* No configuration, just fire and forget
* No complicated package tools, no autoloaders, just ONE file

Installation (recommended)
---------------------------

Download RedBeanPHP from the website:

https://redbeanphp.com/download

Extract the archive and put it in your PHP project, voila!

Optional: sha256sum and check signature.


Installation via Composer (not recommended)
-----------------------------------------

Just open your composer.json file and add the package name ```(e.g. "gabordemooij/redbean": "dev-master")``` in your require list.

```json
{
    "require": {
        "gabordemooij/redbean": "dev-master"
    }
}
```

**NOTE**: 
You will find many examples on the RedBean website make use of RedBean's `R` class. Because of namespaced autoloading in Composer, this class will be available as `\RedbeanPHP\R` instead of `R`. If you desire to use the much shorter `R` alias, you can add a `use` statement at the beginning of your code:

```php
use \RedBeanPHP\R as R;
```
**NOTE:**
It is important to note that when using RedBeanPHP with Composer, there are some extra precautions needed when working with [Models](https://redbeanphp.com/index.php?p=/models). Due to the namespace requirements of Composer, when creating Models we need to use the `SimpleModel` to extend, not `RedBean_SimpleModel`. Furthermore, we need to specify the namespace of the `SimpleModel`, so a full example of using a Model with RedBean with Composer is as follows:

```php
use \RedBeanPHP\R;

class User extends \RedBeanPHP\SimpleModel
{
    ...
}
```
Notice that we also need to add the `use \RedBeanPHP\R` statement so that we can use the `R::` shortcut within the Model.


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

https://www.redbeanphp.com/
