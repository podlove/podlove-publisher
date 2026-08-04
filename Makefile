PHP_CS_FIXER = vendor-bin/php-cs-fixer/vendor/friendsofphp/php-cs-fixer/php-cs-fixer

release:
	bin/release.sh

format:
	$(PHP_CS_FIXER) fix . --config .php-cs-fixer.dist.php

validateFormat:
	$(PHP_CS_FIXER) fix . --config .php-cs-fixer.dist.php -v --dry-run --stop-on-violation --using-cache=no

update_subscribe_button:
	rm -rf .tmppsb
	git clone https://github.com/podlove/podlove-subscribe-button.git .tmppsb
	rm -rf lib/modules/subscribe_button/dist
	mv .tmppsb/dist lib/modules/subscribe_button/dist
	rm -rf .tmppsb

player:
	mkdir -p $(player_dst)/bin
	mkdir -p $(player_dst)/css
	mkdir -p $(player_dst)/img
	mkdir -p $(player_dst)/js/vendor
	cp -r $(player_src)/css/vendor $(player_dst)/css/vendor
	cp -r $(player_src)/img/* $(player_dst)/img
	cp -r $(player_src)/js/*.min.js $(player_dst)/js
	cp -r $(player_src)/js/vendor/*.min.js $(player_dst)/js/vendor

composer_with_prefixing:
	rm -rf vendor-prefixed
	rm -rf vendor/monolog/monolog vendor/psr/log vendor/twig/twig vendor/matomo/device-detector
	mkdir -p vendor-prefixed
	composer install --no-progress --prefer-dist --optimize-autoloader --no-dev
	composer prefix-dependencies
	rm -rf vendor/matomo
	rm -rf vendor/twig
	rm -rf vendor/monolog
	rm -rf vendor/psr
	composer dump-autoload --classmap-authoritative
	# composer install --no-progress --prefer-dist --optimize-autoloader --no-dev

install_php_scoper:
	mkdir -p vendor-prefixed
	composer require --dev bamarni/composer-bin-plugin:1.9.1
	composer bin php-scoper config minimum-stability dev
	composer bin php-scoper config prefer-stable true
	composer bin php-scoper require --dev --update-with-all-dependencies humbug/php-scoper:0.18.19

install_php_cs_fixer:
	composer bin php-cs-fixer install

client_legacy:
	cd js && npm install
	cd js && NODE_ENV=production npm run build

client_next:
	cd client && npm install
	cd client && NODE_ENV=production npm run build

client: client_legacy client_next

build:
	make composer_with_prefixing
	make client
	make package

package:
	rm -rf dist
	mkdir -p dist
	# Copy runtime files. .distignore is shared with the WordPress.org release process.
	rsync -r --exclude-from=.distignore --exclude=node_modules --exclude=/client/ --exclude=/dist/ . dist

	# Copy only the compiled client application.
	mkdir -p dist/client/dist
	rsync -r client/dist/ dist/client/dist/
	rm -f dist/client/dist/index.html

	# Remove development copies of generated assets while retaining files loaded directly at runtime.
	rm -rf dist/lib/modules/subscribe_button/js/dist
	rm -f dist/js/admin/dc.js
	rm -f dist/js/admin/chosen/chosen.jquery.min.js
	rm -f dist/js/admin/chosen/chosenImage.jquery.js
	rm -f dist/js/admin/ace/theme-github.js
	rm -f dist/fonts/Podlove.dev.svg

	# Composer packages include tests and examples even in --no-dev installations.
	rm -rf dist/vendor-prefixed/matomo/mustangostang/spyc/examples
	rm -rf dist/vendor/dariuszp/cli-progress-bar/examples
	rm -rf dist/vendor/dariuszp/cli-progress-bar/test
	rm -rf dist/vendor/gajus/dindent/tests
	rm -rf dist/vendor/geoip2/geoip2/examples
	rm -rf dist/vendor/maxmind-db/reader/ext/tests
	rm -rf dist/vendor/maxmind/web-service-common/dev-bin
	rm -rf dist/vendor/mustangostang/spyc/examples
	rm -rf dist/vendor/mustangostang/spyc/tests
	rm -rf dist/vendor/podlove/normalplaytime/test
	rm -rf dist/vendor/podlove/podlove-timeline/test
	rm -rf dist/vendor/podlove/webvtt-parser/test

	# Package documentation is not needed at runtime. License files are intentionally retained.
	find dist/vendor dist/vendor-prefixed -type f \
		\( -iname "README*" -o -iname "CHANGELOG*" -o -iname "UPGRADE*" \
		-o -name "SECURITY.md" -o -iname "CONTRIBUTING*" -o -iname ".travis.yml" \
		-o -iname "docker-compose.yml" \) -exec rm -f {} +
	rm -f dist/lib/modules/readme.md
	rm -f dist/lib/modules/podlove_web_player/player_v4/dist/README.md
	php bin/verify-dist-autoload.php


install: install_php_scoper install_php_cs_fixer composer_with_prefixing
