<?php
use Podlove\Model;

if (\Podlove\get_setting('metadata', 'enable_episode_license' )) {
	add_action('podlove_episode_meta_box_end', 'podlove_episode_license_add_js');
	add_filter('podlove_episode_form_data', 'podlove_episode_license_extend_form', 10, 2);
}

function podlove_episode_license_add_js() {
	
	$episode = Model\Episode::find_or_create_by_post_id(get_the_ID());
	?>
	<script type="text/javascript">
	PODLOVE.License({
		plugin_url: "<?php echo \Podlove\PLUGIN_URL; ?>",

		locales:  JSON.parse('<?php echo json_encode(\Podlove\License\locales_cc()); ?>'),
		versions: JSON.parse('<?php echo json_encode(\Podlove\License\version_per_country_cc()); ?>'),
		license:  JSON.parse('<?php echo json_encode(\Podlove\Model\License::get_license_from_url($episode->license_url)); ?>'),

		license_name_field_id: '#_podlove_meta_license_name',
		license_url_field_id:  '#_podlove_meta_license_url'
	});
	</script>
	<?php
}

function podlove_episode_license_extend_form ($form_data, $episode) {

	$podcast = Model\Podcast::get_instance();
	$license = $episode->get_license();

	$form_data[] = array(
		'type' => 'string',
		'key'  => 'license_name',
		'options' => array(
			'label' => __( 'License Name', 'podlove' )
		),
		'position' => 525
	);

	$form_data[] = array(
		'type' => 'string',
		'key'  => 'license_url',
		'options' => array(
			'label'       => __( 'License URL', 'podlove' ),
			'description' => __( 'Example: http://creativecommons.org/licenses/by/3.0/', 'podlove' )
		),
		'position' => 524
	);

	$form_data[] = array(
		'type' => 'callback',
		'key'  => 'license_url',
		'options' => array(
			'label'       => '
				<span id="podlove_cc_license_selector_toggle">
					<span class="_podlove_episode_list_triangle">&#9658;</span>
					<span class="_podlove_episode_list_triangle_expanded">&#9660;</span>
					' . __('License Selector', 'podlove') . '
				</span>
				',
			'callback' => function() {}
		),
		'position' => 523
	);

	$form_data[] = array(
		'type' => 'callback',
		'key'  => 'podlove_cc_license_selector',
		'options' => array(
			'label' => '',
			'callback' => function() {
				?>
				<div class="row_podlove_cc_license_selector">
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
				</div>
				<?php
			}
		),
		'position' => 522
	);

	$form_data[] = array(
		'type' => 'callback',
		'key'  => 'podlove_podcast_license_preview',
		'options' => array(
			'label' => '',
			'callback' => function() {
				?>
				<div class="row_podlove_podcast_license_preview">
						<span><label for="podlove_podcast_subtitle">License Preview</label></span>
						<p class="podlove_podcast_license_image"></p>
						<div class="podlove_license">
							<p>
								This work is licensed under the 
								<a class="podlove-license-link" rel="license" href=""></a>.
							</p>
						</div>
				</div>
				<?php
			}
		),
		'position' => 521
	);

	return $form_data;
}

add_filter('podlove_episode_data_filter', function ($filter) {
	return array_merge($filter, [
		'license_name' => FILTER_SANITIZE_STRING,
		'license_url'  => FILTER_SANITIZE_URL
	]);
});