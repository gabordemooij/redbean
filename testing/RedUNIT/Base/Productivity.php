<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\Util\MatchUp;

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
		$didGenToken = $matchUp->matchUp( 'account', ' uname = ? AND archived = ?', ['Shawn',0], [
			'token'     => sha1(rand(0,9000) . time()),
			'tokentime' => time()
		], NULL, $account );
		
		asrt( $didGenToken, TRUE );
		asrt( !is_null( $account->token ) , TRUE );
		asrt( !is_null( $account->tokentime ) , TRUE );
		
		
		/* simulate a password reset script */
		$newpass = '1234';
		$didResetPass = $matchUp->matchUp( 'account', ' token = ? AND tokentime > ? ', [ $account->token, time()-100 ], [
			'pass' => $newpass,
			'token' => ''
		], NULL, $account );
		asrt( $account->pass, '1234' );
		asrt( $account->token, '' );
		
		/* simulate a login */
		$didFindUsr = $matchUp->matchUp( 'account', ' uname = ? ', ['Shawn'], [
			'attempts' => function( $acc ) {
				return ( $acc->pass !== '1234' ) ? ( $acc->attempts + 1 ) : 0;
			}
		], NULL, $account);
		
		asrt( $didFindUsr, TRUE );
		asrt( $account->attempts, 0 );
		
		/* Login failure */
		$didFindUsr = $matchUp->matchUp( 'account', ' uname = ? ', ['Shawn'], [
			'attempts' => function( $acc ) {
				return ( $acc->pass !== '1236' ) ? ( $acc->attempts + 1 ) : 0;
			}
		], NULL, $account);
		
		/* Create user if not exists */
		$didFindUsr = R::matchUp( 'account', ' uname = ? ', ['Anonymous'], [
		], [
			'uname' => 'newuser'
		], $account);
			
		
		asrt( $didFindUsr, FALSE );
		asrt( $account->uname, 'newuser' );
		
		
		
		
	}
	
	public function testLook()
	{
		
		
		
	}
	
	
}
