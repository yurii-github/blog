#!/bin/bash

color="\e[0;34;40m";

function install()
{
	case $1 in

		phpunit*)
			echo -e "${color}getting latest PHPUnit..."
            wget https://phar.phpunit.de/phpunit-6.1.phar -O vendor/phpunit.phar --no-check-certificate
            ;;

		climate*)
			echo -e "${color}getting latest code climate reporter..."
			# broken for now https://github.com/codeclimate/php-test-reporter/issues/123
			wget https://github.com/codeclimate/php-test-reporter/releases/download/0.4.0/codeclimate-test-reporter.phar
			;;

		deps*)
		    echo -e "${color}downloading required dependencies...";
		    composer require codeclimate/php-test-reporter --no-update
		    composer install --prefer-dist --optimize-autoloader --no-dev --no-progress
			echo -e "${color}show installed dependencies:";
			composer show --installed
			;;

		*)
		echo 'Unknown parameter provided for instal()'
		;;
	esac
}


#
# INSTALL
#
if [ "$1" == "install" ]
then

	# cache usage
	#
	if [ -d vendor/bin ]
	then
		echo -e "${color}Using cache.";
	else
		echo -e "${color}Update Composer and set github oauth token..";
		composer self-update
		composer config -g github-oauth.github.com $GITHUB_TOKEN

		install phpunit
		install deps

		echo -e "${color}DEBUG: show vendor dir. IT will be cached";
		ls vendor -l
	fi

	exit $?
fi


#
# SCRIPT
#
if [ "$1" == "script" ]
then
	# if php7.0 use clover
	if [ "${TRAVIS_PHP_VERSION:0:3}" == "7.0" ]
	then
		php vendor/phpunit.phar --coverage-clover build/logs/clover.xml
	else
		php vendor/phpunit.phar
	fi

	export RES=$?
	exit $RES
fi

#
# AFTER SUCCESS
#
if [ "$1" == "after_success" ]
then
	# if php7.0 use clover
	if [ "${TRAVIS_PHP_VERSION:0:3}" == "7.0" ]
	then
		vendor/bin/test-reporter
	else
		echo -e "${color}skipping codeclimate reporter"
	fi

	exit $?
fi