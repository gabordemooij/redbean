<?php

namespace RedUNIT\Postgres;

use RedUNIT\Postgres as Postgres;
use RedBeanPHP\Facade as R;

/**
 * Trees
 *
 * This class has been designed to test tree traversal using
 * R::children() and R::parents() relying on
 * recursive common table expressions.
 *
 * @file    RedUNIT/Postgres/Trees.php
 * @desc    Tests trees
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Trees extends Postgres
{
	protected function summarize( $beans )
	{
		$names = array();
		foreach( $beans as $bean ) {
			$names[] = $bean->title;
		}
		return implode( ',', $names );
	}
	
	/**
	 * Test trees
	 *
	 * @return void
	 */
	public function testDateObject()
	{
		R::nuke();
		$pages = R::dispense(array(
			'_type' => 'page',
			'title' => 'home',
			'ownPageList' => array(array(
				'_type' => 'page',
				'title' => 'shop',
				'ownPageList' => array(array(
					'_type' => 'page',
					'title' => 'wines',
					'ownPageList' => array(array(
						'_type' => 'page',
						'title' => 'whiskies',
					))
				))
			))
		));
		R::store( $pages );
		$whiskyPage = R::findOne( 'page', 'title = ?', array('whiskies') );
		asrt( $this->summarize( R::parents( $whiskyPage, ' ORDER BY title ASC ' ) ), 'home,shop,whiskies,wines' );
		asrt( $this->summarize( R::children( $whiskyPage, ' ORDER BY title ASC ' ) ), 'whiskies' );
		$homePage = R::findOne( 'page', 'title = ?', array('home') );
		asrt( $this->summarize( R::parents( $homePage, ' ORDER BY title ASC ' ) ), 'home' );
		asrt( $this->summarize( R::children( $homePage, ' ORDER BY title ASC ' ) ), 'home,shop,whiskies,wines' );
		$shopPage = R::findOne( 'page', 'title = ?', array('shop') );
		asrt( $this->summarize( R::parents( $shopPage, ' ORDER BY title ASC ' ) ), 'home,shop' );
		asrt( $this->summarize( R::children( $shopPage, ' ORDER BY title ASC ' ) ), 'shop,whiskies,wines' );
		$winePage = R::findOne( 'page', 'title = ?', array('wines') );
		asrt( $this->summarize( R::parents( $winePage, ' ORDER BY title ASC ' ) ), 'home,shop,wines' );
		asrt( $this->summarize( R::children( $winePage, ' ORDER BY title ASC ' ) ), 'whiskies,wines' );
		asrt( $this->summarize( R::children( $winePage, ' title NOT IN (\'wines\') ORDER BY title ASC ' ) ), 'whiskies' );
		asrt( $this->summarize( R::parents( $winePage, '  title NOT IN (\'home\') ORDER BY title ASC ' ) ), 'shop,wines' );
		asrt( $this->summarize( R::parents( $winePage, ' ORDER BY title ASC ', array() ) ), 'home,shop,wines' );
		asrt( $this->summarize( R::children( $winePage, ' ORDER BY title ASC ', array() ) ), 'whiskies,wines' );
		asrt( $this->summarize( R::children( $winePage, ' title NOT IN (\'wines\') ORDER BY title ASC ', array() ) ), 'whiskies' );
		asrt( $this->summarize( R::parents( $winePage, '  title NOT IN (\'home\') ORDER BY title ASC ', array() ) ), 'shop,wines' );
		asrt( $this->summarize( R::children( $winePage, ' title != ? ORDER BY title ASC ', array( 'wines' ) ) ), 'whiskies' );
		asrt( $this->summarize( R::parents( $winePage, '  title != ? ORDER BY title ASC ', array( 'home' ) ) ), 'shop,wines' );
		asrt( $this->summarize( R::children( $winePage, ' title != :title ORDER BY title ASC ', array( ':title' => 'wines' ) ) ), 'whiskies' );
		asrt( $this->summarize( R::parents( $winePage, '  title != :title ORDER BY title ASC ', array( ':title' => 'home' ) ) ), 'shop,wines' );
	}
}
