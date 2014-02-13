<?php
namespace Podlove\Settings\Podcast\Tab;
use \Podlove\Settings\Podcast\Tab;

class License extends Tab {

	public function init() {
		add_action( $this->page_hook, array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	public function process_form() {
		if (!isset($_POST['podlove_podcast']) || !$this->is_active())
			return;

		$formKeys = array(
			'license_type',
			'license_name',
			'license_url',
			'license_cc_allow_modifications',
			'license_cc_allow_commercial_use',
			'license_cc_license_jurisdiction'
		);

		$settings = get_option('podlove_podcast');
		foreach ($formKeys as $key) {
			$settings[$key] = $_POST['podlove_podcast'][$key];
		}
		update_option('podlove_podcast', $settings);
		header('Location: ' . $this->get_url());
	}

	public function register_page() {
		$podcast = \Podlove\Model\Podcast::get_instance();
		
		$form_attributes = array(
			'context' => 'podlove_podcast',
			'action'  => $this->get_url()
		);

		\Podlove\Form\build_for( $podcast, $form_attributes, function ( $form ) {
			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$podcast = $form->object;
			
			$podcast = \Podlove\Model\Podcast::get_instance();

			$wrapper->select( 'license_type', array(
				'label'       => __( 'License', 'podlove' ),
				'options' 	  => array('cc' => 'Creative Commons', 'other' => 'Other'),
				'description' => __( "<p class=\"podlove_podcast_license_status\"></p>", 'podlove' )
			) );

			$wrapper->string( 'license_name', array(
				'label'       => __( 'License Name', 'podlove' )
			) );

			$wrapper->string( 'license_url', array(
				'label'       => __( 'License URL', 'podlove' ),
				'description' => __( 'Example: http://creativecommons.org/licenses/by/3.0/', 'podlove' )
			) );

			$wrapper->select( 'license_cc_allow_modifications', array(
				'label'       => __( 'Modification', 'podlove' ),
				'description' => __( 'Allow modifications of your work?', 'podlove' ),
				'options' => array('yes' => 'Yes', 'yesbutshare' => 'Yes, as long as others share alike', 'no' => 'No')
			) );

			$wrapper->select( 'license_cc_allow_commercial_use', array(
				'label'       => __( 'Commercial Use', 'podlove' ),
				'description' => __( 'Allow commercial uses of your work?', 'podlove' ),
				'options' => array('yes' => 'Yes', 'no' => 'No')
			) );

			$wrapper->select( 'license_cc_license_jurisdiction', array(
				'label'       => __( 'License Jurisdiction', 'podlove' ),
				'options' => \Podlove\License\locales_cc()
			) );

			?>
				<tr class="row_podlove_podcast_license_preview">
					<th scope="row" valign="top">
							<label for="podlove_podcast_subtitle">License Preview</label>
					</th>
					<td>
						<p class="podlove_podcast_license_image"></p>
					</td>
				</tr>
			<?php
		});
		?>
		<script type="text/javascript">
		PODLOVE.License({
			plugin_url: "<?php echo \Podlove\PLUGIN_URL; ?>",

			locales: JSON.parse('<?php echo json_encode(\Podlove\License\locales_cc()); ?>'),
			versions: JSON.parse('<?php echo json_encode(\Podlove\License\version_per_country_cc()); ?>'),

			container: '.row_podlove_podcast_license_type',
			type: '<?php echo $podcast->license_type; ?>',
			status: '.podlove_podcast_license_status',
			image: '.podlove_podcast_license_image',
			image_row: 'tr.podlove_podcast_license_image',
			form_row_cc_preview: 'tr.row_podlove_podcast_license_preview',

			form_type: '#podlove_podcast_license_type',
			form_other_name: '#podlove_podcast_license_name',
			form_other_url: '#podlove_podcast_license_url',
			form_cc_commercial_use: '#podlove_podcast_license_cc_allow_commercial_use',
			form_cc_modification: '#podlove_podcast_license_cc_allow_modifications',
			form_cc_jurisdiction: '#podlove_podcast_license_cc_license_jurisdiction',
			form_cc_preview: '#podlove_podcast_license_preview',

			form_row_other_name: 'tr.row_podlove_podcast_license_name',
			form_row_other_url: 'tr.row_podlove_podcast_license_url',
			form_row_cc_commercial_use: 'tr.row_podlove_podcast_license_cc_allow_commercial_use',
			form_row_cc_modification: 'tr.row_podlove_podcast_license_cc_allow_modifications',
			form_row_cc_jurisdiction: 'tr.row_podlove_podcast_license_cc_license_jurisdiction'
		});
		</script>
		<?php
	}
}