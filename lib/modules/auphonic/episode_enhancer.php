<?php 
namespace Podlove\Modules\Auphonic;

use \Podlove\Model;

/**
 * Auphonic Episode Enhancer
 *
 * Adds an Auphonic interface to the episode management forms.
 */
class EpisodeEnhancer {

	private $module;

	public function __construct(\Podlove\Modules\Auphonic\Auphonic $module)
	{
		$this->module = $module;

		add_action('save_post', array($this, 'save_post'));

		if ($this->module->get_module_option('auphonic_api_key') != "") {
			add_filter('podlove_episode_form_data', array($this, 'auphonic_episodes'), 10, 2);
		}
	}

	public function auphonic_episodes($form_data, $episode)
	{
		$form_data[] = array(
			'type' => 'callback',
			'key'  => 'import_from_auphonic_form',
			'options' => array(
				'label'    => __('Auphonic', 'podlove'),
				'callback' => array($this, 'auphonic_episodes_form')
			),
			'position' => 500
		);

		return $form_data;
	}

	public function save_post($post_id)
	{
		if (get_post_type($post_id) !== 'podcast')
			return;

		if (!current_user_can('edit_post', $post_id))
			return;

		if (isset($_REQUEST['_auphonic_production']))
			update_post_meta( $post_id, '_auphonic_production', $_REQUEST['_auphonic_production']);
	}

