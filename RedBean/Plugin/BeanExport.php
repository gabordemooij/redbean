<?php
/**
 * Recursive Bean Export
 *
 * @file    RedBean/Plugin/BeanExport.php
 * @desc    Plugin to export beans to arrays recursively
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @deprecated
 *          Use the R::exportAll method instead.
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_BeanExport implements RedBean_Plugin
{
	/**
	 * @var NULL|\RedBean_Toolbox
	 */
	protected $toolbox = NULL;

	/**
	 * @var array
	 */
	protected $recurCheck = array();

	/**
	 * @var array
	 */
	protected $recurTypeCheck = array();

	/**
	 * @var boolean
	 */
	protected $typeShield = FALSE;

	/**
	 * @var integer
	 */
	protected $depth = 0;

	/**
	 * @var integer
	 */
	protected $maxDepth = FALSE;

	/**
	 * @var array
	 */
	protected $tables = array();

	/**
	 * Extracts the parent bean from the current bean and puts the
	 * contents in the export array.
	 *
	 * @param array            $export export array to store data in
	 * @param RedBean_OODBBean $bean   bean to collect data from
	 * @param string           $key    key for the current value
	 * @param RedBean_OODBBean $value  the parent bean to extract
	 *
	 * @return void
	 */
	private function extractParentBean( &$export, &$bean, $key, $value )
	{
		if ( strpos( $key, '_id' ) === FALSE ) return;

		$sub = str_replace( '_id', '', $key );

		$subBean = $bean->$sub;

		if ( $subBean ) {
			$export[$sub] = $this->export( $subBean, FALSE );
		}
	}

	/**
	 * Extracts the own list from the current bean and puts the
	 * contents in the export array.
	 *
	 * @param array            $export export array to store data in
	 * @param RedBean_OODBBean $bean   bean to collect data from
	 * @param string           $table  table
	 * @param array            $cols   columns
	 *
	 * @return void
	 */
	private function extractOwnList( &$export, &$bean, $table, $cols )
	{
		if ( strpos( $table, '_' ) !== FALSE ) return;

		$linkField = $bean->getMeta( 'type' ) . '_id';

		if ( !in_array( $linkField, array_keys( $cols ) ) ) return;

		$field = 'own' . ucfirst( $table );

		$export[$field] = self::export( $bean->$field, FALSE );
	}

	/**
	 * Extracts the shared list from the current bean and puts the
	 * contents in the export array.
	 *
	 * @param array            $export export array to store data in
	 * @param RedBean_OODBBean $bean   bean to collect data from
	 * @param string           $table  table
	 *
	 * @return void
	 */
	private function extractSharedList( &$export, &$bean, $table )
	{
		if ( strpos( $table, '_' ) === FALSE ) return;

		$type = $bean->getMeta( 'type' );

		$parts = explode( '_', $table );

		if ( !is_array( $parts ) ) return;

		if ( !in_array( $type, $parts ) ) return;

		$other = $parts[0];

		if ( $other == $type ) {
			$other = $parts[1];
		}

		$field  = 'shared' . ucfirst( $other );

		$export[$field] = self::export( $bean->$field, FALSE );
	}

	/**
	 * Constructor
	 *
	 * @param RedBean_Toolbox $toolbox
	 */
	public function __construct( RedBean_Toolbox $toolbox )
	{
		$this->toolbox = $toolbox;
	}

	/**
	 * Loads Schema
	 *
	 * @return void
	 */
	public function loadSchema()
	{
		$tables = array_flip( $this->toolbox->getWriter()->getTables() );

		foreach ( $tables as $table => $columns ) {
			try {
				$tables[$table] = $this->toolbox->getWriter()->getColumns( $table );
			} catch ( RedBean_Exception_SQL $e ) {
				$tables[$table] = array();
			}
		}

		$this->tables = $tables;
	}

	/**
	 *Returs a serialized representation of the schema
	 *
	 * @return string $serialized serialized representation
	 */
	public function getSchema()
	{
		return serialize( $this->tables );
	}

	/**
	 * Loads a schema from a string (containing serialized export of schema)
	 *
	 * @param string $schema
	 */
	public function loadSchemaFromString( $schema )
	{
		$this->tables = unserialize( $schema );
	}

	/**
	 * Exports a collection of beans
	 *
	 * @param    mixed $beans      Either array or RedBean_OODBBean
	 * @param    bool  $resetRecur Whether we need to reset the recursion check array (first time only)
	 *
	 * @return    array $export Exported beans
	 */
	public function export( $beans, $resetRecur = TRUE )
	{
		if ( $resetRecur ) $this->recurCheck = array();

		if ( !is_array( $beans ) ) $beans = array( $beans );

		if ( $this->maxDepth !== FALSE ) {
			$this->depth++;
			if ( $this->depth > $this->maxDepth ) {
				$this->depth--;

				return array();
			}
		}

		if ( $this->typeShield === TRUE ) {
			if ( is_array( $beans ) && count( $beans ) > 0 ) {
				$firstBean = reset( $beans );

				$type = $firstBean->getMeta( 'type' );

				if ( isset( $this->recurTypeCheck[$type] ) ) {
					if ( $this->maxDepth !== FALSE ) {
						$this->depth--;
					}

					return array();
				}

				$this->recurTypeCheck[$type] = TRUE;
			}
		}

		$export = array();
		foreach ( $beans as $bean ) {
			$export[$bean->getID()] = $this->exportBean( $bean );
		}

		if ( $this->maxDepth !== FALSE ) {
			$this->depth--;
		}

		return $export;
	}

	/**
	 * Exports beans, just like export() but with additional
	 * parameters for limitation on recursion and depth.
	 *
	 * @param array           $beans      beans to export
	 * @param boolean         $typeShield whether to use a type recursion shield
	 * @param boolean|integer $depth      maximum number of iterations allowed (boolean FALSE to turn off)
	 *
	 * @return array
	 */
	public function exportLimited( $beans, $typeShield = TRUE, $depth = FALSE )
	{
		$this->depth      = 0;
		$this->maxDepth   = $depth;
		$this->typeShield = $typeShield;

		$export           = $this->export( $beans );

		$this->typeShield = FALSE;
		$this->maxDepth   = FALSE;

		return $export;
	}

	/**
	 * Exports a single bean
	 *
	 * @param RedBean_OODBBean $bean Bean to be exported
	 *
	 * @return array|NULL $array Array export of bean
	 */
	public function exportBean( RedBean_OODBBean $bean )
	{
		$bid = $bean->getMeta( 'type' ) . '-' . $bean->getID();

		if ( isset( $this->recurCheck[$bid] ) ) return NULL;

		$this->recurCheck[$bid] = $bid;

		$export = $bean->export();

		foreach ( $export as $key => $value ) {
			$this->extractParentBean( $export, $bean, $key, $value );
		}

		foreach ( $this->tables as $table => $cols ) { //get all ownProperties
			$this->extractOwnList( $export, $bean, $table, $cols );
		}

		foreach ( $this->tables as $table => $cols ) { //get all sharedProperties
			$this->extractSharedList( $export, $bean, $table );
		}

		return $export;
	}
}
