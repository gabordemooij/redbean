<?php
/**
 * Syncs schemas
 *
 * @file    RedBean/Plugin/Sync.php
 * @desc    Plugin for Synchronizing databases.
 *
 * @plugin  public static function syncSchema($from, $to) { return RedBean_Plugin_Sync::syncSchema($from, $to); }
 *
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_Sync implements RedBean_Plugin
{
	/**
	 * @var RedBean_OODB
	 */
	private $oodb;

	/**
	 * @var RedBean_QueryWriter
	 */
	private $sourceWriter;

	/**
	 * @var RedBean_QueryWriter
	 */
	private $targetWriter;

	/**
	 * @var array
	 */
	private $sourceTables;

	/**
	 * @var array
	 */
	private $targetTables;

	/**
	 * @var array
	 */
	private $missingTables;

	/**
	 * @var array
	 */
	private $translations;

	/**
	 * @var integer
	 */
	private $defaultCode;

	/**
	 * Performs a database schema sync. For use with facade.
	 * Instead of toolboxes this method accepts simply string keys and is static.
	 *
	 * @param string $database1 the source database
	 * @param string $database2 the target database
	 *
	 * @return void
	 *
	 * @throws RedBean_Exception_Security
	 */
	public static function syncSchema( $database1, $database2 )
	{
		if ( !isset( RedBean_Facade::$toolboxes[$database1] ) ) {
			throw new RedBean_Exception_Security( 'No database for this key: ' . $database1 );
		}

		if ( !isset( RedBean_Facade::$toolboxes[$database2] ) ) {
			throw new RedBean_Exception_Security( 'No database for this key: ' . $database2 );
		}

		$db1  = RedBean_Facade::$toolboxes[$database1];
		$db2  = RedBean_Facade::$toolboxes[$database2];

		$sync = new self;

		$sync->doSync( $db1, $db2 );
	}

	/**
	 * Returns a translation map for the writer pair.
	 *
	 * @return array
	 */
	private function getTranslations()
	{
		$longText = str_repeat( 'lorem ipsum', 9000 );

		$testmap = array(
			false, 1, 2.5, -10,
			1000, 'abc', $longText, '2010-10-10',
			'2010-10-10 10:00:00', '10:00:00', 'POINT(1 2)'
		);

		$this->defaultCode = $this->targetWriter->scanType( 'string' );

		foreach ( $testmap as $v ) {
			$code        = $this->sourceWriter->scanType( $v, true );
			$translation = $this->targetWriter->scanType( $v, true );

			if ( !isset( $this->translations[$code] ) ) {
				$this->translations[$code] = $translation;
			}

			if ( $translation > $this->translations[$code] && $translation < 50 ) {
				$this->translations[$code] = $translation;
			}
		}

		// Fix narrow translations SQLiteT stores date as double. (double != really double)
		if ( get_class( $this->sourceWriter ) === 'RedBean_QueryWriter_SQLiteT' ) {
			// Use magic number in case writer not loaded.
			$this->translations[1] = $this->defaultCode;
		}
	}

	/**
	 * Adds missing tables to target database.
	 *
	 * @return void
	 */
	private function addMissingTables()
	{
		foreach ( $this->missingTables as $missingTable ) {
			$this->targetWriter->createTable( $missingTable );
		}
	}

	/**
	 * Synchronizes the tables and columns.
	 *
	 * @return void
	 */
	private function syncTablesAndColumns()
	{
		foreach ( $this->sourceTables as $sourceTable ) {
			$sourceColumns = $this->sourceWriter->getColumns( $sourceTable );

			$targetColumns = array();
			if ( !in_array( $sourceTable, $this->missingTables ) ) {
				$targetColumns = $this->targetWriter->getColumns( $sourceTable );
			}

			unset( $sourceColumns['id'] );

			foreach ( $sourceColumns as $sourceColumn => $sourceType ) {
				if ( substr( $sourceColumn, -3 ) === '_id' ) {
					$targetCode = $this->targetWriter->getTypeForID();
				} else {
					$sourceCode = $this->sourceWriter->code( $sourceType, true );
					$targetCode = ( isset( $this->translations[$sourceCode] ) ) ? $this->translations[$sourceCode] : $this->defaultCode;
				}

				if ( !isset( $targetColumns[$sourceColumn] ) ) {
					$this->targetWriter->addColumn( $sourceTable, $sourceColumn, $targetCode );
				}
			}
		}
	}

	/**
	 * Synchronizes the foreign key and unique constraints.
	 *
	 * @return void
	 */
	private function syncConstraints()
	{
		foreach ( $this->sourceTables as $sourceTable ) {
			$sourceColumns = $this->sourceWriter->getColumns( $sourceTable );

			// Don't delete sourceType, sourceColumn needs to be the key!
			foreach ( $sourceColumns as $sourceColumn => $sourceType ) {
				if ( substr( $sourceColumn, -3 ) !== '_id' ) continue;

				$fkTargetType  = substr( $sourceColumn, 0, strlen( $sourceColumn ) - 3 );
				$fkType        = $sourceTable;
				$fkField       = $sourceColumn;
				$fkTargetField = 'id';
				$this->targetWriter->addFK( $fkType, $fkTargetType, $fkField, $fkTargetField );
			}

			// Is it a link table? -- Add Unique constraint and FK constraint
			if ( strpos( $sourceTable, '_' ) !== false ) {
				$this->targetWriter->addUniqueIndex( $sourceTable, array_keys( $sourceColumns ) );

				$types = explode( '_', $sourceTable );

				$this->targetWriter->addConstraintForTypes(
					$this->oodb->dispense( $types[0] )->getMeta( 'type' ),
					$this->oodb->dispense( $types[1] )->getMeta( 'type' ) );
			}
		}
	}

	/**
	 * Initializes the Sync plugin for usage.
	 *
	 * @param RedBean_Toolbox $source toolbox of source database
	 * @param RedBean_Toolbox $target toolbox of target database
	 *
	 * @return void
	 */
	private function initialize( RedBean_Toolbox $source, RedBean_Toolbox $target )
	{
		$this->oodb          = $target->getRedBean();

		$this->sourceWriter  = $source->getWriter();
		$this->targetWriter  = $target->getWriter();

		$this->sourceTables  = $this->sourceWriter->getTables();
		$this->targetTables  = $this->targetWriter->getTables();
		$this->missingTables = array_diff( $this->sourceTables, $this->targetTables );

		$this->translations  = array();
	}

	/**
	 * Captures the SQL required to adjust source database to match
	 * schema of target database and feeds this sql code to the
	 * adapter of the target database.
	 *
	 * @param RedBean_Toolbox $source toolbox of source database
	 * @param RedBean_Toolbox $target toolbox of target database
	 *
	 * @return void
	 */
	public function doSync( RedBean_Toolbox $source, RedBean_Toolbox $target )
	{
		$this->initialize( $source, $target );

		$this->getTranslations();

		$this->addMissingTables();

		$this->syncTablesAndColumns();
		$this->syncConstraints();
	}
}
