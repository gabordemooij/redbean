<?php
/**
 * Association Manager
 *
 * @file    RedBean/AssociationManager.php
 * @desc    Manages simple bean associations.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_AssociationManager extends RedBean_Observable
{

	/**
	 * @var RedBean_OODB
	 */
	protected $oodb;

	/**
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;

	/**
	 * @var RedBean_QueryWriter
	 */
	protected $writer;

	/**
	 * Handles Exceptions. Suppresses exceptions caused by missing structures.
	 *
	 * @param Exception $exception
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	private function handleException( Exception $exception )
	{
		if ( !$this->writer->sqlStateIn( $exception->getSQLState(),
			array(
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN )
			)
		) {
			throw $exception;
		}
	}

	/**
	 * Internal method.
	 * Returns the many-to-many related rows of table $type for bean $bean using additional SQL in $sql and
	 * $bindings bindings. If $getLinks is TRUE, link rows are returned instead.
	 *
	 * @param RedBean_OODBBean $bean     reference bean
	 * @param string           $type     target type
	 * @param boolean          $getLinks TRUE returns rows from the link table
	 * @param string           $sql      additional SQL snippet
	 * @param array            $bindings bindings
	 *
	 * @return array
	 *
	 * @throws RedBean_Exception_Security
	 * @throws RedBean_Exception_SQL
	 */
	private function relatedRows( $bean, $type, $getLinks = FALSE, $sql = '', $bindings = array() )
	{
		if ( !is_array( $bean ) && !( $bean instanceof RedBean_OODBBean ) ) {
			throw new RedBean_Exception_Security(
				'Expected array or RedBean_OODBBean but got:' . gettype( $bean )
			);
		}

		$ids = array();
		if ( is_array( $bean ) ) {
			$beans = $bean;
			foreach ( $beans as $singleBean ) {
				if ( !( $singleBean instanceof RedBean_OODBBean ) ) {
					throw new RedBean_Exception_Security(
						'Expected RedBean_OODBBean in array but got:' . gettype( $singleBean )
					);
				}
				$ids[] = $singleBean->id;
			}
			$bean = reset( $beans );
		} else {
			$ids[] = $bean->id;
		}

		$sourceType = $bean->getMeta( 'type' );
		try {
			if ( !$getLinks ) {
				return $this->writer->queryRecordRelated( $sourceType, $type, $ids, $sql, $bindings );
			} else {
				return $this->writer->queryRecordLinks( $sourceType, $type, $ids, $sql, $bindings );
			}
		} catch ( RedBean_Exception_SQL $exception ) {
			$this->handleException( $exception );

			return array();
		}
	}

	/**
	 * Associates a pair of beans. This method associates two beans, no matter
	 * what types.Accepts a base bean that contains data for the linking record.
	 *
	 * @param RedBean_OODBBean $bean1 first bean
	 * @param RedBean_OODBBean $bean2 second bean
	 * @param RedBean_OODBBean $bean  base bean
	 *
	 * @throws Exception|RedBean_Exception_SQL
	 *
	 * @return mixed
	 */
	protected function associateBeans( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, RedBean_OODBBean $bean )
	{

		$property1 = $bean1->getMeta( 'type' ) . '_id';
		$property2 = $bean2->getMeta( 'type' ) . '_id';

		if ( $property1 == $property2 ) {
			$property2 = $bean2->getMeta( 'type' ) . '2_id';
		}

		//add a build command for Unique Indexes
		$bean->setMeta( 'buildcommand.unique', array( array( $property1, $property2 ) ) );

		//add a build command for Single Column Index (to improve performance in case unqiue cant be used)
		$indexName1 = 'index_for_' . $bean->getMeta( 'type' ) . '_' . $property1;
		$indexName2 = 'index_for_' . $bean->getMeta( 'type' ) . '_' . $property2;

		$bean->setMeta( 'buildcommand.indexes', array( $property1 => $indexName1, $property2 => $indexName2 ) );

		$this->oodb->store( $bean1 );
		$this->oodb->store( $bean2 );

		$bean->setMeta( "cast.$property1", "id" );
		$bean->setMeta( "cast.$property2", "id" );

		$bean->$property1 = $bean1->id;
		$bean->$property2 = $bean2->id;

		$results   = array();
		try {
			$id = $this->oodb->store( $bean );

			//On creation, add constraints....
			if ( !$this->oodb->isFrozen() &&
				$bean->getMeta( 'buildreport.flags.created' )
			) {
				$bean->setMeta( 'buildreport.flags.created', 0 );
				if ( !$this->oodb->isFrozen() ) {
					$this->writer->addConstraintForTypes( $bean1->getMeta( 'type' ), $bean2->getMeta( 'type' ) );
				}
			}
			$results[] = $id;
		} catch ( RedBean_Exception_SQL $exception ) {
			if ( !$this->writer->sqlStateIn( $exception->getSQLState(),
				array( RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION ) )
			) {
				throw $exception;
			}
		}

		return $results;
	}

	/**
	 * Constructor
	 *
	 * @param RedBean_ToolBox $tools toolbox
	 */
	public function __construct( RedBean_ToolBox $tools )
	{
		$this->oodb    = $tools->getRedBean();
		$this->adapter = $tools->getDatabaseAdapter();
		$this->writer  = $tools->getWriter();
		$this->toolbox = $tools;
	}

	/**
	 * Creates a table name based on a types array.
	 * Manages the get the correct name for the linking table for the
	 * types provided.
	 *
	 * @todo find a nice way to decouple this class from QueryWriter?
	 *
	 * @param array $types 2 types as strings
	 *
	 * @return string
	 */
	public function getTable( $types )
	{
		return $this->writer->getAssocTable( $types );
	}

	/**
	 * Associates two beans with eachother using a many-to-many relation.
	 *
	 * @param RedBean_OODBBean $bean1 bean1
	 * @param RedBean_OODBBean $bean2 bean2
	 *
	 * @return array
	 */
	public function associate( $beans1, $beans2 )
	{
		if ( !is_array( $beans1 ) ) {
			$beans1 = array( $beans1 );
		}

		if ( !is_array( $beans2 ) ) {
			$beans2 = array( $beans2 );
		}

		$results = array();
		foreach ( $beans1 as $bean1 ) {
			foreach ( $beans2 as $bean2 ) {
				$table     = $this->getTable( array( $bean1->getMeta( 'type' ), $bean2->getMeta( 'type' ) ) );
				$bean      = $this->oodb->dispense( $table );
				$results[] = $this->associateBeans( $bean1, $bean2, $bean );
			}
		}

		return ( count( $results ) > 1 ) ? $results : reset( $results );
	}

	/**
	 * Counts the number of related beans in an N-M relation.
	 *
	 * @param RedBean_OODBBean|array $bean     a bean object or an array of beans
	 * @param string                 $type     type of bean you're interested in
	 * @param string                 $sql      SQL snippet (optional)
	 * @param array                  $bindings bindings for your SQL string
	 *
	 * @return integer
	 *
	 * @throws RedBean_Exception_Security
	 */
	public function relatedCount( $bean, $type, $sql = NULL, $bindings = array() )
	{
		if ( !( $bean instanceof RedBean_OODBBean ) ) {
			throw new RedBean_Exception_Security(
				'Expected array or RedBean_OODBBean but got:' . gettype( $bean )
			);
		}

		if ( !$bean->id ) {
			return 0;
		}

		$beanType = $bean->getMeta( 'type' );

		try {
			return $this->writer->queryRecordCountRelated( $beanType, $type, $bean->id, $sql, $bindings );
		} catch ( RedBean_Exception_SQL $exception ) {
			$this->handleException( $exception );

			return 0;
		}
	}

	/**
	 * Returns all ids of beans of type $type that are related to $bean. If the
	 * $getLinks parameter is set to boolean TRUE this method will return the ids
	 * of the association beans instead. You can also add additional SQL. This SQL
	 * will be appended to the original query string used by this method. Note that this
	 * method will not return beans, just keys. For a more convenient method see the R-facade
	 * method related(), that is in fact a wrapper for this method that offers a more
	 * convenient solution. If you want to make use of this method, consider the
	 * OODB batch() method to convert the ids to beans.
	 *
	 * Since 3.2, you can now also pass an array of beans instead just one
	 * bean as the first parameter.
	 *
	 * @throws RedBean_Exception_SQL
	 *
	 * @param RedBean_OODBBean|array $bean     reference bean
	 * @param string                 $type     target type
	 * @param boolean                $getLinks whether you are interested in the assoc records
	 * @param string                 $sql      room for additional SQL
	 * @param array                  $bindings bindings for SQL snippet
	 *
	 * @return array
	 */
	public function related( $bean, $type, $getLinks = FALSE, $sql = '', $bindings = array() )
	{
		$sql  = $this->writer->glueSQLCondition( $sql );

		$rows = $this->relatedRows( $bean, $type, $getLinks, $sql, $bindings );

		$ids  = array();
		foreach ( $rows as $row ) $ids[] = $row['id'];

		return $ids;
	}

	/**
	 * Breaks the association between two beans. This method unassociates two beans. If the
	 * method succeeds the beans will no longer form an association. In the database
	 * this means that the association record will be removed. This method uses the
	 * OODB trash() method to remove the association links, thus giving FUSE models the
	 * opportunity to hook-in additional business logic. If the $fast parameter is
	 * set to boolean TRUE this method will remove the beans without their consent,
	 * bypassing FUSE. This can be used to improve performance.
	 *
	 * @param RedBean_OODBBean $bean1 first bean
	 * @param RedBean_OODBBean $bean2 second bean
	 * @param boolean          $fast  If TRUE, removes the entries by query without FUSE
	 *
	 * @return void
	 */
	public function unassociate( $beans1, $beans2, $fast = NULL )
	{
		$beans1 = ( !is_array( $beans1 ) ) ? array( $beans1 ) : $beans1;
		$beans2 = ( !is_array( $beans2 ) ) ? array( $beans2 ) : $beans2;

		foreach ( $beans1 as $bean1 ) {
			foreach ( $beans2 as $bean2 ) {
				try {
					$this->oodb->store( $bean1 );
					$this->oodb->store( $bean2 );

					$type1 = $bean1->getMeta( 'type' );
					$type2 = $bean2->getMeta( 'type' );

					$row      = $this->writer->queryRecordLink( $type1, $type2, $bean1->id, $bean2->id );
					$linkType = $this->getTable( array( $type1, $type2 ) );

					if ( $fast ) {
						$this->writer->deleteRecord( $linkType, array( 'id' => $row['id'] ) );

						return;
					}

					$beans = $this->oodb->convertToBeans( $linkType, array( $row ) );

					if ( count( $beans ) > 0 ) {
						$bean = reset( $beans );
						$this->oodb->trash( $bean );
					}
				} catch ( RedBean_Exception_SQL $exception ) {
					$this->handleException( $exception );
				}
			}
		}
	}

	/**
	 * Removes all relations for a bean. This method breaks every connection between
	 * a certain bean $bean and every other bean of type $type. Warning: this method
	 * is really fast because it uses a direct SQL query however it does not inform the
	 * models about this. If you want to notify FUSE models about deletion use a foreach-loop
	 * with unassociate() instead. (that might be slower though)
	 *
	 * @param RedBean_OODBBean $bean reference bean
	 * @param string           $type type of beans that need to be unassociated
	 *
	 * @return void
	 */
	public function clearRelations( RedBean_OODBBean $bean, $type )
	{
		$this->oodb->store( $bean );
		try {
			$this->writer->deleteRelations( $bean->getMeta( 'type' ), $type, $bean->id );
		} catch ( RedBean_Exception_SQL $exception ) {
			$this->handleException( $exception );
		}
	}

	/**
	 * Given two beans this function returns TRUE if they are associated using a
	 * many-to-many association, FALSE otherwise.
	 *
	 * @throws RedBean_Exception_SQL
	 *
	 * @param RedBean_OODBBean $bean1 bean
	 * @param RedBean_OODBBean $bean2 bean
	 *
	 * @return boolean
	 */
	public function areRelated( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 )
	{
		try {
			$row = $this->writer->queryRecordLink( $bean1->getMeta( 'type' ), $bean2->getMeta( 'type' ), $bean1->id, $bean2->id );

			return (boolean) $row;
		} catch ( RedBean_Exception_SQL $exception ) {
			$this->handleException( $exception );

			return FALSE;
		}
	}

	/**
	 * Swaps a property of two beans.
	 *
	 * @deprecated
	 * This method does not seem very useful.
	 *
	 * @param array  $beans    beans
	 * @param string $property property to swap
	 *
	 * @return void
	 */
	public function swap( $beans, $property )
	{
		$bean1            = array_shift( $beans );
		$bean2            = array_shift( $beans );
		$tmp              = $bean1->$property;
		$bean1->$property = $bean2->$property;
		$bean2->$property = $tmp;
		$this->oodb->store( $bean1 );
		$this->oodb->store( $bean2 );
	}

	/**
	 * Returns all the beans associated with $bean.
	 * This method will return an array containing all the beans that have
	 * been associated once with the associate() function and are still
	 * associated with the bean specified. The type parameter indicates the
	 * type of beans you are looking for. You can also pass some extra SQL and
	 * values for that SQL to filter your results after fetching the
	 * related beans.
	 *
	 * Don't try to make use of subqueries, a subquery using IN() seems to
	 * be slower than two queries!
	 *
	 * Since 3.2, you can now also pass an array of beans instead just one
	 * bean as the first parameter.
	 *
	 * @param RedBean_OODBBean|array $bean      the bean you have
	 * @param string                 $type      the type of beans you want
	 * @param string                 $sql       SQL snippet for extra filtering
	 * @param array                  $bindings  values to be inserted in SQL slots
	 * @param boolean                $glue      whether the SQL should be prefixed with WHERE
	 *
	 * @return array
	 */
	public function relatedSimple( $bean, $type, $sql = '', $bindings = array() )
	{
		$sql   = $this->writer->glueSQLCondition( $sql );

		$rows  = $this->relatedRows( $bean, $type, FALSE, $sql, $bindings );

		$links = array();
		foreach ( $rows as $key => $row ) {
			if ( !isset( $links[$row['id']] ) ) {
				$links[$row['id']] = array();
			}

			$links[$row['id']][] = $row['linked_by'];

			unset( $rows[$key]['linked_by'] );
		}

		$beans = $this->oodb->convertToBeans( $type, $rows );

		foreach ( $beans as $bean ) {
			$bean->setMeta( 'sys.belongs-to', $links[$bean->id] );
		}

		return $beans;
	}

	/**
	 * Returns only a single associated bean.
	 *
	 * @param RedBean_OODBBean $bean     bean provided
	 * @param string           $type     type of bean you are searching for
	 * @param string           $sql      SQL for extra filtering
	 * @param array            $bindings values to be inserted in SQL slots
	 *
	 * @return RedBean_OODBBean
	 */
	public function relatedOne( RedBean_OODBBean $bean, $type, $sql = NULL, $bindings = array() )
	{
		$beans = $this->relatedSimple( $bean, $type, $sql, $bindings );

		if ( empty( $beans ) ) {
			return NULL;
		}

		return reset( $beans );
	}

	/**
	 * Returns only the last, single associated bean.
	 *
	 * @param RedBean_OODBBean $bean     bean provided
	 * @param string           $type     type of bean you are searching for
	 * @param string           $sql      SQL for extra filtering
	 * @param array            $bindings values to be inserted in SQL slots
	 *
	 * @return RedBean_OODBBean
	 */
	public function relatedLast( RedBean_OODBBean $bean, $type, $sql = NULL, $bindings = array() )
	{
		$beans = $this->relatedSimple( $bean, $type, $sql, $bindings );

		if ( empty( $beans ) ) {
			return NULL;
		}

		return end( $beans );
	}

}
