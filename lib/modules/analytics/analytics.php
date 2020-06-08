<?php
namespace Podlove\Modules\Podtrac;
use \Podlove\Model;

class Podtrac extends \Podlove\Modules\Base {

    protected $module_name = 'Podtrac';
    protected $module_description = 'Add Podtrac Analytics';
    protected $module_group = 'external services';
    public function load() {
        //add_action( 'init', array( $this, 'register_hooks' ) );
        add_filter('podlove_enclosure_url', function ($original_url) {
            $ext            = pathinfo($original_url, PATHINFO_EXTENSION);
            $schemeless_url = preg_replace('/^https?:\/\//', '', $original_url);
            $podtrac_prefix = "https://dts.podtrac.com/redirect.$ext/";
            return $podtrac_prefix . $schemeless_url;
        });
    }


    public function register_hooks() {
    }

}
