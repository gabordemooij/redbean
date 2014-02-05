<?php

testpack('Running pre-tests. (before config).');

try {
	R::debug( TRUE );
	fail();
} catch( Exception $e ) {
	pass();
}

