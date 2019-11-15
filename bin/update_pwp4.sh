#!/usr/bin/env bash

npm update @podlove/web-player
rm -r lib/modules/podlove_web_player/player_v4/dist
cp -r node_modules/@podlove/web-player/ lib/modules/podlove_web_player/player_v4/dist
