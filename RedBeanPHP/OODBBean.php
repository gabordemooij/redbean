<?php

namespace RedBeanPHP;

use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\RedException\Security as Security;

/**
 * OODBBean (Object Oriented DataBase Bean)
 *
 * @file    RedBean/OODBBean.php
 * @desc    The Bean class used for passing information
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class OODBBean implements\IteratorAggregate,\ArrayAccess,\Countable
{

	/**
	 * Whether to skip beautification of columns or not.
	 *
	 * @var boolean
	 */
	private $flagSkipBeau = FALSE;

	/**
	 * This is where the real properties of the bean live. They are stored and retrieved
	 * by the magic getter and setter (__get and __set).
	 *
	 * @var array $properties
	 */
	private $properties = array();

	/**
	 * Here we keep the meta data of a bean.
	 *
	 * @var array
	 */
	private $__info = array();

	/**
	 * The BeanHelper allows the bean to access the toolbox objects to implement
	 * rich functionality, otherwise you would have to do everything with R or
	 * external objects.
	 *
	 * @var BeanHelper
	 */
	private $beanHelper = NULL;

	/**
	 * @var null
	 */
	private $fetchType = NULL;

	/**
	 * @var string
	 */
	private $withSql = '';

	/**
	 * @var array
	 */
	private $withParams = array();

	/**
	 * @var string
	 */
	private $aliasName = NULL;

	/**
	 * @var string
	 */
	private $via = NULL;

	/**
	 * @var boolean
	 */
	private $noLoad = FALSE;

	/**
	 * @var boolean
	 */
	private $all = FALSE;

	/** Returns the alias for a type
	 *
	 * @param string $type type
	 *
	 * @return string $type type
	 */
	private function getAlias( $type )
	{
		if ( $this->fetchType ) {
			$type            = $this->fetchType;
			$this->fetchType = NULL;
		}

		return $type;
	}

	/**
	 * Internal method.
	 * Obtains a shared list for a certain type.
	 *
	 * @param string $type the name of the list you want to retrieve.
	 *
	 * @return array
	 */
	private function getSharedList( $type, $redbean, $toolbox )
	{
		$writer = $toolbox->getWriter();

		if ( $this->via ) {
			$oldName = $writer->getAssocTable( array( $this->__info['type'], $type ) );
			if ( $oldName !== $this->via ) {
				//set the new renaming rule
				$writer->renameAssocTable( $oldName, $this->via );
				$this->via = NULL;
			}
		}

		$type             = $this->beau( $type );

		$assocManager     = $redbean->getAssociationManager();

		$beans            = $assocManager->related( $this, $type, $this->withSql, $this->withParams );

		$this->withSql    = '';
		$this->withParams = array();

		return $beans;
	}

	/**
	 * Internal method.
	 * Obtains the own list of a certain type.
	 *
	 * @param string $type name of the list you want to retrieve
	 *
	 * @return array
	 */
	private function getOwnList( $type, $redbean )
	{
		$type = $this->beau( $type );

		if ( $this->aliasName ) {
			$parentField = $this->aliasName;
			$myFieldLink = $parentField . '_id';

			$this->__info['sys.alias.' . $type] = $this->aliasName;

			$this->aliasName = NULL;
		} else {
			$parentField = $this->__info['type'];
			$myFieldLink = $parentField . '_id';
		}

		$beans = array();

		if ( $this->getID() > 0 ) {

			$firstKey = NULL;
			if ( count( $this->withParams ) > 0 ) {
				reset( $this->withParams );

				$firstKey = key( $this->withParams );
			}

			if ( !is_numeric( $firstKey ) || $firstKey === NULL ) {
				$bindings           = $this->withParams;
				$bindings[':slot0'] = $this->getID();

				$beans = $redbean->find( $type, array(), " $myFieldLink = :slot0 " . $this->withSql, $bindings );
			} else {
				$bindings = array_merge( array( $this->getID() ), $this->withParams );

				$beans = $redbean->find( $type, array(), " $myFieldLink = ? " . $this->withSql, $bindings );
			}
		}

		$this->withSql    = '';
		$this->withParams = array();

		foreach ( $beans as $beanFromList ) {
			$beanFromList->__info['sys.parentcache.' . $parentField] = $this;
		}

		return $beans;
	}

	/**
	 * Sets a meta property for all beans. This is a quicker way to set
	 * the meta properties for a collection of beans because this method
	 * can directly access the property arrays of the beans.
	 * This method returns the beans.
	 *
	 * @param array  $beans    beans to set the meta property of
	 * @param string $property property to set
	 * @param mixed  $value    value
	 *
	 * @return array
	 */
	public static function setMetaAll( $beans, $property, $value )
	{
		foreach( $beans as $bean ) {
			$bean->__info[ $property ] = $value;
		}

		return $beans;
	}

	/**
	 * Initializes a bean. Used by OODB for dispensing beans.
	 * It is not recommended to use this method to initialize beans. Instead
	 * use the OODB object to dispense new beans. You can use this method
	 * if you build your own bean dispensing mechanism.
	 *
	 * @param string             $type       type of the new bean
	 * @param BeanHelper $beanhelper bean helper to obtain a toolbox and a model
	 *
	 * @return void
	 */
	public function initializeForDispense( $type, BeanHelper $beanhelper )
	{
		$this->beanHelper         = $beanhelper;
		$this->__info['type']     = $type;
		$this->__info['sys.id']   = 'id';
		$this->__info['sys.orig'] = array( 'id' => 0 );
		$this->__info['tainted']  = TRUE;
		$this->properties['id']   = 0;
	}

	/**
	 * Sets the Bean Helper. Normally the Bean Helper is set by OODB.
	 * Here you can change the Bean Helper. The Bean Helper is an object
	 * providing access to a toolbox for the bean necessary to retrieve
	 * nested beans (bean lists: ownBean, sharedBean) without the need to
	 * rely on static calls to the facade (or make this class dep. on OODB).
	 *
	 * @param BeanHelper $helper
	 *
	 * @return void
	 */
	public function setBeanHelper( BeanHelper $helper )
	{
		$this->beanHelper = $helper;
	}

	/**
	 * Returns an\ArrayIterator so you can treat the bean like
	 * an array with the properties container as its contents.
	 * This method is meant for PHP and allows you to access beans as if
	 * they were arrays, i.e. using array notation:
	 *
	 * $bean[ $key ] = $value;
	 *
	 * Note that not all PHP functions work with the array interface.
	 *
	 * @return\ArrayIterator
	 */
	public function getIterator()
	{
		return new\ArrayIterator( $this->properties );
	}

	/**
	 * Imports all values from an associative array $array. Chainable.
	 * This method imports the values in the first argument as bean
	 * propery and value pairs. Use the second parameter to provide a
	 * selection. If a selection array is passed, only the entries
	 * having keys mentioned in the selection array will be imported.
	 * Set the third parameter to TRUE to preserve spaces in selection keys.
	 *
	 * @param array        $array     what you want to import
	 * @param string|array $selection selection of values
	 * @param boolean      $notrim    if TRUE selection keys will NOT be trimmed
	 *
	 * @return OODBBean
	 */
	public function import( $array, $selection = FALSE, $notrim = FALSE )
	{
		if ( is_string( $selection ) ) {
			$selection = explode( ',', $selection );
		}

		if ( !$notrim && is_array( $selection ) ) {
			foreach ( $selection as $key => $selected ) {
				$selection[$key] = trim( $selected );
			}
		}

		foreach ( $array as $key => $value ) {
			if ( $key != '__info' ) {
				if ( !$selection || ( $selection && in_array( $key, $selection ) ) ) {
					if ( is_array($value ) ) {
						if ( isset( $value['_type'] ) ) {
							$bean = $this->beanHelper->getToolbox()->getRedBean()->dispense( $value['_type'] );
							unset( $value['_type'] );
							$bean->import($value);
							$this->$key = $bean;
						} else {
							$listBeans = array();
							foreach( $value as $listKey => $listItem ) {
								$bean = $this->beanHelper->getToolbox()->getRedBean()->dispense( $listItem['_type'] );
								unset( $listItem['_type'] );
								$bean->import($listItem);
								$list = &$this->$key;
								$list[ $listKey ] = $bean;
							}
						}
					} else {
						$this->$key = $value;
					}
				}
			}
		}

		return $this;
	}

	/**
	* Fast way to import a row.
	* Does not perform any checks.
	*
	* @param array $row a database row
	*
	* @return self
	*/
	public function importRow( $row )
	{
		$this->properties = $row;
		$this->__info['sys.orig'] = $row;
		return $this;
	}

	/**
	 * Imports data from another bean. Chainable.
	 * Copies the properties from the source bean to the internal
	 * property list.
	 *
	 * @param OODBBean $sourceBean the source bean to take properties from
	 *
	 * @return OODBBean
	 */
	public function importFrom( OODBBean $sourceBean )
	{
		$this->__info['tainted'] = TRUE;

		$this->properties = $sourceBean->properties;

		return $this;
	}

	/**
	 * Injects the properties of another bean but keeps the original ID.
	 * Just like import() but keeps the original ID.
	 * Chainable.
	 *
	 * @param OODBBean $otherBean the bean whose properties you would like to copy
	 *
	 * @return OODBBean
	 */
	public function inject( OODBBean $otherBean )
	{
		$myID = $this->properties['id'];

		$this->import( $otherBean->export() );

		$this->id = $myID;

		return $this;
	}

	/**
	 * Exports the bean as an array.
	 * This function exports the contents of a bean to an array and returns
	 * the resulting array.
	 *
	 * @param boolean $meta    set to TRUE if you want to export meta data as well
	 * @param boolean $parents set to TRUE if you want to export parents as well
	 * @param boolean $onlyMe  set to TRUE if you want to export only this bean
	 * @param array   $filters optional whitelist for export
	 *
	 * @return array
	 */
	public function export( $meta = FALSE, $parents = FALSE, $onlyMe = FALSE, $filters = array() )
	{
		$arr = array();

		if ( $parents ) {
			foreach ( $this as $key => $value ) {
				if ( substr( $key, -3 ) != '_id' ) continue;

				$prop = substr( $key, 0, strlen( $key ) - 3 );
				$this->$prop;
			}
		}

		$hasFilters = is_array( $filters ) && count( $filters );

		foreach ( $this as $key => $value ) {
			if ( !$onlyMe && is_array( $value ) ) {
				$vn = array();

				foreach ( $value as $i => $b ) {
					$vn[] = $b->export( $meta, FALSE, FALSE, $filters );
					$value = $vn;
				}
			} elseif ( $value instanceof OODBBean ) {
				if ( $hasFilters ) {
					if ( !in_array( strtolower( $value->getMeta( 'type' ) ), $filters ) ) continue;
				}

				$value = $value->export( $meta, $parents, FALSE, $filters );
			}

			$arr[$key] = $value;
		}

		if ( $meta ) {
			$arr['__info'] = $this->__info;
		}

		return $arr;
	}

	/**
	 * Implements isset() function for use as an array.
	 *
	 * @param string $property name of the property you want to check
	 *
	 * @return boolean
	 */
	public function __isset( $property )
	{
		$property = $this->beau( $property );

		if ( strpos( $property, 'xown' ) === 0 && ctype_upper( substr( $property, 4, 1 ) ) ) {
			$property = substr($property, 1);
		}
		return isset( $this->properties[$property] );
	}

	/**
	 * Returns the ID of the bean no matter what the ID field is.
	 *
	 * @return string|null
	 */
	public function getID()
	{
		return ( isset( $this->properties['id'] ) ) ? (string) $this->properties['id'] : NULL;
	}

	/**
	 * Unsets a property. This method will load the property first using
	 * __get.
	 *
	 * @param  string $property property
	 *
	 * @return void
	 */
	public function __unset( $property )
	{
		$property = $this->beau( $property );

		if ( strpos( $property, 'xown' ) === 0 && ctype_upper( substr( $property, 4, 1 ) ) ) {
			$property = substr($property, 1);
		}

		unset( $this->properties[$property] );

		$shadowKey = 'sys.shadow.'.$property;
		if ( isset( $this->__info[ $shadowKey ] ) ) unset( $this->__info[$shadowKey] );

		//also clear modifiers
		$this->withSql    = '';
		$this->withParams = array();
		$this->aliasName  = NULL;
		$this->fetchType  = NULL;
		$this->noLoad     = FALSE;
		$this->all        = FALSE;

		return;
	}

	/**
	 * Adds WHERE clause conditions to ownList retrieval.
	 * For instance to get the pages that belong to a book you would
	 * issue the following command: $book->ownPage
	 * However, to order these pages by number use:
	 *
	 * $book->with(' ORDER BY `number` ASC ')->ownPage
	 *
	 * the additional SQL snippet will be merged into the final
	 * query.
	 *
	 * @param string $sql SQL to be added to retrieval query.
	 * @param array       $bindings array with parameters to bind to SQL snippet
	 *
	 * @return OODBBean
	 */
	public function with( $sql, $bindings = array() )
	{
		$this->withSql    = $sql;
		$this->withParams = $bindings;
		return $this;
	}

	/**
	 * Just like with(). Except that this method prepends the SQL query snippet
	 * with AND which makes it slightly more comfortable to use a conditional
	 * SQL snippet. For instance to filter an own-list with pages (belonging to
	 * a book) on specific chapters you can use:
	 *
	 * $book->withCondition(' chapter = 3 ')->ownPage
	 *
	 * This will return in the own list only the pages having 'chapter == 3'.
	 *
	 * @param string $sql      SQL to be added to retrieval query (prefixed by AND)
	 * @param array  $bindings array with parameters to bind to SQL snippet
	 *
	 * @return OODBBean
	 */
	public function withCondition( $sql, $bindings = array() )
	{
		$this->withSql    = ' AND ' . $sql;
		$this->withParams = $bindings;
		return $this;
	}

	/**
	 * When prefix for a list, this causes the list to reload.
	 *
	 * @return self
	 */
	public function all()
	{
		$this->all = TRUE;
		return $this;
	}

	/**
	 * Tells the bean to only access the list but not load
	 * its contents. Use this if you only want to add something to a list
	 * and you have no interest in retrieving its contents from the database.
	 *
	 * @return self
	 */
	public function noLoad()
	{
		$this->noLoad = TRUE;
		return $this;
	}

	/**
	 * Prepares an own-list to use an alias. This is best explained using
	 * an example. Imagine a project and a person. The project always involves
	 * two persons: a teacher and a student. The person beans have been aliased in this
	 * case, so to the project has a teacher_id pointing to a person, and a student_id
	 * also pointing to a person. Given a project, we obtain the teacher like this:
	 *
	 * $project->fetchAs('person')->teacher;
	 *
	 * Now, if we want all projects of a teacher we cant say:
	 *
	 * $teacher->ownProject
	 *
	 * because the $teacher is a bean of type 'person' and no project has been
	 * assigned to a person. Instead we use the alias() method like this:
	 *
	 * $teacher->alias('teacher')->ownProject
	 *
	 * now we get the projects associated with the person bean aliased as
	 * a teacher.
	 *
	 * @param string $aliasName the alias name to use
	 *
	 * @return OODBBean
	 */
	public function alias( $aliasName )
	{
		$this->aliasName = $this->beau( $aliasName );

		return $this;
	}

	/**
	 * Returns properties of bean as an array.
	 * This method returns the raw internal property list of the
	 * bean. Only use this method for optimization purposes. Otherwise
	 * use the export() method to export bean data to arrays.
	 *
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * Returns properties of bean as an array.
	 * This method returns the raw internal property list of the
	 * bean. Only use this method for optimization purposes. Otherwise
	 * use the export() method to export bean data to arrays.
	 * This method returns an array with the properties array and
	 * the type (string).
	 *
	 * @return array
	 */
	public function getPropertiesAndType()
	{
		return array( $this->properties, $this->__info['type'] );
	}

	/**
	 * Turns a camelcase property name into an underscored property name.
	 * Examples:
	 *    oneACLRoute -> one_acl_route
	 *    camelCase -> camel_case
	 *
	 * Also caches the result to improve performance.
	 *
	 * @param string $property
	 *
	 * @return string
	 */
	public function beau( $property )
	{
		static $beautifulColumns = array();

		if ( ctype_lower( $property ) ) return $property;

		if (
			strpos( $property, 'own' ) === 0
			|| strpos( $property, 'xown' ) === 0
			|| strpos( $property, 'shared' ) === 0
		) {

			$property = preg_replace( '/List$/', '', $property );
			return $property;
		}

		if ( !isset( $beautifulColumns[$property] ) ) {
			$beautifulColumns[$property] = AQueryWriter::camelsSnake( $property );
		}

		return $beautifulColumns[$property];
	}

	/**
	 * Returns current status of modification flags.
	 *
	 * @return string
	 */
	public function getModFlags()
	{
		$modFlags = '';
		if ($this->aliasName !== NULL) $modFlags .= 'a';
		if ($this->fetchType !== NULL) $modFlags .= 'f';
		if ($this->noLoad === TRUE) $modFlags .= 'n';
		if ($this->all === TRUE) $modFlags .= 'r';
		if ($this->withSql !== '') $modFlags .= 'w';

		return $modFlags;
	}

	/**
	 * Clears all modifiers.
	 *
	 * @return self
	 */
	public function clearModifiers()
	{
		$this->withSql    = '';
		$this->withParams = array();
		$this->aliasName  = NULL;
		$this->fetchType  = NULL;
		$this->noLoad     = FALSE;
		$this->all        = FALSE;
		return $this;
	}

	/**
	 * Determines whether a list is opened in exclusive mode or not.
	 * If a list has been opened in exclusive mode this method will return TRUE,
	 * othwerwise it will return FALSE.
	 *
	 * @param string $listName name of the list to check
	 *
	 * @return boolean
	 */
	public function isListInExclusiveMode( $listName )
	{
		$listName = $this->beau( $listName );

		if ( strpos( $listName, 'xown' ) === 0 && ctype_upper( substr( $listName, 4, 1 ) ) ) {
			$listName = substr($listName, 1);
		}

		$listName = lcfirst( substr( $listName, 3 ) );

		return ( isset( $this->__info['sys.exclusive-'.$listName] ) && $this->__info['sys.exclusive-'.$listName] );
	}

	/**
	 * Magic Getter. Gets the value for a specific property in the bean.
	 * If the property does not exist this getter will make sure no error
	 * occurs. This is because RedBean allows you to query (probe) for
	 * properties. If the property can not be found this method will
	 * return NULL instead.
	 *
	 * @param string $property name of the property you wish to obtain the value of
	 *
	 * @return mixed
	 */
	public function &__get( $property )
	{
		$isEx          = FALSE;
		$isOwn         = FALSE;
		$isShared      = FALSE;

		if ( !ctype_lower( $property ) ) {
			$property = $this->beau( $property );
			if ( strpos( $property, 'xown' ) === 0 && ctype_upper( substr( $property, 4, 1 ) ) ) {
				$property = substr($property, 1);
				$listName = lcfirst( substr( $property, 3 ) );
				$isEx     = TRUE;
				$isOwn    = TRUE;
				$this->__info['sys.exclusive-'.$listName] = TRUE;
			} elseif ( strpos( $property, 'own' ) === 0 && ctype_upper( substr( $property, 3, 1 ) ) )  {
				$isOwn    = TRUE;
				$listName = lcfirst( substr( $property, 3 ) );
			} elseif ( strpos( $property, 'shared' ) === 0 && ctype_upper( substr( $property, 6, 1 ) ) ) {
				$isShared = TRUE;
			}
		}

		$fieldLink      = $property . '_id';
		$exists         = isset( $this->properties[$property] );

		//If not exists and no field link and no list, bail out.
		if ( !$exists && !isset($this->$fieldLink) && (!$isOwn && !$isShared )) {

			$this->withSql    = '';
			$this->withParams = array();
			$this->aliasName  = NULL;
			$this->fetchType  = NULL;
			$this->noLoad     = FALSE;
			$this->all        = FALSE;

			$NULL = NULL;
			return $NULL;
		}

		$hasAlias       = (!is_null($this->aliasName));
		$differentAlias = ($hasAlias && $isOwn && isset($this->__info['sys.alias.'.$listName])) ?
								($this->__info['sys.alias.'.$listName] !== $this->aliasName) : FALSE;
		$hasSQL         = ($this->withSql !== '' || $this->via !== NULL);
		$hasAll         = (boolean) ($this->all);

		//If exists and no list or exits and list not changed, bail out.
		if ( $exists && ((!$isOwn && !$isShared ) ||  (!$hasSQL && !$differentAlias && !$hasAll)) ) {

			$this->withSql    = '';
			$this->withParams = array();
			$this->aliasName  = NULL;
			$this->fetchType  = NULL;
			$this->noLoad     = FALSE;
			$this->all        = FALSE;

			return $this->properties[$property];
		}


		list( $redbean, , , $toolbox ) = $this->beanHelper->getExtractedToolbox();

		if ( isset( $this->$fieldLink ) ) {
			$this->__info['tainted'] = TRUE;

			if ( isset( $this->__info["sys.parentcache.$property"] ) ) {
				$bean = $this->__info["sys.parentcache.$property"];
			} else {
				$type = $this->getAlias( $property );
				$bean = $redbean->load( $type, $this->properties[$fieldLink] );
			}

			$this->properties[$property] = $bean;

			$this->withSql    = '';
			$this->withParams = array();
			$this->aliasName  = NULL;
			$this->fetchType  = NULL;
			$this->noLoad     = FALSE;
			$this->all        = FALSE;

			return $this->properties[$property];

		}
		//Implicit: elseif ( $isOwn || $isShared ) {
		if ( $this->noLoad ) {
			$beans = array();
		} elseif ( $isOwn ) {
			$beans = $this->getOwnList( $listName, $redbean );
		} else {
			$beans = $this->getSharedList( lcfirst( substr( $property, 6 ) ), $redbean, $toolbox );
		}

		$this->properties[$property] = $beans;
		$this->__info["sys.shadow.$property"] = $beans;
		$this->__info['tainted']              = TRUE;

		$this->withSql    = '';
		$this->withParams = array();
		$this->aliasName  = NULL;
		$this->fetchType  = NULL;
		$this->noLoad     = FALSE;
		$this->all        = FALSE;

		return $this->properties[$property];
	}

	/**
	 * Magic Setter. Sets the value for a specific property.
	 * This setter acts as a hook for OODB to mark beans as tainted.
	 * The tainted meta property can be retrieved using getMeta("tainted").
	 * The tainted meta property indicates whether a bean has been modified and
	 * can be used in various caching mechanisms.
	 *
	 * @param string $property name of the property you wish to assign a value to
	 * @param  mixed $value    the value you want to assign
	 *
	 * @return void
	 *
	 * @throws Security
	 */
	public function __set( $property, $value )
	{
		$isEx          = FALSE;
		$isOwn         = FALSE;
		$isShared      = FALSE;

		if ( !ctype_lower( $property ) ) {
			$property = $this->beau( $property );
			if ( strpos( $property, 'xown' ) === 0 && ctype_upper( substr( $property, 4, 1 ) ) ) {
				$property = substr($property, 1);
				$listName = lcfirst( substr( $property, 3 ) );
				$isEx     = TRUE;
				$isOwn    = TRUE;
				$this->__info['sys.exclusive-'.$listName] = TRUE;
			} elseif ( strpos( $property, 'own' ) === 0 && ctype_upper( substr( $property, 3, 1 ) ) )  {
				$isOwn    = TRUE;
				$listName = lcfirst( substr( $property, 3 ) );
			} elseif ( strpos( $property, 'shared' ) === 0 && ctype_upper( substr( $property, 6, 1 ) ) ) {
				$isShared = TRUE;
			}
		}

		$hasAlias       = (!is_null($this->aliasName));
		$differentAlias = ($hasAlias && $isOwn && isset($this->__info['sys.alias.'.$listName])) ?
								($this->__info['sys.alias.'.$listName] !== $this->aliasName) : FALSE;
		$hasSQL         = ($this->withSql !== '' || $this->via !== NULL);
		$exists         = isset( $this->properties[$property] );
		$fieldLink      = $property . '_id';

		if ( ($isOwn || $isShared) &&  (!$exists || $hasSQL || $differentAlias) ) {

			if ( !$this->noLoad ) {
				list( $redbean, , , $toolbox ) = $this->beanHelper->getExtractedToolbox();
				if ( $isOwn ) {
					$beans = $this->getOwnList( $listName, $redbean );
				} else {
					$beans = $this->getSharedList( lcfirst( substr( $property, 6 ) ), $redbean, $toolbox );
				}
				$this->__info["sys.shadow.$property"] = $beans;
			}
		}

		$this->withSql    = '';
		$this->withParams = array();
		$this->aliasName  = NULL;
		$this->fetchType  = NULL;
		$this->noLoad     = FALSE;
		$this->all        = FALSE;

		$this->__info['tainted'] = TRUE;

		if ( isset( $this->properties[$fieldLink] ) && !( $value instanceof OODBBean ) ) {
			if ( is_null( $value ) || $value === FALSE ) {

				unset( $this->properties[ $property ]);
				$this->properties[ $fieldLink ] = NULL;

				return;
			} else {
				throw new RedException( 'Cannot cast to bean.' );
			}
		}

		if ( $value === FALSE ) {
			$value = '0';
		} elseif ( $value === TRUE ) {
			$value = '1';
		} elseif ( $value instanceof \DateTime ) {
			$value = $value->format( 'Y-m-d H:i:s' );
		}

		$this->properties[$property] = $value;
	}

	/**
	 * Sets a property directly, for internal use only.
	 *
	 * @param string  $property     property
	 * @param mixed   $value        value
	 * @param boolean $updateShadow whether you want to update the shadow
	 * @param boolean $taint        whether you want to mark the bean as tainted
	 *
	 * @return void
	 */
	public function setProperty( $property, $value, $updateShadow = FALSE, $taint = FALSE )
	{
		$this->properties[$property] = $value;

		if ( $updateShadow ) {
			$this->__info['sys.shadow.' . $property] = $value;
		}

		if ( $taint ) {
			$this->__info['tainted'] = TRUE;
		}
	}

	/**
	 * Returns the value of a meta property. A meta property
	 * contains extra information about the bean object that will not
	 * get stored in the database. Meta information is used to instruct
	 * RedBean as well as other systems how to deal with the bean.
	 * For instance: $bean->setMeta("buildcommand.unique", array(
	 * array("column1", "column2", "column3") ) );
	 * Will add a UNIQUE constraint for the bean on columns: column1, column2 and
	 * column 3.
	 * To access a Meta property we use a dot separated notation.
	 * If the property cannot be found this getter will return NULL instead.
	 *
	 * @param string $path    path
	 * @param mixed  $default default value
	 *
	 * @return mixed
	 */
	public function getMeta( $path, $default = NULL )
	{
		return ( isset( $this->__info[$path] ) ) ? $this->__info[$path] : $default;
	}

	/**
	 * Stores a value in the specified Meta information property. $value contains
	 * the value you want to store in the Meta section of the bean and $path
	 * specifies the dot separated path to the property. For instance "my.meta.property".
	 * If "my" and "meta" do not exist they will be created automatically.
	 *
	 * @param string $path  path
	 * @param mixed  $value value
	 *
	 * @return OODBBean
	 */
	public function setMeta( $path, $value )
	{
		$this->__info[$path] = $value;

		return $this;
	}

	/**
	 * Copies the meta information of the specified bean
	 * This is a convenience method to enable you to
	 * exchange meta information easily.
	 *
	 * @param OODBBean $bean
	 *
	 * @return OODBBean
	 */
	public function copyMetaFrom( OODBBean $bean )
	{
		$this->__info = $bean->__info;

		return $this;
	}

	/**
	 * Sends the call to the registered model.
	 *
	 * @param string $method name of the method
	 * @param array  $args   argument list
	 *
	 * @return mixed
	 */
	public function __call( $method, $args )
	{
		if ( !isset( $this->__info['model'] ) ) {
			$model = $this->beanHelper->getModelForBean( $this );

			if ( !$model ) {
				return NULL;
			}

			$this->__info['model'] = $model;
		}
		if ( !method_exists( $this->__info['model'], $method ) ) {
			return NULL;
		}

		return call_user_func_array( array( $this->__info['model'], $method ), $args );
	}

	/**
	 * Implementation of __toString Method
	 * Routes call to Model. If the model implements a __toString() method this
	 * method will be called and the result will be returned. In case of an
	 * echo-statement this result will be printed. If the model does not
	 * implement a __toString method, this method will return a JSON
	 * representation of the current bean.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$string = $this->__call( '__toString', array() );

		if ( $string === NULL ) {
			return json_encode( $this->properties );
		} else {
			return $string;
		}
	}

	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 * Call gets routed to __set.
	 *
	 * @param  mixed $offset offset string
	 * @param  mixed $value  value
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value )
	{
		$this->__set( $offset, $value );
	}

	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 *
	 * Array functions do not reveal x-own-lists and list-alias because
	 * you dont want duplicate entries in foreach-loops.
	 * Also offers a slight performance improvement for array access.
	 *
	 * @param  mixed $offset property
	 *
	 * @return boolean
	 */
	public function offsetExists( $offset )
	{
		return $this->__isset( $offset );
	}

	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 * Unsets a value from the array/bean.
	 *
	 * Array functions do not reveal x-own-lists and list-alias because
	 * you dont want duplicate entries in foreach-loops.
	 * Also offers a slight performance improvement for array access.
	 *
	 * @param  mixed $offset property
	 *
	 * @return void
	 */
	public function offsetUnset( $offset )
	{
		$this->__unset( $offset );
	}

	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 * Returns value of a property.
	 *
	 * Array functions do not reveal x-own-lists and list-alias because
	 * you dont want duplicate entries in foreach-loops.
	 * Also offers a slight performance improvement for array access.
	 *
	 * @param  mixed $offset property
	 *
	 * @return mixed
	 */
	public function &offsetGet( $offset )
	{
		return $this->__get( $offset );
	}

	/**
	 * Chainable method to cast a certain ID to a bean; for instance:
	 * $person = $club->fetchAs('person')->member;
	 * This will load a bean of type person using member_id as ID.
	 *
	 * @param  string $type preferred fetch type
	 *
	 * @return OODBBean
	 */
	public function fetchAs( $type )
	{
		$this->fetchType = $type;

		return $this;
	}

	/**
	 * For polymorphic bean relations.
	 * Same as fetchAs but uses a column instead of a direct value.
	 *
	 * @param string $column
	 *
	 * @return OODBBean
	 */
	public function poly( $field )
	{
		return $this->fetchAs( $this->$field );
	}

	/**
	 * Traverses a bean property with the specified function.
	 * Recursively iterates through the property invoking the
	 * function for each bean along the way passing the bean to it.
	 *
	 * Can be used together with with, withCondition, alias and fetchAs.
	 *
	 * @param string  $property property
	 * @param closure $function function
	 *
	 * @return OODBBean
	 */
	public function traverse( $property, $function, $maxDepth = NULL )
	{

		if ( strpos( $property, 'shared' ) !== FALSE ) {
			throw new RedException( 'Traverse only works with (x)own-lists.' );
		}

		if ( !is_null( $maxDepth ) ) {
			if ( !$maxDepth-- ) return $this;
		}

		$oldFetchType = $this->fetchType;
		$oldAliasName = $this->aliasName;
		$oldWith      = $this->withSql;
		$oldBindings  = $this->withParams;

		$beans = $this->$property;

		if ( $beans === NULL ) return $this;

		if ( !is_array( $beans ) ) $beans = array( $beans );

		foreach( $beans as $bean ) {

			$function( $bean );

			$bean->fetchType  = $oldFetchType;
			$bean->aliasName  = $oldAliasName;
			$bean->withSql    = $oldWith;
			$bean->withParams = $oldBindings;

			$bean->traverse( $property, $function, $maxDepth );
		}

		return $this;
	}

	/**
	 * Implementation of\Countable interface. Makes it possible to use
	 * count() function on a bean.
	 *
	 * @return integer
	 */
	public function count()
	{
		return count( $this->properties );
	}

	/**
	 * Checks whether a bean is empty or not.
	 * A bean is empty if it has no other properties than the id field OR
	 * if all the other property are empty().
	 *
	 * @return boolean
	 */
	public function isEmpty()
	{
		$empty = TRUE;
		foreach ( $this->properties as $key => $value ) {
			if ( $key == 'id' ) {
				continue;
			}
			if ( !empty( $value ) ) {
				$empty = FALSE;
			}
		}

		return $empty;
	}

	/**
	 * Chainable setter.
	 *
	 * @param string $property the property of the bean
	 * @param mixed  $value    the value you want to set
	 *
	 * @return OODBBean
	 */
	public function setAttr( $property, $value )
	{
		$this->$property = $value;

		return $this;
	}

	/**
	 * Comfort method.
	 * Unsets all properties in array.
	 *
	 * @param array $properties properties you want to unset.
	 *
	 * @return OODBBean
	 */
	public function unsetAll( $properties )
	{
		foreach ( $properties as $prop ) {
			if ( isset( $this->properties[$prop] ) ) {
				unset( $this->properties[$prop] );
			}
		}

		return $this;
	}

	/**
	 * Returns original (old) value of a property.
	 * You can use this method to see what has changed in a
	 * bean.
	 *
	 * @param string $property name of the property you want the old value of
	 *
	 * @return mixed
	 */
	public function old( $property )
	{
		$old = $this->getMeta( 'sys.orig', array() );

		if ( array_key_exists( $property, $old ) ) {
			return $old[$property];
		}

		return NULL;
	}

	/**
	 * Convenience method.
	 * Returns TRUE if the bean has been changed, or FALSE otherwise.
	 * Same as $bean->getMeta('tainted');
	 * Note that a bean becomes tainted as soon as you retrieve a list from
	 * the bean. This is because the bean lists are arrays and the bean cannot
	 * determine whether you have made modifications to a list so RedBeanPHP
	 * will mark the whole bean as tainted.
	 *
	 * @return boolean
	 */
	public function isTainted()
	{
		return $this->getMeta( 'tainted' );
	}

	/**
	 * Returns TRUE if the value of a certain property of the bean has been changed and
	 * FALSE otherwise.
	 *
	 * @param string $property name of the property you want the change-status of
	 *
	 * @return boolean
	 */
	public function hasChanged( $property )
	{
		return ( array_key_exists( $property, $this->properties ) ) ?
			$this->old( $property ) != $this->properties[$property] : FALSE;
	}

	/**
	 * Creates a N-M relation by linking an intermediate bean.
	 * This method can be used to quickly connect beans using indirect
	 * relations. For instance, given an album and a song you can connect the two
	 * using a track with a number like this:
	 *
	 * Usage:
	 *
	 * $album->link('track', array('number'=>1))->song = $song;
	 *
	 * or:
	 *
	 * $album->link($trackBean)->song = $song;
	 *
	 * What this method does is adding the link bean to the own-list, in this case
	 * ownTrack. If the first argument is a string and the second is an array or
	 * a JSON string then the linking bean gets dispensed on-the-fly as seen in
	 * example #1. After preparing the linking bean, the bean is returned thus
	 * allowing the chained setter: ->song = $song.
	 *
	 * @param string|OODBBean $type          type of bean to dispense or the full bean
	 * @param string|array            $qualification JSON string or array (optional)
	 *
	 * @return OODBBean
	 */
	public function link( $typeOrBean, $qualification = array() )
	{
		if ( is_string( $typeOrBean ) ) {

			$typeOrBean = AQueryWriter::camelsSnake( $typeOrBean );

			$bean = $this->beanHelper->getToolBox()->getRedBean()->dispense( $typeOrBean );

			if ( is_string( $qualification ) ) {
				$data = json_decode( $qualification, TRUE );
			} else {
				$data = $qualification;
			}

			foreach ( $data as $key => $value ) {
				$bean->$key = $value;
			}
		} else {
			$bean = $typeOrBean;
		}

		$list = 'own' . ucfirst( $bean->getMeta( 'type' ) );

		array_push( $this->$list, $bean );

		return $bean;
	}

	/**
	 * Returns the same bean freshly loaded from the database.
	 *
	 * @return OODBBean
	 */
	public function fresh()
	{
		return $this->beanHelper->getToolbox()->getRedBean()->load( $this->getMeta( 'type' ), $this->properties['id'] );
	}

	/**
	 * Registers a association renaming globally.
	 *
	 * @param string $via type you wish to use for shared lists
	 *
	 * @return OODBBean
	 */
	public function via( $via )
	{
		$this->via = $via;

		return $this;
	}

	/**
	 * Counts all own beans of type $type.
	 * Also works with alias(), with() and withCondition().
	 *
	 * @param string $type the type of bean you want to count
	 *
	 * @return integer
	 */
	public function countOwn( $type )
	{
		$type = $this->beau( $type );

		if ( $this->aliasName ) {
			$myFieldLink     = $this->aliasName . '_id';

			$this->aliasName = NULL;
		} else {
			$myFieldLink = $this->__info['type'] . '_id';
		}

		$count = 0;

		if ( $this->getID() !== 0 ) {

			$firstKey = NULL;
			if ( count( $this->withParams ) > 0 ) {
				reset( $this->withParams );
				$firstKey = key( $this->withParams );
			}

			if ( !is_numeric( $firstKey ) || $firstKey === NULL ) {
					$bindings           = $this->withParams;
					$bindings[':slot0'] = $this->getID();
					$count              = $this->beanHelper->getToolbox()->getWriter()->queryRecordCount( $type, array(), " $myFieldLink = :slot0 " . $this->withSql, $bindings );
			} else {
					$bindings = array_merge( array( $this->getID() ), $this->withParams );
					$count    = $this->beanHelper->getToolbox()->getWriter()->queryRecordCount( $type, array(), " $myFieldLink = ? " . $this->withSql, $bindings );
			}

		}

		$this->clearModifiers();
		return (int) $count;
	}

	/**
	 * Counts all shared beans of type $type.
	 * Also works with via(), with() and withCondition().
	 *
	 * @param string $type type of bean you wish to count
	 *
	 * @return integer
	 */
	public function countShared( $type )
	{
		$toolbox = $this->beanHelper->getToolbox();
		$redbean = $toolbox->getRedBean();
		$writer  = $toolbox->getWriter();

		if ( $this->via ) {
			$oldName = $writer->getAssocTable( array( $this->__info['type'], $type ) );

			if ( $oldName !== $this->via ) {
				//set the new renaming rule
				$writer->renameAssocTable( $oldName, $this->via );
				$this->via = NULL;
			}
		}

		$type  = $this->beau( $type );
		$count = 0;

		if ( $this->getID() > 0 ) {
			$count = $redbean->getAssociationManager()->relatedCount( $this, $type, $this->withSql, $this->withParams, TRUE );
		}

		$this->clearModifiers();
		return (integer) $count;
	}

	/**
	 * Tests whether the database identities of two beans are equal.
	 *
	 * @param OODBBean $bean other bean
	 *
	 * @return boolean
	 */
	public function equals(OODBBean $bean)
	{
		return (bool) (
			   ( (string) $this->properties['id'] === (string) $bean->properties['id'] )
			&& ( (string) $this->__info['type']   === (string) $bean->__info['type']   )
		);
	}
}
