<?php

echo "Patch for PHP <= 5.3.4".PHP_EOL;
echo "Writing new file...".PHP_EOL;

if (!file_exists('rb.phar')) {
	echo "ERROR: Cannot find rb.phar, should be in this same directory!";
	echo PHP_EOL;
	exit(1);
}

$file = file_get_contents('rb.phar');
$file = str_replace('&__offsetGet', '__offsetGet', $file);
$written = @file_put_contents('rb.phar', $file);

if (!$written) {
	echo "ERROR: Could not write rb.phar file.";
	echo PHP_EOL;
	exit(1);
}

echo "Done.";
echo PHP_EOL;
exit(0);

