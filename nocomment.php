<?php

$fileStr = file_get_contents( 'rb.php' );

$newStr  = '';

function removeEmptyLines( $string )
{
	return preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string );
}

$commentTokens = array( T_COMMENT );

if ( defined( 'T_DOC_COMMENT' ) ) $commentTokens[] = T_DOC_COMMENT; // PHP 5

if ( defined( 'T_ML_COMMENT' ) ) $commentTokens[] = T_ML_COMMENT; // PHP 4

$tokens = token_get_all( $fileStr );

foreach ( $tokens as $token ) {
	if ( is_array( $token ) ) {
		if ( in_array( $token[0], $commentTokens ) ) continue;

		$token = $token[1];
	}

	$newStr .= $token;
}

$newStr = removeEmptyLines( $newStr );

$newStr = str_replace( "<" . "?php", "", $newStr );
$newStr = "<" . "?php\n//Written by Gabor de Mooij and the RedBeanPHP Community, copyright 2009-2013\n//Licensed New BSD/GPLV2 - see license.txt\n" . $newStr;

file_put_contents( 'rbnc.php', $newStr );
