<?php 

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException; 

/**
 * RedUNIT_Base_Tags
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
