RedBeanPHP 4 - simirima fork
============================

This is a fork of RedBeanPHP, the PHP ORM library created by Gabor de Mooji. The homepage of the original version is <http://readbean.com>.
The reason for this fork is to add support for composer and to provide a composer package which can be found via <http://packagist.org>.


RedBeanPHP is an easy to use ORM tool for PHP.

* Automatically creates tables and columns as you go
* No configuration, just fire and forget
* ~~No complicated package tools, no autoloaders, just ONE file~~ 
This fork uses composer as package tool, comes in multiple files and provides support for the composer autloader. So just
add this package as a dependency to your composer.json file, include the composer autoload.php file and you are ready to go.


Quick Example
-------------

How we store a book object with RedBeanPHP:
```php
/* original: $book = R::dispense("book"); */
$book = \ReadBeanPHP\R::dispense("book");
$book->author = "Santa Claus";
$book->title = "Secrets of Christmas";
/* original: $id = R::store( $book ); */
$id = \ReadBeanPHP\R::store( $book );
```

Yep, it's that simple. Still simple, I think, even if you have to add the ReadBeanPHP namespace to the classname. 
Or just 
```php
use \ReadBeanPHP\R;
$book = \ReadBeanPHP\R::dispense("book");
$book->author = "Santa Claus";
$book->title = "Secrets of Christmas";
$id = \ReadBeanPHP\R::store( $book );
$id = R::store( $book );
```



More information
----------------

For more information about RedBeanPHP please consult
the RedBeanPHP website:

http://www.redbeanphp.com/


For questions regarding this fork please contact me: simirimia@triosolutions.at
