#!/bin/bash

if [ "$(id -u)" == "0" ]; then
    echo "Run this without sudo. Don't run this as root."
    exit 1
fi

bootstrap() {
    PHP_VERSION=$(php -v | grep 'PHP 5\.4' | wc -l)
    if [ $PHP_VERSION -eq 0 ]
        then
        echo "PHP version must be 5.4"
        exit
    fi
    if ! which composer > /dev/null; then
        read -p "Enter path of composer: " COMPOSER_PATH
        COMMAND="php $COMPOSER_PATH"
    else
        COMMAND='composer'
    fi
}

install_php5() {
    sudo apt-get install php5
}

install_php5_libs() {
    sudo apt-get install curl libcurl3 libcurl3-dev php5-curl
    sudo apt-get install php5-gd
    sudo apt-get install php5-tidy
}

install_php5gd() {
}

install_composer() {
    curl -sS https://getcomposer.org/installer | php
}

run_composer() {
    $COMMAND install
}

bootstrap
install_php5
install_php5_libs
install_composer
run_composer