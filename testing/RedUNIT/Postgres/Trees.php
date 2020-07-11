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
	public function testCTETrees()
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
		asrt( R::countChildren( $homePage ), 3 );
		asrt( R::countParents( $whiskyPage ), 3 );
		asrt( R::countChildren( $winePage, ' title != :title  ', array( ':title' => 'wines' ) ) , 1 );
		asrt( R::countChildren( $winePage, ' title = :title  ', array( ':title' => 'wines' ) ) , 1 );
		asrt( R::countParents( $winePage, '  title != :title  ', array( ':title' => 'home' ) ) , 2 );
	}
	
	/**
	 * Test CTE and Parsed Joins.
	 *
	 * @return void
	 */
	public function testCTETreesAndParsedJoins()
	{
		R::nuke();
		list($cards, $details, $colors) = R::dispenseAll('card*9,detail*4,color*2');
		$colors[0]->name = 'red';
		$colors[1]->name = 'black';
		$details[0]->points = 500;
		$details[1]->points = 200;
		$details[2]->points = 300;
		$details[3]->points = 100;
		$cards[0]->ownCardList = array( $cards[1] );
		$cards[1]->ownCardList = array( $cards[2], $cards[3] );
		$cards[2]->ownCardList = array( $cards[4], $cards[5] );
		$cards[3]->ownCardList = array( $cards[6], $cards[7], $cards[8] );
		$cards[0]->ownOwner[] = R::dispense(array('_type'=>'owner', 'name'=>'User0'));
		$cards[1]->ownOwner[] = R::dispense(array('_type'=>'owner', 'name'=>'User1'));
		$cards[2]->ownOwner[] = R::dispense(array('_type'=>'owner', 'name'=>'User2'));
		$cards[3]->ownOwner[] = R::dispense(array('_type'=>'owner', 'name'=>'User3'));
		$cards[4]->ownOwner[] = R::dispense(array('_type'=>'owner', 'name'=>'User4'));
		$cards[5]->ownOwner[] = R::dispense(array('_type'=>'owner', 'name'=>'User5'));
		$cards[6]->ownOwner[] = R::dispense(array('_type'=>'owner', 'name'=>'User6'));
		$cards[7]->ownOwner[] = R::dispense(array('_type'=>'owner', 'name'=>'User7'));
		$cards[8]->ownOwner[] = R::dispense(array('_type'=>'owner', 'name'=>'User8'));
		$cards[0]->detail = $details[0];
		$cards[1]->detail = $details[0];
		$cards[2]->detail = $details[1];
		$cards[3]->detail = $details[2];
		$cards[4]->detail = $details[3];
		$cards[5]->detail = $details[3];
		$cards[6]->detail = $details[3];
		$cards[7]->detail = $details[3];
		$cards[8]->detail = $details[3];
		$colors[0]->sharedCardList = array( $cards[0], $cards[2], $cards[4], $cards[6], $cards[8] );
		$colors[1]->sharedCardList = array( $cards[1], $cards[3], $cards[5], $cards[7] );
		R::storeAll(array_merge($cards,$details,$colors));
		$cardsWith100Points = R::children($cards[0], ' @joined.detail.points = ? ', array(100));
		asrt(count($cardsWith100Points),5);
		$cardsWith200Points = R::children($cards[0], ' @joined.detail.points = ? ', array(200));
		asrt(count($cardsWith200Points),1);
		$cardsWith300Points = R::children($cards[0], ' @joined.detail.points = ? ', array(300));
		asrt(count($cardsWith200Points),1);
		$cardsWith500Points = R::children($cards[0], ' @joined.detail.points = ? ', array(500));
		asrt(count($cardsWith200Points),1);
		for($i=8; $i>=4; $i--) {
			$cardsWithMoreThan100Points = R::parents($cards[$i], ' @joined.detail.points > ? ', array(100));
			asrt(count($cardsWithMoreThan100Points),3);
		}
		$cardsWithMoreThan200Points = R::parents($cards[8], ' @joined.detail.points > ? ', array(200));
		asrt(count($cardsWithMoreThan200Points),3);
		$cardsWithMoreThan200Points = R::parents($cards[7], ' @joined.detail.points > ? ', array(200));
		asrt(count($cardsWithMoreThan200Points),3);
		$cardsWithMoreThan200Points = R::parents($cards[6], ' @joined.detail.points > ? ', array(200));
		asrt(count($cardsWithMoreThan200Points),3);
		$cardsWithMoreThan200Points = R::parents($cards[5], ' @joined.detail.points > ? ', array(200));
		asrt(count($cardsWithMoreThan200Points),2);
		$cardsWithMoreThan200Points = R::parents($cards[4], ' @joined.detail.points > ? ', array(200));
		asrt(count($cardsWithMoreThan200Points),2);
		for($i=8; $i>=2; $i--) {
			$cardsWithMoreThan300Points = R::parents($cards[4], ' @joined.detail.points > ? ', array(300));
			asrt(count($cardsWithMoreThan200Points),2);
		}
		$black = R::children($cards[0], ' @shared.color.name = ? ', array('black'));
		asrt(count($black),4);
		$red = R::children($cards[0], ' @shared.color.name = ? ', array('red'));
		asrt(count($red),5);
		$black = R::parents($cards[8], ' @shared.color.name = ? ', array('black'));
		asrt(count($black),2);
		$red = R::parents($cards[6], ' @shared.color.name = ? ', array('red'));
		asrt(count($red),2);
		$found = R::children($cards[0], ' @own.owner.name = ? ', array('User0'));
		asrt(count($found),1);
		$found = R::children($cards[0], ' @own.owner.name IN (?,?) ', array('User1','User2'));
		asrt(count($found),2);
		$found = R::parents($cards[8], ' @own.owner.name IN (?,?) ', array('User3','User0'));
		asrt(count($found),2);
		$found = R::children($cards[8], ' @own.owner.name IN (?,?) ', array('User3','User0'));
		asrt(count($found),0);
		$found = R::children($cards[3], ' @own.owner.name IN (?,?) ', array('User3','User7'));
		asrt(count($found),2);
	}

	/**
	 * Test CTE and Parsed Joins with aliases as well
	 * as chained parsed joins.
	 *
	 * @return void
	 */
	public function testCTETreesAndParsedJoinsAndAliases() {
		R::nuke();
		R::aliases(array( 'author' => 'person', 'chart' => 'image' ));
		$person = R::dispense( 'person' );
		$ceo = R::dispense('person');
		$ceo->name = 'John';
		$role = R::dispense('role');
		$role->label = 'CEO';
		$ceo->sharedRoleList = array( $role );
		$person->name = 'James';
		$editor = R::dispense('role');
		$editor->label = 'editor';
		$person->sharedRoleList = array( $editor );
		$website = R::dispense('page');
		$about = R::dispense('page');
		$investor = R::dispense('page');
		$links = R::dispense('page');
		$category = R::dispense('category');
		$category->title = 'business';
		$about->sharedCategoryList = array( $category );
		$blog = R::dispense('page');
		$article = R::dispense('page');
		$investor->title = 'investors';
		$links->title = 'links';
		$about->title = 'about';
		$chart = R::dispense('image');
		$chart->file = 'report.jpg';
		$picture = R::dispense('image');
		$picture->file = 'logo.jpg';
		$picture->source = $website;
		$website->ownImageList = array( $picture );
		$article->ownImageList = array( $picture );
		$investor->ownImageList = array( $picture );
		$about->ownImageList = array( $chart );
		$about->author = $ceo;
		$about->coauthor = $person;
		$article->title = 'a walk in the park';
		$website->ownPageList = array( $about, $blog );
		$blog->ownPageList = array( $article );
		$article->ownPageList = array( $links );
		$about->ownPageList = array( $investor );
		$article->author = $person;
		R::store( $website );
		//joined-alias
		$pages = R::children( $website, ' @joined.author.name = ? ', array('James') );
		asrt(count($pages), 1);
		$page = reset($pages);
		asrt($page->title, 'a walk in the park');
		//Chained joined-alias+shared
		$pages = R::children( $website, ' @joined.author.shared.role.label = ? ', array('CEO') );
		asrt(count($pages),1);
		$page = reset($pages);
		asrt($page->title,'about');
		//joined-alias+shared parents
		$pages = R::parents( $investor, ' @joined.author.shared.role.label = ? ', array('CEO') );
		asrt(count($pages),1);
		$page = reset($pages);
		asrt($page->title,'about');
		//joined-alias parents
		$pages = R::parents( $links, ' @joined.author.name = ? ', array('James') );
		asrt(count($pages), 1);
		$page = reset($pages);
		asrt($page->title, 'a walk in the park');
		//own-alias
		$pages = R::children( $website, '@own.chart.file = ?', array('report.jpg') );
		asrt(count($pages),1);
		$page = reset($pages);
		asrt($page->title,'about');
		//own-alias parent
		$pages = R::parents( $investor, '@own.chart.file = ?', array('report.jpg') );
		asrt(count($pages),1);
		$page = reset($pages);
		asrt($page->title,'about');
		//own-alias parent + joined-alias+shared parents
		$pages = R::parents( $investor, '@own.chart.file = ? AND @joined.author.shared.role.label = ? ', array('report.jpg', 'CEO') );
		asrt(count($pages),1);
		$page = reset($pages);
		asrt($page->title,'about');
		//own-alias + Chained joined-alias+shared
		$pages = R::children( $website, ' @own.chart.file = ? AND @joined.author.shared.role.label = ? ',  array('report.jpg', 'CEO') );
		asrt(count($pages),1);
		$page = reset($pages);
		asrt($page->title,'about');
		//own-alias + Chained joined-alias+shared + joined-alias
		$pages = R::children( $website, ' @joined.author.name = ? AND @own.chart.file = ? AND @joined.author.shared.role.label = ? ',  array('John', 'report.jpg', 'CEO') );
		asrt(count($pages),1);
		$page = reset($pages);
		asrt($page->title,'about');
		//own-alias + Chained joined-alias+shared + joined-alias parents
		$pages = R::parents( $investor, ' @joined.author.name = ? AND @own.chart.file = ? AND @joined.author.shared.role.label = ? ',  array('John', 'report.jpg', 'CEO') );
		asrt(count($pages),1);
		$page = reset($pages);
		asrt($page->title,'about');
		$pages = R::parents( $links, ' @joined.author.name = ? AND @own.chart.file = ? AND @joined.author.shared.role.label = ? ',  array('James', 'logo.jpg', 'editor') );
		asrt(count($pages),1);
		$page = reset($pages);
		asrt($page->title,'a walk in the park');
		$pages = R::parents( $links, ' @joined.author.name = ? AND @own.chart.file = ? AND @joined.author.shared.role.label = ? ',  array('James', 'report.jpg', 'editor') );
		asrt(count($pages),0);
		//other variations
		$pages = R::parents( $investor, ' @shared.category.title = ? ',  array('business') );
		asrt(count($pages),1);
		$page = reset($pages);
		asrt($page->title,'about');
		R::aliases(array());
		//joined-alias - explicit/non-global
		$pages = R::children( $website, ' @joined.person[as:author].name = ? ', array('James') );
		asrt(count($pages), 1);
		$page = reset($pages);
		asrt($page->title, 'a walk in the park');
		//Chained joined-alias+shared - explicit/non-global
		$pages = R::children( $website, ' @joined.person[as:author].shared.role.label = ? ', array('CEO') );
		asrt(count($pages),1);
		$page = reset($pages);
		asrt($page->title,'about');
		//joined-alias+shared parents - explicit/non-global
		$pages = R::parents( $investor, ' @joined.person[as:author].shared.role.label = ? ', array('CEO') );
		asrt(count($pages),1);
		$page = reset($pages);
		asrt($page->title,'about');
		//joined-alias parents - explicit/non-global
		$pages = R::parents( $links, ' @joined.person[as:author].name = ? ', array('James') );
		asrt(count($pages), 1);
		$page = reset($pages);
		asrt($page->title, 'a walk in the park');
		//double joined-alias parents - explicit/non-global
		$pages = R::children( $website, ' @joined.person[as:author/coauthor].name = ? ', array('James') );
		asrt(count($pages), 2);
		//own-alias - explicit/non-global
		$pages = R::children( $website, ' @own.image[alias:source].file = ?', array('logo.jpg') );
		asrt(count($pages),1);
		$pages = R::children( $website, ' @own.image[alias:source].file = ?', array('report.jpg') );
		asrt(count($pages),0);
		$pages = R::children( $about, ' @own.image[alias:source].file = ?', array('logo.jpg') );
		asrt(count($pages),0);
	}
}
