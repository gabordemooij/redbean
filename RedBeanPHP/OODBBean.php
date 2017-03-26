<?php

namespace RedBeanPHP;

use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\RedException as RedException;

/* PHP 5.3 compatibility */
if (interface_exists('\JsonSerializable')) {
		/* We extend JsonSerializable to avoid namespace conflicts,
		can't define interface with special namespace in PHP */
		interface Jsonable extends \JsonSerializable {};
} else {
	interface Jsonable {};
}

/**
 * OODBBean (Object Oriented DataBase Bean).
 *
 * to exchange information with the database. A bean represents
 * a single table row and offers generic services for interaction
 * with databases systems as well as some meta-data.
 *
 * @file    RedBeanPHP/OODBBean.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 * @desc    OODBBean represents a bean. RedBeanPHP uses beans
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class OODBBean implements\IteratorAggregate,\ArrayAccess,\Countable,Jsonable
{
	/**
	 * FUSE error modes.
	 */
	const C_ERR_IGNORE    = FALSE;
	const C_ERR_LOG       = 1;
	const C_ERR_NOTICE    = 2;
	const C_ERR_WARN      = 3;
	const C_ERR_EXCEPTION = 4;
	const C_ERR_FUNC      = 5;
	const C_ERR_FATAL     = 6;

	/**
	 * @var boolean
	 */
	protected static $errorHandlingFUSE = FALSE;

	/**
	 * @var callable|NULL
	 */
	protected static $errorHandler = NULL;

	/**
	 * @var array
	 */
	protected static $aliases = array();

	/**
	 * @var boolean
	 */
	protected static $autoResolve = FALSE;

	/**
	 * This is where the real properties of the bean live. They are stored and retrieved
	 * by the magic getter and setter (__get and __set).
	 *
	 * @var array $properties
	 */
	protected $properties = array();

	/**
	 * Here we keep the meta data of a bean.
	 *
	 * @var array
	 */
	protected $__info = array();

	/**
	 * The BeanHelper allows the bean to access the toolbox objects to implement
	 * rich functionality, otherwise you would have to do everything with R or
	 * external objects.
	 *
	 * @var BeanHelper
	 */
	protected $beanHelper = NULL;

	/**
	 * @var null
	 */
	protected $fetchType = NULL;

	/**
	 * @var string
	 */
	protected $withSql = '';

	/**
	 * @var array
	 */
	protected $withParams = array();

	/**
	 * @var string
	 */
	protected $aliasName = NULL;

	/**
	 * @var string
	 */
	protected $via = NULL;

	/**
	 * @var boolean
	 */
	protected $noLoad = FALSE;

	/**
	 * @var boolean
	 */
	protected $all = FALSE;

	/**
	 * Sets the error mode for FUSE.
	 * What to do if a FUSE model method does not exist?
	 * You can set the following options:
	 *
	 * * OODBBean::C_ERR_IGNORE (default), ignores the call, returns NULL
	 * * OODBBean::C_ERR_LOG, logs the incident using error_log
	 * * OODBBean::C_ERR_NOTICE, triggers a E_USER_NOTICE
	 * * OODBBean::C_ERR_WARN, triggers a E_USER_WARNING
	 * * OODBBean::C_ERR_EXCEPTION, throws an exception
	 * * OODBBean::C_ERR_FUNC, allows you to specify a custom handler (function)
	 * * OODBBean::C_ERR_FATAL, triggers a E_USER_ERROR
	 *
	 * <code>
	 * Custom handler method signature: handler( array (
	 * 	'message' => string
	 * 	'bean' => OODBBean
	 * 	'method' => string
	 * ) )
	 * </code>
	 *
	 * This method returns the old mode and handler as an array.
	 *
	 * @param integer       $mode error handling mode
	 * @param callable|NULL $func custom handler
	 *
	 * @return array
	 */
	public static function setErrorHandlingFUSE($mode, $func = NULL) {
		if (
			   $mode !== self::C_ERR_IGNORE
			&& $mode !== self::C_ERR_LOG
			&& $mode !== self::C_ERR_NOTICE
			&& $mode !== self::C_ERR_WARN
			&& $mode !== self::C_ERR_EXCEPTION
			&& $mode !== self::C_ERR_FUNC
			&& $mode !== self::C_ERR_FATAL
		) throw new \Exception( 'Invalid error mode selected' );

		if ( $mode === self::C_ERR_FUNC && !is_callable( $func ) ) {
			throw new \Exception( 'Invalid error handler' );
		}

		$old = array( self::$errorHandlingFUSE, self::$errorHandler );
		self::$errorHandlingFUSE = $mode;
		if ( is_callable( $func ) ) {
			self::$errorHandler = $func;
		} else {
			self::$errorHandler = NULL;
		}
		return $old;
	}

	/**
	 * Sets global aliases.
	 * Registers a batch of aliases in one go. This works the same as
	 * fetchAs and setAutoResolve but explicitly. For instance if you register
	 * the alias 'cover' for 'page' a property containing a reference to a
	 * page bean called 'cover' will correctly return the page bean and not
	 * a (non-existant) cover bean.
	 *
	 * <code>
	 * R::aliases( array( 'cover' => 'page' ) );
	 * $book = R::dispense( 'book' );
	 * $page = R::dispense( 'page' );
	 * $book->cover = $page;
	 * R::store( $book );
	 * $book = $book->fresh();
	 * $cover = $book->cover;
	 * echo $cover->getMeta( 'type' ); //page
	 * </code>
	 *
	 * The format of the aliases registration array is:
	 *
	 * {alias} => {actual type}
	 *
	 * In the example above we use:
	 *
	 * cover => page
	 *
	 * From that point on, every bean reference to a cover
	 * will return a 'page' bean. Note that with autoResolve this
	 * feature along with fetchAs() is no longer very important, although
	 * relying on explicit aliases can be a bit faster.
	 *
	 * @param array $list list of global aliases to use
	 *
	 * @return void
	 */
	public static function aliases( $list )
	{
		self::$aliases = $list;
	}

	/**
	 * Enables or disables auto-resolving fetch types.
	 * Auto-resolving aliased parent beans is convenient but can
	 * be slower and can create infinite recursion if you
	 * used aliases to break cyclic relations in your domain.
	 *
	 * @param boolean $automatic TRUE to enable automatic resolving aliased parents
	 *
	 * @return void
	 */
	public static function setAutoResolve( $automatic = TRUE )
	{
		self::$autoResolve = (boolean) $automatic;
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
			if ( $bean instanceof OODBBean ) $bean->__info[ $property ] = $value;
		}

		return $beans;
	}

	/**
	 * Parses the join in the with-snippet.
	 * For instance:
	 *
	 * <code>
	 * $author
	 * 	->withCondition(' @joined.detail.title LIKE ? ')
	 *  ->ownBookList;
	 * </code>
	 *
	 * will automatically join 'detail' on book to
	 * access the title field.
	 *
	 * @note this feature requires Narrow Field Mode and Join Feature
	 * to be both activated (default).
	 *
	 * @param string $type the source type for the join
	 *
	 * @return string
	 */
	private function parseJoin( $type )
	{
		$joinSql = '';
		$joins = array();
		if ( strpos($this->withSql, '@joined.' ) !== FALSE ) {
			$writer   = $this->beanHelper->getToolBox()->getWriter();
			$oldParts = $parts = explode( '@joined.', $this->withSql );
			array_shift( $parts );
			foreach($parts as $part) {
				$explosion = explode( '.', $part );
				$joinInfo  = array_shift( $explosion );
				//Dont join more than once..
				if ( !isset( $joins[$joinInfo] ) ) {
					$joins[ $joinInfo ] = true;
					$joinSql  .= $writer->writeJoin( $type, $joinInfo, 'LEFT' );
				}
			}
			$this->withSql = implode( '', $oldParts );
			$joinSql      .= ' WHERE ';
		}
		return $joinSql;
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
			}
			$this->via = NULL;
		}

		$beans = array();
		if ($this->getID()) {
			$type             = $this->beau( $type );
			$assocManager     = $redbean->getAssociationManager();
			$beans            = $assocManager->related( $this, $type, $this->withSql, $this->withParams );
		}

		$this->withSql    = '';
		$this->withParams = array();

		return $beans;
	}

	/**
	 * Internal method.
	 * Obtains the own list of a certain type.
	 *
	 * @param string      $type   name of the list you want to retrieve
	 * @param OODB        $oodb   The RB OODB object database instance
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

		if ( $this->getID() ) {

			$firstKey = NULL;
			if ( count( $this->withParams ) > 0 ) {
				reset( $this->withParams );

				$firstKey = key( $this->withParams );
			}

			$joinSql = $this->parseJoin( $type );

			if ( !is_numeric( $firstKey ) || $firstKey === NULL ) {
				$bindings           = $this->withParams;
				$bindings[':slot0'] = $this->getID();

				$beans = $redbean->find( $type, array(), " {$joinSql} $myFieldLink = :slot0 " . $this->withSql, $bindings );
			} else {
				$bindings = array_merge( array( $this->getID() ), $this->withParams );

				$beans = $redbean->find( $type, array(), " {$joinSql} $myFieldLink = ? " . $this->withSql, $bindings );
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
	 * Initializes a bean. Used by OODB for dispensing beans.
	 * It is not recommended to use this method to initialize beans. Instead
	 * use the OODB object to dispense new beans. You can use this method
	 * if you build your own bean dispensing mechanism.
	 *
	 * @param string     $type       type of the new bean
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
		$this->__info['changed']  = TRUE;
		$this->__info['changelist'] = array();
		$this->properties['id']   = 0;
	}

	/**
	 * Sets the Bean Helper. Normally the Bean Helper is set by OODB.
	 * Here you can change the Bean Helper. The Bean Helper is an object
	 * providing access to a toolbox for the bean necessary to retrieve
	 * nested beans (bean lists: ownBean, sharedBean) without the need to
	 * rely on static calls to the facade (or make this class dep. on OODB).
	 *
	 * @param BeanHelper $helper helper to use for this bean
	 *
	 * @return void
	 */
	public function setBeanHelper( BeanHelper $helper )
	{
		$this->beanHelper = $helper;
	}

	/**
	 * Returns an ArrayIterator so you can treat the bean like
	 * an array with the properties container as its contents.
	 * This method is meant for PHP and allows you to access beans as if
	 * they were arrays, i.e. using array notation:
	 *
	 * $bean[$key] = $value;
	 *
	 * Note that not all PHP functions work with the array interface.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator( $this->properties );
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
		$this->__info['changed'] = FALSE;
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
		$this->__info['changed'] = TRUE;
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

		$this->import( $otherBean->export( FALSE, FALSE, TRUE ) );

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
					if ( !( $b instanceof OODBBean ) ) continue;
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
	 * Unsets a property of a bean.
	 * Magic method, gets called implicitly when performing the unset() operation
	 * on a bean property.
	 *
	 * @param  string $property property to unset
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
		$this->via        = NULL;

		return;
	}

	/**
	 * Adds WHERE clause conditions to ownList retrieval.
	 * For instance to get the pages that belong to a book you would
	 * issue the following command: $book->ownPage
	 * However, to order these pages by number use:
	 *
	 * <code>
	 * $book->with(' ORDER BY `number` ASC ')->ownPage
	 * </code>
	 *
	 * the additional SQL snippet will be merged into the final
	 * query.
	 *
	 * @param string $sql      SQL to be added to retrieval query.
	 * @param array  $bindings array with parameters to bind to SQL snippet
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
	 * Tells the bean to (re)load the following list without any
	 * conditions. If you have an ownList or sharedList with a
	 * condition you can use this method to reload the entire list.
	 *
	 * Usage:
	 *
	 * <code>
	 * $bean->with( ' LIMIT 3 ' )->ownPage; //Just 3
	 * $bean->all()->ownPage; //Reload all pages
	 * </code>
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
	 * <code>
	 * $project->fetchAs('person')->teacher;
	 * </code>
	 *
	 * Now, if we want all projects of a teacher we cant say:
	 *
	 * <code>
	 * $teacher->ownProject
	 * </code>
	 *
	 * because the $teacher is a bean of type 'person' and no project has been
	 * assigned to a person. Instead we use the alias() method like this:
	 *
	 * <code>
	 * $teacher->alias('teacher')->ownProject
	 * </code>
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
	 *
	 * Examples:
	 *
	 * * oneACLRoute -> one_acl_route
	 * * camelCase -> camel_case
	 *
	 * Also caches the result to improve performance.
	 *
	 * @param string $property property to un-beautify
	 *
	 * @return string
	 */
	public function beau( $property )
	{
		static $beautifulColumns = array();

		if ( ctype_lower( $property ) ) return $property;

		if (
			( strpos( $property, 'own' ) === 0 && ctype_upper( substr( $property, 3, 1 ) ) )
			|| ( strpos( $property, 'xown' ) === 0 && ctype_upper( substr( $property, 4, 1 ) ) )
			|| ( strpos( $property, 'shared' ) === 0 && ctype_upper( substr( $property, 6, 1 ) ) )
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
		$this->via        = NULL;
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
			$this->via        = NULL;

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
			$this->via        = NULL;
			return $this->properties[$property];
		}

		list( $redbean, , , $toolbox ) = $this->beanHelper->getExtractedToolbox();

		if ( isset( $this->$fieldLink ) ) {
			$this->__info['tainted'] = TRUE;

			if ( isset( $this->__info["sys.parentcache.$property"] ) ) {
				$bean = $this->__info["sys.parentcache.$property"];
			} else {
				if ( isset( self::$aliases[$property] ) ) {
					$type = self::$aliases[$property];
				} elseif ( $this->fetchType ) {
					$type = $this->fetchType;
					$this->fetchType = NULL;
				} else {
					$type = $property;
				}
				$bean = NULL;
				if ( !is_null( $this->properties[$fieldLink] ) ) {
					$bean = $redbean->load( $type, $this->properties[$fieldLink] );
					//If the IDs dont match, we failed to load, so try autoresolv in that case...
					if ( $bean->id !== $this->properties[$fieldLink] && self::$autoResolve ) {
						$type = $this->beanHelper->getToolbox()->getWriter()->inferFetchType( $this->__info['type'], $property );
						if ( !is_null( $type) ) {
							$bean = $redbean->load( $type, $this->properties[$fieldLink] );
							$this->__info["sys.autoresolved.{$property}"] = $type;
						}
					}
				}
			}

			$this->properties[$property] = $bean;
			$this->withSql               = '';
			$this->withParams            = array();
			$this->aliasName             = NULL;
			$this->fetchType             = NULL;
			$this->noLoad                = FALSE;
			$this->all                   = FALSE;
			$this->via                   = NULL;

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

		$this->properties[$property]          = $beans;
		$this->__info["sys.shadow.$property"] = $beans;
		$this->__info['tainted']              = TRUE;

		$this->withSql    = '';
		$this->withParams = array();
		$this->aliasName  = NULL;
		$this->fetchType  = NULL;
		$this->noLoad     = FALSE;
		$this->all        = FALSE;
		$this->via        = NULL;

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
		$isFieldLink	= (($pos = strrpos($property, '_id')) !== FALSE) && array_key_exists( ($fieldName = substr($property, 0, $pos)), $this->properties );


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
		$this->via        = NULL;

		$this->__info['tainted'] = TRUE;
		$this->__info['changed'] = TRUE;
		array_push( $this->__info['changelist'], $property );

		if ( array_key_exists( $fieldLink, $this->properties ) && !( $value instanceof OODBBean ) ) {
			if ( is_null( $value ) || $value === FALSE ) {

				unset( $this->properties[ $property ]);
				$this->properties[ $fieldLink ] = NULL;

				return;
			} else {
				throw new RedException( 'Cannot cast to bean.' );
			}
		}
		
		if ( $isFieldLink ){
			unset( $this->properties[ $fieldName ]);
			$this->properties[ $property ] = NULL;
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
			$this->__info['changed'] = TRUE;
		}
	}

	/**
	 * Returns the value of a meta property. A meta property
	 * contains additional information about the bean object that will not
	 * be stored in the database. Meta information is used to instruct
	 * RedBeanPHP as well as other systems how to deal with the bean.
	 * If the property cannot be found this getter will return NULL instead.
	 *
	 * Example:
	 *
	 * <code>
	 * $bean->setMeta( 'flush-cache', TRUE );
	 * </code>
	 *
	 * RedBeanPHP also stores meta data in beans, this meta data uses
	 * keys prefixed with 'sys.' (system).
	 *
	 * @param string $path    path to property in meta data
	 * @param mixed  $default default value
	 *
	 * @return mixed
	 */
	public function getMeta( $path, $default = NULL )
	{
		return ( isset( $this->__info[$path] ) ) ? $this->__info[$path] : $default;
	}

	/**
	 * Gets and unsets a meta property.
	 * Moves a meta property out of the bean.
	 * This is a short-cut method that can be used instead
	 * of combining a get/unset.
	 *
	 * @param string $path    path to property in meta data
	 * @param mixed  $default default value
	 *
	 * @return mixed
	 */
	public function moveMeta( $path, $value = NULL )
	{
		if ( isset( $this->__info[$path] ) ) {
			$value = $this->__info[ $path ];
			unset( $this->__info[ $path ] );
		}
		return $value;
	}

	/**
	 * Stores a value in the specified Meta information property.
	 * The first argument should be the key to store the value under,
	 * the second argument should be the value. It is common to use
	 * a path-like notation for meta data in RedBeanPHP like:
	 * 'my.meta.data', however the dots are purely for readability, the
	 * meta data methods do not store nested structures or hierarchies.
	 *
	 * @param string $path  path / key to store value under
	 * @param mixed  $value value to store in bean (not in database) as meta data
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
	 * @param OODBBean $bean bean to copy meta data of
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
	 * This method can also be used to override bean behaviour.
	 * In that case you don't want an error or exception to be triggered
	 * if the method does not exist in the model (because it's optional).
	 * Unfortunately we cannot add an extra argument to __call() for this
	 * because the signature is fixed. Another option would be to set
	 * a special flag ( i.e. $this->isOptionalCall ) but that would
	 * cause additional complexity because we have to deal with extra temporary state.
	 * So, instead I allowed the method name to be prefixed with '@', in practice
	 * nobody creates methods like that - however the '@' symbol in PHP is widely known
	 * to suppress error handling, so we can reuse the semantics of this symbol.
	 * If a method name gets passed starting with '@' the overrideDontFail variable
	 * will be set to TRUE and the '@' will be stripped from the function name before
	 * attempting to invoke the method on the model. This way, we have all the
	 * logic in one place.
	 *
	 * @param string $method name of the method
	 * @param array  $args   argument list
	 *
	 * @return mixed
	 */
	public function __call( $method, $args )
	{
		$overrideDontFail = FALSE;
		if ( strpos( $method, '@' ) === 0 ) {
			$method = substr( $method, 1 );
			$overrideDontFail = TRUE;
		}

		if ( !isset( $this->__info['model'] ) ) {
			$model = $this->beanHelper->getModelForBean( $this );

			if ( !$model ) {
				return NULL;
			}

			$this->__info['model'] = $model;
		}
		if ( !method_exists( $this->__info['model'], $method ) ) {

			if ( self::$errorHandlingFUSE === FALSE || $overrideDontFail ) {
				return NULL;
			}

			if ( in_array( $method, array( 'update', 'open', 'delete', 'after_delete', 'after_update', 'dispense' ), TRUE ) ) {
				return NULL;
			}

			$message = "FUSE: method does not exist in model: $method";
			if ( self::$errorHandlingFUSE === self::C_ERR_LOG ) {
				error_log( $message );
				return NULL;
			} elseif ( self::$errorHandlingFUSE === self::C_ERR_NOTICE ) {
				trigger_error( $message, E_USER_NOTICE );
				return NULL;
			} elseif ( self::$errorHandlingFUSE === self::C_ERR_WARN ) {
				trigger_error( $message, E_USER_WARNING );
				return NULL;
			} elseif ( self::$errorHandlingFUSE === self::C_ERR_EXCEPTION ) {
				throw new \Exception( $message );
			} elseif ( self::$errorHandlingFUSE === self::C_ERR_FUNC ) {
				$func = self::$errorHandler;
				return $func(array(
					'message' => $message,
					'method' => $method,
					'args' => $args,
					'bean' => $this
				));
			}
			trigger_error( $message, E_USER_ERROR );
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
		$string = $this->__call( '@__toString', array() );

		if ( $string === NULL ) {
			$list = array();
			foreach($this->properties as $property => $value) {
				if (is_scalar($value)) {
					$list[$property] = $value;
				}
			}
			return json_encode( $list );
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
	 * @param string $field field name to use for mapping
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
	 * @param string $property property
	 * @param callable $function function
	 * @param integer $maxDepth maximum depth for traversal
	 *
	 * @return OODBBean
	 * @throws RedException
	 */
	public function traverse( $property, $function, $maxDepth = NULL )
	{
		$this->via = NULL;
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
			/** @var OODBBean $bean */
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
	 * Implementation of Countable interface. Makes it possible to use
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
	 * Note that this method will return TRUE if applied to a loaded list.
	 * Also note that this method keeps track of the bean's history regardless whether
	 * it has been stored or not. Storing a bean does not undo it's history,
	 * to clean the history of a bean use: clearHistory().
	 *
	 * @param string  $property name of the property you want the change-status of
	 *
	 * @return boolean
	 */
	public function hasChanged( $property )
	{
		return ( array_key_exists( $property, $this->properties ) ) ?
			$this->old( $property ) != $this->properties[$property] : FALSE;
	}

	/**
	 * Returns TRUE if the specified list exists, has been loaded and has been changed:
	 * beans have been added or deleted. This method will not tell you anything about
	 * the state of the beans in the list.
	 *
	 * @param string $property name of the list to check
	 *
	 * @return boolean
	 */
	public function hasListChanged( $property )
	{
		if ( !array_key_exists( $property, $this->properties ) ) return FALSE;
		$diffAdded = array_diff_assoc( $this->properties[$property], $this->__info['sys.shadow.'.$property] );
		if ( count( $diffAdded ) ) return TRUE;
		$diffMissing = array_diff_assoc( $this->__info['sys.shadow.'.$property], $this->properties[$property] );
		if ( count( $diffMissing ) ) return TRUE;
		return FALSE;
	}

	/**
	 * Clears (syncs) the history of the bean.
	 * Resets all shadow values of the bean to their current value.
	 *
	 * @return self
	 */
	public function clearHistory()
	{
		$this->__info['sys.orig'] = array();
		foreach( $this->properties as $key => $value ) {
			if ( is_scalar($value) ) {
				$this->__info['sys.orig'][$key] = $value;
			} else {
				$this->__info['sys.shadow.'.$key] = $value;
			}
		}
		return $this;
	}

	/**
	 * Creates a N-M relation by linking an intermediate bean.
	 * This method can be used to quickly connect beans using indirect
	 * relations. For instance, given an album and a song you can connect the two
	 * using a track with a number like this:
	 *
	 * Usage:
	 *
	 * <code>
	 * $album->link('track', array('number'=>1))->song = $song;
	 * </code>
	 *
	 * or:
	 *
	 * <code>
	 * $album->link($trackBean)->song = $song;
	 * </code>
	 *
	 * What this method does is adding the link bean to the own-list, in this case
	 * ownTrack. If the first argument is a string and the second is an array or
	 * a JSON string then the linking bean gets dispensed on-the-fly as seen in
	 * example #1. After preparing the linking bean, the bean is returned thus
	 * allowing the chained setter: ->song = $song.
	 *
	 * @param string|OODBBean $type          type of bean to dispense or the full bean
	 * @param string|array    $qualification JSON string or array (optional)
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
	 * Returns a bean of the given type with the same ID of as
	 * the current one. This only happens in a one-to-one relation.
	 * This is as far as support for 1-1 goes in RedBeanPHP. This
	 * method will only return a reference to the bean, changing it
	 * and storing the bean will not update the related one-bean.
	 *
	 * @return OODBBean
	 */
	public function one( $type ) {
		return $this->beanHelper->getToolBox()->getRedBean()->load( $type, $this->id );
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
		$this->via = AQueryWriter::camelsSnake( $via );

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

		if ( $this->getID() ) {

			$firstKey = NULL;
			if ( count( $this->withParams ) > 0 ) {
				reset( $this->withParams );
				$firstKey = key( $this->withParams );
			}

			$joinSql = $this->parseJoin( $type );

			if ( !is_numeric( $firstKey ) || $firstKey === NULL ) {
					$bindings           = $this->withParams;
					$bindings[':slot0'] = $this->getID();
					$count              = $this->beanHelper->getToolbox()->getWriter()->queryRecordCount( $type, array(), " {$joinSql} $myFieldLink = :slot0 " . $this->withSql, $bindings );
			} else {
					$bindings = array_merge( array( $this->getID() ), $this->withParams );
					$count    = $this->beanHelper->getToolbox()->getWriter()->queryRecordCount( $type, array(), " {$joinSql} $myFieldLink = ? " . $this->withSql, $bindings );
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

		if ( $this->getID() ) {
			$count = $redbean->getAssociationManager()->relatedCount( $this, $type, $this->withSql, $this->withParams );
		}

		$this->clearModifiers();
		return (integer) $count;
	}

	/**
	 * Iterates through the specified own-list and
	 * fetches all properties (with their type) and
	 * returns the references.
	 * Use this method to quickly load indirectly related
	 * beans in an own-list. Whenever you cannot use a
	 * shared-list this method offers the same convenience
	 * by aggregating the parent beans of all children in
	 * the specified own-list.
	 *
	 * Example:
	 *
	 * <code>
	 * $quest->aggr( 'xownQuestTarget', 'target', 'quest' );
	 * </code>
	 *
	 * Loads (in batch) and returns references to all
	 * quest beans residing in the $questTarget->target properties
	 * of each element in the xownQuestTargetList.
	 *
	 * @param string $list     the list you wish to process
	 * @param string $property the property to load
	 * @param string $type     the type of bean residing in this property (optional)
	 *
	 * @return array
	 */
	public function &aggr( $list, $property, $type = NULL )
	{
		$this->via = NULL;
		$ids = $beanIndex = $references = array();

		if ( strlen( $list ) < 4 ) throw new RedException('Invalid own-list.');
		if ( strpos( $list, 'own') !== 0 ) throw new RedException('Only own-lists can be aggregated.');
		if ( !ctype_upper( substr( $list, 3, 1 ) ) ) throw new RedException('Invalid own-list.');

		if ( is_null( $type ) ) $type = $property;

		foreach( $this->$list as $bean ) {
			$field = $property . '_id';
			if ( isset( $bean->$field)  ) {
				$ids[] = $bean->$field;
				$beanIndex[$bean->$field] = $bean;
			}
		}

		$beans = $this->beanHelper->getToolBox()->getRedBean()->batch( $type, $ids );

		//now preload the beans as well
		foreach( $beans as $bean ) {
			$beanIndex[$bean->id]->setProperty( $property, $bean );
		}

		foreach( $beanIndex as $indexedBean ) {
			$references[] = $indexedBean->$property;
		}

		return $references;
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

	/**
	 * Magic method jsonSerialize, implementation for the \JsonSerializable interface,
	 * this method gets called by json_encode and facilitates a better JSON representation
	 * of the bean. Exports the bean on JSON serialization, for the JSON fans.
	 *
	 * @see  http://php.net/manual/en/class.jsonserializable.php
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->export();
	}
}
