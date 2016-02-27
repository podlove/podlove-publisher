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
				'label'    => __('Auphonic', 'podlove-podcasting-plugin-for-wordpress'),
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
		$podcast = Model\Podcast::get();
		?>

		<input type="hidden" id="_auphonic_production" name="_auphonic_production" value="<?php echo get_post_meta( get_the_ID(), '_auphonic_production', true ) ?>" />
		<input type="hidden" id="auphonic" value="1"
			data-api-key="<?php echo $this->module->get_module_option('auphonic_api_key') ?>"
			data-presetuuid="<?php echo $this->module->get_module_option('auphonic_production_preset') ?>"
			data-assignment-chapter="<?php echo $asset_assignments->chapters ?>"
			data-assignment-image="<?php echo $asset_assignments->image ?>"
			data-module-url="<?php echo $this->module->get_module_url() ?>"
			data-site-url="<?php echo get_home_url(); ?>"
			data-podcast-image="<?php echo $podcast->cover_art()->url() ?>"
			/>

		<div id="auphonic-box">

			<em id="auphonic-credits-status"><?php echo __('Available processing time: <span id="auphonic-credits"></span></em>', 'podlove-podcasting-plugin-for-wordpress'); ?>

			<div id="auphonic-production-status" class="auphonic-status status-info"></div>

			<fieldset>
				<legend><?php echo __('Create Production</legend>', 'podlove-podcasting-plugin-for-wordpress'); ?>
				<div class="auphonic-segment">
					<div class="auphonic_production_head">
						<label for="auphonic_services">
							<?php echo __('Source', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</label>
					</div>
					<select id="auphonic_services">
						<option><?php echo __( 'Loading sources ...' ) ?></option>
					</select>
				</div>
				
				<div class="auphonic-segment">
					<div class="auphonic_production_head">
						<label for="auphonic_production_files">
							<?php echo __('Master Audio File', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</label>
						<span id="fetch_auphonic_production_files" title="<?php echo __( 'Fetch available audio files.', 'podlove-podcasting-plugin-for-wordpress' ) ?>">
							<span class="state_idle"><i class="podlove-icon-repeat"></i></span>
							<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
							<span class="state_success"><i class="podlove-icon-ok"></i></span>
							<span class="state_fail"><i class="podlove-icon-remove"></i></span>
						</span>
					</div>
					<select id="auphonic_production_files" name="input_file">
						<option>-</option>
					</select>
					<input type="text" id="auphonic_http_upload_url" name="auphonic_http_upload_url" style="display:none" class="large-text" />
					<input type="file" id="auphonic_local_upload_url" name="auphonic_local_upload_url" style="display:none" class="large-text" />
				</div>

				<div class="auphonic-row">

					<button class="button button-primary" id="create_auphonic_production_button" title="<?php echo __( 'Create a production for the selected file.', 'podlove-podcasting-plugin-for-wordpress' ) ?>">
						<span class="indicating_button_wrapper">
							<span class="state_idle"><i class="podlove-icon-plus"></i></span>
							<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
							<span class="state_success"><i class="podlove-icon-ok"></i></span>
							<span class="state_fail"><i class="podlove-icon-remove"></i></span>
						</span>
						<?php echo __('Create Production', 'podlove-podcasting-plugin-for-wordpress'); ?>
					</button>

					<label>
						<input type="checkbox" id="auphonic_start_after_creation"> <?php echo __( 'Start after creation', 'podlove-podcasting-plugin-for-wordpress' ) ?>
					</label>
				</div>
			</fieldset>

			<fieldset>
				<legend><?php echo __('Manage Production', 'podlove-podcasting-plugin-for-wordpress') ?></legend>
				<div class="auphonic-row">
						<select name="import_from_auphonic" id="auphonic_productions">
							<option><?php echo __( 'Loading productions ...', 'podlove-podcasting-plugin-for-wordpress' ) ?></option>
						</select>
						<span title="fetch available productions" id="reload_productions_button" data-token='<?php echo $this->module->get_module_option('auphonic_api_key') ?>'>
							<span class="state_idle"><i class="podlove-icon-repeat"></i></span>
							<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
							<span class="state_success"><i class="podlove-icon-ok"></i></span>
							<span class="state_fail"><i class="podlove-icon-remove"></i></span>
						</span>

						<button class="button" id="open_production_button" title="<?php echo __('Open in Auphonic', 'podlove-podcasting-plugin-for-wordpress') ?>" disabled>
							<span class="indicating_button_wrapper">
								<i class="podlove-icon-share"></i>
							</span>
							<?php echo __('Open Production', 'podlove-podcasting-plugin-for-wordpress') ?>
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
							<?php echo __('Start Production', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</button>

						<button class="button" id="stop_auphonic_production_button" disabled>
							<span class="indicating_button_wrapper">
								<span class="state_idle"><i class="podlove-icon-ban-circle"></i></span>
								<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
								<span class="state_success"><i class="podlove-icon-ok"></i></span>
								<span class="state_fail"><i class="podlove-icon-remove"></i></span>
							</span>
							<?php echo __('Stop Production', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</button>

						<label>
							<input type="checkbox" id="auphonic_publish_after_finishing"> <?php echo __( 'Publish episode when done', 'podlove-podcasting-plugin-for-wordpress' ) ?>
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
							<?php echo __('Get Production Results', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</button>
						<button id="fetch_production_data_button" class="button" disabled>
							<span class="indicating_button_wrapper">
								<span class="state_idle"><i class="podlove-icon-cloud-download"></i></span>
								<span class="state_working"><i class="podlove-icon-spinner rotate"></i></span>
								<span class="state_success"><i class="podlove-icon-ok"></i></span>
								<span class="state_fail"><i class="podlove-icon-remove"></i></span>
							</span>
							<?php echo __('Import Episode Metadata', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</button>
					</div>
				</div>
			</fieldset>

		</div>
		<?php
	}
}