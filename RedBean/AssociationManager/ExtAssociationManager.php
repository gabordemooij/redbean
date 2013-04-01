<?php
/**
 * Extended Association Manager
 * 
 * @file			RedBean/ExtAssociationManager.php
 * @desc			Manages complex bean associations.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_AssociationManager_ExtAssociationManager extends RedBean_AssociationManager {
	/**
	 * @deprecated
	 */
	public function extAssociate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, RedBean_OODBBean $baseBean) {
		$table = $this->getTable(array($bean1->getMeta('type') , $bean2->getMeta('type')));
		$baseBean->setMeta('type', $table);
		return $this->associateBeans($bean1, $bean2, $baseBean);
	}
	/**
	 * @deprecated
	 */
	public function extAssociateSimple($beans1, $beans2, $extra = null) {
		if (!is_array($extra)) {
			$info = json_decode($extra, true);
			if (!$info) $info = array('extra' => $extra);
		} else {
			$info = $extra;
		}
		$bean = $this->oodb->dispense('xtypeless');
		$bean->import($info);
		return $this->extAssociate($beans1, $beans2, $bean);
	}
}