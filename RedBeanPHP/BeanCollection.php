<?php

namespace RedBeanPHP;

use RedBeanPHP\Cursor as Cursor;
use RedBeanPHP\Repository as Repository;

/**
 * BeanCollection.
 *
 * The BeanCollection represents a collection of beans and
 * makes it possible to use database cursors. The BeanCollection
 * has a method next() to obtain the first, next and last bean
 * in the collection. The BeanCollection does not implement the array
 * interface nor does it try to act like an array because it cannot go
 * backward or rewind itself.
 *
 * Use the BeanCollection for large datasets where skip/limit is not an
 * option. Keep in mind that ID-marking (querying a start ID) is a decent
 * alternative though.
 *
 * @file    RedBeanPHP/BeanCollection.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class BeanCollection
{
	/**
	 * @var Cursor
	 */
	protected $cursor = NULL;

	/**
	 * @var Repository
	 */
	protected $repository = NULL;

	/**
	 * @var string
	 */
	protected $type = NULL;

	/**
	 * Constructor, creates a new instance of the BeanCollection.
	 *
	 * @param string     $type       type of beans in this collection
	 * @param Repository $repository repository to use to generate bean objects
	 * @param Cursor     $cursor     cursor object to use
	 *
	 * @return void
	 */
	public function __construct( $type, Repository $repository, Cursor $cursor )
	{
		$this->type = $type;
		$this->cursor = $cursor;
		$this->repository = $repository;
	}

	/**
	 * Returns the next bean in the collection.
	 * If called the first time, this will return the first bean in the collection.
	 * If there are no more beans left in the collection, this method
	 * will return NULL.
	 *
	 * @return OODBBean|NULL
	 */
	public function next()
	{
		$row = $this->cursor->getNextItem();
		if ( $row ) {
			$beans = $this->repository->convertToBeans( $this->type, array( $row ) );
			return reset( $beans );
		}
		return NULL;
	}
	
	/**
	 * Resets the collection from the start, like a fresh() on a bean.
	 *
	 * @return void
	 */
	public function reset()
	{
		$this->cursor->reset();
	}

	/**
	 * Closes the underlying cursor (needed for some databases).
	 *
	 * @return void
	 */
	public function close()
	{
		$this->cursor->close();
	}
}
