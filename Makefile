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