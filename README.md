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

## Where is the Webplayer / Download list?

Right now, these have to be inserted manually via shortcodes.
They are `[podlove-web-player]` and `[podlove-episode-downloads]`.

## A Feed link directs me to a Blog page — What did I do wrong?

Nothing :) Go to `Settings > Permalinks`, hit save and try again.

## How do I add Flattr Integration to my episodes?

1. If you haven't already, get the official Flattr plugin here: http://wordpress.org/extend/plugins/flattr/
2. Find the setting `Flattr > Advanced Settings > Flattrable content > Post Types` and check `podcast`. Save changes.
3. There is no step 3 ;)

# Shortcodes

## Episode Shortcodes

Use these in an episode post:

- `[podlove-episode-downloads]`: Display downloads in a dropdown menu.
- `[podlove-episode-downloads style="buttons"]` : Display download buttons for all available formats.
- `[podlove-web-player]`: Display a web player.

## Episode Data

`[podlove-episode field="..."]` displays the fields data.

**Parameters**
- field: (required) Name of the data field. Possible values:
```
title, subtitle, summary, slug, duration, chapters, image
```

- format: (optional) used by `duration` field. Possible values: `full`, `HH:MM:SS`. Default: `HH:MM:SS`

```
[podlove-episode field="subtitle"]
[podlove-episode field="summary"]
[podlove-episode field="slug"]
[podlove-episode field="duration"]
[podlove-episode field="chapters"]
```

## Podcast Data

`[podlove-podcast field="..."]` displays the fields data.
Alias: `[podlove-show field="..."]`

**Parameters**
- field: (required) Name of the data field. Possible values:
```
title, slug, subtitle, cover_image, summary, author_name, owner_name, owner_email,
publisher_name, publisher_url, license_name, license_url, keywords, explicit,
label, episode_prefix, media_file_base_uri, uri_delimiter, episode_number_length, language
```

## Contributors module

`[podlove-contributors]` Lists all contributors.

**Parameters**
- separator: (optional) Default: ", "

## Episode templates

`[podlove-template id="Template Title"]` Renders configured episode template.

`[podlove-template id="..."]`

**Parameters**

- title: (required) Title of template to render.
- autop: (optional) Wraps blocks of text in p tags. 'yes' or 'no'. Default: 'yes'

# Podlove Publisher Modules

Modules can be compared to WordPress plugins. We use modules to keep the code base clean and decoupled. Furthermore, this system allows for easy activation/deactivation of modules without the risk to break stuff.

## Creating A Module

- each module lives in a separate directory in `/lib/modules`
- each module contains at least one file containing the main module class
- each module class inherits from `\Podlove\Modules\Base`

Each module has a `load()` method. This will be called to load the module. Here hooks can be registered, files be loaded etc. The module must not change any behavior before `load()` being called!

There should be protected properties `$module_name, $module_description`. They can be accessed from the outside via getters. See Base module.

## Naming Conventions

Directories, file names and class names are snake cased. Directories and file names are all lowercased, class names are `Camel_Snake_Cased`. The namespace is CamelCased. Example:

```
Plugin Name: Podlove Web Player
Directory:   podlove_web_player
File Name:   podlove_web_player.php
Class Name:  Podlove_Web_Player
Namespace:   \Podlove\Modules\PodloveWebPlayer
```

### Example Module File

```php
<?php 
namespace Podlove\Modules\PodloveWebPlayer;

class Podlove_Web_Player extends \Podlove\Modules\Base {

	protected $module_name = 'Podlove Web Player';
	protected $module_description = 'An audio player for the web';

	public function load() {
		// register actions
		add_action( 'podlove_dashboard_meta_boxes', array( $this, 'register_meta_boxes' ) );
		// require additional module files
		require_once 'player/podlove-web-player/podlove-web-player.php';
	}

	public function register_meta_boxes() {
		// code ...
	}

}
```

## Options

A module can register its own options. This happens in a declarative way. The Publisher plugin will handle storage and form generation.

### Register Options

Use the module method `public function register_option( $name, $input_type, $args )` to register an option. `$name` is the name of your option. There is no need to prefix it. Uniqueness is handled by the Publisher, so `"title"` would be an acceptable name.

Available input types:

* string
* text
* select
* checkbox
* radio
* image

Right now there is no documentation on forms. Please look [into the form builder file](https://github.com/eteubert/podlove/blob/master/lib/form/input/builder.php) for possible arguments.

```php
<?php
// register options in load()
$this->register_option( 'my setting', 'string', array(
	'label'       => __( 'my setting', 'podlove' ),
	'description' => __( 'yada yada yada', 'podlove' ),
	'default'     => 'A sensible default',
	'html'        => array( 'class' => 'regular-text' )
) );

$this->register_option( 'a_number', 'select', array(
	'label'       => __( 'Number', 'podlove' ),
	'description' => __( 'Select a number', 'podlove' ),
	'default'     => 3,
	'options'     => array( 1 => 'one', 2 => 'two', 3 => 'three' )
) );
```