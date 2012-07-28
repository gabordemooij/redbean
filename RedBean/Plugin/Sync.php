<?php
/**
 * Sync
 *
 * @file                       RedBean/Plugin/Sync.php
 * @description                Plugin for Synchronizing databases.
 *
 * @author                     Gabor de Mooij
 * @license                    BSD
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
	public function sync(RedBean_Toolbox $source, RedBean_Toolbox $target) {

		$sourceWriter = $source->getWriter();
		$targetWriter = $target->getWriter();

		$longText = str_repeat('lorem ipsum', 9000);
		$testmap = array(
			false, 1, 2.5, -10, 1000, 'abc', $longText, '2010-10-10', '2010-10-10 10:00:00', '10:00:00', 'POINT(1 2)'
		);
		$translations = array();
		foreach ($testmap as $v) {
			$code = $sourceWriter->scanType($v, true);
			$translation = $targetWriter->scanType($v, true);
			if (!isset($translations[$code]))
				$translations[$code] = $translation;
			if ($translation > $translations[$code])
				$translations[$code] = $translation;
		}
		$defaultCode = $targetWriter->scanType('string');
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
}
