<?php
/**
 * Sync
 * @file			RedBean/Plugin/Sync.php
 * @desc			Plugin for Synchronizing databases.
 * 
 * @plugin			public static function syncSchema($from, $to) { return RedBean_Plugin_Sync::syncSchema($from, $to); }
 *
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_Sync implements RedBean_Plugin {
	/**
	 * Captures the SQL required to adjust source database to match
	 * schema of target database and feeds this sql code to the
	 * adapter of the target database.
	 *
	 * @param RedBean_Toolbox $source toolbox of source database
	 * @param RedBean_Toolbox $target toolbox of target database
	 */
	public function doSync(RedBean_Toolbox $source, RedBean_Toolbox $target) {
		$sourceWriter = $source->getWriter();
		$targetWriter = $target->getWriter();
		$longText = str_repeat('lorem ipsum', 9000);
		$testmap = array(
			false, 1, 2.5, -10, 1000, 'abc', $longText, '2010-10-10', '2010-10-10 10:00:00', '10:00:00', 'POINT(1 2)'
		);
		$translations = array();
		$defaultCode = $targetWriter->scanType('string');
		foreach ($testmap as $v) {
			$code = $sourceWriter->scanType($v, true);
			$translation = $targetWriter->scanType($v, true);
			if (!isset($translations[$code]))
				$translations[$code] = $translation;
			if ($translation > $translations[$code] && $translation < 50)
				$translations[$code] = $translation;
		}
		//Fix narrow translations SQLiteT stores date as double. (double != really double)
		if (get_class($sourceWriter) === 'RedBean_QueryWriter_SQLiteT') {
			$translations[1] = $defaultCode;  //use magic number in case writer not loaded.
		}
		$sourceTables = $sourceWriter->getTables();
		$targetTables = $targetWriter->getTables();
		$missingTables = array_diff($sourceTables, $targetTables);
		foreach ($missingTables as $missingTable) {
			$targetWriter->createTable($missingTable);
		}
		//First run, create tables and columns
		foreach ($sourceTables as $sourceTable) {
			$sourceColumns = $sourceWriter->getColumns($sourceTable);
			if (in_array($sourceTable, $missingTables)) {
				$targetColumns = array();
			} else {
				$targetColumns = $targetWriter->getColumns($sourceTable);
			}
			unset($sourceColumns['id']);
			foreach ($sourceColumns as $sourceColumn => $sourceType) {
				if (substr($sourceColumn, -3) === '_id') {
					$targetCode = $targetWriter->getTypeForID();
				} else {
					$sourceCode = $sourceWriter->code($sourceType, true);
					$targetCode = (isset($translations[$sourceCode])) ? $translations[$sourceCode] : $defaultCode;
				}
				if (!isset($targetColumns[$sourceColumn])) {
					$targetWriter->addColumn($sourceTable, $sourceColumn, $targetCode);
				}
			}
		}
		foreach ($sourceTables as $sourceTable) {
			$sourceColumns = $sourceWriter->getColumns($sourceTable);
			foreach ($sourceColumns as $sourceColumn => $sourceType) {
				if (substr($sourceColumn, -3) === '_id') {
					$fkTargetType = substr($sourceColumn, 0, strlen($sourceColumn) - 3);
					$fkType = $sourceTable;
					$fkField = $sourceColumn;
					$fkTargetField = 'id';
					$targetWriter->addFK($fkType, $fkTargetType, $fkField, $fkTargetField);
				}
			}
			//Is it a link table? -- Add Unique constraint and FK constraint
			if (strpos($sourceTable, '_') !== false) {
				$targetWriter->addUniqueIndex($sourceTable, array_keys($sourceColumns));
				$types = explode('_', $sourceTable);
				$targetWriter->addConstraint(R::dispense($types[0]), R::dispense($types[1]));
			}
		}
	}
	/**
	 * Performs a database schema sync. For use with facade.
	 * Instead of toolboxes this method accepts simply string keys and is static.
	 * 
	 * @param string $database1 the source database
	 * @param string $database2 the target database
	 */
	public static function syncSchema($database1, $database2) {
		if (!isset(RedBean_Facade::$toolboxes[$database1])) throw new RedBean_Exception_Security('No database for this key: '.$database1);
		if (!isset(RedBean_Facade::$toolboxes[$database2])) throw new RedBean_Exception_Security('No database for this key: '.$database2);
		$db1 = RedBean_Facade::$toolboxes[$database1];
		$db2 = RedBean_Facade::$toolboxes[$database2];
		$sync = new self;
		$sync->doSync($db1, $db2);
	}
}