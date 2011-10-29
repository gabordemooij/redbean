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
-------------------

RedBean supports MySQL (InnoDB and MyISAM), MySQL strict, PostgreSQL and SQLite.
There are specific database plugins though.


Quick Example
-------------

How we store a book object with RedBean:

	$book = R::dispense("book");
	$book->author = "Santa Claus";
	$book->title = "Secrets of Christmas";
	$id = R::store( $book );

Yep, it's that simple.


More information
----------------

For more information about RedBeanPHP please consult
the RedBeanPHP online manual at:

http://www.redbeanphp.com/manual

