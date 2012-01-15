#!/bin/bash
cd $(dirname $0)

./generateCodeCoverage.sh &
git push &
./generateJSParserDemoLite.php
./generateJSParserDemo.php
./generateDocBlox.sh

wait

cd ../../s9e.github.com/TextFormatter

git add *
git commit -a -m "Synced"
git push