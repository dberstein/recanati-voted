PHPSTAN_LEVEL := 7

.PHONY: build
build:
	@docker build -t phpapp .

run/dev: test phpstan build
	@docker run --rm -p 8080:80 --env-file src/.env --name phpapp -v $(PWD)/src/html:/var/www/html -v $(PWD)/src/voted.db:/data/voted.db phpapp

.PHONY: run
run: build
	@docker run --rm -p 8080:80 --env-file src/.env --name phpapp --restart unless-stopped phpapp

.PHONY: run/prod
run/prod: phpstan build
	@docker run -d -p 8080:80 --env-file src/.env --name phpapp --restart unless-stopped -v $(PWD)/src/html:/var/www/html -v $(PWD)/src/voted.db:/data/voted.db phpapp

.PHONY: sync
sync:
	@rsync -avz -e 'ssh -i ~/.ssh/id_rsa_new' --exclude voted.db $(PWD)/ basegeo.com:/home/daniel/nginx-proxy/app/

.PHONY: unsync
unsync:
	@rsync -avz -e 'ssh -i ~/.ssh/id_rsa_new' basegeo.com:/home/daniel/nginx-proxy/app/ $(PWD)/

.PHONY: test
test:
	@vendor/bin/phpunit tests

.PHONY: composer
composer:
	@composer update && composer du --dev

.PHONY: phpstan
phpstan:
	@vendor/bin/phpstan analyse -vvv -n --level $(PHPSTAN_LEVEL) src tests