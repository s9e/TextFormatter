#!/bin/bash
cd $(dirname $0)

git push &
./generateDocBlox.sh &
./generateJSParserDemoLite.php &
./generateJSParserDemo.php 1 &
./generateCodeCoverage.sh

cd ../../s9e.github.com/TextFormatter

git add *
git commit -a -m "Synced"
git push