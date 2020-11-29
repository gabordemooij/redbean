<?php

namespace RedBeanPHP;


/**
 * RedBeanPHP Finder.
 * Service class to find beans. For the most part this class
 * offers user friendly utility methods for interacting with the
 * OODB::find() method, which is rather complex. This class can be
 * used to find beans using plain old SQL queries.
 *
 * @file    RedBeanPHP/Finder.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Finder
{
	/**
	 * @var ToolBox
	 */
	protected $toolbox;

	/**
	 * @var OODB
	 */
	protected $redbean;

	/**
	 * Constructor.
	 * The Finder requires a toolbox.
	 *
	 * @param ToolBox $toolbox
	 */
	public function __construct( ToolBox $toolbox )
	{
		$this->toolbox = $toolbox;
		$this->redbean = $toolbox->getRedBean();
	}

	/**
	 * A custom record-to-bean mapping function for findMulti.
	 *
	 * Usage:
	 *
	 * <code>
	 * $collection = R::findMulti( 'shop,product,price',
	 * 'SELECT shop.*, product.*, price.* FROM shop
	 *	LEFT JOIN product ON product.shop_id = shop.id
	 *	LEFT JOIN price ON price.product_id = product.id', [], [
	 *		Finder::map( 'shop', 'product' ),
	 *		Finder::map( 'product', 'price' ),
	 *	]);
	 * </code>
	 *
	 * @param string $parentName name of the parent bean
	 * @param string $childName  name of the child bean
	 *
	 * @return array
	 */
	public static function map($parentName,$childName) {
		return array(
			'a' => $parentName,
			'b' => $childName,
			'matcher' => function( $parent, $child ) use ( $parentName, $childName ) {
				$propertyName = 'own' . ucfirst( $childName );
				if (!isset($parent[$propertyName])) {
					$parent->noLoad()->{$propertyName} = array();
				}
				$property = "{$parentName}ID";
				return ( $child->$property == $parent->id );
			},
			'do' => function( $parent, $child ) use ( $childName ) {
				$list = 'own'.ucfirst( $childName ).'List';
				$parent->noLoad()->{$list}[$child->id] = $child;
			}
		);
	}

	/**
	* A custom record-to-bean mapping function for findMulti.
	*
	* Usage:
	*
	* <code>
	* $collection = R::findMulti( 'book,book_tag,tag',
	* 'SELECT book.*, book_tag.*, tag.* FROM book
	*      LEFT JOIN book_tag ON book_tag.book_id = book.id
	*      LEFT JOIN tag ON book_tag.tag_id = tag.id', [], [
	*              Finder::nmMap( 'book', 'tag' ),
	*      ]);
	* </code>
	*
	* @param string $parentName name of the parent bean
	* @param string $childName  name of the child bean
	*
	* @return array
	*/
	public static function nmMap( $parentName, $childName )
	{
		$types = array($parentName, $childName);
		sort( $types );
		$link = implode( '_', $types );
		return array(
			'a' => $parentName,
			'b' => $childName,
			'matcher' => function( $parent, $child, $beans ) use ( $parentName, $childName, $link ) {
				$propertyName = 'shared' . ucfirst( $childName );
				if (!isset($parent[$propertyName])) {
					$parent->noLoad()->{$propertyName} = array();
				}
				foreach( $beans[$link] as $linkBean ) {
					if ( $linkBean["{$parentName}ID"] == $parent->id && $linkBean["{$childName}ID"] == $child->id ) {
						return true;
					}
				}
			},
			'do' => function( $parent, $child ) use ( $childName ) {
				$list = 'shared'.ucfirst( $childName ).'List';
				$parent->noLoad()->{$list}[$child->id] = $child;
			}
		);
	}

	/**
	 * Finder::onMap() -> One-to-N mapping.
	 * A custom record-to-bean mapping function for findMulti.
	 * Opposite of Finder::map(). Maps child beans to parents.
	 *
	 * Usage:
	 *
	 * <code>
	 * $collection = R::findMulti( 'shop,product',
	 * 'SELECT shop.*, product.* FROM shop
	 *	LEFT JOIN product ON product.shop_id = shop.id',
	 *  [], [
	 *		Finder::onmap( 'product', 'shop' ),
	 *	]);
	 * </code>
	 *
	 * Can also be used for instance to attach related beans
	 * in one-go to save some queries:
	 *
	 * Given $users that have a country_id:
	 *
	 * <code>
	 * $all = R::findMulti('country',
	 *    R::genSlots( $users,
	 *       'SELECT country.* FROM country WHERE id IN ( %s )' ),
	 *    array_column( $users, 'country_id' ),
	 *    [Finder::onmap('country', $users)]
	 * );
	 * </code>
	 *
	 * For your convenience, an even shorter notation has been added:
	 *
	 * $countries = R::loadJoined( $users, 'country' );
	 *
	 * @param string       $parentName name of the parent bean
	 * @param string|array $childName  name of the child bean
	 *
	 * @return array
	 */
	public static function onMap($parentName,$childNameOrBeans) {
		return array(
			'a' => $parentName,
			'b' => $childNameOrBeans,
			'matcher' => array( $parentName, "{$parentName}_id" ),
			'do' => 'match'
		);
	}

	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return array
	 */
	public function find( $type, $sql = NULL, $bindings = array() )
	{
		if ( !is_array( $bindings ) ) {
			throw new RedException(
				'Expected array, ' . gettype( $bindings ) . ' given.'
			);
		}

		return $this->redbean->find( $type, array(), $sql, $bindings );
	}

	/**
	 * Like find() but also exports the beans as an array.
	 * This method will perform a find-operation. For every bean
	 * in the result collection this method will call the export() method.
	 * This method returns an array containing the array representations
	 * of every bean in the result set.
	 *
	 * @see Finder::find
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return array
	 */
	public function findAndExport( $type, $sql = NULL, $bindings = array() )
	{
		$arr = array();
		foreach ( $this->find( $type, $sql, $bindings ) as $key => $item ) {
			$arr[] = $item->export();
		}

		return $arr;
	}

	/**
	 * Like find() but returns just one bean instead of an array of beans.
	 * This method will return only the first bean of the array.
	 * If no beans are found, this method will return NULL.
	 *
	 * @see Finder::find
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return OODBBean|NULL
	 */
	public function findOne( $type, $sql = NULL, $bindings = array() )
	{
		$sql = $this->toolbox->getWriter()->glueLimitOne( $sql );

		$items = $this->find( $type, $sql, $bindings );

		if ( empty($items) ) {
			return NULL;
		}

		return reset( $items );
	}

	/**
	 * Like find() but returns the last bean of the result array.
	 * Opposite of Finder::findLast().
	 * If no beans are found, this method will return NULL.
	 *
	 * @see Finder::find
	 *
	 * @param string $type     the type of bean you are looking for
	 * @param string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return OODBBean|NULL
	 */
	public function findLast( $type, $sql = NULL, $bindings = array() )
	{
		$items = $this->find( $type, $sql, $bindings );

		if ( empty($items) ) {
			return NULL;
		}

		return end( $items );
	}

	/**
	 * Tries to find beans of a certain type,
	 * if no beans are found, it dispenses a bean of that type.
	 * Note that this function always returns an array.
	 *
	 * @see Finder::find
	 *
	 * @param  string $type     the type of bean you are looking for
	 * @param  string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param  array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return array
	 */
	public function findOrDispense( $type, $sql = NULL, $bindings = array() )
	{
		$foundBeans = $this->find( $type, $sql, $bindings );

		if ( empty( $foundBeans ) ) {
			return array( $this->redbean->dispense( $type ) );
		} else {
			return $foundBeans;
		}
	}

	/**
	 * Finds a BeanCollection using the repository.
	 * A bean collection can be used to retrieve one bean at a time using
	 * cursors - this is useful for processing large datasets. A bean collection
	 * will not load all beans into memory all at once, just one at a time.
	 *
	 * @param  string $type     the type of bean you are looking for
	 * @param  string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param  array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return BeanCollection
	 */
	public function findCollection( $type, $sql, $bindings = array() )
	{
		return $this->redbean->findCollection( $type, $sql, $bindings );
	}

	/**
	 * Finds or creates a bean.
	 * Tries to find a bean with certain properties specified in the second
	 * parameter ($like). If the bean is found, it will be returned.
	 * If multiple beans are found, only the first will be returned.
	 * If no beans match the criteria, a new bean will be dispensed,
	 * the criteria will be imported as properties and this new bean
	 * will be stored and returned.
	 *
	 * Format of criteria set: property => value
	 * The criteria set also supports OR-conditions: property => array( value1, orValue2 )
	 *
	 * @param string  $type type of bean to search for
	 * @param array   $like criteria set describing bean to search for
	 * @param boolean $hasBeenCreated set to TRUE if bean has been created
	 *
	 * @return OODBBean
	 */
	public function findOrCreate( $type, $like = array(), $sql = '', &$hasBeenCreated = false )
	{
			$sql = $this->toolbox->getWriter()->glueLimitOne( $sql );
			$beans = $this->findLike( $type, $like, $sql );
			if ( count( $beans ) ) {
				$bean = reset( $beans );
				$hasBeenCreated = false;
				return $bean;
			}

			$bean = $this->redbean->dispense( $type );
			$bean->import( $like );
			$this->redbean->store( $bean );
			$hasBeenCreated = true;
			return $bean;
	}

	/**
	 * Finds beans by its type and a certain criteria set.
	 *
	 * Format of criteria set: property => value
	 * The criteria set also supports OR-conditions: property => array( value1, orValue2 )
	 *
	 * If the additional SQL is a condition, this condition will be glued to the rest
	 * of the query using an AND operator. Note that this is as far as this method
	 * can go, there is no way to glue additional SQL using an OR-condition.
	 * This method provides access to an underlying mechanism in the RedBeanPHP architecture
	 * to find beans using criteria sets. However, please do not use this method
	 * for complex queries, use plain SQL instead ( the regular find method ) as it is
	 * more suitable for the job. This method is
	 * meant for basic search-by-example operations.
	 *
	 * @param string $type       type of bean to search for
	 * @param array  $conditions criteria set describing the bean to search for
	 * @param string $sql        additional SQL (for sorting)
	 * @param array  $bindings   bindings
	 *
	 * @return array
	 */
	public function findLike( $type, $conditions = array(), $sql = '', $bindings = array() )
	{
		return $this->redbean->find( $type, $conditions, $sql, $bindings );
	}

	/**
	 * Returns a hashmap with bean arrays keyed by type using an SQL
	 * query as its resource. Given an SQL query like 'SELECT movie.*, review.* FROM movie... JOIN review'
	 * this method will return movie and review beans.
	 *
	 * Example:
	 *
	 * <code>
	 * $stuff = $finder->findMulti('movie,review', '
	 *          SELECT movie.*, review.* FROM movie
	 *          LEFT JOIN review ON review.movie_id = movie.id');
	 * </code>
	 *
	 * After this operation, $stuff will contain an entry 'movie' containing all
	 * movies and an entry named 'review' containing all reviews (all beans).
	 * You can also pass bindings.
	 *
	 * If you want to re-map your beans, so you can use $movie->ownReviewList without
	 * having RedBeanPHP executing an SQL query you can use the fourth parameter to
	 * define a selection of remapping closures.
	 *
	 * The remapping argument (optional) should contain an array of arrays.
	 * Each array in the remapping array should contain the following entries:
	 *
	 * <code>
	 * array(
	 * 	'a'       => TYPE A
	 *  'b'       => TYPE B OR BEANS
	 *    'matcher' =>
	 * 			MATCHING FUNCTION ACCEPTING A, B and ALL BEANS
	 * 			OR ARRAY
	 * 				WITH FIELD on B that should match with FIELD on A
	 * 				AND  FIELD on A that should match with FIELD on B
	 *          OR TRUE
	 *              TO JUST PERFORM THE DO-FUNCTION ON EVERY A-BEAN
	 *
	 *    'do'      => OPERATION FUNCTION ACCEPTING A, B, ALL BEANS, ALL REMAPPINGS
	 * 				   (ONLY IF MATCHER IS ALSO A FUNCTION)
	 * )
	 * </code>
	 *
	 * Using this mechanism you can build your own 'preloader' with tiny function
	 * snippets (and those can be re-used and shared online of course).
	 *
	 * Example:
	 *
	 * <code>
	 * array(
	 * 	'a' => 'movie'     //define A as movie
	 *  'b' => 'review'    //define B as review
	 *  matcher' => function( $a, $b ) {
	 *     return ( $b->movie_id == $a->id );  //Perform action if review.movie_id equals movie.id
	 *  }
	 *  'do' => function( $a, $b ) {
	 *       $a->noLoad()->ownReviewList[] = $b; //Add the review to the movie
	 *       $a->clearHistory();                 //optional, act 'as if these beans have been loaded through ownReviewList'.
	 *   }
	 * )
	 * </code>
	 *
	 * The Query Template parameter is optional as well but can be used to
	 * set a different SQL template (sprintf-style) for processing the original query.
	 *
	 * @note the SQL query provided IS NOT THE ONE used internally by this function,
	 * this function will pre-process the query to get all the data required to find the beans.
	 *
	 * @note if you use the 'book.*' notation make SURE you're
	 * selector starts with a SPACE. ' book.*' NOT ',book.*'. This is because
	 * it's actually an SQL-like template SLOT, not real SQL.
	 *
	 * @note instead of an SQL query you can pass a result array as well.
	 *
	 * @note the performance of this function is poor, if you deal with large number of records
	 * please use plain SQL instead. This function has been added as a bridge between plain SQL
	 * and bean oriented approaches but it is really on the edge of both worlds. You can safely
	 * use this function to load additional records as beans in paginated context, let's say
	 * 50-250 records. Anything above that will gradually perform worse. RedBeanPHP was never
	 * intended to replace SQL but offer tooling to integrate SQL with object oriented
	 * designs. If you have come to this function, you have reached the final border between
	 * SQL-oriented design and OOP. Anything after this will be just as good as custom mapping
	 * or plain old database querying. I recommend the latter.
	 *
	 * @param string|array $types         a list of types (either array or comma separated string)
	 * @param string|array $sql           optional, an SQL query or an array of prefetched records
	 * @param array        $bindings      optional, bindings for SQL query
	 * @param array        $remappings    optional, an array of remapping arrays
	 * @param string       $queryTemplate optional, query template
	 *
	 * @return array
	 */
	public function findMulti( $types, $sql = NULL, $bindings = array(), $remappings = array(), $queryTemplate = ' %s.%s AS %s__%s' )
	{
		if ( !is_array( $types ) ) $types = array_map( 'trim', explode( ',', $types ) );
		if ( is_null( $sql ) ) {
			$beans = array();
			foreach( $types as $type ) $beans[$type] = $this->redbean->find( $type );
		} else {
			if ( !is_array( $sql ) ) {
				$writer = $this->toolbox->getWriter();
				$adapter = $this->toolbox->getDatabaseAdapter();

				//Repair the query, replace book.* with book.id AS book_id etc..
				foreach( $types as $type ) {
					$regex = "#( (`?{$type}`?)\.\*)#";
					if ( preg_match( $regex, $sql, $matches ) ) {
						$pattern = $matches[1];
						$table = $matches[2];
						$newSelectorArray = array();
						$columns = $writer->getColumns( $type );
						foreach( $columns as $column => $definition ) {
							$newSelectorArray[] = sprintf( $queryTemplate, $table, $column, $type, $column );
						}
						$newSelector = implode( ',', $newSelectorArray );
						$sql = str_replace( $pattern, $newSelector, $sql );
					}
				}

				$rows = $adapter->get( $sql, $bindings );
			} else {
				$rows = $sql;
			}

			//Gather the bean data from the query results using the prefix
			$wannaBeans = array();
			foreach( $types as $type ) {
				$wannaBeans[$type] = array();
				$prefix            = "{$type}__";
				foreach( $rows as $rowkey=>$row ) {
					$wannaBean = array();
					foreach( $row as $cell => $value ) {
						if ( strpos( $cell, $prefix ) === 0 ) {
							$property = substr( $cell, strlen( $prefix ) );
							unset( $rows[$rowkey][$cell] );
							$wannaBean[$property] = $value;
						}
					}
					if ( !isset( $wannaBean['id'] ) ) continue;
					if ( is_null( $wannaBean['id'] ) ) continue;
					$wannaBeans[$type][$wannaBean['id']] = $wannaBean;
				}
			}

			//Turn the rows into beans
			$beans = array();
			foreach( $wannaBeans as $type => $wannabees ) {
				$beans[$type] = $this->redbean->convertToBeans( $type, $wannabees );
			}
		}

		//Apply additional re-mappings
		foreach($remappings as $remapping) {
			$a       = $remapping['a'];
			$b       = $remapping['b'];
			if (is_array($b)) {
				$firstBean = reset($b);
				$type = $firstBean->getMeta('type');
				$beans[$type] = $b;
				$b = $type;
			}
			$matcher = $remapping['matcher'];
			if (is_callable($matcher) || $matcher === TRUE) {
				$do = $remapping['do'];
				foreach( $beans[$a] as $bean ) {
					if ( $matcher === TRUE ) {
						$do( $bean, $beans[$b], $beans, $remapping );
						continue;
					}
					foreach( $beans[$b] as $putBean ) {
						if ( $matcher( $bean, $putBean, $beans ) ) $do( $bean, $putBean, $beans, $remapping );
					}
				}
			} else {
				list($field1, $field2) = $matcher;
				foreach( $beans[$b] as $key => $bean ) {
					$beans[$b][$key]->{$field1} = $beans[$a][$bean->{$field2}];
				}
			}
		}
		return $beans;
	}
}
