<?php
$pat = "/\/\*([\n]|.)+?\*\//";



function clean($raw) {
   global $pat;
   $raw = str_replace("\r\n","\n",$raw); //str_replace("\r\n","\n", '<?php ' . "\n/** " . file_get_contents($base . 'license.txt') . "**/\n" . self::$class_definitions);
   $raw = str_replace("\n\r","\n",$raw);
   $raw = preg_replace("/\n+/", "\n", $raw);
   $raw = str_replace("<?php", "", $raw);
   $raw = str_replace("?>", "", $raw);
   //$raw = preg_replace($pat, "", $raw);
   $raw = preg_replace("/\/\/.*/", "", $raw);
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
//echo $code;
file_put_contents("rb.php", $code);

