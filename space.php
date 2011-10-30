<?php

/*
* RedBean NameSpace Utility (experimental)
* Written by Gabor de Mooij (c) copyright 2011
* Licensed New BSD
*/

echo "\n Namespace Utility for RedBeanPHP (experimental). ";

if (($_SERVER["argc"])!==2) {
	die("\n Usage php space.php [namespace] \n\n");
}

if (!file_exists("rb.php")) {
	die("\n could not find rb.php, must be in same directory. ");
}

$n = $_SERVER["argv"][1];

if (preg_match("/\w+/",$n)) {
	echo "\n selected namespace: $n ";
}
else {
	die("\n namespace contains illegal characters. ");
}

$code = file_get_contents("rb.php");

//prefix
$code = "<?php

namespace $n;

?>".$code;

$natObjects = array(
"IteratorAggregate","ArrayAccess","Exception","PDO","ArrayIterator"
);

foreach($natObjects as $natObj) {
$code = str_replace(" ".$natObj,"\\$natObj",$code);
$code = str_replace("(".$natObj,"(\\$natObj",$code);
$code = str_replace(",".$natObj,",\\$natObj",$code);
$code = str_replace(";".$natObj,";\\$natObj",$code);
$code = str_replace("{".$natObj,"{\\$natObj",$code);
}

//save code
file_put_contents("rbn.php", $code);
die("\n output has been stored in rbn.php \n");
