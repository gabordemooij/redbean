<?php

/**
 * Short Query Notation Library
 *
 * SQN is a small library that allows you to write
 * convention based SQL queries using a short notation.
 * SQL is a very flexible and powerful language. Since SQL
 * does not rely on assumptions you have to specify a lot.
 * The SQN-library uses some basic assumptions regarding
 * naming conventions and in return it gives you a shorter notation.
 *
 * Usage:
 *
 * <code>
 * R::sqn('shop<product<price'); - left joins shop, product and price
 * R::sqn('book<<tag'); - doubly left joins book, and tag using book_tag
 * </code>
 *
 * SQN assumes id fields follow the following conventions:
 *
 * Primary key: id
 * Foreign key: {table}_id
 * No table prefix.
 *
 * SQN can also generate additional aliases:
 *
 * <code>
 * R::sqn( ..., 'area/x,y;place/x,y' ) - area_x area_y place_x place_y
 * </code>
 *
 * @author  Gabor de Mooij
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD License that is bundled
 * with this source code in the file license.txt.
 */
R::ext('sqn', function( $query, $aliases = null, $q = '`' ) {
	$map = [
		'|'  => 'INNER JOIN',
		'||' => 'INNER JOIN',
		'>'  => 'RIGHT JOIN',
		'>>' => 'RIGHT JOIN',
		'<'  => 'LEFT JOIN',
		'<<' => 'LEFT JOIN',
	];
	$select = [];
	$from   = '';
	$joins  = [];
	$prev   = '';
	$ents   = preg_split( '/[^\w_]+/', $query );
	$tokens = preg_split( '/[\w_]+/', $query );
	array_pop($tokens);
	foreach( $ents as $i => $ent ) {
		$select[] = " {$q}{$ent}{$q}.* ";
		if (!$i) {
			$from = $ent;
			$prev = $ent;
			continue;
		}
		if ( $tokens[$i] == '<' || $tokens[$i] == '>' || $tokens[$i] == '|') {
			$join[] = " {$map[$tokens[$i]]} {$q}{$ent}{$q} ON {$q}{$ent}{$q}.{$prev}_id = {$q}{$prev}{$q}.id ";
		}
		if ( $tokens[$i] == '<<' || $tokens[$i] == '>>' || $tokens[$i] == '||') {
			$combi = [$prev, $ent];
			sort( $combi );
			$combi = implode( '_', $combi );
			$select[] = " {$q}{$combi}{$q}.* ";
			$join[] = " {$map[$tokens[$i]]} {$q}{$combi}{$q} ON {$q}{$combi}{$q}.{$prev}_id = {$q}{$prev}{$q}.id ";
			$join[] = " {$map[$tokens[$i]]} {$q}{$ent}{$q} ON {$q}{$combi}{$q}.{$ent}_id = {$q}{$ent}{$q}.id ";
		}
		$prev = $ent;
	}
	if (!is_null($aliases)) {
		$aliases = explode(';', $aliases);
		foreach($aliases as $alias) {
			list($table, $cols) = explode('/', $alias);
			$cols = explode(',', $cols);
			foreach($cols as $col) {
				$select[] = " {$q}{$table}{$q}.{$q}{$col}{$q} AS {$q}{$table}_{$col}{$q} ";
			}
		}
	}
	$selectSQL = implode( ",\n", $select );
	$joinSQL   = implode( "\n", $join );
	return "SELECT{$selectSQL}\n FROM {$q}{$from}{$q}\n{$joinSQL}";
});

