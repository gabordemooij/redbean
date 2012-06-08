<?php

    echo "1";

    if(function_exists('generalTP')) {
        require_once 'include3.php';
    }
    else {
        require_once 'include2.php';
    }

    echo "2";
?>
