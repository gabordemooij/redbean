<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Cursors
 *
 * @file    RedUNIT/Base/Cursors.php
 * @desc    Tests whether we can use cursors
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Cursors extends Base
{
	/**
	 * Test basic cursor functionality.
	 *
	 * @return void
	 */
	public function testBasicCursors()
	{
		R::nuke();
		for( $i=0; $i<20; $i++ ) {
			$page = R::dispense( 'page' );
			$page->number = $i;
			$page->content = sha1( $i );
			R::store( $page );
		}
		$collection = R::findCollection( 'page' );
		asrt( get_class( $collection ), 'RedBeanPHP\BeanCollection');
		$i = 0;
		$list = array();
		while( $bean = $collection->next() ) {
			asrt( ( $bean instanceof OODBBean ), TRUE );
			asrt( (string) $bean->number, strval( $i )  );
			asrt( $bean->content, sha1( $i ) );
			$list[] = $bean->content;
			$i ++;
		}
		$collection = R::findCollection( 'page', ' ORDER BY content ASC ' );
		sort( $list );
		$i = 0;
		while( $bean = $collection->next() ) {
			asrt( $bean->content, $list[$i] );
			$i ++;
		}
		$collection = R::findCollection( 'page', ' ORDER BY content ASC LIMIT 5 ' );
		sort( $list );
		$i = 0;
		while( $bean = $collection->next() ) {
			asrt( $bean->content, $list[$i] );
			$i ++;
			if ( $i > 5 ) break;
		}
		$key = array_rand( $list );
		$content = $list[ $key ];
		$collection = R::findCollection( 'page', ' content = ? ', array( $content ) );
		$bean = $collection->next();
		asrt( $bean->content, $content );
		$collection->close();
	}

	/**
	 * Test empty collections (NULLCursor).
	 *
	 * @return void
	 */
	public function testEmptyCollection()
	{
		R::nuke();
		$page = R::dispense( 'page' );
		$page->content = 'aaa';
		R::store( $page );
		$collection = R::findCollection( 'page' );
		asrt( get_class( $collection ), 'RedBeanPHP\BeanCollection');
		$collection = R::findCollection( 'page', ' content  =  ?', array( 'bbb' ) );
		asrt( get_class( $collection ), 'RedBeanPHP\BeanCollection');
		asrt( is_null( $collection->next() ), TRUE );
		$collection = R::findCollection( 'something' );
		asrt( get_class( $collection ), 'RedBeanPHP\BeanCollection');
		asrt( is_null( $collection->next() ), TRUE );
		$collection->close();
	}
}
