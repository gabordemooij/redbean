<?php

@exec('rm rb.phar; rm -Rf build');
@mkdir('build');
@exec('cp -R RedBeanPHP build/RedBeanPHP');
@exec('mv build/RedBeanPHP/loader.php build/');
$phar = new Phar("rb.phar", 0, "rb.phar");
$phar->buildFromDirectory('./build');
$phar->setStub($phar->createDefaultStub("loader.php"));
 
