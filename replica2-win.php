<?php

echo "Welcome to Replica 2 Build Script for RedBeanPHP\n";
echo "Now building your beans!\n";
echo "-------------------------------------------\n";

echo "Cleaning up... ";
@exec('del /q rb.phar');
@exec('del /q build');
echo "Done.\n";

echo "Trying to create a directory called build to build the PHAR... ";
@mkdir('build');
@mkdir('build\RedBeanPHP');
echo "Done.\n";

echo "Trying to copy RedBeanPHP to build/RedBeanPHP... ";
@exec('xcopy .\RedBeanPHP .\build\RedBeanPHP /e /y');
echo "Done.\n";

echo "Moving loader to build folder... ";
@exec('move .\build\RedBeanPHP\loader.php .\build\loader.php');
echo "Done.\n";

echo "Creating PHAR archive... ";
$phar = new Phar("rb.phar", 0, "rb.phar");
$phar->buildFromDirectory('./build');
echo "Done.\n";

echo "Adding stub... ";
$phar->setStub($phar->createDefaultStub("loader.php"));
echo "Done.\n"; 

echo "Your PHAR file has been generated.\n";
