<?php
/**
 * Strips out all fluid functions (experimental)
 */
$functions = array(
	'addFK','addIndex','addUniqueConstraint','createTable',
	'widenColumn','buildFK','addColumn','wipeAll'
);
$code = file_get_contents('rb.php');
$functionDefs = array();
foreach($functions as $function) {
	$functionDefs[] = "public function $function";
	$functionDefs[] = "private function $function";
	$functionDefs[] = "protected function $function";
	$functionDefs[] = "public static function $function";
	$functionDefs[] = "private static function $function";
	$functionDefs[] = "protected static function $function";
}
$functionDefs[] = 'class Fluid extends Repository';
foreach( $functionDefs as $function ) {
	while( strpos( $code, $function ) !== FALSE ) {
		$begin = strpos( $code, $function );
		$pointer = $begin;
		$char = '';
		while( $char !== '{'  && $char !== ';' ) {
			echo $char;
			$char = substr( $code, $pointer, 1);
			$pointer ++;
		}
		if ($char === ';') {
			$code = substr( $code, 0, $begin-1 ) . substr( $code, $pointer );
			continue;
		}
		if ($char === '{') {
			$nesting = 1;
			$pointer ++;
			$beginOfFunction = $pointer;
			while( !( $char === '}' && $nesting === 0 ) ) {
				$char = substr( $code, $pointer, 1);
				if ($char === '{') {  $nesting ++; echo "($nesting)"; }
				if ($char === '}') { $nesting --; echo "($nesting)"; }
				$pointer ++;
			}
			$code = substr( $code, 0, $begin-1 ) . substr( $code, $pointer );
			continue;		
		}
	}
}
file_put_contents('rbf.php', $code);
