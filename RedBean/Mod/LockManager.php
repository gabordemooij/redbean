<?php

class RedBean_Mod_LockManager extends RedBean_Mod {

    private $locking = true;
    private $locktime = 10;
    private $pkey = null;

    public function __construct(RedBean_ToolBox_ModHub $provider) {
        $this->pkey = str_replace(".","",microtime(true)."".mt_rand());
        parent::__construct($provider);
    }

    public function getLockingTime() { return $this->locktime; }
     public function setLockingTime( $timeInSecs ) {

        if (is_int($timeInSecs) && $timeInSecs >= 0) {
            $this->locktime = $timeInSecs;
        }
        else {
            throw new RedBean_Exception_InvalidArgument( "time must be integer >= 0" );
        }
    }
    public function openBean($bean, $mustlock = false) {

                        $this->provider->getBeanChecker()->check( $bean);
			//If locking is turned off, or the bean has no persistance yet (not shared) life is always a success!
			if (!$this->getLocking() || $bean->id === 0) return true;
                        $db = $this->provider->getDatabase();

			//remove locks that have been expired...
			$removeExpiredSQL = $this->provider->getWriter()->getQuery("remove_expir_lock", array(
				"locktime"=>$this->getLockingTime()
			));

			$db->exec($removeExpiredSQL);

			$tbl = $db->escape( $bean->type );
			$id = intval( $bean->id );

			//Is the bean already opened for us?
			$checkopenSQL = $this->provider->getWriter()->getQuery("get_lock",array(
				"id"=>$id,
				"table"=>$tbl,
				"key"=>$this->pkey
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
				"key"=>$this->pkey,
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


    public function setLocking( $tf ) {
        $this->locking = $tf;
    }



    public function getLocking() {
        return $this->locking;
    }

    public function unlockAll() {
          $this->provider->getDatabase()->exec($this->provider->getWriter()->getQuery("release",array("key"=>$this->pkey)));
    }

    public function reset() {
        $sql = $this->provider->getWriter()->getQuery("releaseall");
        $this->provider->getDatabase()->exec( $sql );
        return true;
    }

    public function getKey() {
        return $this->pkey;
    }

    public function setKey($key) {
        $this->pkey = $key;
    }

}