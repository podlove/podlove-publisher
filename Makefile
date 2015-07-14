player_src = bower_components/podlove-web-player/dist
player_dst = lib/modules/podlove_web_player/player_v3

player:
	mkdir -p $(player_dst)/bin
	mkdir -p $(player_dst)/css
	mkdir -p $(player_dst)/js/vendor
	cp $(player_src)/bin/flashmediaelement.swf $(player_dst)/bin
	cp $(player_src)/css/*.min.css $(player_dst)/css
	cp -r $(player_src)/css/vendor $(player_dst)/css/vendor
	cp -r $(player_src)/js/*.min.js $(player_dst)/js
	cp -r $(player_src)/js/vendor/*.min.js $(player_dst)/js/vendor

build:
	rm -rf dist
	mkdir dist
	# move everything into dist
	rsync -r --exclude=.git --exclude=dist . dist
	# cleanup
	find dist -name "*.git*" | xargs rm -rf
	rm -rf dist/lib/modules/podlove_web_player/player_v2/player/podlove-web-player/libs
	rm -rf dist/lib/modules/podlove_web_player/player_v2/player/podlove-web-player/img/banner-772x250.png
	rm -rf dist/lib/modules/podlove_web_player/player_v2/player/podlove-web-player/img/banner-1544x500.png
	rm -rf dist/tests
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
	find dist/vendor -type d -iname "test" | xargs rm -rf
	find dist/vendor -type d -iname "tests" | xargs rm -rf
	# player v2 / mediaelement
	find dist -iname "echo-hereweare.*" | xargs rm -rf
	find dist -iname "*.jar" | xargs rm -rf
