<?php 
namespace Podlove\Modules\AuphonicProductionData;
use \Podlove\Model;

class Auphonic_Production_Data extends \Podlove\Modules\Base {

	protected $module_name = 'Auphonic Production Data';
	protected $module_description = 'Use Auphonic production description file to automatically fill in episode title, subtitle, summary and duration.<br>In the Auphoninc production, you need to add JSON "Production Description".';

	public function load() {
		add_action( 'admin_print_styles', array( $this, 'add_jquery_auphonicdata' ) );
		add_action( 'podlove_episode_form', array( $this, 'add_media_base_url' ), 1, 2 );
		add_action( 'wp_ajax_get_auphonic_data', array($this, 'get_auphonic_data_callback'));
	}

	function add_jquery_auphonicdata() {
    	
		if ( 'podcast' !== get_post_type() ) 
			return;	

		wp_register_script(
			'auphonic-production-data-script',
			$this->get_module_url() . '/js/auphonic_production_data.js',
			array( 'jquery' ),
			"6"
		);
		wp_enqueue_script('auphonic-production-data-script', array('jquery'));

	}

	public function add_media_base_url ( $form_wrapper, $episode ) {
		$podcast = Model\Podcast::get_instance();	
		?>
		<script type="text/javascript">
		var podlove_media_base_url = '<?php echo($podcast->media_file_base_uri); ?>';
		</script>
		<?php 
	}

	function get_auphonic_data_callback() {
		$url = $_REQUEST['url'];
		echo wp_remote_retrieve_body( wp_remote_get( $url ));
		die(); // this is required to return a proper result
	}

}