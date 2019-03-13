<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;

/**
 * Tags
 *
 * Tests RedBeanPHP tagging functionality, should be easy
 * to tag beans, collect tags and integrate tags in SQL
 * snippets. Tags automatically result in N-M relations, i.e.
 * shared lists.
 *
 * @file    RedUNIT/Base/Tags.php
 * @desc    Tests the tagging of beans.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Tags extends Base
{
	public function testTagsCache()
	{
		R::nuke();
		R::getWriter()->setUseCache( TRUE );
		list( $beer1, $beer2, $beer3 ) = R::dispense( 'beer', 3 );
		$beer1->title = 'b1';
		$beer2->title = 'b2';
		$beer3->title = 'b3';
		R::tag( $beer1, 'stout' );
		R::tag( $beer2, 'porter' );
		R::tag( $beer3, 'lager,popular' );
		$beers = R::tagged( 'beer', 'lager,popular' );
		asrt(count($beers),1);
		R::exec( 'DELETE FROM beer_tag WHERE beer_id = ? -- keep-cache', array( $beer3->id ) );
		$beers = R::tagged( 'beer', 'lager,popular' );
		asrt(count($beers),1);
		R::getWriter()->setUseCache( FALSE );
		$beers = R::tagged( 'beer', 'lager,popular' );
		asrt(count($beers),0);
	}

	/**
	 * Tests tags with SQL.
	 *
	 * @return void
	 */
	public function testTagsWithSQL()
	{
		R::nuke();
		list( $m1, $m2, $m3 ) = R::dispense( 'movie', 3 );
		$m1->title = 'Frankenstein';
		$m2->title = 'Fall of the House Usher';
		$m3->title = 'Sleepy Hollow';
		R::tag($m1, 'horror,gothic');
		R::tag($m2, 'horror,gothic,short');
		R::tag($m3, 'horror,legend');
		asrt( count( R::tagged( 'movie', 'horror' ) ), 3);
		asrt( count( R::tagged( 'movie', 'horror', ' LIMIT 2' ) ), 2);
		asrt( count( R::tagged( 'movie', 'horror', ' LIMIT ?', array( 2 ) ) ), 2);
		asrt( count( R::tagged( 'movie', 'horror', ' ORDER BY movie.title DESC LIMIT ?', array( 2 ) ) ), 2);
		asrt( count( R::tagged( 'movie', 'horror,gothic', ' ORDER BY movie.title DESC LIMIT ?', array( 1 ) ) ), 1);
		asrt( count( R::tagged( 'movie', 'horror,gothic') ), 3 );
		asrt( count( R::taggedAll( 'movie', 'horror,gothic') ), 2 );
		asrt( R::countTaggedAll( 'movie', 'horror,gothic'), 2 );
		asrt( count( R::tagged( 'movie', 'horror,gothic', ' LIMIT ? ', array( 2 ) ) ), 2 );
		asrt( ( R::countTagged( 'movie', 'horror,gothic', ' LIMIT ? ', array( 2 ) ) ), 2 );
		asrt( count( R::taggedAll( 'movie', 'horror,gothic', ' LIMIT ? ', array( 2 ) ) ), 2 );
		asrt( R::countTaggedAll( 'movie', 'horror,gothic', ' LIMIT ? ', array( 2 ) ), 2 );
		asrt( count( R::tagged( 'movie', 'horror,gothic', ' LIMIT ? ', array( 1 ) ) ), 1 );
		asrt( ( R::countTagged( 'movie', 'horror,gothic', ' LIMIT ? ', array( 1 ) ) ), 1 );
		asrt( count( R::taggedAll( 'movie', 'horror,gothic', ' LIMIT ? ', array( 1 ) ) ), 1 );
		asrt( ( R::countTaggedAll( 'movie', 'horror,gothic', ' LIMIT ? ', array( 1 ) ) ), 1 );
		asrt( count( R::tagged( 'movie', 'horror,legend', ' LIMIT ? ', array( 1 ) ) ), 1 );
		asrt( ( R::countTagged( 'movie', 'horror,legend', ' LIMIT ? ', array( 1 ) ) ), 1 );
		asrt( count( R::taggedAll( 'movie', 'horror,legend', ' LIMIT ? ', array( 1 ) ) ), 1 );
		asrt( ( R::countTaggedAll( 'movie', 'horror,legend', ' LIMIT ? ', array( 1 ) ) ), 1 );
		asrt( count( R::tagged( 'movie', 'gothic,legend', ' LIMIT ? ', array( 1 ) ) ), 1 );
		asrt( ( R::countTagged( 'movie', 'gothic,legend', ' LIMIT ? ', array( 1 ) ) ), 1 );
		asrt( count( R::taggedAll( 'movie', 'gothic,legend', ' LIMIT ? ', array( 1 ) ) ), 0 );
		asrt( ( R::countTaggedAll( 'movie', 'gothic,legend', ' LIMIT ? ', array( 1 ) ) ), 0 );
		asrt( count( R::tagged( 'movie', 'romance', ' LIMIT ? ', array( 1 ) ) ), 0 );
		asrt( ( R::countTagged( 'movie', 'romance', ' LIMIT ? ', array( 1 ) ) ), 0 );
		asrt( count( R::taggedAll( 'movie', 'romance', ' LIMIT ? ', array( 1 ) ) ), 0 );
		asrt( ( R::countTaggedAll( 'movie', 'romance', ' LIMIT ? ', array( 1 ) ) ), 0 );
		asrt( count( R::tagged( 'movie', 'romance,xmas', ' LIMIT ? ', array( 1 ) ) ), 0 );
		asrt( ( R::countTagged( 'movie', 'romance,xmas', ' LIMIT ? ', array( 1 ) ) ), 0 );
		asrt( count( R::taggedAll( 'movie', 'romance,xmas', ' LIMIT ? ', array( 1 ) ) ), 0 );
		asrt( ( R::countTaggedAll( 'movie', 'romance,xmas', ' LIMIT ? ', array( 1 ) ) ), 0 );
		asrt( count( R::tagged( 'movie', 'gothic,short', ' LIMIT ? ', array( 4 ) ) ), 2 );
		asrt( ( R::countTagged( 'movie', 'gothic,short', ' LIMIT ? ', array( 4 ) ) ), 2 );
		asrt( count( R::taggedAll( 'movie', 'gothic,short', ' LIMIT ? ', array( 4 ) ) ), 1 );
		asrt( ( R::countTaggedAll( 'movie', 'gothic,short', ' LIMIT ? ', array( 4 ) ) ), 1 );
		asrt( count( R::tagged( 'movie', 'gothic,short', ' LIMIT 4 ' ) ), 2 );
		asrt( ( R::countTagged( 'movie', 'gothic,short', ' LIMIT 4 ' ) ), 2 );
		asrt( count( R::taggedAll( 'movie', 'gothic,short', ' LIMIT 4 ' ) ), 1 );
		asrt( ( R::countTaggedAll( 'movie', 'gothic,short', ' LIMIT 4 ' ) ), 1 );
		asrt( count( R::tagged( 'movie', 'gothic,short', ' ORDER BY movie.id DESC LIMIT 4 ' ) ), 2 );
		asrt( ( R::countTagged( 'movie', 'gothic,short', ' ORDER BY movie.id DESC LIMIT 4 ' ) ), 2 );
		asrt( count( R::taggedAll( 'movie', 'gothic,short', ' ORDER BY movie.id DESC LIMIT 4 ' ) ), 1 );
		asrt( ( R::countTaggedAll( 'movie', 'gothic,short', ' ORDER BY movie.id DESC LIMIT 4 ' ) ), 1 );
		asrt( count( R::tagged( 'movie', 'short', ' LIMIT ? ', array( 4 ) ) ), 1 );
		asrt( ( R::countTagged( 'movie', 'short', ' LIMIT ? ', array( 4 ) ) ), 1 );
		asrt( count( R::taggedAll( 'movie', 'short', ' LIMIT ? ', array( 4 ) ) ), 1 );
		asrt( ( R::countTaggedAll( 'movie', 'short', ' LIMIT ? ', array( 4 ) ) ), 1 );
		asrt( count( R::tagged( 'movie', '', ' LIMIT ? ', array( 4 ) ) ), 0 );
		asrt( ( R::countTagged( 'movie', '', ' LIMIT ? ', array( 4 ) ) ), 0 );
		asrt( count( R::taggedAll( 'movie', '', ' LIMIT ? ', array( 4 ) ) ), 0 );
		asrt( ( R::countTaggedAll( 'movie', '', ' LIMIT ? ', array( 4 ) ) ), 0 );
		asrt( count( R::tagged( 'movie', '', ' LIMIT 4 ' ) ), 0 );
		asrt( ( R::countTagged( 'movie', '', ' LIMIT 4 ' ) ), 0 );
		asrt( count( R::taggedAll( 'movie', '', ' LIMIT 4 ' ) ), 0 );
		asrt( ( R::countTaggedAll( 'movie', '', ' LIMIT 4 ' ) ), 0 );
		asrt( count( R::tagged( 'movie', '', '' ) ), 0 );
		asrt( ( R::countTagged( 'movie', '', '' ) ), 0 );
		asrt( count( R::taggedAll( 'movie', '', '' ) ), 0 );
		asrt( ( R::countTaggedAll( 'movie', '', '' ) ), 0 );
		asrt( count( R::tagged( 'movie', '' ) ), 0 );
		asrt( ( R::countTagged( 'movie', '' ) ), 0 );
		asrt( count( R::taggedAll( 'movie', '' ) ), 0 );
		asrt( ( R::countTaggedAll( 'movie', '' ) ), 0 );
	}

	/**
	 * Some basic tests.
	 *
	 * @return void
	 */
	public function testTags()
	{
		list( $c, $d, $e, $f ) = R::dispense( 'coffee', 4 );
		R::tag( $c, 'strong,black' );
		R::tag( $d, 'black' );
		R::tag( $e, 'strong,sweet' );
		R::tag( $f, 'black,strong' );
		asrt( count( R::taggedAll( 'coffee', 'strong,sweet' ) ), 1 );
		asrt( count( R::taggedAll( 'coffee', 'strong' ) ), 3 );
		asrt( count( R::taggedAll( 'coffee', '' ) ), 0 );
		asrt( count( R::taggedAll( 'coffee', 'sweet' ) ), 1 );
		asrt( count( R::taggedAll( 'coffee', 'sweet,strong' ) ), 1 );
		asrt( count( R::taggedAll( 'coffee', 'black,strong' ) ), 2 );
		asrt( count( R::taggedAll( 'coffee', array( 'black', 'strong' ) ) ), 2 );
		asrt( count( R::taggedAll( 'coffee', 'salty' ) ), 0 );
		$blog = R::dispense( 'blog' );
		$blog->title = 'testing';
		$blog->blog  = 'tesing';
		R::store( $blog );
		$blogpost = ( R::load( "blog", 1 ) );
		$post = R::dispense( "post" );
		$post->message = "hello";
		R::tag( $post, "lousy,smart" );
		asrt( implode( ',', R::tag( $post ) ), "lousy,smart" );
		R::tag( $post, "clever,smart" );
		$tagz = implode( ',', R::tag( $post ) );
		asrt( ( $tagz == "smart,clever" || $tagz == "clever,smart" ), TRUE );
		R::tag( $blog, array( "smart", "interesting" ) );
		asrt( implode( ',', R::tag( $blog ) ), "smart,interesting" );
		try {
			R::tag( $blog, array( "smart", "interesting", "lousy!" ) );
			pass();
		} catch ( RedException $e ) {
			fail();
		}
		asrt( implode( ',', R::tag( $blog ) ), "smart,interesting,lousy!" );
		R::untag( $blog, array( "smart", "interesting" ) );
		asrt( implode( ",", R::tag( $blog ) ), "lousy!" );
		asrt( R::hasTag( $blog, array( "lousy!" ) ), TRUE );
		asrt( R::hasTag( $blog, array( "lousy!", "smart" ) ), TRUE );
		asrt( R::hasTag( $blog, array( "lousy!", "smart" ), TRUE ), FALSE );
		R::tag( $blog, FALSE );
		asrt( count( R::tag( $blog ) ), 0 );
		R::tag( $blog, array( "funny", "comic" ) );
		asrt( count( R::tag( $blog ) ), 2 );
		R::addTags( $blog, array( "halloween" ) );
		asrt( count( R::tag( $blog ) ), 3 );
		asrt( R::hasTag( $blog, array( "funny", "commic", "halloween" ), TRUE ), FALSE );
		R::unTag( $blog, "funny" );
		R::addTags( $blog, "horror" );
		asrt( count( R::tag( $blog ) ), 3 );
		asrt( R::hasTag( $blog, array( "horror", "commic", "halloween" ), TRUE ), FALSE );
		//no double tags
		R::addTags( $blog, "horror" );
		asrt( R::hasTag( $blog, array( "horror", "commic", "halloween" ), TRUE ), FALSE );
		asrt( R::hasTag( $blog, "horror,commic,halloween", TRUE ), FALSE );
		asrt( count( R::tag( $blog ) ), 3 );
		testpack( "fetch tagged items" );
	}

	/**
	 * Fetching tagged items.
	 *
	 * @return void
	 */
	public function fetchTaggedItems()
	{
		$b = R::dispense( "book" );
		$b->title = 'horror';
		R::store( $b );
		$c = R::dispense( "book" );
		$c->title = 'creepy';
		R::store( $c );
		$d = R::dispense( "book" );
		$d->title = "chicklit";
		R::store( $d );
		R::tag( $b, "horror,classic" );
		R::tag( $d, "women,classic" );
		R::tag( $c, "horror" );
		$x = R::tagged( "book", "classic" );
		asrt( count( $x ), 2 );
		$x = R::tagged( "book", "classic,horror" );
		asrt( count( $x ), 3 );
		$x = R::tagged( "book", array( "classic", "horror" ) );
		asrt( count( $x ), 3 );
	}
}
