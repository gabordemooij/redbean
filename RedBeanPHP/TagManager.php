<?php

namespace RedBeanPHP;

use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * RedBeanPHP Tag Manager.
 *
 * The tag manager offers an easy way to quickly implement basic tagging
 * functionality.
 *
 * Provides methods to tag beans and perform tag-based searches in the
 * bean database.
 *
 * @file       RedBeanPHP/TagManager.php
 * @author     Gabor de Mooij and the RedBeanPHP community
 * @license    BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class TagManager
{
	/**
	 * @var ToolBox
	 */
	protected $toolbox;

	/**
	 * @var AssociationManager
	 */
	protected $associationManager;

	/**
	 * @var OODBBean
	 */
	protected $redbean;

	/**
	 * Checks if the argument is a comma separated string, in this case
	 * it will split the string into words and return an array instead.
	 * In case of an array the argument will be returned 'as is'.
	 *
	 * @param array|string $tagList list of tags
	 *
	 * @return array
	 */
	private function extractTagsIfNeeded( $tagList )
	{
		if ( $tagList !== FALSE && !is_array( $tagList ) ) {
			$tags = explode( ',', (string) $tagList );
		} else {
			$tags = $tagList;
		}

		return $tags;
	}

	/**
	 * Finds a tag bean by it's title.
	 * Internal method.
	 *
	 * @param string $title title to search for
	 *
	 * @return OODBBean
	 */
	protected function findTagByTitle( $title )
	{
		$beans = $this->redbean->find( 'tag', array( 'title' => array( $title ) ) );

		if ( $beans ) {
			$bean = reset( $beans );

			return $bean;
		}

		return NULL;
	}

	/**
	 * Constructor.
	 * The tag manager offers an easy way to quickly implement basic tagging
	 * functionality.
	 *
	 * @param ToolBox $toolbox toolbox object
	 */
	public function __construct( ToolBox $toolbox )
	{
		$this->toolbox = $toolbox;
		$this->redbean = $toolbox->getRedBean();

		$this->associationManager = $this->redbean->getAssociationManager();
	}

	/**
	 * Tests whether a bean has been associated with one ore more
	 * of the listed tags. If the third parameter is TRUE this method
	 * will return TRUE only if all tags that have been specified are indeed
	 * associated with the given bean, otherwise FALSE.
	 * If the third parameter is FALSE this
	 * method will return TRUE if one of the tags matches, FALSE if none
	 * match.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * @param  OODBBean     $bean bean to check for tags
	 * @param  array|string $tags list of tags
	 * @param  boolean      $all  whether they must all match or just some
	 *
	 * @return boolean
	 */
	public function hasTag( $bean, $tags, $all = FALSE )
	{
		$foundtags = $this->tag( $bean );

		$tags = $this->extractTagsIfNeeded( $tags );
		$same = array_intersect( $tags, $foundtags );

		if ( $all ) {
			return ( implode( ',', $same ) === implode( ',', $tags ) );
		}

		return (bool) ( count( $same ) > 0 );
	}

	/**
	 * Removes all sepcified tags from the bean. The tags specified in
	 * the second parameter will no longer be associated with the bean.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * @param  OODBBean     $bean    tagged bean
	 * @param  array|string $tagList list of tags (names)
	 *
	 * @return void
	 */
	public function untag( $bean, $tagList )
	{
		$tags = $this->extractTagsIfNeeded( $tagList );

		foreach ( $tags as $tag ) {
			if ( $t = $this->findTagByTitle( $tag ) ) {
				$this->associationManager->unassociate( $bean, $t );
			}
		}
	}

	/**
	 * Tags a bean or returns tags associated with a bean.
	 * If $tagList is NULL or omitted this method will return a
	 * comma separated list of tags associated with the bean provided.
	 * If $tagList is a comma separated list (string) of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * @param OODBBean     $bean    bean to be tagged
	 * @param array|string $tagList a list of tags
	 *
	 * @return array
	 */
	public function tag( OODBBean $bean, $tagList = NULL )
	{
		if ( is_null( $tagList ) ) {

			$tags = $bean->sharedTag;
			$foundTags = array();

			foreach ( $tags as $tag ) {
				$foundTags[] = $tag->title;
			}

			return $foundTags;
		}

		$this->associationManager->clearRelations( $bean, 'tag' );
		$this->addTags( $bean, $tagList );

		return $tagList;
	}

	/**
	 * Adds tags to a bean.
	 * If $tagList is a comma separated list of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * @param OODBBean     $bean    bean to add tags to
	 * @param array|string $tagList list of tags to add to bean
	 *
	 * @return void
	 */
	public function addTags( OODBBean $bean, $tagList )
	{
		$tags = $this->extractTagsIfNeeded( $tagList );

		if ( $tagList === FALSE ) {
			return;
		}

		foreach ( $tags as $tag ) {
			if ( !$t = $this->findTagByTitle( $tag ) ) {
				$t        = $this->redbean->dispense( 'tag' );
				$t->title = $tag;

				$this->redbean->store( $t );
			}

			$this->associationManager->associate( $bean, $t );
		}
	}

	/**
	 * Returns all beans that have been tagged with one or more
	 * of the specified tags.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * @param string       $beanType type of bean you are looking for
	 * @param array|string $tagList  list of tags to match
	 * @param string       $sql      additional SQL (use only for pagination)
	 * @param array        $bindings bindings
	 *
	 * @return array
	 */
	public function tagged( $beanType, $tagList, $sql = '', $bindings = array() )
	{
		$tags       = $this->extractTagsIfNeeded( $tagList );
		$records    = $this->toolbox->getWriter()->queryTagged( $beanType, $tags, FALSE, $sql, $bindings );

		return $this->redbean->convertToBeans( $beanType, $records );
	}

	/**
	 * Returns all beans that have been tagged with ALL of the tags given.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * @param string       $beanType type of bean you are looking for
	 * @param array|string $tagList  list of tags to match
	 * @param string       $sql      additional sql snippet
	 * @param array        $bindings bindings
	 *
	 * @return array
	 */
	public function taggedAll( $beanType, $tagList, $sql = '', $bindings = array() )
	{
		$tags  = $this->extractTagsIfNeeded( $tagList );
		$records    = $this->toolbox->getWriter()->queryTagged( $beanType, $tags, TRUE, $sql, $bindings );

		return $this->redbean->convertToBeans( $beanType, $records );
	}
}
