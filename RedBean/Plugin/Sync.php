<?php
/**
 * Sync
 *
 * @file 			RedBean/Plugin/Sync.php
 * @description		Plugin for Synchronizing databases.
 * 					
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_Sync implements RedBean_Observer {
	
	/**
	 * List of SQL statements to process for Sync.
	 * @var array
	 */
	private $sqlCode;
	
	
	/**
	 * Captures the SQL required to adjust source database to match
	 * schema of target database.
	 * 
	 * @param RedBean_Toolbox $source toolbox of source database
	 * @param RedBean_Toolbox $target toolbox of target database
	 */
	public function sync( RedBean_Toolbox $source, RedBean_Toolbox $target) {
		
		$this->sqlCode = array();
		
		$sourceWriter = $source->getWriter();
		$targetWriter = $target->getWriter();
		
		$sourceTables = $sourceWriter->getTables();
		$targetTables = $targetWriter->getTables();
		
		$nullAdapter = new RedBean_Adapter_NullAdapter;
		
		if ($targetWriter instanceof RedBean_QueryWriter_SQLiteT) {
			$nullWriter = new RedBean_QueryWriter_SQLiteT( $nullAdapter );
		}
		if ($targetWriter instanceof RedBean_QueryWriter_MySQL) {
			$nullWriter = new RedBean_QueryWriter_MySQL( $nullAdapter );
		}
		if ($targetWriter instanceof RedBean_QueryWriter_PostgreSQL) {
			$nullWriter = new RedBean_QueryWriter_PostgreSQL( $nullAdapter );
		}
		
		
		$nullAdapter->setReferenceAdapter($target->getDatabaseAdapter());
		
		
		$nullAdapter->addEventListener('sql_exec', $this);
		
		$missingTables = array_diff($sourceTables,$targetTables);
		foreach($missingTables as $missingTable) {
			
			$nullWriter->createTable($missingTable);
			
		}
		
		foreach($sourceTables as $sourceTable) {
			$sourceColumns = $sourceWriter->getColumns($sourceTable);
			if (in_array($sourceTable,$missingTables)) {
				$targetColumns=array();
			}
			else {
				$targetColumns = $targetWriter->getColumns($sourceTable);
			}
			unset($sourceColumns['id']);
			
			//Is it a link table? -- Add Unique constraint and FK constraint
			if (strpos($sourceTable,'_')!==false) {
				$nullWriter->addUniqueIndex($sourceTable, array_keys($sourceColumns));
				$types = explode('_',$sourceTable);
				$nullWriter->addConstraint(R::dispense($types[0]),R::dispense($types[1]));
			}
			
			foreach($sourceColumns as $sourceColumn => $sourceType) {
				$sourceCode = $sourceWriter->code($sourceType,true);
				if (!isset($targetColumns[$sourceColumn])) {
					$nullWriter->addColumn($sourceTable, $sourceColumn, $sourceCode);
				}
				else {
					if ($targetColumns[$sourceColumn]!==$sourceType) {
						$targetCode = $sourceWriter->code($targetColumns[$sourceColumn],true);
						if ($sourceCode > $targetCode && $targetCode != 99) {
							$nullWriter->widenColumn($sourceType, $sourceColumn, $sourceCode);
						}
					}
				}
			}
		}
		
		
		return $this->sqlCode;
		
	}
	
	/**
	 * Event hook for NULL Adapter. This hook receives the SQL for the NULL adapter
	 * and stores the queries in an SQL Code property.
	 * 
	 * @param string $event  string ID telling about the event that happened
	 * @param object $sender object that caused the event
	 */
	public function onEvent($event, $sender) {
		$this->sqlCode[] = $sender->getSQL();
	}
	
}