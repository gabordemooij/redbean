<?php

@exec('rm rb.phar; rm -Rf build');
@mkdir('build');
@exec('cp -R RedBeanPHP build/RedBeanPHP');
@exec('mv build/RedBeanPHP/loader.php build/');
  
$phar = new Phar("rb.phar", 
    //FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
	 0, "rb.phar");
//$phar["index.php"] = file_get_contents($srcRoot . "/index.php");
//$phar["common.php"] = file_get_contents($srcRoot . "/common.php");

$phar->buildFromDirectory('./build');


$phar->setStub($phar->createDefaultStub("loader.php"));
 
