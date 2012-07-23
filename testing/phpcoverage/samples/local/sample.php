<?php
/*
 *  $Id: sample.php 49493 2006-04-08 00:16:04Z hkodungallur $
 *  
 *  Copyright(c) 2004-2006, SpikeSource Inc. All Rights Reserved.
 *  Licensed under the Open Software License version 2.1
 *  (See http://www.spikesource.com/license.html)
 */
?>
<?php

    function a($a) {
        echo $a * 2.5;
    }

    function b($count) {
        for ($i = 0; $i < $count; $i++) {
            a($i + 0.17);
        }
    }

    b(6);
    b(10);

?>
