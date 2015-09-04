#!/bin/sh
if [ -z "$1" ]
then
	echo "Please enter the name of a test suite, example: Blackhole/Version"
	exit
fi
php replica2.php
cp rb.php testing/cli/testcontainer/rb.php
cd testing
cd cli
php runtests.php $1
