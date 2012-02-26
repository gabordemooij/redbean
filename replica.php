<?php
$pat = "/\/\*([\n]|.)+?\*\//";

$code = "";
$loader = simplexml_load_file("replica.xml");
$items = $loader->load->item;
foreach($items as $item) {
    echo "Adding: $item \n";
    $code .= file_get_contents( $item ) . "\n";
}
$code .= "

class R extends RedBean_Facade{
}
";

//Clean php tags and whitespace from codebase.
$code = "<?php ".str_replace( array("<?php", "<?", "?>"), array("", "", ""), $code);
file_put_contents("rb.php", $code);

if ($_SERVER['argc']>1 && $_SERVER['argv'][1]=='-s')
file_put_contents("rb.php", php_strip_whitespace("rb.php"));

?>
