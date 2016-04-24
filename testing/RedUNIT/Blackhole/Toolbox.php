<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\ToolBox as TB;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper as SimpleFacadeBeanHelper;
use RedBeanPHP\Repository as Repository;
use RedBeanPHP\Repository\Fluid as FluidRepo;
use RedBeanPHP\Repository\Frozen as FrozenRepo;
use RedBeanPHP\RedException as RedException;

/**
 * Toolbox
 *
 * @file    RedUNIT/Blackhole/Toolbox.php
 * @desc    Toolbox tests.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Toolbox extends Blackhole {

	/**
	 * Tests the hasDatabase() method.
	 *
	 * @return void
	 */
	public function testDatabaseCheck()
	{
		R::addDatabase( 'key1', 'mysql:dsn1', 'user1', 'password1', TRUE );
		asrt( R::hasDatabase( 'key1' ), TRUE );
		asrt( R::hasDatabase( 'key2' ), FALSE );
	}

	/**
	 * Github issue #458, selectDatabase causes PHP notice undefined index
	 * if database key not found.
	 *
	 * @return void
	 */
	public function testInvalidDB()
	{
		try {
			R::selectDatabase( 'idontexist' );
			fail();
		} catch ( RedException $exception ) {
			pass();
		}
	}

	/**
	 * Test whether we can obtain a toolbox properly.
	 *
	 * @return void
	 */
	public function testCanWeObtainToolbox()
	{
		$toolbox = R::getToolBox();
		asrt( ( $toolbox instanceof TB), TRUE );
		$extractedToolbox = R::getExtractedToolbox();
		asrt( is_array( $extractedToolbox ), TRUE );
		asrt( count( $extractedToolbox ), 4 );
		asrt( ( $extractedToolbox[0] instanceof OODB ), TRUE );
		asrt( ( $extractedToolbox[1] instanceof Adapter ), TRUE );
		asrt( ( $extractedToolbox[2] instanceof QueryWriter ), TRUE );
		asrt( ( $extractedToolbox[3] instanceof TB ), TRUE );

		$beanHelper = new SimpleFacadeBeanHelper;
		$toolbox2 = $beanHelper->getToolbox();
		asrt( ( $toolbox2 instanceof TB), TRUE );
		asrt( $toolbox, $toolbox2 );
		$extractedToolbox = $beanHelper->getExtractedToolbox();
		asrt( is_array( $extractedToolbox ), TRUE );
		asrt( count( $extractedToolbox ), 4 );
		asrt( ( $extractedToolbox[0] instanceof OODB ), TRUE );
		asrt( ( $extractedToolbox[1] instanceof Adapter ), TRUE );
		asrt( ( $extractedToolbox[2] instanceof QueryWriter ), TRUE );
		asrt( ( $extractedToolbox[3] instanceof TB ), TRUE );
	}

	/**
	 * Does the toolbox contain the necessary tools ?
	 *
	 * @return void
	 */
	public function testDoesToolboxContainTheTools()
	{
		$toolbox = R::getToolBox();
		asrt( ( $toolbox->getDatabaseAdapter() instanceof Adapter ), TRUE );
		asrt( ( $toolbox->getRedBean() instanceof OODB ), TRUE );
		asrt( ( $toolbox->getWriter() instanceof QueryWriter ), TRUE );
	}

	/**
	 * Tests whether freeze() switches the repository object
	 * as it is supposed to do.
	 *
	 * @return void
	 */
	public function testRepoSwitching()
	{
		asrt( class_exists( 'RedBeanPHP\Repository' ), TRUE );
		asrt( class_exists( 'RedBeanPHP\Repository\Fluid' ), TRUE );
		asrt( class_exists( 'RedBeanPHP\Repository\Frozen' ), TRUE );
		R::freeze( FALSE );
		$redbean = R::getRedBean();
		$repo = $redbean->getCurrentRepository();
		asrt( is_object( $repo ), TRUE );
		asrt( ( $repo instanceof Repository ), TRUE );
		asrt( ( $repo instanceof FluidRepo ), TRUE );
		R::freeze( TRUE );
		$fluid = $repo;
		$repo = $redbean->getCurrentRepository();
		asrt( is_object( $repo ), TRUE );
		asrt( ( $repo instanceof Repository ), TRUE );
		asrt( ( $repo instanceof FrozenRepo ), TRUE );
		$frozen = $repo;
		R::freeze( FALSE );
		$redbean = R::getRedBean();
		$repo = $redbean->getCurrentRepository();
		asrt( is_object( $repo ), TRUE );
		asrt( ( $repo instanceof Repository ), TRUE );
		asrt( ( $repo instanceof FluidRepo ), TRUE );
		asrt( $repo, $fluid );
		R::freeze( TRUE );
		$fluid = $repo;
		$repo = $redbean->getCurrentRepository();
		asrt( is_object( $repo ), TRUE );
		asrt( ( $repo instanceof Repository ), TRUE );
		asrt( ( $repo instanceof FrozenRepo ), TRUE );
		asrt( $repo, $frozen );
		R::freeze( FALSE );
	}
}
