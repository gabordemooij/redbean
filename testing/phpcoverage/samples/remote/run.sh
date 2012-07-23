#!/bin/bash
################################################################################
#  $Id: license.txt 13981 2005-03-16 08:09:28Z eespino $
#  
#  Copyright(c) 2004-2006, SpikeSource Inc. All Rights Reserved.
#  Licensed under the Open Software License version 2.1
#  (See http://www.spikesource.com/license.html)
################################################################################

source /opt/oss/env.sh
cp -f /home/npac/svn-root/trunk/corestackbase/spike/util/coverage/php/src/phpcoverage.inc.php phpcoverage.inc.php
sudo rm -rf /opt/oss/share/apache2/htdocs/phpcoverage-sample
sudo cp -rp web /opt/oss/share/apache2/htdocs/phpcoverage-sample
php /home/npac/svn-root/trunk/corestackbase/spike/util/coverage/php/src/cli/instrument.php -b '/opt/oss/share/apache2/htdocs/phpcoverage-sample' -r -v /opt/oss/share/apache2/htdocs/phpcoverage-sample
php /home/npac/svn-root/trunk/corestackbase/spike/util/coverage/php/src/cli/driver.php --init --cov-url "http://localhost/phpcoverage-sample" -v
wget "http://localhost/phpcoverage-sample/sample.php" -O /tmp/sample.php
php /home/npac/svn-root/trunk/corestackbase/spike/util/coverage/php/src/cli/driver.php --report --cov-url "http://localhost/phpcoverage-sample" --report-dir /tmp/coverage-report --report-name "Sample Report" --include-paths /opt/oss/share/apache2/htdocs/phpcoverage-sample --print-summary --verbose

php /home/npac/svn-root/trunk/corestackbase/spike/util/coverage/php/src/cli/driver.php --cleanup --cov-url "http://localhost/phpcoverage-sample"
