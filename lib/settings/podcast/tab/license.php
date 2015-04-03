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
			'license_name',
			'license_url'
		);

		$settings = get_option('podlove_podcast');
		foreach ($formKeys as $key) {
			$settings[$key] = $_POST['podlove_podcast'][$key];
		}
		update_option('podlove_podcast', $settings);
		header('Location: ' . $this->get_url());
	}

	public function register_page() {
		$podcast = \Podlove\Model\Podcast::get();
		
		$form_attributes = array(
			'context' => 'podlove_podcast',
			'action'  => $this->get_url()
		);

		\Podlove\Form\build_for( $podcast, $form_attributes, function ( $form ) {

			$wrapper = new \Podlove\Form\Input\TableWrapper( $form );
			$podcast = $form->object;
			
			$podcast = \Podlove\Model\Podcast::get();

			$wrapper->string( 'license_name', array(
				'label'       => __( 'License Name', 'podlove' ),
				'html' => array( 'class' => 'regular-text podlove-check-input' )
			) );

			$wrapper->string( 'license_url', array(
				'label'       => __( 'License URL', 'podlove' ),
				'html' => array( 'class' => 'regular-text podlove-check-input', 'data-podlove-input-type' => 'url' ),
				'description' => __( 'Example: http://creativecommons.org/licenses/by/3.0/', 'podlove' )
			) );
			?>
				
				<tr class="row_podlove_cc_license_selector_toggle">
					<th></th>
					<td>
						<span id="podlove_cc_license_selector_toggle">
							<span class="_podlove_episode_list_triangle">&#9658;</span>
							<span class="_podlove_episode_list_triangle_expanded">&#9660;</span>
							License Selector
						</span>
					</td>
				</tr>
				<tr class="row_podlove_cc_license_selector">
					<th></th>
					<td>
						<div>
							<label for="license_cc_version" class="podlove_cc_license_selector_label">Version</label>
							<select id="license_cc_version">
								<option value="cc0">Public Domain</option>
								<option value="pdmark">Public Domain Mark</option>
								<option value="cc3">Creative Commons 3.0 and earlier</option>
								<option value="cc4">Creative Commons 4.0</option>
							</select>
						</div>
						<div class="podlove-hide">
							<label for="license_cc_allow_modifications" class="podlove_cc_license_selector_label">Allow modifications of your work?</label>
							<select id="license_cc_allow_modifications">
								<option value="yes">Yes</option>
								<option value="yesbutshare">Yes, as long as others share alike</option>
								<option value="no">No</option>
							</select>
						</div>
						<div class="podlove-hide">
							<label for="license_cc_allow_commercial_use" class="podlove_cc_license_selector_label">Allow commercial uses of your work?</label>
							<select id="license_cc_allow_commercial_use">
								<option value="yes">Yes</option>
								<option value="no">No</option>
							</select>
						</div>
						<div class="podlove-hide">
							<label for="license_cc_license_jurisdiction" class="podlove_cc_license_selector_label">License Jurisdiction</label>
							<select id="license_cc_license_jurisdiction">
								<?php
									foreach ( \Podlove\License\locales_cc() as $locale_key => $locale_description) {
										echo "<option value='" . $locale_key . "' " . ( $locale_key == 'international' ? "selected='selected'" : '' ) . ">" . $locale_description . "</option>\n";
									}
								?>			
							</select>
						</div>
					</td>
				</tr>
				<tr class="row_podlove_podcast_license_preview">
					<th scope="row" valign="top">
							<label for="podlove_podcast_subtitle">License Preview</label>
					</th>
					<td>
						<p class="podlove_podcast_license_image"></p>
						<div class="podlove_license">
							<p>
								This work is licensed under the 
								<a class="podlove-license-link" rel="license" href=""></a>.
							</p>
						</div>
					</td>
				</tr>
			<?php
		});
		?>
		<script type="text/javascript">
		PODLOVE.License({
			plugin_url: "<?php echo \Podlove\PLUGIN_URL; ?>",

			types: JSON.parse('<?php echo json_encode(\Podlove\License\locales_cc()); ?>'),
			locales: JSON.parse('<?php echo json_encode(\Podlove\License\locales_cc()); ?>'),
			versions: JSON.parse('<?php echo json_encode(\Podlove\License\version_per_country_cc()); ?>'),
			license: JSON.parse('<?php echo json_encode(\Podlove\Model\License::get_license_from_url($podcast->license_url)); ?>'),

			license_name_field_id: '#podlove_podcast_license_name',
			license_url_field_id: '#podlove_podcast_license_url'
		});

		</script>
		<?php
	}
}