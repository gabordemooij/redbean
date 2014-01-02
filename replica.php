<?php
$pat = "/\/\*([\n]|.)+?\*\//";

$code   = array( 'default' => "" );

$loader = simplexml_load_file( "replica.xml" );

$items  = $loader->load->item;

foreach ( $items as $item ) {
	$a = $item->attributes();

	if ( $a && isset( $a->flavour ) ) {
		$fl = $a->flavour->__toString();

		if ( !isset( $code[$fl] ) ) $code[$fl] = $code['default'];

		$code[$fl] .= '===BOUNDARY===' . file_get_contents( $item ) . "\n";

		echo "Adding: $item to flavour: $fl \n";
	} else {
		echo "Adding: $item \n";

		$c = file_get_contents( $item ) . "\n";

		foreach ( $code as $k => $codeFlav ) {
			$code[$k] .= '===BOUNDARY===' . $c;
		}
	}
}

foreach ( $code as $f => $codeFlav ) {
	$m = array( array() );

	preg_match_all( '/@plugin\s+.*/', $codeFlav, $m );

	$code[$f] .= "
class R extends RedBean_Facade{
  " . str_replace( '@plugin', '', trim( implode( "\n", $m[0] ) ) ) . "
}
";

	// Remove headers
	$x     = explode( '===BOUNDARY===', $code[$f] );
	$clean = '';
	$i     = 0;

	foreach ( $x as $file ) {
		$i++;
		if ( $i < 3 ) {
			$clean .= $file;
			continue;
		}

		$clean .= substr( $file, strpos( $file, '*/' ) + 2 );
	}

	$code[$f] = $clean;

	// Clean php tags and whitespace from codebase.
	$code[$f] = "<?php " . str_replace( array( "<?php", "<?", "?>" ), array( "", "", "" ), $code[$f] );

	file_put_contents( "rb" . ( $f === 'default' ? '' : $f ) . ".php", $code[$f] );

	if ( $_SERVER['argc'] > 1 && $_SERVER['argv'][1] == '-s' ) {
		file_put_contents(
			"rb" . ( $f === 'default' ? '' : $f ) . ".php",
			php_strip_whitespace( "rb" . ( $f === 'default' ? '' : $f ) . ".php" )
		);
	}
}
