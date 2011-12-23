#!/bin/bash
cd $(dirname $0)

git push &
./generateCodeCoverage.sh &
./generateDocBlox.sh &
./generateJSParserDemoLite.php &
./generateJSParserDemo.php 1 &

# wait for everyone to be done
wait

cd ../../s9e.github.com/TextFormatter

git add *
git commit -a -m "Synced"
git push