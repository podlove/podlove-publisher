#!/usr/bin/env bash

if [ $# -lt 1 ]; then
    echo "usage: $0 <version-string/tag>"
    echo "Get available tags from here: https://github.com/podlove/podlove-web-player/releases"
    exit 1
fi

VERSION_STRING=$1
RELEASES_BASEURL=https://github.com/podlove/podlove-web-player/releases/download/

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    fi
}

declare -a files=("embed.js" "share.html" "share.js" "window.js")

for file in "${files[@]}"
do
    echo "Downloading $file..."
    download $RELEASES_BASEURL$VERSION_STRING/$file ./lib/modules/podlove_web_player/player_v4/dist/$file
done


