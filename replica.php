<?php
$pat = '/\/\*([\n]|.)+?\*\//';

function clean($raw) {
   global $pat;
   $raw = str_replace("\r\n", "\n",$raw);
   $raw = str_replace("\n\r", "\n",$raw);
   $raw = preg_replace('/\n+/', "\n", $raw);
   $raw = str_replace('<?php', '', $raw);
   $raw = str_replace('?>', '', $raw);
   $raw = preg_replace('/\/\/.*/', '', $raw);
   return $raw;
}

$code = '<?php ';
$loader = simplexml_load_file('replica.xml');
$items = $loader->load->item;
foreach($items as $item) {
    echo 'Adding: ' . $item . "\n";
    $raw = file_get_contents( $item );
    $code .= clean($raw);
    
}

file_put_contents('rb.php', $code);

