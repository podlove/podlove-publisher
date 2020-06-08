<?php
namespace Podlove\Modules\Analytics;
use \Podlove\Model;

class Analytics extends \Podlove\Modules\Base {

    protected $module_name = 'Analytics';
    protected $module_description = 'Add Analytics';
    protected $module_group = 'external services';
    public function load() {
        add_action( 'init', array( $this, 'register_hooks' ) );
        $this->register_option( 'analytics_prefix', 'string', array(
                'label'       => __( 'Analytics Prefix', 'podlove-podcasting-plugin-for-wordpress' ),
                'description' => __( 'dont forget the trailing /', 'podlove-podcasting-plugin-for-wordpress' ),
                'html'        => array(
                        'class' => 'regular-text podlove-check-input',
                        'data-podlove-input-type' => 'text',
                        'placeholder' => 'https://dts.podtrac.com/redirect.mp3/'
                )
        ) );
    }


    public function register_hooks() {
	$analytics_prefix = $this->get_module_option( 'analytics_prefix' );
	if ( ! $analytics_prefix )
            return;
        
//         add_filter('podlove_enclosure_url', function ($original_url) {
//             $ext            = pathinfo($original_url, PATHINFO_EXTENSION);
//             $schemeless_url = preg_replace('/^https?:\/\//', '', $original_url);
//             $podtrac_prefix = "https://dts.podtrac.com/redirect.$ext/";
//             return $podtrac_prefix . $schemeless_url;
//         });
        add_filter('podlove_enclosure_url', function ($original_url) {
            //$ext            = pathinfo($original_url, PATHINFO_EXTENSION);
            $schemeless_url = preg_replace('/^https?:\/\//', '', $original_url);
            //$podtrac_prefix = "https://dts.podtrac.com/redirect.$ext/";
            $podtrac_prefix = $analytics_prefix;
            return $podtrac_prefix . $schemeless_url;
        });
    }

}
