 make build
 # add build number to version
 sed -i.bak "s/\(Version:.*\)/\1.build$TRAVIS_BUILD_NUMBER/" dist/podlove.php
 rm -f dist/podlove.php.bak
 mv dist podlove-podcasting-plugin-for-wordpress
 zip -r latest.zip podlove-podcasting-plugin-for-wordpress
 curl --ftp-create-dirs -T latest.zip -u $FTP_USER:$FTP_PASSWORD ftp://eric.co.de/files/$TRAVIS_REPO_SLUG/$TRAVIS_BRANCH/
