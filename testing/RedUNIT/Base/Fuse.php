<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\ModelHelper as ModelHelper;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper as SimpleFacadeBeanHelper;

/**
 * FUSE
 *
 * Tests whether we can associate model logic on-the-fly
 * by defining models extending from SimpleModel. Tests
 * whether the calls to facade trigger the corresponding
 * methods on the model.
 *
 * @file    RedUNIT/Base/Fuse.php
 * @desc    Tests Fuse feature; coupling beans to models.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Fuse extends Base
{
	/**
	 * Hook into the jsonSerialize function #651
	 * Allow models to provide a jsonSerialize return.
	 * This test uses the Coffee Helper Model to add
	 * a description to the JSON representation of the bean.
	 *
	 * @return void
	 */
	public function testFUSEJSonSerialize()
	{
		if ( phpversion() < 5.4 ) return;
		$coffee = R::dispense( 'coffee' );
		$coffee->variant = 'Tropical';
		$coffee->strength = 4;
		$json = json_encode( $coffee );
		$array = json_decode( $json, TRUE );
		asrt( isset( $array['description'] ), TRUE );
		asrt( $array['description'], 'Tropical.4' );
	}

	/**
	 * Test whether we can override the getModelForBean() method
	 * of the BeanHelper and use a custom BeanHelper to attach a model
	 * based on type.
	 *
	 * @return void
	 */
	public function testCustomBeanHelper()
	{
		$customBeanHelper = new \SoupBeanHelper( R::getToolbox() );
		$oldBeanHelper = R::getRedBean()->getBeanHelper();
		asrt( ( $oldBeanHelper instanceof SimpleFacadeBeanHelper ), TRUE );
		R::getRedBean()->setBeanHelper( $customBeanHelper );
		$meal = R::dispense( 'meal' );
		asrt( ( $meal->box() instanceof \Model_Soup ), TRUE );
		$cake = R::dispense( 'cake' );
		asrt( is_null( $cake->box() ), TRUE );
		$bean = R::dispense( 'coffee' );
		asrt( ( $bean->box() instanceof \Model_Coffee ), TRUE );
		$meal->setFlavour( 'tomato' );
		asrt( $meal->getFlavour(), 'tomato' );
		$meal->rating = 5;
		R::store( $meal );
		asrt( $meal->getFlavour(), 'tomato' );
		$meal = $meal->unbox();
		asrt( $meal->getFlavour(), 'tomato' );
		$meal = R::findOne( 'meal' );
		asrt( ( $meal->box() instanceof \Model_Soup ), TRUE );
		asrt( $meal->getFlavour(), '' );
		$meal->setFlavour( 'tomato' );
		asrt( $meal->getFlavour(), 'tomato' );
		$meal = $meal->unbox();
		asrt( $meal->getFlavour(), 'tomato' );
		R::getRedBean()->setBeanHelper( $oldBeanHelper );
	}

	/**
	 * Test FUSE hooks (i.e. open, update, update_after etc..)
	 *
	 * @return void
	 */
	public function testHooks()
	{
		R::nuke();
		$probe = R::dispense( 'probe' );
		$probe->name = 'test';
		asrt( $probe->getLogActionCount(), 1 );
		asrt( $probe->getLogActionCount( 'dispense' ), 1 );
		asrt( $probe->getLogActionCount( 'open' ), 0 );
		asrt( $probe->getLogActionCount( 'update' ), 0 );
		asrt( $probe->getLogActionCount( 'after_update' ), 0 );
		asrt( $probe->getLogActionCount( 'delete' ), 0 );
		asrt( $probe->getLogActionCount( 'after_delete' ), 0 );
		asrt( ( $probe->getDataFromLog( 0, 'bean' ) === $probe ), TRUE );
		R::store( $probe );
		asrt( $probe->getLogActionCount(), 3 );
		asrt( $probe->getLogActionCount( 'dispense' ), 1 );
		asrt( $probe->getLogActionCount( 'open' ), 0 );
		asrt( $probe->getLogActionCount( 'update' ), 1 );
		asrt( $probe->getLogActionCount( 'after_update' ), 1 );
		asrt( $probe->getLogActionCount( 'delete' ), 0 );
		asrt( $probe->getLogActionCount( 'after_delete' ), 0 );
		asrt( ( $probe->getDataFromLog( 2, 'bean' ) === $probe ), TRUE );
		$probe = R::load( 'probe', $probe->id );
		asrt( $probe->getLogActionCount(), 2 );
		asrt( $probe->getLogActionCount( 'dispense' ), 1 );
		asrt( $probe->getLogActionCount( 'open' ), 1 );
		asrt( $probe->getLogActionCount( 'update' ), 0 );
		asrt( $probe->getLogActionCount( 'after_update' ), 0 );
		asrt( $probe->getLogActionCount( 'delete' ), 0 );
		asrt( $probe->getLogActionCount( 'after_delete' ), 0 );
		asrt( ( $probe->getDataFromLog( 0, 'bean' ) === $probe ), TRUE );
		asrt( ( $probe->getDataFromLog( 1, 'id' ) === $probe->id ), TRUE );
		$probe->clearLog();
		R::trash( $probe );
		asrt( $probe->getLogActionCount(), 2 );
		asrt( $probe->getLogActionCount( 'dispense' ), 0 );
		asrt( $probe->getLogActionCount( 'open' ), 0 );
		asrt( $probe->getLogActionCount( 'update' ), 0 );
		asrt( $probe->getLogActionCount( 'after_update' ), 0 );
		asrt( $probe->getLogActionCount( 'delete' ), 1 );
		asrt( $probe->getLogActionCount( 'after_delete' ), 1 );
		asrt( ( $probe->getDataFromLog( 0, 'bean' ) === $probe ), TRUE );
		asrt( ( $probe->getDataFromLog( 1, 'bean' ) === $probe ), TRUE );
		//less 'normal scenarios'
		$probe = R::dispense( 'probe' );
		$probe->name = 'test';
		asrt( $probe->getLogActionCount(), 1 );
		asrt( $probe->getLogActionCount( 'dispense' ), 1 );
		asrt( $probe->getLogActionCount( 'open' ), 0 );
		asrt( $probe->getLogActionCount( 'update' ), 0 );
		asrt( $probe->getLogActionCount( 'after_update' ), 0 );
		asrt( $probe->getLogActionCount( 'delete' ), 0 );
		asrt( $probe->getLogActionCount( 'after_delete' ), 0 );
		asrt( ( $probe->getDataFromLog( 0, 'bean' ) === $probe ), TRUE );
		R::store( $probe );
		asrt( $probe->getLogActionCount(), 3 );
		asrt( $probe->getLogActionCount( 'dispense' ), 1 );
		asrt( $probe->getLogActionCount( 'open' ), 0 );
		asrt( $probe->getLogActionCount( 'update' ), 1 );
		asrt( $probe->getLogActionCount( 'after_update' ), 1 );
		asrt( $probe->getLogActionCount( 'delete' ), 0 );
		asrt( $probe->getLogActionCount( 'after_delete' ), 0 );
		asrt( ( $probe->getDataFromLog( 2, 'bean' ) === $probe ), TRUE );
		asrt( $probe->getMeta( 'tainted' ), FALSE );
		asrt( $probe->getMeta( 'changed' ), FALSE );
		R::store( $probe ); //not tainted, no FUSE save!
		asrt( $probe->getLogActionCount(), 3 );
		asrt( $probe->getLogActionCount( 'dispense' ), 1 );
		asrt( $probe->getLogActionCount( 'open' ), 0 );
		asrt( $probe->getLogActionCount( 'update' ), 1 );
		asrt( $probe->getLogActionCount( 'after_update' ), 1 );
		asrt( $probe->getLogActionCount( 'delete' ), 0 );
		asrt( $probe->getLogActionCount( 'after_delete' ), 0 );
		asrt( ( $probe->getDataFromLog( 2, 'bean' ) === $probe ), TRUE );
		$probe->xownProbeList[] = R::dispense( 'probe' );
		//tainted, not changed, triggers FUSE
		asrt( $probe->getMeta( 'tainted' ), TRUE );
		asrt( $probe->getMeta( 'changed' ), FALSE );
		R::store( $probe );
		asrt( $probe->getMeta( 'tainted' ), FALSE );
		asrt( $probe->getMeta( 'changed' ), FALSE );
		asrt( $probe->getLogActionCount(), 5 );
		asrt( $probe->getLogActionCount( 'dispense' ), 1 );
		asrt( $probe->getLogActionCount( 'open' ), 0 );
		asrt( $probe->getLogActionCount( 'update' ), 2 );
		asrt( $probe->getLogActionCount( 'after_update' ), 2 );
		asrt( $probe->getLogActionCount( 'delete' ), 0 );
		asrt( $probe->getLogActionCount( 'after_delete' ), 0 );
		asrt( ( $probe->getDataFromLog( 2, 'bean' ) === $probe ), TRUE );
	}

	/**
	 * Tests the SimpleFacadeBeanHelper factory setter.
	 *
	 * @return void
	 */
	public function testFactory()
	{
		SimpleFacadeBeanHelper::setFactoryFunction( function( $name ) {
			$model = new $name();
			$model->setNote( 'injected', 'dependency' );
			return $model;
		} );

		$bean = R::dispense( 'band' )->box();

		asrt( ( $bean instanceof \Model_Band ), TRUE );
		asrt( ( $bean->getNote('injected') ), 'dependency' );

		SimpleFacadeBeanHelper::setFactoryFunction( NULL );
	}

	/**
	 * Make sure that beans of type book_page can be fused with
	 * models like BookPage (beautified) as well as Book_Page (non-beautified).
	 */
	public function testBeutificationOfLinkModel()
	{
		$page = R::dispense( 'page' );
		$widget = R::dispense( 'widget' );
		$page->sharedWidgetList[] = $widget;
		R::store( $page );
		$testReport = \Model_PageWidget::getTestReport();
		asrt( $testReport, 'didSave' );

		$page = R::dispense( 'page' );
		$gadget = R::dispense( 'gadget' );
		$page->sharedGadgetList[] = $gadget;
		R::store( $page );
		$testReport = \Model_Gadget_Page::getTestReport();
		asrt( $testReport, 'didSave' );
	}

	/**
	 * Only theoretical.
	 *
	 * @return void
	 */
	public function testTheoreticalBeautifications()
	{
		$bean = R::dispense('bean');
		$bean->setMeta('type', 'a_b_c');
		R::store($bean);
		$testReport = \Model_A_B_C::getTestReport();
		asrt( $testReport, 'didSave' );
	}

	/**
	 * Test extraction of toolbox.
	 *
	 * @return void
	 */
	public function testGetExtractedToolBox()
	{
		$helper = new SimpleFacadeBeanHelper;

		list( $redbean, $database, $writer, $toolbox ) = $helper->getExtractedToolbox();

		asrt( ( $redbean  instanceof OODB        ), TRUE );
		asrt( ( $database instanceof Adapter     ), TRUE );
		asrt( ( $writer   instanceof QueryWriter ), TRUE );
		asrt( ( $toolbox  instanceof ToolBox     ), TRUE );
	}

	/**
	 * Test FUSE and model formatting.
	 *
	 * @todo move tagging tests to tag tester.
	 *
	 * @return void
	 */
	public function testFUSE()
	{
		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$blog = R::dispense( 'blog' );
		$blog->title = 'testing';
		$blog->blog  = 'tesing';
		R::store( $blog );
		$blogpost = R::load( "blog", 1 );
		$post = R::dispense( "post" );
		$post->message = "hello";
		$blog->sharedPost[] = $post;
		R::store($blog);
		$a = R::getAll( "select * from blog " );
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
		asrt( implode( ",", R::tag( $blog ) ), "smart,interesting,lousy!" );
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
		R::unTag( $blog, array( "funny" ) );
		R::addTags( $blog, "horror" );
		asrt( count( R::tag( $blog ) ), 3 );
		asrt( R::hasTag( $blog, array( "horror", "commic", "halloween" ), TRUE ), FALSE );
		// No double tags
		R::addTags( $blog, "horror" );
		asrt( R::hasTag( $blog, array( "horror", "commic", "halloween" ), TRUE ), FALSE );
		asrt( count( R::tag( $blog ) ), 3 );
	}

	/**
	 * Test error handling options FUSE.
	 */
	public function testModelErrorHandling()
	{
		$test = R::dispense( 'feed' );
		$test->nonExistantMethod();
		pass();
		$old = R::setErrorHandlingFUSE( OODBBean::C_ERR_LOG );
		asrt( is_array( $old ), TRUE );
		asrt( count( $old ), 2 );
		asrt( $old[0], FALSE );
		asrt( $old[1], NULL);
		$test->nonExistantMethod(); //we cant really test this... :(
		pass();
		$old = R::setErrorHandlingFUSE( OODBBean::C_ERR_NOTICE );
		asrt( is_array( $old ), TRUE );
		asrt( count( $old ), 2 );
		asrt( $old[0], OODBBean::C_ERR_LOG );
		asrt( $old[1], NULL);
		set_error_handler(function($error, $str) {
			asrt( $str, 'FUSE: method does not exist in model: nonExistantMethod' );
		}, E_USER_NOTICE);
		$test->nonExistantMethod();
		restore_error_handler();
		$old = OODBBean::setErrorHandlingFUSE( OODBBean::C_ERR_WARN );
		asrt( is_array( $old ), TRUE );
		asrt( count( $old ), 2 );
		asrt( $old[0], OODBBean::C_ERR_NOTICE );
		asrt( $old[1], NULL);
		set_error_handler(function($error, $str) {
			asrt( $str, 'FUSE: method does not exist in model: nonExistantMethod' );
		}, E_USER_WARNING);
		$test->nonExistantMethod();
		restore_error_handler();
		$old = OODBBean::setErrorHandlingFUSE( OODBBean::C_ERR_FATAL );
		asrt( is_array( $old ), TRUE );
		asrt( count( $old ), 2 );
		asrt( $old[0], OODBBean::C_ERR_WARN );
		asrt( $old[1], NULL);
		set_error_handler(function($error, $str) {
			asrt( $str, 'FUSE: method does not exist in model: nonExistantMethod' );
		}, E_USER_ERROR);
		$test->nonExistantMethod();
		restore_error_handler();
		$old = OODBBean::setErrorHandlingFUSE( OODBBean::C_ERR_EXCEPTION );
		asrt( is_array( $old ), TRUE );
		asrt( count( $old ), 2 );
		asrt( $old[0], OODBBean::C_ERR_FATAL );
		asrt( $old[1], NULL);
		try {
			$test->nonExistantMethod();
			fail();
		} catch (\Exception $e) {
			pass();
		}
		global $test_bean;
		$test_bean = $test;
		global $has_executed_error_func_fuse;
		$has_executed_error_func_fuse = FALSE;
		$old = OODBBean::setErrorHandlingFUSE( OODBBean::C_ERR_FUNC, function( $info ){
			global $has_executed_error_func_fuse;
			global $test_bean;
			$has_executed_error_func_fuse = TRUE;
			asrt( is_array( $info ), TRUE );
			asrt( $info['method'], 'nonExistantMethod' );
			asrt( json_encode( $info['bean']->export() ), json_encode( $test_bean->export() ) );
			asrt( $info['message'], 'FUSE: method does not exist in model: nonExistantMethod' );
		} );
		asrt( is_array( $old ), TRUE );
		asrt( count( $old ), 2 );
		asrt( $old[0], OODBBean::C_ERR_EXCEPTION );
		asrt( $old[1], NULL);
		$test->nonExistantMethod();
		asrt( $has_executed_error_func_fuse, TRUE );
		$old = OODBBean::setErrorHandlingFUSE( OODBBean::C_ERR_IGNORE );
		asrt( is_array( $old ), TRUE );
		asrt( count( $old ), 2 );
		asrt( $old[0], OODBBean::C_ERR_FUNC );
		asrt( is_callable( $old[1] ), TRUE );
		$old = OODBBean::setErrorHandlingFUSE( OODBBean::C_ERR_IGNORE );
		asrt( is_array( $old ), TRUE );
		asrt( count( $old ), 2 );
		asrt( $old[0], OODBBean::C_ERR_IGNORE );
		asrt( $old[1], NULL);
		try {
			OODBBean::setErrorHandlingFUSE( 900 );
			fail();
		} catch (\Exception $e) {
			pass();
			asrt( $e->getMessage(), 'Invalid error mode selected' );
		}
		try {
			OODBBean::setErrorHandlingFUSE( OODBBean::C_ERR_FUNC, 'hello' );
			fail();
		} catch (\Exception $e) {
			pass();
			asrt( $e->getMessage(), 'Invalid error handler' );
		}
		OODBBean::setErrorHandlingFUSE( OODBBean::C_ERR_EXCEPTION );
		//make sure ignore FUSE events
		$test = R::dispense('feed');
		R::store( $test );
		$test = $test->fresh();
		R::trash( $test );
		pass();
		OODBBean::setErrorHandlingFUSE( OODBBean::C_ERR_IGNORE );
	}
}
