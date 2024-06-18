PHPSTAN_LEVEL := 7

.PHONY: build
build:
	@docker build -t phpapp .

run/dev: phpstan test build
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
	@vendor/bin/phpunit --stop-on-defect tests

.PHONY: composer
composer:
	@composer update --dev && composer du --dev

.PHONY: phpstan
phpstan:
	@vendor/bin/phpstan analyse -vvv -n --no-progress --level $(PHPSTAN_LEVEL) src tests

.PHONY: format/check
format/check:
	@vendor/bin/php-cs-fixer check src

.PHONY: format/fix
format/fix:
	@vendor/bin/php-cs-fixer fix src