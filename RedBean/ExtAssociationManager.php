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
	 *
	 */
	public function extAssociate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, RedBean_OODBBean $baseBean ) {
		$table = $this->getTable( array($bean1->getMeta("type") , $bean2->getMeta("type")) );
		$baseBean->setMeta("type", $table );
		return $this->associateBeans( $bean1, $bean2, $baseBean );
	}
}