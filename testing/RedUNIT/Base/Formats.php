<?php
/**
 * RedUNIT_Base_Formats
 *
 * @file    RedUNIT/Base/Formats.php
 * @desc    Tests bean formatting, custom table mapping features.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Formats extends RedUNIT_Base
{
	public function run()
	{
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();

		R::$writer->tableFormatter = new MyTableFormatter;

		$page = R::dispense( "page" );

		$page->title = "mypage";

		$id = R::store( $page );

		$page = R::dispense( "page" );

		$page->title = "mypage2";

		R::store( $page );

		$beans = R::find( "page" );

		asrt( count( $beans ), 2 );

		$user = R::dispense( "user" );

		$user->name = "me";

		R::store( $user );

		R::associate( $user, $page );

		asrt( count( R::related( $user, "page" ) ), 1 );

		$page = R::load( "page", $id );

		asrt( $page->title, "mypage" );

		R::associate( $user, $page );

		asrt( count( R::related( $user, "page" ) ), 2 );
		asrt( count( R::related( $page, "user" ) ), 1 );

		$user2 = R::dispense( "user" );

		$user2->name = "Bob";

		R::store( $user2 );

		$user3 = R::dispense( "user" );

		$user3->name = "Kim";

		R::store( $user3 );

		$tables = R::$writer->getTables();

		asrt( in_array( "xx_page", $tables ), true );
		asrt( in_array( "xx_page_user", $tables ), true );
		asrt( in_array( "xx_user", $tables ), true );

		asrt( in_array( "page", $tables ), false );
		asrt( in_array( "page_user", $tables ), false );
		asrt( in_array( "user", $tables ), false );

		$page2 = R::dispense( "page" );

		$page2->title = "mypagex";

		R::store( $page2 );

		R::associate( $page, $page2, '{"bla":2}' );

		$pgs = R::related( $page, "page" );

		$p = reset( $pgs );

		asrt( $p->title, "mypagex" );
		asrt( R::getCell( "select bla from xx_page_page where bla > 0" ), "2" );

		$tables = R::$writer->getTables();

		asrt( in_array( "xx_page_page", $tables ), true );
		asrt( in_array( "page_page", $tables ), false );

		R::$writer->setBeanFormatter( new MyBeanFormatter() );

		$blog = R::dispense( 'blog' );

		$blog->title = 'testing';
		$blog->blog  = 'tesing';

		R::store( $blog );

		$blogpost = ( R::load( "blog", 1 ) );

		asrt( isset( $blogpost->cms_blog_id ), false );
		asrt( isset( $blogpost->blog_id ), true );

		asrt( in_array( "blog_id", array_keys( R::$writer->getColumns( "blog" ) ) ), true );
		asrt( in_array( "cms_blog_id", array_keys( R::$writer->getColumns( "blog" ) ) ), false );

		$post = R::dispense( "post" );

		$post->message = "hello";

		R::associate( $blog, $post );

		asrt( count( R::related( $blog, "post" ) ), 1 );
		asrt( count( R::find( "blog", " title LIKE '%est%' " ) ), 1 );

		$a = R::getAll( "select * from " . tbl( "blog" ) . " " );

		asrt( count( $a ), 1 );
	}
}
