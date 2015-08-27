# Podlove Podcast Publisher

This is the podcast publishing plugin for WordPress.

- [Getting Started & Documentation][6]
- [Podlove Community][9]
- Latest stable version: in [WordPress plugin directory][3]
- [Podlove Project & Blog][7]
- Report a bug: [Use GitHub Issues][5]

[![Flattr This][2]][1]

## Development

Code dependencies are managed via [Composer](http://getcomposer.org/). So you need to clone the repository and then fetch the dependencies via Composer.

```
git clone --recursive git@github.com:<your-name-here>/podlove-publisher.git
cd podlove-publisher
curl -sS https://getcomposer.org/installer | php
php composer.phar --dev install
```


### Vagrant virtual machine

For easy setup of a virtual machine for development including the latest WordPress release, Apache 2.4, PHP 5.6 and MySQL 5.6, a configuration file for
Vagrant is included.

Just run the following command in the root directory of your checkout:

```
vagrant up
```

The provisioning script will take care of all the initial installation stuff. Then point your browser to `http://localhost:8080` for HTTP or
`https://localhost:8443` for HTTPS. Database username and password is `root`. A database called `wordpress` is already created.


## Release Checklist

- Ensure changelog in `readme.txt` has an entry for that version
- Update "tested with WordPress version" in `readme.txt`
- Merge release-branch into master
- Increase version number in `podlove.php`
- `git add -A . && git commit -m ":shipit: release <version>"`
- git push
- Deploy to WordPress plugin repository
- Post changelog to [Github Releases][8]
- Rerun `bin/template_ref_json.php` and `bin/template_ref.rb`
- Rebuild & Redeploy docs.podlove.org
- Remove/cleanup published feature branches
- Create new release branch


[1]: http://flattr.com/thing/728463/Podlove-Podcasting-Plugin-for-WordPress
[2]: http://api.flattr.com/button/flattr-badge-large.png (Flattr This)
[3]: http://wordpress.org/plugins/podlove-podcasting-plugin-for-wordpress/
[4]: https://trello.com/b/zB4mKQlD/podlove-publisher
[5]: https://github.com/podlove/podlove-publisher/issues
[6]: http://docs.podlove.org/
[7]: http://podlove.org/
[8]: https://github.com/podlove/podlove-publisher/releases
[9]: https://community.podlove.org/
