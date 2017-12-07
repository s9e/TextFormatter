#!/bin/bash
cd $(dirname $(dirname $0))
#rm -f tests/.cache/minifier.*
ls -1r tests/Parser/AttributeFilters/*Test.php tests/Plugins/BBCodes/BBCodesTest.php tests/Plugins/*/ParserTest.php | xargs -n1 -P2 --verbose phpunit --group needs-js > /dev/null