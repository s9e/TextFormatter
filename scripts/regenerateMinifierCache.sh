#!/bin/bash
cd $(dirname $(dirname $0))
rm -f tests/.cache/minifier.*
ls -1 tests/Plugins/*/ParserTest.php | xargs -n1 -P2 --verbose phpunit --group needs-js > /dev/null