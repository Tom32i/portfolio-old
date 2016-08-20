.SILENT:
.PHONY: help

## Colors
COLOR_RESET   = \033[0m
COLOR_INFO    = \033[32m
COLOR_COMMENT = \033[33m

## Help
help:
	printf "${COLOR_COMMENT}Usage:${COLOR_RESET}\n"
	printf " make [target]\n\n"
	printf "${COLOR_COMMENT}Available targets:${COLOR_RESET}\n"
	awk '/^[a-zA-Z\-\_0-9\.@]+:/ { \
		helpMessage = match(lastLine, /^## (.*)/); \
		if (helpMessage) { \
			helpCommand = substr($$1, 0, index($$1, ":")); \
			helpMessage = substr(lastLine, RSTART + 3, RLENGTH); \
			printf " ${COLOR_INFO}%-16s${COLOR_RESET} %s\n", helpCommand, helpMessage; \
		} \
	} \
	{ lastLine = $$0 }' $(MAKEFILE_LIST)

###############
# Environment #
###############

## Setup environment & Install & Build application
setup:
	vagrant up --no-provision
	vagrant provision
	vagrant ssh -- "cd /srv/app && make install build@prod"

## Update environment
update: export ANSIBLE_TAGS = manala.update
update:
	vagrant provision

## Update ansible
update-ansible: export ANSIBLE_TAGS = manala.update
update-ansible:
	vagrant provision --provision-with ansible

## Provision environment
provision: export ANSIBLE_EXTRA_VARS = {"manala":{"update":false}}
provision:
	vagrant provision --provision-with app

## Provision nginx
provision-nginx: export ANSIBLE_TAGS = manala_nginx
provision-nginx: provision

## Provision php
provision-php: export ANSIBLE_TAGS = manala_php
provision-php: provision

###########
# Install #
###########

## Install application
install:
	# Composer
	composer install --prefer-dist --optimize-autoloader --no-progress --no-interaction
	# Npm
	npm install --no-spin

install@prod: SYMFONY_ENV = prod
install@prod:
	# Composer
	composer install --prefer-dist --optimize-autoloader --no-progress --no-interaction
	# Npm
	npm install --no-spin

#########
# Build #
#########

## Build application
build: build-assets

build-assets:
	gulp dev

build@prod: SYMFONY_ENV = prod
build@prod: build-assets@prod

build-assets@prod:
	gulp

#########
# Watch #
#########

## Watch application
watch: watch-assets

watch-assets:
	gulp watch

#######
# Run #
#######

## Run application
run: run-server

run-server:
	bin/console phpillip:serve

########
# Lint #
########

## Run lint tools
lint:
	php-cs-fixer fix --config-file=.php_cs --dry-run --diff

lint@test: SYMFONY_ENV = test
lint@test: lint

##########
# Deploy #
##########

## Publish
deploy@demo:
	vagrant ssh -c 'cd /srv/app && make build@prod'
	chmod -R 755 dist
	rsync -arzv --delete dist/* tom32i@deployer.dev:/home/tom32i/portfolio

## Publish
deploy@prod:
	vagrant ssh -c 'cd /srv/app && make build@prod'
	chmod -R 755 dist
	rsync -arzv --delete dist/* tom32i@tom32i.fr:/home/tom32i/portfolio

##########
# Custom #
##########

## Launch dev server
supervisor-start:
	sudo supervisorctl start all

## Launch dev server
supervisor-stop:
	sudo supervisorctl stop all
