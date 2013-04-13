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

		// make asset assignment configurable
		add_filter( 'podlove_model_asset_assignment_schema', array( $this, 'add_metadata_to_asset_assignments' ) );

		add_action( 'podlove_asset_assignment_form', array( $this, 'add_metadata_to_asset_assignments_form'), 10, 2 );
	}

	public function add_metadata_to_asset_assignments( $asset_assignment ) {
		$asset_assignment->property( 'metadata' );
		return $asset_assignment;
	}

	public function add_metadata_to_asset_assignments_form( $wrapper, $asset_assignment ) {

		$metadata_file_options = array( '0' => __( 'None', 'podlove' ) );

		foreach ( Model\EpisodeAsset::all() as $episode_asset ) {
			$file_type = $episode_asset->file_type();
			if ( $file_type && $file_type->type === 'metadata' ) {
				$metadata_file_options[ $episode_asset->id ] = sprintf( __( 'Asset: %s', 'podlove' ), $episode_asset->title );
			}
		}

		$wrapper->select( 'metadata', array(
			'label'   => __( 'Episode Metadata', 'podlove' ),
			'options' => $metadata_file_options
		) );
	}

	public function add_jquery_auphonicdata() {
    	
		if ( 'podcast' !== get_post_type() ) 
			return;	

		wp_register_script(
			'auphonic-production-data-script',
			$this->get_module_url() . '/js/auphonic_production_data.js',
			array( 'jquery' ),
			\Podlove\get_plugin_header( 'Version' )
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

		if ( isset( $_REQUEST['slug'] ) ) { // use unsaved episode slug
			add_filter( 'podlove_file_url_template', function ( $template ) {
				return str_replace( '%episode_slug%', $_REQUEST['slug'], $template );;
			} );
		}

		$post_id = (int) $_GET['post_id'];
		$episode = Model\Episode::find_or_create_by_post_id( $post_id );
		$asset_assignment = Model\AssetAssignment::get_instance();

		$url = NULL;
		$valid_file_types = array();
		foreach ( $episode->media_files() as $media_file ) {

			$asset = $media_file->episode_asset();
			$valid_file_types[] = $asset->file_type()->type;

			if ( $asset->id == $asset_assignment->metadata ) {
				$url = $media_file->get_file_url();
			}
		}
		
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		if ( $url ) {
			echo wp_remote_retrieve_body( wp_remote_get( $url ));
		} else {

			$is_file_type_registered = array_map( function($asset){ return $asset->file_type()->type;}, Model\EpisodeAsset::all() );
			$is_asset_assigned = Model\AssetAssignment::get_instance()->metadata > 0;

			if ( ! $is_file_type_registered || ! $is_asset_assigned ) {
				$message = "--- # Can't read file from Auphonic\n";

				if ( ! $is_file_type_registered )
					$message .= "--- ## There is no episode asset of type \"metadata\"\n";

				if ( ! $is_asset_assigned )
					$message .= "--- ## There is no asset assigned as \"Episode Metadata\"\n";

				echo json_encode( array( 'message' => $message ) );
			}
		}
		die();
	}

}