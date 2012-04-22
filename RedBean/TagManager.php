<?php
/**
 * RedBean Tag Manager
 * 
 * @file			RedBean/TagManager.php
 * @description 	RedBean Tag Manager
 * 
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 * Provides methods to tag beans and perform tag-based searches in the
 * bean database.
 * 
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_TagManager {
	
	/**
	 * The Tag Manager requires a toolbox
	 * @var RedBean_Toolbox 
	 */
	protected $toolbox;
	
	/**
	 * Association Manager to manage tag-bean relations
	 * @var RedBean_AssociationManager
	 */
	protected $associationManager;
	
	/**
	 * RedBeanPHP OODB instance
	 * @var RedBean_OODBBean 
	 */
	protected $redbean;
	
	
	/**
	 * Constructor,
	 * creates a new instance of TagManager.
	 * @param RedBean_Toolbox $toolbox 
	 */
	public function __construct( RedBean_Toolbox $toolbox ) {
		$this->toolbox = $toolbox;
		$this->redbean = $toolbox->getRedBean();
		$this->associationManager = $this->redbean->getAssociationManager();
	}
	
	/**
	 * Finds a tag bean by it's title.
	 * 
	 * @param string $title title
	 * 
	 * @return RedBean_OODBBean $bean | null
	 */
	public function findTagByTitle($title) {
		$beans = $this->redbean->find('tag',array('title'=>array($title)));
		if ($beans) {
			return reset($beans);
		}
		return null;
	}
	
	/**
	 * Part of RedBeanPHP Tagging API.
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
	 * @return boolean $didMatch whether the bean has been assoc. with the tags
	 */
	public function hasTag($bean, $tags, $all=false) {
		$foundtags = $this->tag($bean);
		if (is_string($foundtags)) $foundtags = explode(",",$tags);
		$same = array_intersect($tags,$foundtags);
		if ($all) {
			return (implode(",",$same)===implode(",",$tags));
		}
		return (bool) (count($same)>0);
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Removes all sepcified tags from the bean. The tags specified in
	 * the second parameter will no longer be associated with the bean.
	 *
	 * @param  RedBean_OODBBean $bean    tagged bean
	 * @param  array            $tagList list of tags (names)
	 *
	 * @return void
	 */
	public function untag($bean,$tagList) {
		if ($tagList!==false && !is_array($tagList)) $tags = explode( ",", (string)$tagList); else $tags=$tagList;
		foreach($tags as $tag) {
			if ($t = $this->findTagByTitle($tag)) {
				$this->associationManager->unassociate( $bean, $t );
			}
		}
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Tags a bean or returns tags associated with a bean.
	 * If $tagList is null or omitted this method will return a
	 * comma separated list of tags associated with the bean provided.
	 * If $tagList is a comma separated list (string) of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param RedBean_OODBBean $bean    bean
	 * @param mixed				$tagList tags
	 *
	 * @return string $commaSepListTags
	 */
	public function tag( RedBean_OODBBean $bean, $tagList = null ) {
		if (is_null($tagList)) {
			$tags = array();
			$keys = $this->associationManager->related($bean, 'tag'); 
			if ($keys) {
				$tags = $this->redbean->batch('tag',$keys);
			}
			$foundTags = array();
			foreach($tags as $tag) {
				$foundTags[] = $tag->title;
			}
			return $foundTags;
		}
		$this->associationManager->clearRelations( $bean, 'tag' );
		$this->addTags( $bean, $tagList );
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Adds tags to a bean.
	 * If $tagList is a comma separated list of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param RedBean_OODBBean  $bean    bean
	 * @param array				$tagList list of tags to add to bean
	 *
	 * @return void
	 */
	public function addTags( RedBean_OODBBean $bean, $tagList ) {
		if ($tagList!==false && !is_array($tagList)) $tags = explode( ",", (string)$tagList); else $tags=$tagList;
		if ($tagList===false) return;
		foreach($tags as $tag) {
			if (!$t = $this->findTagByTitle($tag)) {
				$t = $this->redbean->dispense('tag');
				$t->title = $tag;
				$this->redbean->store($t);
			}
			$this->associationManager->associate( $bean, $t );
		}
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Returns all beans that have been tagged with one of the tags given.
	 *
	 * @param  $beanType type of bean you are looking for
	 * @param  $tagList  list of tags to match
	 *
	 * @return array
	 */
	public function tagged( $beanType, $tagList ) {
		if ($tagList!==false && !is_array($tagList)) $tags = explode( ",", (string)$tagList); else $tags=$tagList;
		$collection = array();
		$tags = $this->redbean->find('tag',array('title'=>$tags));
		if (count($tags)>0) {
			$collectionKeys = $this->associationManager->related($tags,$beanType);
			if ($collectionKeys) {
				$collection = $this->redbean->batch($beanType,$collectionKeys);
			}
		}
		return $collection;
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Returns all beans that have been tagged with ALL of the tags given.
	 *
	 * @param  $beanType type of bean you are looking for
	 * @param  $tagList  list of tags to match
	 *
	 * @return array
	 */
	public function taggedAll( $beanType, $tagList ) {
		if ($tagList!==false && !is_array($tagList)) $tags = explode( ",", (string)$tagList); else $tags=$tagList;
		$beans = array();
		foreach($tags as $tag) {
			$beans = $this->tagged($beanType,$tag);
			if (isset($oldBeans)) $beans = array_intersect_assoc($beans,$oldBeans);
			$oldBeans = $beans;
		}
		return $beans;
	}

}