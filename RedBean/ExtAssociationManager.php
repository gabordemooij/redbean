<?php
/**
 * RedBean Extended Association
 * @file			RedBean/ExtAssociationManager.php
 * @description		Manages complex bean associations.
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_ExtAssociationManager extends RedBean_AssociationManager {

	/**
	 * Associates two beans with eachother.
	 *
	 * @param RedBean_OODBBean $bean1 bean 1
	 * @param RedBean_OODBBean $bean2 bean 2
	 *
	 */
	public function extAssociate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, RedBean_OODBBean $baseBean ) {
		$table = $this->getTable( array($bean1->getMeta("type") , $bean2->getMeta("type")) );
		$baseBean->setMeta("type", $table );
		return $this->associateBeans( $bean1, $bean2, $baseBean );
	}


}