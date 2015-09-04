<?php

namespace RedBeanPHP\Util;

use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\Adapter as Adapter;

/**
 * Transaction Helper
 *
 * This code was originally part of the facade, however it has
 * been decided to remove unique features to service classes like
 * this to make them available to developers not using the facade class.
 *
 * Database transaction helper. This is a convenience class
 * to perform a callback in a database transaction. This class
 * contains a method to wrap your callback in a transaction.
 *
 * @file    RedBeanPHP/Util/Transaction.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Transaction
{
	/**
	 * Wraps a transaction around a closure or string callback.
	 * If an Exception is thrown inside, the operation is automatically rolled back.
	 * If no Exception happens, it commits automatically.
	 * It also supports (simulated) nested transactions (that is useful when
	 * you have many methods that needs transactions but are unaware of
	 * each other).
	 *
	 * Example:
	 *
	 * <code>
	 * $from = 1;
	 * $to = 2;
	 * $amount = 300;
	 *
	 * R::transaction(function() use($from, $to, $amount)
	 * {
	 *   $accountFrom = R::load('account', $from);
	 *   $accountTo = R::load('account', $to);
	 *   $accountFrom->money -= $amount;
	 *   $accountTo->money += $amount;
	 *   R::store($accountFrom);
	 *   R::store($accountTo);
	 * });
	 * </code>
	 *
	 * @param Adapter  $adapter  Database Adapter providing transaction mechanisms.
	 * @param callable $callback Closure (or other callable) with the transaction logic
	 *
	 * @return mixed
	 */
	public static function transaction( Adapter $adapter, $callback )
	{
		if ( !is_callable( $callback ) ) {
			throw new RedException( 'R::transaction needs a valid callback.' );
		}

		static $depth = 0;
		$result = null;
		try {
			if ( $depth == 0 ) {
				$adapter->startTransaction();
			}
			$depth++;
			$result = call_user_func( $callback ); //maintain 5.2 compatibility
			$depth--;
			if ( $depth == 0 ) {
				$adapter->commit();
			}
		} catch ( \Exception $exception ) {
			$depth--;
			if ( $depth == 0 ) {
				$adapter->rollback();
			}
			throw $exception;
		}
		return $result;
	}
}
