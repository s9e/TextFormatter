#!/bin/bash
cd $(dirname $0)

git push &
./generateJSParserDemoLite.php &
./generateJSParserDemo.php &
./generateDocBlox.sh &

./generateCodeCoverage.sh

cd ../../s9e.github.com/TextFormatter

git add *
git commit -a -m "Synced"
git push