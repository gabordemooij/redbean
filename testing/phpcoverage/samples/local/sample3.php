<?php
    print "hello\n";
    print "?>" . '?>';
    print "still here\n";
    $test = 2;
    switch($test) {
        case 1:
            echo "here\n";
            break;
        default:
            echo "there\n";
            break;
    }
    try {
        $error = 'Always throw this error';
        throw new Exception($error);
        // Code following an exception is not executed.
        echo 'Never executed';
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

?>
<br/>
<?php
    $str = <<<EOD
Example of string
spanning multiple lines
using heredoc syntax.
EOD;
    echo $str;
?>
