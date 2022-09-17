<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Either
 *
 * Tests Either-Or-functionality.
 *
 * @file    RedUNIT/Base/Either.php
 * @desc    Test for Either-or
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Either extends Base {

	/**
	 * Test either() with beans.
	 *
	 * @return void
	 */
	public function testEitherWithBeans()
	{
		R::nuke();
		$id = R::store(R::dispense(array(
			'_type' => 'book',
			'title' => 'Socratic Questions',
			'ownPageList' => array(
				array(
					'_type'=> 'page',
					'ownParagraphList' => array(
						array(
							'_type'=>'paragraph',
							'text' => 'What is a Bean?',
						)
					)
				)
			)
		)));
		$book = R::load('book', $id);
		asrt($book->either()->title->_or('nothing'),'Socratic Questions');
		$pageId = R::findOne('page')->id;
		asrt($book->either()->ownPageList->index($pageId)->id->_or(0), $pageId);
		asrt($book->either()->ownPageList->index($pageId+1)->ownTextList->index(1)->_or(0), 0);
		$textId = $book->either()->ownPageList->first()->ownParagraphList->last()->id->_or(0);
		asrt($textId > 0, TRUE);
		$text = R::load('paragraph', $textId);
		asrt($text->either()->page->book->title->_or('nothing'), 'Socratic Questions');
		asrt($book->either()->ownPageList->last()->id->_or(-1),$pageId);
		asrt($book->either()->ownPageList->id->_or(-1),-1);
		asrt($book->either()->ownPageList->first()->ownQuoteList->first()->_or('?'),'?');
	}
}