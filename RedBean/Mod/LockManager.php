<?php

class RedBean_Mod_LockManager extends RedBean_Mod {


    public function openBean($bean, $mustlock = false) {

			//If locking is turned off, or the bean has no persistance yet (not shared) life is always a success!
			if (!$this->provider->getLocking() || $bean->id === 0) return true;
                        $db = $this->provider->getDatabase();

			//remove locks that have been expired...
			$removeExpiredSQL = $this->provider->getWriter()->getQuery("remove_expir_lock", array(
				"locktime"=>$this->provider->getLockingTime()
			));

			$db->exec($removeExpiredSQL);

			$tbl = $db->escape( $bean->type );
			$id = intval( $bean->id );

			//Is the bean already opened for us?
			$checkopenSQL = $this->provider->getWriter()->getQuery("get_lock",array(
				"id"=>$id,
				"table"=>$tbl,
				"key"=>$this->provider->pkey
			));

			$row = $db->getRow($checkopenSQL);
			if ($row && is_array($row) && count($row)>0) {
				$updateexpstamp = $this->provider->getWriter()->getQuery("update_expir_lock",array(
					"time"=>time(),
					"id"=>$row["id"]
				));
				$db->exec($updateexpstamp);
				return true; //bean is locked for us!
			}

			//If you must lock a bean then the bean must have been locked by a previous call.
			if ($mustlock) {
				throw new RedBean_Exception_FailedAccessBean("Could not acquire a lock for bean $tbl . $id ");
				return false;
			}

			//try to get acquire lock on the bean
			$openSQL = $this->provider->getWriter()->getQuery("aq_lock", array(
				"table"=>$tbl,
				"id"=>$id,
				"key"=>$this->provider->pkey,
				"time"=>time()
			));

			$trials = 0;
			$aff = 0;
			while( $aff < 1 && $trials < 5 ) {
				$db->exec($openSQL);
				$aff = $db->getAffectedRows();
				$trials++;
				if ($aff < 1) usleep(500000); //half a sec
			}

			if ($trials > 4) {
				return false;
			}
			else {
				return true;
			}
    }

}