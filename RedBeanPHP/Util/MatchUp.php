<?php

namespace RedBeanPHP\Util;

use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\Finder;

/**
 * MatchUp Utility
 *
 * Tired of creating login systems and password-forget systems?
 * MatchUp is an ORM-translation of these kind of problems.
 * A matchUp is a match-and-update combination in terms of beans.
 * Typically login related problems are all about a match and
 * a conditional update.
 * 
 * @file    RedBeanPHP/Util/MatchUp.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class MatchUp
{
	/**
	 * @var Toolbox
	 */
	protected $toolbox;

	/**
	 * Constructor.
	 * The MatchUp class requires a toolbox
	 *
	 * @param ToolBox $toolbox
	 */
	public function __construct( ToolBox $toolbox )
	{
		$this->toolbox = $toolbox;
	}

	/**
	 * MatchUp is a powerful productivity boosting method that can replace simple control
	 * scripts with a single RedBeanPHP command. Typically, matchUp() is used to
	 * replace login scripts, token generation scripts and password reset scripts.
	 * The MatchUp method takes a bean type, an SQL query snippet (starting at the WHERE clause),
	 * SQL bindings, a pair of task arrays and a bean reference.
	 *
	 * If the first 3 parameters match a bean, the first task list will be considered,
	 * otherwise the second one will be considered. On consideration, each task list,
	 * an array of keys and values will be executed. Every key in the task list should
	 * correspond to a bean property while every value can either be an expression to
	 * be evaluated or a closure (PHP 5.3+). After applying the task list to the bean
	 * it will be stored. If no bean has been found, a new bean will be dispensed.
	 *
	 * This method will return TRUE if the bean was found and FALSE if not AND
	 * there was a NOT-FOUND task list. If no bean was found AND there was also
	 * no second task list, NULL will be returned.
	 *
	 * @param string   $type         type of bean you're looking for
	 * @param string   $sql          SQL snippet (starting at the WHERE clause, omit WHERE-keyword)
	 * @param array    $bindings     array of parameter bindings for SQL snippet
	 * @param array    $onFoundDo    task list to be considered on finding the bean
	 * @param array    $onNotFoundDo task list to be considered on NOT finding the bean
	 * @param OODBBean &$bean        reference to obtain the found bean
	 *
	 * @return mixed
	 */
	public function matchUp( $type, $sql, $bindings = array(), $onFoundDo = NULL, $onNotFoundDo = NULL, &$bean = NULL )
	{
		$finder = new Finder( $this->toolbox );
		$oodb   = $this->toolbox->getRedBean();
		$bean = $finder->findOne( $type, $sql, $bindings );
		if ( $bean && $onFoundDo ) {
			foreach( $onFoundDo as $property => $value ) {
				if ( function_exists('is_callable') && is_callable( $value ) ) {
					$bean[$property] = call_user_func_array( $value, array( $bean ) );
				} else {
					$bean[$property] = $value;
				}
			}
			$oodb->store( $bean );
			return TRUE;
		}
		if ( $onNotFoundDo ) {
			$bean = $oodb->dispense( $type );
			foreach( $onNotFoundDo as $property => $value ) {
				if ( function_exists('is_callable') && is_callable( $value ) ) {
					$bean[$property] = call_user_func_array( $value, array( $bean ) );
				} else {
					$bean[$property] = $value;
				}
			}
			$oodb->store( $bean );
			return FALSE;
		}
		return NULL;
	}
}
