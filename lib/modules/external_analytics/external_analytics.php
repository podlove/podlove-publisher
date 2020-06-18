<?php
namespace Podlove\Modules\ExternalAnalytics;
use \Podlove\Model;

class External_Analytics extends \Podlove\Modules\Base {

    protected $module_name = 'External Analytics';
    protected $module_description = 'Add Analytics for an external site, e.g. podtrac, blubrry etc.';
    protected $module_group = 'external services';
    public function load() {
        $this->register_option( 'analytics_prefix', 'string', array(
                'label'       => __( 'Analytics Prefix', 'podlove-podcasting-plugin-for-wordpress' ),
                'description' => __( 'dont forget the trailing /', 'podlove-podcasting-plugin-for-wordpress' ),
                'html'        => array(
                        'class' => 'regular-text podlove-check-input',
                        'data-podlove-input-type' => 'text',
                        'placeholder' => 'https://dts.podtrac.com/redirect.mp3/'
                )
        ) );
        add_action( 'init', array( $this, 'register_hooks' ) );
    }


    public function register_hooks() {
	    $analytics_prefix = $this->get_module_option( 'analytics_prefix' );
	    if ( ! $analytics_prefix )
            return "" . $original_url;
        
        add_filter('podlove_enclosure_url', function ($original_url) use ( $analytics_prefix ){
            $schemeless_url = preg_replace('/^https?:\/\//', '', $original_url);
            $podtrac_prefix = $analytics_prefix;
            return $podtrac_prefix . $schemeless_url;
        });
    }

}
