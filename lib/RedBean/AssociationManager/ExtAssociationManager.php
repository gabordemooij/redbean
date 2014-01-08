<?php 

namespace RedBean\AssociationManager; 

use RedBean\AssociationManager;
use RedBean\OODBBean; 

/**
 * Extended Association Manager
 *
 * @file    RedBean/ExtAssociationManager.php
 * @desc    Manages complex bean associations.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @deprecated
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class ExtAssociationManager extends AssociationManager
{

	/**
	 * @deprecated
	 *
	 * Associates two beans and allows you to specify a base bean to form the
	 * link between the two.
	 *
	 * This method has been deprecated, please use $bean->link instead to form
	 * many-to-many associations with additional properties.
	 *
	 * @param OODBBean $bean1    the first bean you want to associate
	 * @param OODBBean $bean2    the second bean you want to associate
	 * @param OODBBean $baseBean the link bean
	 *
	 * @return array
	 */
	public function extAssociate( OODBBean $bean1, OODBBean $bean2, OODBBean $baseBean )
	{
		$table = $this->getTable( array( $bean1->getMeta( 'type' ), $bean2->getMeta( 'type' ) ) );

		$baseBean->setMeta( 'type', $table );

		return $this->associateBeans( $bean1, $bean2, $baseBean );
	}

	/**
	 * @deprecated
	 *
	 * Simplified version of extAssociate().
	 * Associates two beans $bean1 and $bean2 with additional properties defined in
	 * third parameter $extra. This third parameter can be either an array, a
	 * JSON string, a single value (will be assigned to property 'extra') or a
	 * bean.
	 *
	 * This method has been deprecated, please use $bean->link instead to form
	 * many-to-many associations with additional properties.
	 *
	 * @param OODBBean $bean1 the first bean you want to associate
	 * @param OODBBean $bean2 the second bean you want to associate
	 * @param mixed            $extra one or more additional properties and values
	 *
	 * @return array
	 */
	public function extAssociateSimple( $beans1, $beans2, $extra = NULL )
	{
		if ( !is_array( $extra ) ) {
			$info = json_decode( $extra, TRUE );

			if ( !$info ) $info = array( 'extra' => $extra );
		} else {
			$info = $extra;
		}

		$bean = $this->oodb->dispense( 'xtypeless' );
		$bean->import( $info );

		return $this->extAssociate( $beans1, $beans2, $bean );
	}
}
