<?php
/**
 * RedBean Tag Manager.
 * The tag manager offers an easy way to quickly implement basic tagging
 * functionality.
 *
 * @file       RedBean/TagManager.php
 * @desc       RedBean Tag Manager
 * @author     Gabor de Mooij and the RedBeanPHP community
 * @license    BSD/GPLv2
 *
 * Provides methods to tag beans and perform tag-based searches in the
 * bean database.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_TagManager
{

	/**
	 * @var RedBean_Toolbox
	 */
	protected $toolbox;

	/**
	 * @var RedBean_AssociationManager
	 */
	protected $associationManager;

	/**
	 * @var RedBean_OODBBean
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
		if ( $tagList !== false && !is_array( $tagList ) ) {
			$tags = explode( ',', (string) $tagList );
		} else {
			$tags = $tagList;
		}

		return $tags;
	}

	/**
	 * Constructor.
	 * The tag manager offers an easy way to quickly implement basic tagging
	 * functionality.
	 *
	 * @param RedBean_Toolbox $toolbox
	 */
	public function __construct( RedBean_Toolbox $toolbox )
	{
		$this->toolbox = $toolbox;
		$this->redbean = $toolbox->getRedBean();

		$this->associationManager = $this->redbean->getAssociationManager();
	}

	/**
	 * Finds a tag bean by it's title.
	 *
	 * @param string $title title
	 *
	 * @return RedBean_OODBBean
	 */
	public function findTagByTitle( $title )
	{
		$beans = $this->redbean->find( 'tag', array( 'title' => array( $title ) ) );

		if ( $beans ) {
			$bean = reset( $beans );

			return $bean;
		}

		return null;
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
	 * @param  RedBean_OODBBean $bean bean to check for tags
	 * @param  array            $tags list of tags
	 * @param  boolean          $all  whether they must all match or just some
	 *
	 * @return boolean
	 */
	public function hasTag( $bean, $tags, $all = false )
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
	 * @param  RedBean_OODBBean $bean    tagged bean
	 * @param  array            $tagList list of tags (names)
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
	 * If $tagList is null or omitted this method will return a
	 * comma separated list of tags associated with the bean provided.
	 * If $tagList is a comma separated list (string) of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param RedBean_OODBBean $bean    bean
	 * @param mixed            $tagList tags
	 *
	 * @return string
	 */
	public function tag( RedBean_OODBBean $bean, $tagList = null )
	{
		if ( is_null( $tagList ) ) {
			$tags = array();
			$keys = $this->associationManager->related( $bean, 'tag' );

			if ( $keys ) {
				$tags = $this->redbean->batch( 'tag', $keys );
			}

			$foundTags = array();

			foreach ( $tags as $tag ) {
				$foundTags[] = $tag->title;
			}

			return $foundTags;
		}

		$this->associationManager->clearRelations( $bean, 'tag' );
		$this->addTags( $bean, $tagList );
	}

	/**
	 * Adds tags to a bean.
	 * If $tagList is a comma separated list of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param RedBean_OODBBean $bean    bean to add tags to
	 * @param array            $tagList list of tags to add to bean
	 *
	 * @return void
	 */
	public function addTags( RedBean_OODBBean $bean, $tagList )
	{
		$tags = $this->extractTagsIfNeeded( $tagList );

		if ( $tagList === false ) {
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
	 * Returns all beans that have been tagged with one of the tags given.
	 *
	 * @param string $beanType type of bean you are looking for
	 * @param array  $tagList  list of tags to match
	 *
	 * @return array
	 */
	public function tagged( $beanType, $tagList )
	{
		$tags       = $this->extractTagsIfNeeded( $tagList );

		$collection = array();

		$tags       = $this->redbean->find( 'tag', array( 'title' => $tags ) );

		if ( is_array( $tags ) && count( $tags ) > 0 ) {
			$collectionKeys = $this->associationManager->related( $tags, $beanType );

			if ( $collectionKeys ) {
				$collection = $this->redbean->batch( $beanType, $collectionKeys );
			}
		}

		return $collection;
	}

	/**
	 * Returns all beans that have been tagged with ALL of the tags given.
	 *
	 * @param string $beanType type of bean you are looking for
	 * @param array  $tagList  list of tags to match
	 *
	 * @return array
	 */
	public function taggedAll( $beanType, $tagList )
	{
		$tags  = $this->extractTagsIfNeeded( $tagList );

		$beans = array();
		foreach ( $tags as $tag ) {
			$beans = $this->tagged( $beanType, $tag );

			if ( isset( $oldBeans ) ) {
				$beans = array_intersect_assoc( $beans, $oldBeans );
			}

			$oldBeans = $beans;
		}

		return $beans;
	}
}
