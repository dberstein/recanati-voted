.PHONY: build
build:
	@docker build -t phpapp .

run/dev: build
	@docker run --rm -p 8080:80 --name phpapp -v $(PWD)/src:/var/www/html phpapp

.PHONY: run
run: build
	@docker run --rm -p 8080:80 --name phpapp phpapp

.PHONY: run/prod
run/prod: build
	@docker run -d -p 8080:80 --name phpapp -v $(PWD)/src:/var/www/html phpapp

.PHONY: sync
sync:
	@rsync -avz -e 'ssh -i ~/.ssh/id_rsa_new' $(PWD)/ basegeo.com:/home/daniel/nginx-proxy/app/

.PHONY: unsync
unsync:
	@rsync -avz -e 'ssh -i ~/.ssh/id_rsa_new' basegeo.com:/home/daniel/nginx-proxy/app/ $(PWD)/
