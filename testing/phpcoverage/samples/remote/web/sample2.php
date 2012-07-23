<?php
/*
 *  $Id: sample.php 16428 2005-04-15 16:37:04Z npac $
 *  
 *  Copyright(c) 2004-2006, SpikeSource Inc. All Rights Reserved.
 *  Licensed under the Open Software License version 2.1
 *  (See http://www.spikesource.com/license.html)
 */
?>
<html>
    <head>
        <title>A simple remove coverage recording sample.</title>
    </head>
    <body>
<?php

    function a($a) {
        echo $a * 2.5 . "<br/>";
    }

    function b($count) {
        for ($i = 0; $i < $count; $i++) {
            a($i + 0.17);
        }
    }
?>
    <h1>Execution results</h1>
<?php
    if(1 != 1) {
        require_once 'include.php';
    }
    else {
        //require_once 'include1.php';
    }
    b(6);
    b(10);
?>
    </body>
</html>
<?php
    //exit();
    echo "After exit";
?>
