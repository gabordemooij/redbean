RedBean
===========

RedBean is an easy to use ORM tool that stores beans directly in the
database and creates all tables and columns required on the fly.
On the other hand it allows plain SQL to search the database. In fact
RedBean is some sort of combination between document oriented database
tools like mongoDB or couchDB and traditional relational database systems
like MySQL. It offers the best of both worlds: SQL and no-SQL. You work
with no-SQL if you interact with objects will you simply turn the switch
and work with SQL if you want to do some typical database tasks like
searching or quikly grabbing something out of the data store with 
specially crafted SQL. RedBean also has excellent performance because it
can freeze the database schema which means it no longer scans schemas.

Databases Supported
-----

RedBean supports MySQL (InnoDB and MyISAM), MySQL strict, PostgreSQL and SQLite.
There are specific database plugins though.


Example
-------

How we store a book object with RedBean:

	$book = $redbean->dispense("book");
	$book->author = "Santa Claus";
	$book->title = "Secrets of Christmas";
	$id = $redbean->store( $book );
	
Yep, it's that simple.


CRUD
----

//Create a Bean
$book = $redbean->dispense("book");

//Store a Bean
$book->author = "Santa Claus";
$book->title = "Secrets of Christmas";
$id = $redbean->store( $book );

//Load a Bean
$aBookFromSanta = $redbean->load( "book", $id );

//Trash a Bean
$redbean->trash( $book );


Finding Beans
=============

This is where most ORM layers simply get it wrong. An ORM tool is only useful if you 
are doing object relational tasks. 
Searching a database has never been a strong feature of objects; 
but SQL is simply made for the job. In many ORM tools you will find statements like: 
$person->select("name")->where("age","20") or something like that. 
I found this a pain to work with. Some tools even promote their own version of SQL. 
To me this sounds incredibly stupid. Why should you use a system less powerful than 
the existing one? This is the reason that RedBean simply uses SQL as its search API.

The easiest way to search a bean using RedBean is to use the Find plugin. 
This plugin is enabled by default; works for most databases and accepts plain old SQL. 
Here is an example:

$actors = Finder::where("page", " name LIKE :str ", array(":str"=>'%more%'));


Features
========

- Easy CRUD.
- Easy ways to convert beans in full fledged domain models.
- Supports many databases.
- Easy integration in Zend Framework, Code Igniter and Kohana (see tutorials).
- Very flexible, works with predefined schemas as well.
- Excellent performance (thanks to freeze-mode).

