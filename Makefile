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

#########
# Setup #
#########

## Setup environment & Install application
setup: provision
	vagrant ssh -c 'cd /srv/app && make install'

#############
# Provision #
#############

## Provision environment
provision: provision-vagrant

provision-vagrant:
	ansible-galaxy install -r ansible/roles.yml -p ansible/roles -f
	vagrant up --no-provision
	vagrant provision

###########
# Install #
###########

install: install-app build

install-app:
	composer --no-progress --no-interaction install
	npm install --no-spin

#########
# Build #
#########

## Build application
build: build-assets

build-assets:
	gulp dev

## Build application production
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

## Publish
publish:
	#vagrant ssh -c 'cd /srv/app && make build@prod'
	make build@prod
	chmod -R 755 dist
	rsync -arzv --delete dist/* dédié:/home/tom32i/sites/portfolio

##########
# Custom #
##########

## Launch dev server
build-start:
	sudo supervisorctl start all

## Launch dev server
build-stop:
	sudo supervisorctl stop all
