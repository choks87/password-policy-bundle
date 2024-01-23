.PHONY: setup up down attach start test code-check

# USER VARIABLES / PROJECT VARIABLES
PHP_CONTAINER_NAME = php

DOCKER_COMPOSE = docker-compose
DOCKER_COMPOSE_UP =  ${DOCKER_COMPOSE} up -d --force-recreate --remove-orphans
DOCKER_COMPOSE_BUILD = ${DOCKER_COMPOSE} build --no-cache
DOCKER_COMPOSE_DOWN =  ${DOCKER_COMPOSE} down
DOCKER_RUN_COMMAND = docker exec -it ${PHP_CONTAINER_NAME}
RUN_SERVER_SWOOLE = swoole:server:run

CONSOLE = ./bin/console
PHPUNIT = ./vendor/bin/phpunit -c ./phpunit.xml.dist
COV_CHECK = ./vendor/bin/coverage-check .analysis/phpunit/coverage/coverage.xml 75
INFECTION = ./vendor/bin/infection  --threads=1 --only-covered --skip-initial-tests --coverage=.analysis/phpunit/coverage
PHP_STAN = ./vendor/bin/phpstan
PHP_MD = ./vendor/bin/phpmd src/ text phpmd_ruleset.xml

setup:
	${DOCKER_COMPOSE_BUILD} && \
	${DOCKER_COMPOSE_UP} && \
	${DOCKER_RUN_COMMAND} composer install

start:
	${DOCKER_RUN_COMMAND} ${CONSOLE} ${RUN_SERVER_SWOOLE}

up:
	${DOCKER_COMPOSE_UP}

attach:
	${DOCKER_RUN_COMMAND} bash

down:
	${DOCKER_COMPOSE_DOWN}

test:
	php-ext-disable xdebug
	php-ext-enable pcov
	${PHPUNIT} --stop-on-failure && ${COV_CHECK} && \
	cp -f .analysis/phpunit/coverage/junit.xml .analysis/phpunit/coverage/phpunit.junit.xml && \
	${INFECTION} && \
	php-ext-disable pcov
	php-ext-enable xdebug

code-check:
	${PHP_STAN} && ${PHP_MD}

help:
	# Usage:
	#   make <target> [OPTION=value]
	#
	# Targets:
	#   setup ...........................Sets up docker & app
	#   up ..............................Up Docker
	#   down ............................Down Docker
	#   attach ..........................attaches to docker PHP container
	#   test ........................... Tests & infection testing
	#   code-check ..................... Complete code check
	#   start ...............,.. Runs Swoole web server


