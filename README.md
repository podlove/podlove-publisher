# Podlove Podcast Publisher

Work before progress. Feel free to touch but handle with care.

[![Flattr This][2]][1]

  [1]: http://flattr.com/thing/728463/Podlove-Podcasting-Plugin-for-WordPress
  [2]: http://api.flattr.com/button/flattr-badge-large.png (Flattr This)

## Wanna help? Cool! Here's how

1. Fork this project
2. `git clone --recursive git@github.com:<yourname>/podlove.git` Be sure to add the `--recursive` as we're using [git submodules](http://git-scm.com/book/en/Git-Tools-Submodules).
3. Develop, develop, develop!
4. Send me a [pull request](https://help.github.com/articles/using-pull-requests)

If you'd like to add a whole feature with UI and stuff, please consider developing it as a [Podlove Module](https://github.com/eteubert/podlove/blob/master/lib/modules/readme.md).

## Links

- official WordPress plugin site: [http://wordpress.org/extend/plugins/podlove-podcasting-plugin-for-wordpress/](http://wordpress.org/extend/plugins/podlove-podcasting-plugin-for-wordpress/)
- [http://podlove.org](http://podlove.org)
- FAQ: [http://eteubert.github.com/podlove](http://eteubert.github.com/podlove)
- my (german) podcast on the development of this plugin: [http://www.satoripress.com/podcast/](http://www.satoripress.com/podcast/)
- Trello board (german): [https://trello.com/board/podlove-publisher/508293f65573fa3f62004e0a](https://trello.com/board/podlove-publisher/508293f65573fa3f62004e0a)

# FAQ

### Where is the Webplayer / Download list?

Right now, these have to be inserted manually via shortcodes.
They are `[podlove-web-player]` and `[podlove-episode-downloads]`.

### A Feed link directs me to a Blog page — What did I do wrong?

Nothing :) Go to `Settings > Permalinks`, hit save and try again.

### How do I add Flattr Integration to my episodes?

1. If you haven't already, get the official Flattr plugin here: http://wordpress.org/extend/plugins/flattr/
2. Find the setting `Flattr > Advanced Settings > Flattrable content > Post Types` and check `podcast`. Save changes.
3. There is no step 3 ;)

### My files are not found. What am I doing wrong?

1. Look at the debug output and doublecheck that the generated URLs are correct.
2. Look at the debug output and look for the http_code. Is it 200? Great! Maybe it's 406? Not so great. That means the server where your files are stored doesn't like WordPress. Try adding the following to the .htaccess file of the server where your files are:

```
<IfModule mod_security.c>
SecFilterEngine Off
SecFilterScanPOST Off
</IfModule>
```