<?php

echo "Running Patch P533...";
echo PHP_EOL;

$code = file_get_contents('rb.php');
$code = str_replace('&offsetGet', 'offsetGet', $code);

$bytes = file_put_contents('rb-p533.php', $code);

if ($bytes > 0) {
	echo 'Applied patch for PHP < 5.3.3';
	echo PHP_EOL;
	exit;
} else {
	echo 'Somthing went wrong.';
	echo PHP_EOL;
	exit;
}
