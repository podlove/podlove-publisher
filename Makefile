player_src = bower_components/podlove-web-player/dist
player_dst = lib/modules/podlove_web_player/playerv3

player:
	mkdir -p $(player_dst)/bin
	mkdir -p $(player_dst)/css
	mkdir -p $(player_dst)/js
	cp $(player_src)/bin/flashmediaelement.swf $(player_dst)/bin
	cp $(player_src)/css/* $(player_dst)/css
	cp $(player_src)/js/*.min.js $(player_dst)/js