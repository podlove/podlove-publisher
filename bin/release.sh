#!/usr/bin/env bash

PLUGIN_FILE=./podlove.php

CURRENT_VERSION=`head -n 20 $PLUGIN_FILE | grep "Version:" | cut -d: -f2 | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//'`

echo "Creating a release will:"
echo "  - update the version in $PLUGIN_FILE"
echo "  - commit that change"
echo "  - create a git tag on that commit with the given version"
echo ""

echo Current Version: $CURRENT_VERSION
echo "Input new version:"
read version

echo "----------"
echo "Current Version: $CURRENT_VERSION"
echo "New Version:     $version"
echo "----------"

while true; do
    read -p "Correct & Continue?" yn
    case $yn in
        [Yy]* )
          sed -i.bak "s/\(Version:\).*/\1 `echo $version | rev | cut -d/ -f1 | rev`/" $PLUGIN_FILE
          rm $PLUGIN_FILE.bak
          git add $PLUGIN_FILE
          git commit -m "release $version"
          git tag -f $version -m $version
          break;;
        [Nn]* ) exit;;
        * ) echo "Please answer yes or no.";;
    esac
done
