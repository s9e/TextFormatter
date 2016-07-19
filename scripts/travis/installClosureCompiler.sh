#!/bin/bash

wget -O - http://dl.google.com/closure-compiler/compiler-latest.tar.gz | tar xzf - -C/tmp && \
mv /tmp/closure-compiler-*.jar /tmp/compiler.jar