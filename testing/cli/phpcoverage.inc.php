<?php

$PHPCOVERAGE_REPORT_DIR = FALSE;
$PHPCOVERAGE_HOME       = FALSE;

global $PHPCOVERAGE_REPORT_DIR;
global $PHPCOVERAGE_HOME;

$PHPCOVERAGE_HOME = '../phpcoverage/src';

$include_path = get_include_path();

set_include_path( $PHPCOVERAGE_HOME . PATH_SEPARATOR . $include_path );

define( 'PHPCOVERAGE_HOME', $PHPCOVERAGE_HOME );
