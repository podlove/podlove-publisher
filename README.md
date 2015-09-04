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
git clone --recursive https://github.com/podlove/podlove-publisher.git
cd podlove-publisher
curl -sS https://getcomposer.org/installer | php
php composer.phar --dev install
```

## Testing

You need a dedicated database for testing because _running tests wipes all data_. To setup tests, run this once:

```
# bash bin/install-wp-tests.sh <database> <user> <password> <host> <wordpress-version>
bash bin/install-wp-tests.sh wordpress_test root root localhost latest
```

This will download the latest WordPress core and files required for testing into `/tmp`.

Then run `phpunit` to run the tests.

```
./vendor/bin/phpunit
```

#### Web Player

To get and update the web player v3+, use bower and make:

```
bower update
make player
```

[1]: http://flattr.com/thing/728463/Podlove-Podcasting-Plugin-for-WordPress
[2]: http://api.flattr.com/button/flattr-badge-large.png (Flattr This)
[3]: http://wordpress.org/plugins/podlove-podcasting-plugin-for-wordpress/
[4]: https://trello.com/b/zB4mKQlD/podlove-publisher
[5]: https://github.com/podlove/podlove-publisher/issues
[6]: http://docs.podlove.org/
[7]: http://podlove.org/
[8]: https://github.com/podlove/podlove-publisher/releases
[9]: https://community.podlove.org/