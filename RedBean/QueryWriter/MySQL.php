<?php 
/**
 * RedBean MySQLWriter
 * @package 		RedBean/QueryWriter/MySQL.php
 * @description		Writes Queries for MySQL Databases
 * @author			Gabor de Mooij
 * @license			BSD
 */
class QueryWriter_MySQL implements QueryWriter {
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryCreateTable( $options=array() ) {
		
		$engine = $options["engine"];
		$table = $options["table"];
		
		if ($engine=="myisam") {
						
			//this fellow has no table yet to put his beer on!
			$createtableSQL = "
			 CREATE TABLE `$table` (
			`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
			 PRIMARY KEY ( `id` )
			 ) ENGINE = MYISAM 
			";
		}
		else {
			$createtableSQL = "
			 CREATE TABLE `$table` (
			`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
			 PRIMARY KEY ( `id` )
			 ) ENGINE = InnoDB 
			";
			
		}
		return $createtableSQL;
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryWiden( $options ) {
		extract($options);
		return "ALTER TABLE `$table` CHANGE `$column` `$column` $newtype ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryAddColumn( $options ) {
		extract($options);
		return "ALTER TABLE `$table` ADD `$column` $type ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryUpdate( $options ) {
		extract($options);
		$update = array();
		foreach($updatevalues as $u) {
			$update[] = " `".$u["property"]."` = \"".$u["value"]."\" ";
		}
		return "UPDATE `$table` SET ".implode(",",$update)." WHERE id = ".$id;
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryInsert( $options ) {
		
		extract($options);
		
		foreach($insertcolumns as $k=>$v) {
			$insertcolumns[$k] = "`".$v."`";
		}
		
		foreach($insertvalues as $k=>$v) {
			$insertvalues[$k] = "\"".$v."\"";
		}
		
		$insertSQL = "INSERT INTO `$table` 
					  ( id, ".implode(",",$insertcolumns)." ) 
					  VALUES( null, ".implode(",",$insertvalues)." ) ";
		return $insertSQL;
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryCreate( $options ) {
		extract($options);
		return "INSERT INTO `$table` VALUES(null) ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryInferType( $options ) {
		extract($options);
		$v = "\"".$value."\"";
		$checktypeSQL = "insert into dtyp VALUES(null,$v,$v,$v,$v,$v )";
		return $checktypeSQL;
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryReadType( $options ) {
		extract($options);
		return "select tinyintus,intus,ints,varchar255,`text` from dtyp where id=$id";
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	private function getQueryResetDTYP() {
		return "truncate table dtyp";	
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryRegisterTable( $options ) {
		extract( $options );
		return "replace into redbeantables values (null, \"$table\") ";
	}
	
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryUnregisterTable( $options ) {
		extract( $options );
		return "delete from redbeantables where tablename = \"$table\" ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryRelease( $options ) {
		extract( $options );
		return "DELETE FROM locking WHERE fingerprint=\"".$key."\" ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryRemoveExpirLock( $options ) {
		extract( $options );
		return "DELETE FROM locking WHERE expire < ".(time()-$locktime);
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQuerySelectLock( $options ) {
		extract( $options );
		return	"SELECT id FROM locking WHERE id=$id AND  tbl=\"$table\" AND fingerprint=\"".$key."\" ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryUpdateExpirLock( $options ) {
		extract( $options );
		return "UPDATE locking SET expire=".$time." WHERE id =".$id;
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryAQLock( $options ) {
		extract($options);
		return "INSERT INTO locking VALUES(\"$table\",$id,\"".$key."\",\"".$time."\") ";	
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryGetBean($options) {
		extract($options);
		return "SELECT * FROM `$type` WHERE id = $id ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryBeanExists( $options ) {
		extract($options);
		return "select count(*) from `$type` where id=$id";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryCount($options) {
		extract($options);
		return "select count(*) from `$type`";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryDistinct($options) {
		extract($options);
		return "SELECT id FROM `$type` GROUP BY $field";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryStat( $options ) {
		extract($options);
		return "select $stat(`$field`) from `$type`";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryFastLoad( $options ) {
		extract( $options );
		return "SELECT * FROM `$type` WHERE id IN ( ".implode(",", $ids)." ) ORDER BY FIELD(id,".implode(",", $ids).") ASC		";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryWhere($options) {
		extract($options);
		return "select `$table`.id from $table where ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryFind($options) {

			extract($options);
			$db = RedBean_OODB::$db;
 			$findSQL = "SELECT id FROM `$tbl` WHERE ";
      
	      
	      foreach($bean as $p=>$v) {
	        if ($p === "type" || $p === "id") continue;
	        $p = $db->escape($p);
	        $v = $db->escape($v);
	        if (isset($searchoperators[$p])) {
	 
	          if ($searchoperators[$p]==="LIKE") {
	            $part[] = " `$p`LIKE \"%$v%\" ";
	          }
	          else {
	            $part[] = " `$p` ".$searchoperators[$p]." \"$v\" ";
	          }
	        }
	        else {
	 
	        }
	      }
	 
	      if ($extraSQL) {
	        $findSQL .= @implode(" AND ",$part) . $extraSQL;
	      }
	      else {
	        $findSQL .= @implode(" AND ",$part) . " ORDER BY $orderby LIMIT $start, $end ";
	      }
	 
	      return $findSQL;
      
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryList($options) {
		
		extract($options);
		$db = RedBean_OODB::$db;
		if ($extraSQL) {
 
			$listSQL = "SELECT * FROM ".$db->escape($type)." ".$extraSQL;
 
		}
		else {
 
			$listSQL = "SELECT * FROM ".$db->escape($type)."
			ORDER BY ".$orderby;
 
			if ($end !== false && $start===false) {
				$listSQL .= " LIMIT ".intval($end);
			}
 
			if ($start !== false && $end !== false) {
				$listSQL .= " LIMIT ".intval($start).", ".intval($end);
			}
 
			if ($start !== false && $end===false) {
				$listSQL .= " LIMIT ".intval($start).", 18446744073709551615 ";
			}
 		}
 		
 		return $listSQL;
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryAddAssocNow( $options ) {
		extract($options);
		return "REPLACE INTO `$assoctable` VALUES(null,$id1,$id2) ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryUnassoc( $options ) {
		extract($options);
		return "DELETE FROM `$assoctable` WHERE ".$t1."_id = $id1 AND ".$t2."_id = $id2 ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryCreateAssoc($options) {
		
		extract($options);
		
		return "
		 CREATE TABLE `$assoctable` (
		`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
		`".$t1."_id` INT( 11 ) UNSIGNED NOT NULL,
		`".$t2."_id` INT( 11 ) UNSIGNED NOT NULL,
		 PRIMARY KEY ( `id` )
		 ) ENGINE = ".$engine."; 
		";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryUntree( $options ) {
		extract($options);
		return "DELETE FROM `$assoctable2` WHERE
				(parent_id = $idx1 AND child_id = $idx2) OR
				(parent_id = $idx2 AND child_id = $idx1) ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryAddAssoc($options) {
		extract( $options );
		return "ALTER TABLE `$assoctable` ADD UNIQUE INDEX `u_$assoctable` (`".$t1."_id`, `".$t2."_id` ) ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryGetAssoc($options) {
		extract( $options );
		return "SELECT `".$t2."_id` FROM `$assoctable` WHERE `".$t1."_id` = $id ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryTrash( $options ) {
		extract( $options );
		return "DELETE FROM ".$table." WHERE id = $id ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryDeltree( $options ) {
		extract( $options );
		return "DELETE FROM $table WHERE parent_id = $id OR child_id = $id ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryUnassocAllT1( $options ) {
		extract( $options );
		return "DELETE FROM $table WHERE ".$t."_id = $id ";
	}

	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryUnassocAllT2( $options ) {
		extract( $options );
		return "DELETE FROM $table WHERE ".$t."2_id = $id ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryDeltreeType($options) {
		extract( $options );
		return "DELETE FROM $assoctable WHERE parent_id = $id  OR child_id = $id ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryUnassocType1($options) {
		extract( $options );
		return "DELETE FROM $assoctable WHERE ".$t1."_id = $id ";	
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryUnassocType2($options) {
		extract( $options );
		return "DELETE FROM $assoctable WHERE ".$t1."2_id = $id ";	
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryCreateTree( $options ) {
		extract( $options );		
		return "
				 CREATE TABLE `$assoctable` (
				`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
				`parent_id` INT( 11 ) UNSIGNED NOT NULL,
				`child_id` INT( 11 ) UNSIGNED NOT NULL,
				 PRIMARY KEY ( `id` )
				 ) ENGINE = ".$engine."; 
				";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryUnique( $options ) {
		extract( $options );		
		return "ALTER TABLE `$assoctable` ADD UNIQUE INDEX `u_$assoctable` (`parent_id`, `child_id` ) ";
	} 
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryAddChild( $options ) {
		extract( $options );	
		return "REPLACE INTO `$assoctable` VALUES(null,$pid,$cid) ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryGetChildren( $options ) {
		extract( $options );
		return "SELECT `child_id` FROM `$assoctable` WHERE `parent_id` = $pid ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryGetParent( $options ) {
		extract( $options );
		return "SELECT `parent_id` FROM `$assoctable` WHERE `child_id` = $cid ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryRemoveChild( $options ) {
		extract( $options );
		return "DELETE FROM `$assoctable` WHERE
				( parent_id = $pid AND child_id = $cid ) ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryNumRelated( $options ) {
		extract( $options );
		return "
						SELECT COUNT(1) 
						FROM `$assoctable` WHERE 
						".$t1."_id = $id
					";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryDescribe( $options ) {
		extract( $options );
		return "describe `$table`";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryDropTables( $options ) {
		extract($options);
		return "drop tables ".implode(",",$tables);
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryDropColumn( $options ) {
		extract($options);
		return "ALTER TABLE `$table` DROP `$property`";	
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryGetNull( $options ) {
		extract($options);
		return "SELECT count(*) FROM `$table` WHERE `$col` IS NOT NULL ";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryTestColumn( $options ) {
		extract($options);
		return "alter table `$table` add __test  ".$type;
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryUpdateTest( $options ) {
		extract($options);
		return "update `$table` set __test=`$col`";
	}

	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryMeasure( $options ) {
		extract($options);
		return "select count(*) as df from `$table` where
				strcmp(`$col`,__test) != 0 AND `$col` IS NOT NULL";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryRemoveTest($options) {
		extract($options);
		return "alter table `$table` change `$col` `$col` ".$type;
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryDropTest($options) {
		extract($options);
		return "alter table `$table` drop __test";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getQueryVariance($options) {
		extract($options);
		return "select count( distinct $col ) from $table";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getIndex1($options) {
		extract($options);
		return "ALTER IGNORE TABLE `$table` ADD INDEX $indexname (`$col`)";
	}
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getIndex2($options) {
		extract($options);
		return "ALTER IGNORE TABLE `$table` DROP INDEX $indexname";
	}
	
	
	/**
	 * 
	 * @param $options
	 * @return unknown_type
	 */
	private function getDropType($options) {
		extract($options);
		return "DELETE FROM ".$type." WHERE 1=1";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see RedBean/QueryWriter#getQuery()
	 */
	public function getQuery( $queryname, $params=array() ) {
		//echo "<b style='color:yellow'>$queryname</b>";
		switch($queryname) {
			case "create_table":
				return $this->getQueryCreateTable($params);
			break;
			case "widen_column":
				return $this->getQueryWiden($params);
			break;
			case "add_column":
				return $this->getQueryAddColumn($params);
			break;
			case "update":
				return $this->getQueryUpdate($params);
			break;
			case "insert":
				return $this->getQueryInsert($params);
			break;
			case "create":	
				return $this->getQueryCreate($params);
			break;
			case "infertype":
				return $this->getQueryInferType($params);
			break;
			case "readtype":
				return $this->getQueryReadType($params);
			break;
			case "reset_dtyp":
				return $this->getQueryResetDTYP();
			break;
			case "prepare_innodb":
				return "SET autocommit=0";
			break;
			case "prepare_myisam":
				return "SET autocommit=1";
			break;
			case "starttransaction":
				return "START TRANSACTION";
			break;
			case "setup_dtyp":
				return "
				CREATE TABLE IF NOT EXISTS `dtyp` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `tinyintus` tinyint(3) unsigned NOT NULL,
				  `intus` int(11) unsigned NOT NULL,
				  `ints` bigint(20) NOT NULL,
				  
				  `varchar255` varchar(255) NOT NULL,
				  `text` text NOT NULL,
				  PRIMARY KEY  (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
				";
			break;
			case "clear_dtyp":
				return "drop tables dtyp";
			break;
			case "setup_locking":
				return "
				CREATE TABLE IF NOT EXISTS `locking` (
				  `tbl` varchar(255) NOT NULL,
				  `id` bigint(20) NOT NULL,
				  `fingerprint` varchar(255) NOT NULL,
				  `expire` int(11) NOT NULL,
				  UNIQUE KEY `tbl` (`tbl`,`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=latin1;
				";
			break;
			case "setup_tables":
				return "
				 CREATE TABLE IF NOT EXISTS `redbeantables` (
				 `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
				 `tablename` VARCHAR( 255 ) NOT NULL ,
				 PRIMARY KEY ( `id` ),
				 UNIQUE KEY `tablename` (`tablename`)
				 ) ENGINE = MYISAM 
				";
			break;
			case "show_tables":
				return "show tables";
			break;
			case "show_rtables":
				return "select tablename from redbeantables";
			break;
			case "register_table":
				return $this->getQueryRegisterTable( $params );
			break;
			case "unregister_table":
				return $this->getQueryUnregisterTable( $params );
			break;
			case "release":
				return $this->getQueryRelease( $params );
			break;
			case "remove_expir_lock":
				return $this->getQueryRemoveExpirLock( $params );
			break;
			case "update_expir_lock":
				return $this->getQueryUpdateExpirLock( $params );
			break;
			case "aq_lock":
				return $this->getQueryAQLock( $params );
			break;
			case "get_lock":
				return $this->getQuerySelectLock( $params);
			break;
			case "get_bean":
				return $this->getQueryGetBean( $params );
			break;
			case "bean_exists":
				return $this->getQueryBeanExists($params);
			break;
			case "count":
				return $this->getQueryCount($params);
			break;
			case "distinct":
				return $this->getQueryDistinct($params);
			break;
			case "stat":
				return $this->getQueryStat($params);
			break;
			case "releaseall":
				return "TRUNCATE locking";
			break;
			case "fastload":
				return $this->getQueryFastLoad($params);
			break;
			case "where":
				return $this->getQueryWhere($params);
			break;
			case "find":
				return $this->getQueryFind( $params);
			break;
			case "list":
				return $this->getQueryList( $params);
			break;
			case "create_assoc":
				return $this->getQueryCreateAssoc( $params );
			break;
			case "add_assoc":
				return $this->getQueryAddAssoc( $params );
			break;
			case "add_assoc_now":
				return $this->getQueryAddAssocNow( $params );
			break;
			case "unassoc":
				return $this->getQueryUnassoc( $params );
			break;
			case "untree":
				return $this->getQueryUntree( $params );
			break;
			case "get_assoc":
				return $this->getQueryGetAssoc( $params );
			break;
			case "trash":
				return $this->getQueryTrash( $params );
			break;
			case "deltree":
				return $this->getQueryDeltree( $params );
			break;
			case "unassoc_all_t1":
				return $this->getQueryUnassocAllT1( $params );
			break;
			case "unassoc_all_t2":
				return $this->getQueryUnassocAllT2( $params );
			break;
			case "deltreetype":
				return $this->getQueryDeltreeType( $params );
			break;
			case "unassoctype1":
				return $this->getQueryUnassocType1( $params );
			break;
			case "unassoctype2":
				return $this->getQueryUnassocType2( $params );
			break;
			case "create_tree":
				return $this->getQueryCreateTree( $params );
			break;
			case "unique":
				return $this->getQueryUnique( $params );
			break;
			case "add_child":
				return $this->getQueryAddChild( $params );
			break;
			case "get_children":
				return $this->getQueryGetChildren( $params );
			break;
			case "get_parent":
				return $this->getQueryGetParent( $params );
			break;
			case "remove_child":
				return $this->getQueryRemoveChild( $params );
			break;
			case "num_related":
				return $this->getQueryNumRelated( $params );
			break;
			case "drop_tables":
				return $this->getQueryDropTables( $params );
			break;
			case "truncate_rtables":
				return "truncate redbeantables";
			break;
			case "drop_column":
				return $this->getQueryDropColumn( $params );
			break;
			case "describe":
				return $this->getQueryDescribe( $params );
			break;
			case "get_null":
				return $this->getQueryGetNull( $params );
			break;
			case "test_column":
				return $this->getQueryTestColumn( $params );
			break;
			case "update_test":
				return $this->getQueryUpdateTest( $params );
			break;
			case "measure":
				return $this->getQueryMeasure( $params );
			break;
			case "remove_test":
				return $this->getQueryRemoveTest($params);
			break;
			case "drop_test":
				return $this->getQueryDropTest($params);
			break;
			case "variance":
				return $this->getQueryVariance($params);
			break;
			case "index1":
				return $this->getIndex1($params);
			break;
			case "index2":
				return $this->getIndex2($params);
			break;
			case "drop_type":
				return $this->getDropType($params);
			break;
			default:
			throw new Exception("QueryWriter has no support for Query:".$queryname);
		}
		
		
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	public function getQuote() {
		return "\"";	
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	public function getEscape() {
		return "`";	
	}
	
}