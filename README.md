# Podlove Podcast Publisher

This is the podcast publishing plugin for WordPress.

- [Getting Started & Documentation][6]
- [Podlove Community][9]
- Latest stable version: in [WordPress plugin directory][3]
- [Podlove Project & Blog][7]
- Report a bug: [Use GitHub Issues][5]

[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fpodlove%2Fpodlove-publisher.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2Fpodlove%2Fpodlove-publisher?ref=badge_shield)

## Development Setup

PHP dependencies are managed via [Composer](http://getcomposer.org/). So you need to clone the repository and then fetch the dependencies via Composer. JavaScript packages are managed with [yarn](https://yarnpkg.com/lang/en/).

Clone the publisher in the `wp-content/plugins` directory.

```
git clone --recursive https://github.com/podlove/podlove-publisher.git
cd podlove-publisher
curl -sS https://getcomposer.org/installer | php
make install
```

If you have a docker environment handy you can simply run:

```
make docker-install
```

## Development

Use webpack when working with JS or CSS files:

```
yarn run dev
```

## Formatting Code

Use [PHP-CS-Fixer](https://github.com/FriendsOfPhp/PHP-CS-Fixer) to format code before committing.

You can do so manually via command line (`make format`) or configure your editor to format the file on save. For VS Code, use the "php cs fixer" extension by junstyle.

## Releases

Both beta and stable releases are creates with GitHub Actions.

To release a new stable version:

1. _manually_ update the following fields in `readme.txt`:
  - Tested up to
  - Stable tag
  - check that changelog has an entry
2. `bash bin/release.sh`, which does:
  - updates version in `podlove.php`
  - creates release commit
  - tags commit
3. git push

The GitHub action detects the release via the tag, builds it and submits it to the wordpress.org plugin directory.

[3]: https://wordpress.org/plugins/podlove-podcasting-plugin-for-wordpress/
[4]: https://trello.com/b/zB4mKQlD/podlove-publisher
[5]: https://github.com/podlove/podlove-publisher/issues
[6]: http://docs.podlove.org/
[7]: http://podlove.org/
[8]: https://github.com/podlove/podlove-publisher/releases
[9]: https://community.podlove.org/


## License
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fpodlove%2Fpodlove-publisher.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2Fpodlove%2Fpodlove-publisher?ref=badge_large)