	public function auphonic_episodes_form() {
		$asset_assignments = Model\AssetAssignment::get_instance();
		?>

		<input type="hidden" id="_auphonic_production" name="_auphonic_production" value="<?php echo get_post_meta( get_the_ID(), '_auphonic_production', true ) ?>" />
		<input type="hidden" id="auphonic" value="1"
			data-api-key="<?php echo $this->module->get_module_option('auphonic_api_key') ?>"
			data-presetuuid="<?php echo $this->module->get_module_option('auphonic_production_preset') ?>"
			data-assignment-chapter="<?php echo $asset_assignments->chapters ?>"
			data-assignment-image="<?php echo $asset_assignments->image ?>"
			data-module-url="<?php echo $this->module->get_module_url() ?>"
			/>

		<script type="text/template" id="podlove-auphonic-track-row">
		<tr class="media_file_row">
			<td class="podlove-auphonic-track-identifier">
				<input type="text" class="regular-text" id="auphonic_track_{{id}}" name="auphonic_track[{{id}}]" />
			</td>
			<td class="podlove-auphonic-sources">
				<select id="auphonic_services_{{id}}" class="auphonic_services" data-trackid="{{id}}">
					<option><?php echo __( 'Loading sources ...' ) ?></option>
				</select>
			</td>
			<td>
				<span id="fetch_auphonic_production_files_{{id}}" class="fetch_auphonic_production_files" title="<?php echo __( 'Fetch available audio files.', 'podlove' ) ?>" data-trackid="{{id}}">
					<span class="state_idle"><i class="podlove-icon-repeat"></i></span>
					<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
					<span class="state_success"><i class="podlove-icon-ok"></i></span>
					<span class="state_fail"><i class="podlove-icon-remove"></i></span>
				</span>
				<select id="auphonic_production_files_{{id}}" class="auphonic_production_files" name="input_file[{{id}}]">
					<option>-</option>
				</select>
				<input type="text" id="auphonic_http_upload_url_{{id}}" name="auphonic_http_upload_url[{{id}}]" class="auphonic_http_upload_url" style="display:none" class="large-text" />
				<input type="file" id="auphonic_local_upload_url_{{id}}" name="auphonic_local_upload_url[{{id}}]" class="auphonic_local_upload_url" style="display:none" class="large-text" />
			</td>
			<td>
				<i class="podlove-auphonic-remove-track podlove-icon-remove"></i>
			</td>
		</tr>
		</script>

		<div id="auphonic-box">

			<em id="auphonic-credits-status">Available processing time: <span id="auphonic-credits"></span></em>

			<div id="auphonic-production-status" class="auphonic-status status-info"></div>

			<fieldset>
				<legend>Create Production</legend>
				
				<table class="media_file_table" border="0" cellspacing="0">
					<thead>
						<tr>
							<th>Track Identifier</th>
							<th>Source</th>
							<th>Audio File</th>
							<th>Remove</th>
						</tr>
					</thead>
					<tbody id="podlove-auphonic-tracks-table-body"></tbody>
				</table>
				<div id="add_new_track_wrapper">
					<input class="button" id="add_new_auphonic_track" value="+" type="button" />
				</div>

				<div class="auphonic-row">

					<button class="button button-primary" id="create_auphonic_production_button" title="<?php echo __( 'Create a production for the selected file.', 'podlove' ) ?>">
						<span class="indicating_button_wrapper">
							<span class="state_idle"><i class="podlove-icon-plus"></i></span>
							<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
							<span class="state_success"><i class="podlove-icon-ok"></i></span>
							<span class="state_fail"><i class="podlove-icon-remove"></i></span>
						</span>
						Create Production
					</button>

					<label>
						<input type="checkbox" id="auphonic_start_after_creation"> <?php echo __( 'Start after creation', 'podlove' ) ?>
					</label>
				</div>
			</fieldset>

			<fieldset>
				<legend>Manage Production</legend>
				<div class="auphonic-row">
						<select name="import_from_auphonic" id="auphonic_productions">
							<option><?php echo __( 'Loading productions ...', 'podlove' ) ?></option>
						</select>
						<span title="fetch available productions" id="reload_productions_button" data-token='<?php echo $this->module->get_module_option('auphonic_api_key') ?>'>
							<span class="state_idle"><i class="podlove-icon-repeat"></i></span>
							<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
							<span class="state_success"><i class="podlove-icon-ok"></i></span>
							<span class="state_fail"><i class="podlove-icon-remove"></i></span>
						</span>

						<button class="button" id="open_production_button" title="<?php echo __('Open in Auphonic', 'podlove') ?>">
							<span class="indicating_button_wrapper">
								<i class="podlove-icon-share"></i>
							</span>
							Open Production
						</button>

					<div style="clear: both"></div>

				</div>

				<div id="auphonic-selected-production">
					<div class="auphonic-row">

						<button class="button button-primary" id="start_auphonic_production_button" disabled>
							<span class="indicating_button_wrapper">
								<span class="state_idle"><i class="podlove-icon-cogs"></i></span>
								<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
								<span class="state_success"><i class="podlove-icon-ok"></i></span>
								<span class="state_fail"><i class="podlove-icon-remove"></i></span>
							</span>
							Start Production
						</button>

						<button class="button" id="stop_auphonic_production_button" disabled>
							<span class="indicating_button_wrapper">
								<span class="state_idle"><i class="podlove-icon-ban-circle"></i></span>
								<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
								<span class="state_success"><i class="podlove-icon-ok"></i></span>
								<span class="state_fail"><i class="podlove-icon-remove"></i></span>
							</span>
							Stop Production
						</button>

						<label>
							<input type="checkbox" id="auphonic_publish_after_finishing"> <?php echo __( 'Publish episode when done', 'podlove' ) ?>
						</label>
					</div>

					<div class="auphonic-row">
						<button id="fetch_production_results_button" class="button" disabled>
							<span class="indicating_button_wrapper">
								<span class="state_idle"><i class="podlove-icon-cloud-download"></i></span>
								<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
								<span class="state_success"><i class="podlove-icon-ok"></i></span>
								<span class="state_fail"><i class="podlove-icon-remove"></i></span>
							</span>
							Get Production Results
						</button>
						<button id="fetch_production_data_button" class="button" disabled>
							<span class="indicating_button_wrapper">
								<span class="state_idle"><i class="podlove-icon-cloud-download"></i></span>
								<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
								<span class="state_success"><i class="podlove-icon-ok"></i></span>
								<span class="state_fail"><i class="podlove-icon-remove"></i></span>
							</span>
							Import Episode Metadata
						</button>
					</div>
				</div>
			</fieldset>

		</div>
		<?php
	}
}