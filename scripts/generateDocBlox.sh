#!/bin/bash

cd $(dirname $(dirname $(realpath $0)))

docblox run -q -d src -t ../s9e.github.com/TextFormatter/DocBlox --visibility public