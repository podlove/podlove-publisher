# Podlove Modules

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
