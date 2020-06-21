<?php
namespace Podlove\Modules\ExternalAnalytics;
use \Podlove\Model;

class External_Analytics extends \Podlove\Modules\Base {

    protected $module_name = 'External Analytics';
    protected $module_description = 'Add an external analytics service, e.g. Podtrac, Blubrry, etc.';
    protected $module_group = 'external services';
    public function load() {
        $this->register_option( 'analytics_prefix', 'string', array(
            'label'       => __( 'Analytics Prefix', 'podlove-podcasting-plugin-for-wordpress' ),
            'description' => '
				' . '<p><b>' . __( 'Examples:', 'podlove-podcasting-plugin-for-wordpress' ) . '</b></p>
				' . '<ul>
				' . '<li><a href="https://publisher.podtrac.com" target="_blank">Podtrac</a>: https://dts.podtrac.com/redirect.mp3/</li>
				' . '<li><a href="https://stats.blubrry.com" target="_blank">Blubrry</a>: http://media.blubrry.com/{blubrry_id}/</li>
				' . '<li><a href="https://chartable.com/publishers" target="_blank">Chartable</a>: https://chtbl.com/track/{chtbl_id}/</li>
				' . '<li>' . __( 'etc.', 'podlove-podcasting-plugin-for-wordpress' ) . '</li>
				' . '</ul>
                ',
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
            return;
        
        add_filter('podlove_enclosure_url', function ($original_url) use ( $analytics_prefix ){
            $schemeless_url = preg_replace('/^https?:\/\//', '', $original_url);
            $podtrac_prefix = $analytics_prefix;
            return trailingslashit( $analytics_prefix ) . $schemeless_url;
        });
    }

}
