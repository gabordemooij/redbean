<?php
/**
 * RedBean Extended Association
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
	 * Associates two beans with eachother. This method connects two beans with eachother, just
	 * like the other associate() method in the Association Manager. The difference is however
	 * that this method accepts a base bean, this bean will be used as the basis of the
	 * association record in the link table. You can thus add additional properties and
	 * even foreign keys.
	 *
	 * @param RedBean_OODBBean $bean1 bean 1
	 * @param RedBean_OODBBean $bean2 bean 2
	 * @param RedBean_OODBBean $bbean base bean for association record
	 *
	 * @return void
	 */
	public function extAssociate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, RedBean_OODBBean $baseBean ) {
		$table = $this->getTable( array($bean1->getMeta('type') , $bean2->getMeta('type')) );
		$baseBean->setMeta('type', $table );
		return $this->associateBeans( $bean1, $bean2, $baseBean );
	}
	/**
	 * Deprecated
	 * 
	 * @param RedBean_OODBBean $beans1 bean
	 * @param RedBean_OODBBean $beans2 bean
	 * @param mixed $extra
	 * @return mixed 
	 */
	public function extAssociateSimple( $beans1, $beans2, $extra = null) {
		if (!is_array($extra)) {
			$info = json_decode($extra,true);
			if (!$info) $info = array('extra'=>$extra);
		} else {
			$info = $extra;
		}
		$bean = $this->oodb->dispense('xtypeless');
		$bean->import($info);
		return $this->extAssociate($beans1, $beans2, $bean);
	}
}