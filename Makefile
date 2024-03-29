.SILENT:
.PHONY: build test

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

## Setup environment
setup:
	vagrant up --no-provision
	vagrant provision
	vagrant ssh -c 'cd /srv/app && make install && make build@prod'

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

##########
# Deploy #
##########

install:
	composer --no-progress --no-interaction install
	npm install --no-spin

#########
# Build #
#########

## Build application
build: build-assets

build-assets:
	gulp dev

## Build application prod
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

###########
# Publish #
###########

deploy@prod:
	vagrant ssh -c 'cd /srv/app && make build@prod'
	chmod -R 755 dist
	rsync -arzv --delete dist/* dédié:/home/tom32i/portfolio

deploy@demo:
	vagrant ssh -c 'cd /srv/app && make build@prod'
	chmod -R 755 dist
	rsync -arzv --delete dist/* deployer.vm:/home/tom32i/portfolio

##########
# Custom #
##########

## Launch dev server
supervisor-start:
	sudo supervisorctl start all

## Launch dev server
supervisor-stop:
	sudo supervisorctl stop all
