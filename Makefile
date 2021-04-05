player_src = bower_components/podlove-web-player/dist
player_dst = lib/modules/podlove_web_player/player_v3

prepareTest:
	bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

test:
	./vendor/bin/phpunit -c phpunit.xml

release:
	bin/release.sh

format:
	php-cs-fixer fix . --rules="@PSR2, @PhpCsFixer, -yoda_style"

validateFormat:
	php-cs-fixer fix . --rules="@PSR2, @PhpCsFixer, -yoda_style" -v --dry-run --stop-on-violation --using-cache=no

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
	cp $(player_src)/bin/flashmediaelement.swf $(player_dst)/bin
	cp $(player_src)/css/*.min.css $(player_dst)/css
	cp -r $(player_src)/css/vendor $(player_dst)/css/vendor
	cp -r $(player_src)/img/* $(player_dst)/img
	cp -r $(player_src)/js/*.min.js $(player_dst)/js
	cp -r $(player_src)/js/vendor/*.min.js $(player_dst)/js/vendor

composer_with_prefixing:
	mkdir -p vendor-prefixed
	composer install --no-progress --prefer-dist --optimize-autoloader 	--no-dev
	./vendor-bin/php-scoper/vendor/humbug/php-scoper/bin/php-scoper add-prefix --prefix=PodlovePublisher_Vendor --output-dir=./vendor-prefixed/twig --config=scoper.inc.php
	composer install --no-progress --prefer-dist --optimize-autoloader --no-dev

install_php_scoper:
	mkdir -p vendor-prefixed
	composer require --dev bamarni/composer-bin-plugin
	composer bin php-scoper config minimum-stability dev
	composer bin php-scoper config prefer-stable true
	composer bin php-scoper require --dev humbug/php-scoper

build:
	mkdir -p vendor-prefixed
	composer install --no-progress --prefer-dist --optimize-autoloader 	--no-dev
	./vendor-bin/php-scoper/vendor/humbug/php-scoper/bin/php-scoper add-prefix --prefix=PodlovePublisher_Vendor --output-dir=./vendor-prefixed/twig --config=scoper.inc.php
	composer install --no-progress --prefer-dist --optimize-autoloader --no-dev
	npm install
	npm run production
	rm -rf dist
	mkdir dist
	# move everything into dist
	rsync -r --exclude=.git --exclude=node_modules --exclude=./dist . dist
	# cleanup
	find dist -name "*.git*" | xargs rm -rf
	rm -rf dist/lib/modules/podlove_web_player/player_v2/player/podlove-web-player/libs
	rm -rf dist/lib/modules/podlove_web_player/player_v2/player/podlove-web-player/img/banner-772x250.png
	rm -rf dist/lib/modules/podlove_web_player/player_v2/player/podlove-web-player/img/banner-1544x500.png
	rm -rf dist/tests
	rm -rf dist/vendor-bin
	rm -rf dist/vendor/bin
	rm -rf dist/vendor/phpunit/php-code-coverage
	rm -rf dist/vendor/phpunit/phpunit
	rm -rf dist/vendor/phpunit/phpunit-mock-objects
	rm -rf dist/vendor/twig/twig/test
	rm -rf dist/vendor/guzzle/guzzle/tests
	rm -f dist/.travis.yml
	rm -f dist/bower.json
	rm -rf dist/bin
	rm -f dist/wprelease.yml
	rm -f dist/CONTRIBUTING.md
	rm -f dist/Makefile
	rm -f dist/phpunit.xml
	rm -f dist/Rakefile
	rm -f dist/README.md
	find dist -name "*composer.json" | xargs rm -rf
	find dist -name "*composer.lock" | xargs rm -rf
	find dist -name "*.swp" | xargs rm -rf
	# find dist/vendor -type d -iname "test" | xargs rm -rf	
	# find dist/vendor -type d -iname "tests" | xargs rm -rf
	# player v2 / mediaelement
	find dist -iname "echo-hereweare.*" | xargs rm -rf
	find dist -iname "*.jar" | xargs rm -rf

install: install_php_scoper composer_with_prefixing
	npm install

docker-install:
	docker image build -t podlove-publisher-build  - < ./.build/build.dockerfile
	docker run --rm --interactive --tty --volume ${PWD}:/usr/src/myapp -w /usr/src/myapp podlove-publisher-build make install
