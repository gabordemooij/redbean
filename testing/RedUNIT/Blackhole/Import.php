<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\RedException as RedException;

/**
 * Import
 *
 * @file    RedUNIT/Blackhole/Import.php
 * @desc    Tests basic bean importing features.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class Import extends Blackhole
{
    /**
     * Test recursive imports (formely known as R::graph).
     *
     * @return void
     */
    public function testRecursiveImport()
    {
        $book = R::dispense(
            array(
                '_type' => 'book',
                'title' => 'The magic book',
                'ownPageList' => array(
                     array(
                        '_type' => 'page',
                        'content'  => 'magic potions',
                     ),
                     array(
                        '_type' => 'page',
                        'content'  => 'magic spells',
                     ),
                ),
            )
        );

        $id = R::store($book);
        $book = R::load('book', $id);
        asrt($book->title, 'The magic book');
        $pages = $book->with(' ORDER BY content ASC ')->ownPageList;
        asrt(count($pages), 2);
        $page1 = array_shift($pages);
        asrt($page1->content, 'magic potions');
        $page2 = array_shift($pages);
        asrt($page2->content, 'magic spells');

        R::nuke();

        $book = R::dispense(
            array(
                '_type' => 'book',
                'title' => 'The magic book',
                'author' => array(
                     '_type' => 'author',
                     'name'  => 'Dr. Evil',
                ),
                'coAuthor' => array(
                     '_type' => 'author',
                     'name'  => 'Dr. Creepy',
                ),
                'ownPageList' => array(
                     array(
                        '_type' => 'page',
                        'content'  => 'magic potions',
                        'ownRecipe' => array(
                             'a' => array('_type' => 'recipe', 'name' => 'Invisibility Salad'),
                             'b' => array('_type' => 'recipe', 'name' => 'Soup of Madness'),
                             'c' => array('_type' => 'recipe', 'name' => 'Love cake'),
                        ),
                     ),
                     array(
                        '_type' => 'page',
                        'content'  => 'magic spells',
                     ),
                ),
                'sharedCategory' => array(
                     array(
                          '_type' => 'category',
                          'label' => 'wizardry',
                     ),
                ),
            )
        );

        $id = R::store($book);
        $book = R::load('book', $id);
        asrt($book->title, 'The magic book');
        $pages = $book->with(' ORDER BY content ASC ')->ownPageList;
        asrt(count($pages), 2);
        $page1 = array_shift($pages);
        asrt($page1->content, 'magic potions');
        $page2 = array_shift($pages);
        asrt($page2->content, 'magic spells');
        $recipes = $page1->with(' ORDER BY name ASC ')->ownRecipeList;
        asrt(count($recipes), 3);
        $recipe1 = array_shift($recipes);
        asrt($recipe1->name, 'Invisibility Salad');
        $recipe2 = array_shift($recipes);
        asrt($recipe2->name, 'Love cake');
        $recipe3 = array_shift($recipes);
        asrt($recipe3->name, 'Soup of Madness');
        $categories = $book->sharedCategoryList;
        asrt(count($categories), 1);
        $category = reset($categories);
        asrt($category->label, 'wizardry');
        asrt($book->author->name, 'Dr. Evil');
        asrt($book->fetchAs('author')->coAuthor->name, 'Dr. Creepy');

        try {
            R::dispense(array());
            fail();
        } catch (RedException $ex) {
            pass();
        }

        try {
            R::dispense(array( 'property' => 'value' ));
            fail();
        } catch (RedException $ex) {
            pass();
        }
    }

    /**
     * Test import from and tainted.
     *
     * @return void
     */
    public function testImportFromAndTainted()
    {
        testpack('Test importFrom() and Tainted');

        $bean = R::dispense('bean');

        R::store($bean);

        $bean->name = 'abc';

        asrt($bean->getMeta('tainted'), true);

        R::store($bean);

        asrt($bean->getMeta('tainted'), false);

        $copy = R::dispense('bean');

        R::store($copy);

        $copy = R::load('bean', $copy->id);

        asrt($copy->getMeta('tainted'), false);

        $copy->import(array( 'name' => 'xyz' ));

        asrt($copy->getMeta('tainted'), true);

        $copy->setMeta('tainted', false);

        asrt($copy->getMeta('tainted'), false);

        $copy->importFrom($bean);

        asrt($copy->getMeta('tainted'), true);

        testpack('Test basic import() feature.');

        $bean = new OODBBean();

        $bean->import(array( "a" => 1, "b" => 2 ));

        asrt($bean->a, 1);
        asrt($bean->b, 2);

        $bean->import(array( "a" => 3, "b" => 4 ), "a,b");

        asrt($bean->a, 3);
        asrt($bean->b, 4);

        $bean->import(array( "a" => 5, "b" => 6 ), " a , b ");

        asrt($bean->a, 5);
        asrt($bean->b, 6);

        $bean->import(array( "a" => 1, "b" => 2 ));

        testpack('Test inject() feature.');

        $coffee = R::dispense('coffee');

        $coffee->id     = 2;
        $coffee->liquid = 'black';

        $cup = R::dispense('cup');

        $cup->color = 'green';

        // Pour coffee in cup
        $cup->inject($coffee);

        // Do we still have our own property?
        asrt($cup->color, 'green');

        // Did we pour the liquid in the cup?
        asrt($cup->liquid, 'black');

        // Id should not be transferred
        asrt($cup->id, 0);
    }

    /**
     * Test import using array access functions
     *
     * @return void
     */
    public function testArrayAccess()
    {
        $book = R::dispense('book');
        $book->isAwesome = true;
        asrt(isset($book->isAwesome), true);
        $book = R::dispense('book');
        $book['isAwesome'] = true;
        asrt(isset($book->isAwesome), true);

        $book = R::dispense('book');
        $book['xownPageList'] = R::dispense('page', 2);
        asrt(isset($book->ownPage), true);
        asrt(isset($book->xownPage), true);
        asrt(isset($book->ownPageList), true);
        asrt(isset($book->xownPageList), true);

        $book = R::dispense('book');
        $book['ownPageList'] = R::dispense('page', 2);
        asrt(isset($book->ownPage), true);
        asrt(isset($book->xownPage), true);
        asrt(isset($book->ownPageList), true);
        asrt(isset($book->xownPageList), true);

        $book = R::dispense('book');
        $book['xownPage'] = R::dispense('page', 2);
        asrt(isset($book->ownPage), true);
        asrt(isset($book->xownPage), true);
        asrt(isset($book->ownPageList), true);
        asrt(isset($book->xownPageList), true);

        $book = R::dispense('book');
        $book['ownPage'] = R::dispense('page', 2);
        asrt(isset($book->ownPage), true);
        asrt(isset($book->xownPage), true);
        asrt(isset($book->ownPageList), true);
        asrt(isset($book->xownPageList), true);

        $book = R::dispense('book');
        $book['sharedTag'] = R::dispense('tag', 2);
        asrt(isset($book->sharedTag), true);
        asrt(isset($book->sharedTagList), true);

        $book = R::dispense('book');
        $book['sharedTagList'] = R::dispense('tag', 2);
        asrt(isset($book->sharedTag), true);
        asrt(isset($book->sharedTagList), true);
    }
}
