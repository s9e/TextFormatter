#!/bin/bash

TARGET=../s9e.github.com/TextFormatter/DocBlox

cd $(dirname $(dirname $(realpath $0)))

rm -rf $TARGET/*
docblox run -q -d src -t $TARGET --visibility public