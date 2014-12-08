<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Export
 *
 * @file    RedUNIT/Blackhole/Export.php
 * @desc    Tests basic bean exporting features.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Export extends Blackhole
{
    /**
     * ExportAll.
     *
     * @return void
     */
    public function testExportAll()
    {
        testpack('Test exportAll');

        $redbean = R::getRedBean();

        $bean = new OODBBean();

        $bean->import(array( "a" => 1, "b" => 2 ));

        $bean->setMeta("justametaproperty", "hellothere");

        $arr = $bean->export();

        asrt(is_array($arr), true);

        asrt(isset($arr["a"]), true);
        asrt(isset($arr["b"]), true);

        asrt($arr["a"], 1);
        asrt($arr["b"], 2);

        asrt(isset($arr["__info"]), false);

        $arr = $bean->export(true);

        asrt(isset($arr["__info"]), true);

        asrt($arr["a"], 1);
        asrt($arr["b"], 2);

        $exportBean = $redbean->dispense("abean");

        $exportBean->setMeta("metaitem.bla", 1);

        $exportedBean = $exportBean->export(true);

        asrt($exportedBean["__info"]["metaitem.bla"], 1);
        asrt($exportedBean["__info"]["type"], "abean");

        // Can we determine whether a bean is empty?
        testpack('test $bean->isEmpty() function');

        $bean = R::dispense('bean');

        asrt($bean->isEmpty(), true);
        asrt((count($bean) > 0), true);

        $bean->property = 1;

        asrt($bean->isEmpty(), false);
        asrt((count($bean) > 0), true);

        $bean->property = 0;

        asrt($bean->isEmpty(), true);
        asrt((count($bean) > 0), true);

        $bean->property = false;

        asrt($bean->isEmpty(), true);
        asrt((count($bean) > 0), true);

        $bean->property = null;

        asrt($bean->isEmpty(), true);
        asrt((count($bean) > 0), true);

        unset($bean->property);

        asrt($bean->isEmpty(), true);
        asrt((count($bean) > 0), true);

        // Export bug I found
        $bandmember = R::dispense('bandmember');
        $bandmember->name = 'Duke';
        $instrument = R::dispense('instrument');
        $instrument->name = 'Piano';
        $bandmember->ownInstrument[] = $instrument;

        $a = R::exportAll($bandmember);

        pass();

        asrt(isset($a[0]), true);
        asrt((int) $a[0]['id'], 0);

        asrt($a[0]['name'], 'Duke');

        asrt($a[0]['ownInstrument'][0]['name'], 'Piano');

        R::nuke();

        $v = R::dispense('village');
        $b = R::dispense('building');

        $v->name = 'a';
        $b->name = 'b';

        $v->ownBuilding[] = $b;

        $id = R::store($v);

        $a = R::exportAll($v);

        asrt($a[0]['name'], 'a');
        asrt($a[0]['ownBuilding'][0]['name'], 'b');

        $v = R::load('village', $id);

        $b2 = R::dispense('building');

        $b2->name = 'c';

        $v->ownBuilding[] = $b2;

        $a = R::exportAll($v);

        asrt($a[0]['name'], 'a');
        asrt($a[0]['ownBuilding'][0]['name'], 'b');

        asrt(count($a[0]['ownBuilding']), 2);

        list($r1, $r2) = R::dispense('army', 2);

        $r1->name = '1';
        $r2->name = '2';

        $v->sharedArmy[] = $r2;

        $a = R::exportAll($v);

        asrt(count($a[0]['sharedArmy']), 1);

        R::store($v);

        $v = R::load('village', $id);

        $a = R::exportAll($v);

        asrt(count($a[0]['sharedArmy']), 1);

        asrt($a[0]['name'], 'a');
        asrt($a[0]['ownBuilding'][0]['name'], 'b');

        asrt(count($a[0]['ownBuilding']), 2);

        $v->sharedArmy[] = $r1;

        $a = R::exportAll($v);

        asrt(count($a[0]['sharedArmy']), 2);

        $v = R::load('village', $id);

        $a = R::exportAll($v);

        asrt(count($a[0]['sharedArmy']), 1);

        $v->sharedArmy[] = $r1;

        R::store($v);

        $v = R::load('village', $id);

        $a = R::exportAll($v);

        asrt(count($a[0]['sharedArmy']), 2);
    }
}
