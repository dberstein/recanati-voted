.PHONY: build
build:
	@docker build -t phpapp .

run/dev: build
	@docker run --rm -p 8080:80 -v $(PWD)/src:/var/www/html phpapp

.PHONY: run
run: build
	@docker run --rm -p 8080:80 phpapp

