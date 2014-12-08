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
class Toolbox extends Blackhole
{
    /**
     * Test whether we can obtain a toolbox properly.
     *
     * @return void
     */
    public function testCanWeObtainToolbox()
    {
        $toolbox = R::getToolBox();
        asrt(($toolbox instanceof TB), true);
        $extractedToolbox = R::getExtractedToolbox();
        asrt(is_array($extractedToolbox), true);
        asrt(count($extractedToolbox), 4);
        asrt(($extractedToolbox[0] instanceof OODB), true);
        asrt(($extractedToolbox[1] instanceof Adapter), true);
        asrt(($extractedToolbox[2] instanceof QueryWriter), true);
        asrt(($extractedToolbox[3] instanceof TB), true);

        $beanHelper = new SimpleFacadeBeanHelper();
        $toolbox2 = $beanHelper->getToolbox();
        asrt(($toolbox2 instanceof TB), true);
        asrt($toolbox, $toolbox2);
        $extractedToolbox = $beanHelper->getExtractedToolbox();
        asrt(is_array($extractedToolbox), true);
        asrt(count($extractedToolbox), 4);
        asrt(($extractedToolbox[0] instanceof OODB), true);
        asrt(($extractedToolbox[1] instanceof Adapter), true);
        asrt(($extractedToolbox[2] instanceof QueryWriter), true);
        asrt(($extractedToolbox[3] instanceof TB), true);
    }

    /**
     * Does the toolbox contain the necessary tools ?
     *
     * @return void
     */
    public function testDoesToolboxContainTheTools()
    {
        $toolbox = R::getToolBox();
        asrt(($toolbox->getDatabaseAdapter() instanceof Adapter), true);
        asrt(($toolbox->getRedBean() instanceof OODB), true);
        asrt(($toolbox->getWriter() instanceof QueryWriter), true);
    }

    /**
     * Tests whether freeze() switches the repository object
     * as it is supposed to do.
     *
     * @return void
     */
    public function testRepoSwitching()
    {
        asrt(class_exists('RedBeanPHP\Repository'), true);
        asrt(class_exists('RedBeanPHP\Repository\Fluid'), true);
        asrt(class_exists('RedBeanPHP\Repository\Frozen'), true);
        R::freeze(false);
        $redbean = R::getRedBean();
        $repo = $redbean->getCurrentRepository();
        asrt(is_object($repo), true);
        asrt(($repo instanceof Repository), true);
        asrt(($repo instanceof FluidRepo), true);
        R::freeze(true);
        $fluid = $repo;
        $repo = $redbean->getCurrentRepository();
        asrt(is_object($repo), true);
        asrt(($repo instanceof Repository), true);
        asrt(($repo instanceof FrozenRepo), true);
        $frozen = $repo;
        R::freeze(false);
        $redbean = R::getRedBean();
        $repo = $redbean->getCurrentRepository();
        asrt(is_object($repo), true);
        asrt(($repo instanceof Repository), true);
        asrt(($repo instanceof FluidRepo), true);
        asrt($repo, $fluid);
        R::freeze(true);
        $fluid = $repo;
        $repo = $redbean->getCurrentRepository();
        asrt(is_object($repo), true);
        asrt(($repo instanceof Repository), true);
        asrt(($repo instanceof FrozenRepo), true);
        asrt($repo, $frozen);
        R::freeze(false);
    }
}
