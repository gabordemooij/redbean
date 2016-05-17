<?php

namespace RedBeanPHP;

use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Bean Helper Interface.
 *
 * Interface for Bean Helper.
 * A little bolt that glues the whole machinery together.
 * The Bean Helper is passed to the OODB RedBeanPHP Object to
 * faciliatte the creation of beans and providing them with
 * a toolbox. The Helper also facilitates the FUSE feature,
 * determining how beans relate to their models. By overriding
 * the getModelForBean method you can tune the FUSEing to
 * fit your business application needs.
 *
 * @file    RedBeanPHP/IBeanHelper.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface BeanHelper
{
	/**
	 * Returns a toolbox to empower the bean.
	 * This allows beans to perform OODB operations by themselves,
	 * as such the bean is a proxy for OODB. This allows beans to implement
	 * their magic getters and setters and return lists.
	 *
	 * @return ToolBox
	 */
	public function getToolbox();

	/**
	 * Does approximately the same as getToolbox but also extracts the
	 * toolbox for you.
	 * This method returns a list with all toolbox items in Toolbox Constructor order:
	 * OODB, adapter, writer and finally the toolbox itself!.
	 *
	 * @return array
	 */
	public function getExtractedToolbox();

	/**
	 * Given a certain bean this method will
	 * return the corresponding model.
	 * If no model is returned (NULL), RedBeanPHP might ask again.
	 *
	 * @note You can make RedBeanPHP faster by doing the setup wiring yourself.
	 * The event listeners take time, so to speed-up RedBeanPHP you can
	 * drop 'FUSE', if you're not interested in the Models.
	 *
	 * @note You can do funny stuff with this method but please be careful.
	 * You *could* create a model depending on properties of the bean, but
	 * it's a bit well... adventurous, here is an example:
	 *
	 * <code>
	 * class Book extends RedBeanPHP\SimpleModel {};
	 * class Booklet extends RedBeanPHP\SimpleModel {};
	 *
	 * class FlexBeanHelper extends RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper {
	 *  public function getModelForBean( RedBeanPHP\OODBBean $bean ) {
	 *   if (!isset($bean->pages)) return NULL; //will ask again
	 *   if ($bean->pages <= 10) return new Booklet;
	 *   return new Book;
	 *	 }
	 * }
	 *
	 * $h = new FlexBeanHelper;
	 * R::getRedBean()->setBeanHelper($h);
	 * $book = R::dispense('book');
	 * var_dump($book->box()); //NULL cant reach model
	 * $book->pages = 5;
	 * var_dump($book->box()); //Booklet
	 * $book->pages = 15;
	 * var_dump($book->box()); //still.. Booklet, model has been set
	 * $book2 = R::dispense('book');
	 * $book2->pages = 15;
	 * var_dump($book2->box()); //Book, more than 10 pages
	 * </code>
	 *
	 * @param OODBBean $bean bean to obtain the corresponding model of
	 *
	 * @return SimpleModel|CustomModel|NULL
	 */
	public function getModelForBean( OODBBean $bean );
}
