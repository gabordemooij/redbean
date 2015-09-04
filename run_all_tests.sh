#!/bin/sh
php replica2.php
cp rb.php testing/cli/testcontainer/rb.php
cd testing
cd cli
php runtests.php
