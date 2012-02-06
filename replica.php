<?php
$pat = "/\/\*([\n]|.)+?\*\//";



function clean($raw) {
   global $pat;
   $raw = str_replace("<?php", "", $raw);
   $raw = str_replace("?>", "", $raw);
   return $raw;
}

$code = "<?php ";
$loader = simplexml_load_file("replica.xml");
$items = $loader->load->item;
foreach($items as $item) {
    echo "Adding: $item \n";
    $raw = file_get_contents( $item );
    $code.=clean($raw);

}
$code .= "

class R extends RedBean_Facade{
}
";

file_put_contents("rb.php", $code);
file_put_contents("rb.php",php_strip_whitespace("rb.php"));
