#!/usr/bin/env bash

wget -O data/apps.runtime.json https://raw.githubusercontent.com/opawg/user-agents-v2/refs/heads/master/build/apps.runtime.json
wget -O data/bots.runtime.json https://raw.githubusercontent.com/opawg/user-agents-v2/refs/heads/master/build/bots.runtime.json
wget -O data/browsers.runtime.json https://raw.githubusercontent.com/opawg/user-agents-v2/refs/heads/master/build/browsers.runtime.json
wget -O data/devices.runtime.json https://raw.githubusercontent.com/opawg/user-agents-v2/refs/heads/master/build/devices.runtime.json
wget -O data/libraries.runtime.json https://raw.githubusercontent.com/opawg/user-agents-v2/refs/heads/master/build/libraries.runtime.json
wget -O data/referrers.runtime.json https://raw.githubusercontent.com/opawg/user-agents-v2/refs/heads/master/build/referrers.runtime.json
