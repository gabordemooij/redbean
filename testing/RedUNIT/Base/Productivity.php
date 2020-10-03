<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\Util\MatchUp;
use RedBeanPHP\Util\Look;

/**
 * MatchUp
 *
 * Tests the MatchUp functionality.
 * Tired of creating login systems and password-forget systems?
 * MatchUp is an ORM-translation of these kind of problems.
 * A matchUp is a match-and-update combination in terms of beans.
 * Typically login related problems are all about a match and
 * a conditional update.
 * 
 * @file    RedUNIT/Base/Matchup.php
 * @desc    Tests MatchUp
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Productivity extends Base
{
	/**
	 * Test matchup
	 *
	 * @return void
	 */
	public function testPasswordForget()
	{
		R::nuke();
		$account = R::dispense( 'account' );
		$account->uname = 'Shawn';
		$account->pass = sha1( 'sheep' );
		$account->archived = 0;
		$account->attempts = 1;

		R::store( $account );
		$matchUp = new MatchUp( R::getToolbox() );

		/* simulate a token generation script */
		$account = NULL;
		$didGenToken = $matchUp->matchUp( 'account', ' uname = ? AND archived = ?', array('Shawn',0), array(
			'token'     => sha1(rand(0,9000) . time()),
			'tokentime' => time()
		), NULL, $account );
		
		asrt( $didGenToken, TRUE );
		asrt( !is_null( $account->token ) , TRUE );
		asrt( !is_null( $account->tokentime ) , TRUE );

		/* simulate a password reset script */
		$newpass = '1234';
		$didResetPass = $matchUp->matchUp( 'account', ' token = ? AND tokentime > ? ', array( $account->token, time()-100 ), array(
			'pass' => $newpass,
			'token' => ''
		), NULL, $account );
		asrt( $account->pass, '1234' );
		asrt( $account->token, '' );
		
		/* simulate a login */
		$didFindUsr = $matchUp->matchUp( 'account', ' uname = ? ', array( 'Shawn' ), array(
			'attempts' => function( $acc ) {
				return ( $acc->pass !== '1234' ) ? ( $acc->attempts + 1 ) : 0;
			}
		), NULL, $account);

		asrt( $didFindUsr, TRUE );
		asrt( $account->attempts, 0 );

		/* Login failure */
		$didFindUsr = $matchUp->matchUp( 'account', ' uname = ? ', array( 'Shawn' ), array(
			'attempts' => function( $acc ) {
				return ( $acc->pass !== '1236' ) ? ( $acc->attempts + 1 ) : 0;
			}
		), NULL, $account);

		/* Create user if not exists */
		$didFindUsr = R::matchUp( 'account', ' uname = ? ', array( 'Anonymous' ), array(
		), array(
			'uname' => 'newuser'
		), $account);
		asrt( $didFindUsr, FALSE );
		asrt( $account->uname, 'newuser' );
	}

	/**
	 * Tests the look function.
	 */
	public function testLook()
	{
		R::nuke();
		$beans = R::dispenseAll( 'color*3' );
		list( $red, $green, $blue ) = $beans[0];
		$red->name = 'red';
		$green->name = 'green';
		$blue->name = 'blue';
		$red->thevalue = 'r';
		$green->thevalue = 'g';
		$blue->thevalue = 'b';
		R::storeAll( array( $red, $green, $blue ) );
		$look = R::getLook();
		asrt( ( $look instanceof Look ), TRUE );
		$str = R::getLook()->look( 'SELECT * FROM color WHERE thevalue != ? ORDER BY thevalue ASC', array( 'g' ),  array( 'thevalue', 'name' ),
			'<option value="%s">%s</option>', 'strtoupper', "\n"
		);
		asrt( $str,
		"<option value=\"B\">BLUE</option>\n<option value=\"R\">RED</option>"
		);
		$str = R::look( 'SELECT * FROM color WHERE thevalue != ? ORDER BY thevalue ASC', array( 'g' ),  array( 'thevalue', 'name' ),
			'<option value="%s">%s</option>', 'strtoupper', "\n"
		);
		asrt( $str,
		"<option value=\"B\">BLUE</option>\n<option value=\"R\">RED</option>"
		);
	}

	/**
	 * Test Bean differ.
	 */
	public function testDiff()
	{
		R::nuke();
		$ad = R::dispense( 'ad' );
		$ad->title = 'dog looking for new home';
		$ad->created = time();
		$ad->modified = time();
		$ad->ownDog[] = R::dispense( 'dog' );
		$ad->ownDog[0]->name = 'Dweep';
		$ad->ownDog[0]->color = 'green';
		$ad->author = R::dispense('user');
		$ad->author->name = 'John';
		R::store( $ad );
		$ad->title = 'green dog';
		$diff = R::diff( $ad->fresh(), $ad );
		/* simple case, property changed */
		var_dump( $diff );
		asrt( is_array( $diff ), TRUE );
		asrt( count( $diff ), 1 );
		asrt( $diff['ad.1.title'][0], 'dog looking for new home' );
		asrt( $diff['ad.1.title'][1], 'green dog' );
		/* test use specific format */
		$diff = R::diff( $ad->fresh(), $ad,  array(), '%1$s.%3$s' );
		asrt( is_array( $diff ), TRUE );
		asrt( count( $diff ), 1 );
		asrt( $diff['ad.title'][0], 'dog looking for new home' );
		asrt( $diff['ad.title'][1], 'green dog' );
		/* skip created modified */
		$ad = $ad->fresh();
		$ad->modified = 111;
		$ad->created = 111;
		$diff = R::diff( $ad->fresh(), $ad );
		asrt( is_array( $diff ), TRUE );
		asrt( count( $diff ), 0 );
		/* unless we set anothe filter */
		$ad = $ad->fresh();
		$ad->modified = 111;
		$ad->created = 111;
		$ad->name = 'x';
		$diff = R::diff( $ad->fresh(), $ad, array( 'name' ) );
		asrt( is_array( $diff ), TRUE );
		asrt( count( $diff ), 2 );
		asrt( $diff['ad.1.modified'][1], 111 );
		asrt( $diff['ad.1.created'][1], 111 );
		$ad = $ad->fresh();
		/* also diff changes in related beans */
		$ad->fetchAs('user')->author->name = 'Fred';
		$dog = reset( $ad->ownDog );
		$dog->color = 999;
		$old = $ad->fresh();
		$old->ownDog;
		$old->fetchAs('user')->author;
		$diff = R::diff( $ad, $old );
		asrt( is_array( $diff ), TRUE );
		asrt( count( $diff ), 2 );
		asrt( $diff['ad.1.ownDog.1.color'][1], 'green' );
		asrt( $diff['ad.1.ownDog.1.color'][0], 999 );
		asrt( $diff['ad.1.author.1.name'][1], 'John' );
		asrt( $diff['ad.1.author.1.name'][0], 'Fred' );
		$diff = R::diff( $ad, null );
		asrt( is_array( $diff ), TRUE );
		asrt( count( $diff ), 0 );
		$diff = R::diff( null, $ad );
		asrt( is_array( $diff ), TRUE );
		asrt( count( $diff ), 0 );

		/* demo case */
		list($book,$pages) = R::dispenseAll('book,page*2');
		$book->title = 'Old Book';
		$book->price = 999;
		$book->ownPageList = $pages;
		$pages[0]->text = 'abc';
		$pages[1]->text = 'def';
		R::store($book);
		$book->title = 'new Book';
		$page = end($book->ownPageList);
		$page->text = 'new';
		$oldBook = $book->fresh();
		$oldBook->ownPageList;
		$diff = R::diff($oldBook, $book);
	}

	/**
	 * Test misc. matchUp scenarios.
	 *
	 * @return void
	 */
	public function testMatchUpMisc()
	{
		R::nuke();
		asrt( R::count( 'bean' ), 0 );
		$found = R::matchUp( 'bean', ' id = ? ',  array(1), array(), array(
			'notfound' => function( $bean ) {
				$bean->status = 'not found';
			}
		) );
		asrt( $found, FALSE );
		asrt( R::count( 'bean' ), 1 );
		$bean = R::findOne( 'bean' );
		asrt( $bean->status, 'not found' );
		$null = R::matchUp( 'bean', ' id = ? ', array( $bean->id ) );
		asrt( is_null( $null ), TRUE );
	}
}
