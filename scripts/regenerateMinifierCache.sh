#!/bin/bash
cd $(dirname $(dirname $0))
#rm -f tests/.cache/minifier.*
ls -1r tests/Parser/AttributeFilters/*Test.php tests/Plugins/BBCodes/BBCodesTest.php tests/Plugins/*/ParserTest.php | xargs -tn1 -P4 ./vendor/bin/phpunit --group needs-js 1> /dev/null
