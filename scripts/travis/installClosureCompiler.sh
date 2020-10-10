#!/bin/bash

cd "$(dirname $0)"/../..
if [ ! -d vendor ]
then
	mkdir vendor
fi

cd vendor
npm i google-closure-compiler-linux@20200927