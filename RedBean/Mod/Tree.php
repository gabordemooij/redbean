<?php

class RedBean_Mod_Tree extends RedBean_Mod {


    public function add( RedBean_OODBBean $parent, RedBean_OODBBean $child ) {
                	//get a database
			$db = $this->provider->getDatabase();

			//first we check the beans whether they are valid
			$parent = $this->provider->getBeanChecker()->checkBeanForAssoc($parent);
			$child = $this->provider->getBeanChecker()->checkBeanForAssoc($child);

			$this->provider->getLockManager()->openBean( $parent, true );
			$this->provider->getLockManager()->openBean( $child, true );


			//are parent and child of the same type?
			if ($parent->type !== $child->type) {
				throw new RedBean_Exception_InvalidParentChildCombination();
			}

			$pid = intval($parent->id);
			$cid = intval($child->id);

			//infer the association table
			$assoctable = "pc_".$db->escape($parent->type."_".$parent->type);

			//check whether this assoctable already exists
			if (!$this->provider->getFacade()->isFrozen()) {
				$alltables = $this->provider->getTableRegister()->getTables();
				if (!in_array($assoctable, $alltables)) {
					//no assoc table does not exist, create it..
					$assoccreateSQL = $this->provider->getWriter()->getQuery("create_tree",array(
						"engine"=>$this->provider->getFacade()->getEngine(),
						"assoctable"=>$assoctable
					));
					$db->exec( $assoccreateSQL );
					//add a unique constraint
					$db->exec( $this->provider->getWriter()->getQuery("unique", array(
						"assoctable"=>$assoctable
					)) );
					$this->provider->getTableRegister()->register( $assoctable );
				}
			}

			//now insert the association record
			$assocSQL = $this->provider->getWriter()->getQuery("add_child",array(
				"assoctable"=>$assoctable,
				"pid"=>$pid,
				"cid"=>$cid
			));
			$db->exec( $assocSQL );

		}

        public function getChildren( RedBean_OODBBean $parent ) {

			//get a database
			$db = $this->provider->getDatabase();

			//first we check the beans whether they are valid
			$parent = $this->provider->getBeanChecker()->checkBeanForAssoc($parent);

			$pid = intval($parent->id);

			//infer the association table
			$assoctable = "pc_".$db->escape( $parent->type . "_" . $parent->type );

			//check whether this assoctable exists
			$alltables = $this->provider->getTableRegister()->getTables();
			if (!in_array($assoctable, $alltables)) {
				return array(); //nope, so no children...!
			}
			else {
				$targettype = $parent->type;
				$getassocSQL = $this->provider->getWriter()->getQuery("get_children", array(
					"assoctable"=>$assoctable,
					"pid"=>$pid
				));
				$rows = $db->getCol( $getassocSQL );
				$beans = array();
				if ($rows && is_array($rows) && count($rows)>0) {
					foreach($rows as $i) {
						$beans[$i] = $this->provider->getBeanStore()->get( $targettype, $i, false);
					}
				}
				return $beans;
			}

		}



                public function getParent( RedBean_OODBBean $child ) {
            		//get a database
			$db = $this->provider->getDatabase();

			//first we check the beans whether they are valid
			$child = $this->provider->getBeanChecker()->checkBeanForAssoc($child);

			$cid = intval($child->id);

			//infer the association table
			$assoctable = "pc_".$db->escape( $child->type . "_" . $child->type );
			//check whether this assoctable exists
			$alltables = $this->provider->getTableRegister()->getTables();
			if (!in_array($assoctable, $alltables)) {
				return array(); //nope, so no children...!
			}
			else {
				$targettype = $child->type;

				$getassocSQL = $this->provider->getWriter()->getQuery("get_parent", array(
					"assoctable"=>$assoctable,
					"cid"=>$cid
				));

				$rows = $db->getCol( $getassocSQL );
				$beans = array();
				if ($rows && is_array($rows) && count($rows)>0) {
					foreach($rows as $i) {
						$beans[$i] = $this->provider->getBeanStore()->get( $targettype, $i, false);
					}
				}

				return $beans;
			}

		}


                public function removeChild(RedBean_OODBBean $parent, RedBean_OODBBean $child) {
                    	$db = $this->provider->getDatabase();

			//first we check the beans whether they are valid
			$parent = $this->provider->getBeanChecker()->checkBeanForAssoc($parent);
			$child = $this->provider->getBeanChecker()->checkBeanForAssoc($child);

			$this->provider->getLockManager()->openBean( $parent, true );
			$this->provider->getLockManager()->openBean( $child, true );


			//are parent and child of the same type?
			if ($parent->type !== $child->type) {
				throw new RedBean_Exception_InvalidParentChildCombination();
			}

			//infer the association table
			$assoctable = "pc_".$db->escape( $parent->type . "_" . $parent->type );

			//check whether this assoctable already exists
			$alltables = $this->provider->getTableRegister()->getTables();
			if (!in_array($assoctable, $alltables)) {
				return true; //no association? then nothing to do!
			}
			else {
				$pid = intval($parent->id);
				$cid = intval($child->id);
				$unassocSQL = $this->provider->getWriter()->getQuery("remove_child", array(
					"assoctable"=>$assoctable,
					"pid"=>$pid,
					"cid"=>$cid
				));
				$db->exec($unassocSQL);
			}
		}



}