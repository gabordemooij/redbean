<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper as SimpleFacadeBeanHelper;

/**
 * Fuse
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
     * Test FUSE hooks (i.e. open, update, update_after etc..)
     *
     * @return void
     */
    public function testHooks()
    {
        R::nuke();
        $probe = R::dispense('probe');
        $probe->name = 'test';
        asrt($probe->getLogActionCount(), 1);
        asrt($probe->getLogActionCount('dispense'), 1);
        asrt($probe->getLogActionCount('open'), 0);
        asrt($probe->getLogActionCount('update'), 0);
        asrt($probe->getLogActionCount('after_update'), 0);
        asrt($probe->getLogActionCount('delete'), 0);
        asrt($probe->getLogActionCount('after_delete'), 0);
        asrt(($probe->getDataFromLog(0, 'bean') === $probe), true);
        R::store($probe);
        asrt($probe->getLogActionCount(), 3);
        asrt($probe->getLogActionCount('dispense'), 1);
        asrt($probe->getLogActionCount('open'), 0);
        asrt($probe->getLogActionCount('update'), 1);
        asrt($probe->getLogActionCount('after_update'), 1);
        asrt($probe->getLogActionCount('delete'), 0);
        asrt($probe->getLogActionCount('after_delete'), 0);
        asrt(($probe->getDataFromLog(2, 'bean') === $probe), true);
        $probe = R::load('probe', $probe->id);
        asrt($probe->getLogActionCount(), 2);
        asrt($probe->getLogActionCount('dispense'), 1);
        asrt($probe->getLogActionCount('open'), 1);
        asrt($probe->getLogActionCount('update'), 0);
        asrt($probe->getLogActionCount('after_update'), 0);
        asrt($probe->getLogActionCount('delete'), 0);
        asrt($probe->getLogActionCount('after_delete'), 0);
        asrt(($probe->getDataFromLog(0, 'bean') === $probe), true);
        asrt(($probe->getDataFromLog(1, 'id') === $probe->id), true);
        $probe->clearLog();
        R::trash($probe);
        asrt($probe->getLogActionCount(), 2);
        asrt($probe->getLogActionCount('dispense'), 0);
        asrt($probe->getLogActionCount('open'), 0);
        asrt($probe->getLogActionCount('update'), 0);
        asrt($probe->getLogActionCount('after_update'), 0);
        asrt($probe->getLogActionCount('delete'), 1);
        asrt($probe->getLogActionCount('after_delete'), 1);
        asrt(($probe->getDataFromLog(0, 'bean') === $probe), true);
        asrt(($probe->getDataFromLog(1, 'bean') === $probe), true);
        //less 'normal scenarios'
        $probe = R::dispense('probe');
        $probe->name = 'test';
        asrt($probe->getLogActionCount(), 1);
        asrt($probe->getLogActionCount('dispense'), 1);
        asrt($probe->getLogActionCount('open'), 0);
        asrt($probe->getLogActionCount('update'), 0);
        asrt($probe->getLogActionCount('after_update'), 0);
        asrt($probe->getLogActionCount('delete'), 0);
        asrt($probe->getLogActionCount('after_delete'), 0);
        asrt(($probe->getDataFromLog(0, 'bean') === $probe), true);
        R::store($probe);
        asrt($probe->getLogActionCount(), 3);
        asrt($probe->getLogActionCount('dispense'), 1);
        asrt($probe->getLogActionCount('open'), 0);
        asrt($probe->getLogActionCount('update'), 1);
        asrt($probe->getLogActionCount('after_update'), 1);
        asrt($probe->getLogActionCount('delete'), 0);
        asrt($probe->getLogActionCount('after_delete'), 0);
        asrt(($probe->getDataFromLog(2, 'bean') === $probe), true);
        asrt($probe->getMeta('tainted'), false);
        asrt($probe->getMeta('changed'), false);
        R::store($probe); //not tainted, no FUSE save!
        asrt($probe->getLogActionCount(), 3);
        asrt($probe->getLogActionCount('dispense'), 1);
        asrt($probe->getLogActionCount('open'), 0);
        asrt($probe->getLogActionCount('update'), 1);
        asrt($probe->getLogActionCount('after_update'), 1);
        asrt($probe->getLogActionCount('delete'), 0);
        asrt($probe->getLogActionCount('after_delete'), 0);
        asrt(($probe->getDataFromLog(2, 'bean') === $probe), true);
        $probe->xownProbeList[] = R::dispense('probe');
        //tainted, not changed, triggers FUSE
        asrt($probe->getMeta('tainted'), true);
        asrt($probe->getMeta('changed'), false);
        R::store($probe);
        asrt($probe->getMeta('tainted'), false);
        asrt($probe->getMeta('changed'), false);
        asrt($probe->getLogActionCount(), 5);
        asrt($probe->getLogActionCount('dispense'), 1);
        asrt($probe->getLogActionCount('open'), 0);
        asrt($probe->getLogActionCount('update'), 2);
        asrt($probe->getLogActionCount('after_update'), 2);
        asrt($probe->getLogActionCount('delete'), 0);
        asrt($probe->getLogActionCount('after_delete'), 0);
        asrt(($probe->getDataFromLog(2, 'bean') === $probe), true);
    }

    /**
     * Tests the SimpleFacadeBeanHelper factory setter.
     *
     * @return void
     */
    public function testFactory()
    {
        SimpleFacadeBeanHelper::setFactoryFunction(function ($name) {
            $model = new $name();
            $model->setNote('injected', 'dependency');

            return $model;
        });

        $bean = R::dispense('band')->box();

        asrt(($bean instanceof \Model_Band), true);
        asrt(($bean->getNote('injected')), 'dependency');

        SimpleFacadeBeanHelper::setFactoryFunction(null);
    }

    /**
     * Make sure that beans of type book_page can be fused with
     * models like BookPage (beautified) as well as Book_Page (non-beautified).
     */
    public function testBeutificationOfLinkModel()
    {
        $page = R::dispense('page');
        $widget = R::dispense('widget');
        $page->sharedWidgetList[] = $widget;
        R::store($page);
        $testReport = \Model_PageWidget::getTestReport();
        asrt($testReport, 'didSave');

        $page = R::dispense('page');
        $gadget = R::dispense('gadget');
        $page->sharedGadgetList[] = $gadget;
        R::store($page);
        $testReport = \Model_Gadget_Page::getTestReport();
        asrt($testReport, 'didSave');
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
        asrt($testReport, 'didSave');
    }

    /**
     * Test extraction of toolbox.
     *
     * @return void
     */
    public function testGetExtractedToolBox()
    {
        $helper = new SimpleFacadeBeanHelper();

        list($redbean, $database, $writer, $toolbox) = $helper->getExtractedToolbox();

        asrt(($redbean  instanceof OODB), true);
        asrt(($database instanceof Adapter), true);
        asrt(($writer   instanceof QueryWriter), true);
        asrt(($toolbox  instanceof ToolBox), true);
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

        $blog = R::dispense('blog');

        $blog->title = 'testing';
        $blog->blog  = 'tesing';

        R::store($blog);

        $blogpost = R::load("blog", 1);

        $post = R::dispense("post");

        $post->message = "hello";

        $blog->sharedPost[] = $post;
        R::store($blog);

        $a = R::getAll("select * from blog ");

        R::tag($post, "lousy,smart");

        asrt(implode(',', R::tag($post)), "lousy,smart");

        R::tag($post, "clever,smart");

        $tagz = implode(',', R::tag($post));

        asrt(($tagz == "smart,clever" || $tagz == "clever,smart"), true);

        R::tag($blog, array( "smart", "interesting" ));

        asrt(implode(',', R::tag($blog)), "smart,interesting");

        try {
            R::tag($blog, array( "smart", "interesting", "lousy!" ));

            pass();
        } catch (RedException $e) {
            fail();
        }

        asrt(implode(',', R::tag($blog)), "smart,interesting,lousy!");
        asrt(implode(",", R::tag($blog)), "smart,interesting,lousy!");

        R::untag($blog, array( "smart", "interesting" ));

        asrt(implode(",", R::tag($blog)), "lousy!");

        asrt(R::hasTag($blog, array( "lousy!" )), true);
        asrt(R::hasTag($blog, array( "lousy!", "smart" )), true);
        asrt(R::hasTag($blog, array( "lousy!", "smart" ), true), false);

        R::tag($blog, false);

        asrt(count(R::tag($blog)), 0);

        R::tag($blog, array( "funny", "comic" ));

        asrt(count(R::tag($blog)), 2);

        R::addTags($blog, array( "halloween" ));

        asrt(count(R::tag($blog)), 3);
        asrt(R::hasTag($blog, array( "funny", "commic", "halloween" ), true), false);

        R::unTag($blog, array( "funny" ));
        R::addTags($blog, "horror");

        asrt(count(R::tag($blog)), 3);
        asrt(R::hasTag($blog, array( "horror", "commic", "halloween" ), true), false);

        // No double tags
        R::addTags($blog, "horror");

        asrt(R::hasTag($blog, array( "horror", "commic", "halloween" ), true), false);
        asrt(count(R::tag($blog)), 3);
    }
}
