<?php

/*
* RedBean NameSpace Utility (experimental)
* Written by Gabor de Mooij (c) copyright 2011
* Licensed New BSD
*/

echo "\n Namespace Utility for RedBeanPHP (experimental). ";

if ( ( $_SERVER["argc"] ) !== 2 ) die( "\n Usage php space.php [namespace] \n\n" );

if ( !file_exists( "rb.php" ) ) die( "\n could not find rb.php, must be in same directory. " );

$n = $_SERVER["argv"][1];

if ( preg_match( "/\w+/", $n ) ) {
	echo "\n selected namespace: $n ";
} else {
	die( "\n namespace contains illegal characters. " );
}

$code = file_get_contents( "rb.php" );

//prefix
$code = "<?php

namespace $n;

?>" . $code;

$natObjects = array(
	"IteratorAggregate", "ArrayAccess", "RuntimeException", "LogicException",
	"Exception", "PDO", "ArrayIterator", "Countable"
);

foreach ( $natObjects as $natObj ) {
	$code = str_replace( " " . $natObj, "\\$natObj", $code );
	$code = str_replace( "(" . $natObj, "(\\$natObj", $code );
	$code = str_replace( "," . $natObj, ",\\$natObj", $code );
	$code = str_replace( ";" . $natObj, ";\\$natObj", $code );
	$code = str_replace( "{" . $natObj, "{\\$natObj", $code );
}

$code .= "

class RedBean_NSFormatter implements RedBean_IModelFormatter {
     public function formatModel(\$model) {
            return '\\\'.'Models'.'\\\'.\$model;
     }
}


 \$formatter = new RedBean_NSFormatter;
 RedBean_ModelHelper::setModelFormatter(\$formatter);

";

//save code
file_put_contents( "rbn.php", $code );

die( "\n output has been stored in rbn.php \n" );
