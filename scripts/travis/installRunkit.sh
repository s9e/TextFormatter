#!/bin/bash

wget -O - https://codeload.github.com/php/pecl-php-runkit/tar.gz/master | tar xzf - -C/tmp && \
cd /tmp/pecl-php-runkit-master                                                             && \
phpize                                                                                     && \
./configure --enable-runkit-modify --disable-runkit-super --disable-runkit-sandbox         && \
make                                                                                       && \
sudo make install                                                                          && \
echo "extension=runkit.so"        >> ~/.phpenv/versions/$TRAVIS_PHP_VERSION/etc/php.ini    && \
echo "runkit.internal_override=1" >> ~/.phpenv/versions/$TRAVIS_PHP_VERSION/etc/php.ini