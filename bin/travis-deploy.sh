make build
# add build number to version
sed -i.bak "s/\(Version:.*\)/\1.build$TRAVIS_BUILD_NUMBER/" dist/podlove.php
rm -f dist/podlove.php.bak
mv dist podlove-podcasting-plugin-for-wordpress
zip -r latest.zip podlove-podcasting-plugin-for-wordpress
# curl -v --ftp-create-dirs -T latest.zip  sftp://$FTP_USER:$FTP_PASSWORD@eric.co.de/home/podloveftp/files/$TRAVIS_REPO_SLUG/$TRAVIS_BRANCH/
chmod 600 ./deploy_key
ssh -i ./deploy_key $FTP_USER@eric.co.de "mkdir -p /home/podloveftp/files/$TRAVIS_REPO_SLUG/$TRAVIS_BRANCH/"
scp -i ./deploy_key latest.zip $FTP_USER@eric.co.de/home/podloveftp/files/$TRAVIS_REPO_SLUG/$TRAVIS_BRANCH/latest.zip
