<?php

function loadfiles()
{
	require_once "testcontainer/rb.php";

	require "../RedUNIT.php";
	require "../helpers/selector.php";
}

if ( file_exists( 'phpcoverage.inc.php' ) ) {
	echo "\n Starting with Coverage...";

	require_once "phpcoverage.inc.php";

	require_once PHPCOVERAGE_HOME . "/CoverageRecorder.php";
	require_once PHPCOVERAGE_HOME . "/reporter/HtmlCoverageReporter.php";

	$reporter = new HtmlCoverageReporter( "Code Coverage Report", "", "report" );

	$includePaths = array( "testcontainer" );

	//$excludePaths = array("old/","codeCoverageMain.php", "test_driver.php");

	$excludePaths = array();

	$cov = new CoverageRecorder( $includePaths, $excludePaths, $reporter );

	$cov->startInstrumentation();

	loadfiles();

	$cov->stopInstrumentation();
	$cov->generateReport();

	$reporter->printTextSummary();
} else {
	loadfiles();
}


