<?php
namespace Podlove\Storage;

use \Podlove\Model;

class ExternalStorage implements StorageInterface {
	
	public static function key() {
		return 'external';
	}

	public static function description() {
		return __('External Hosting (sFTP, S3, libsyn, podseed, ...)', 'podlove');
	}

	public function register() {
		add_filter('podlove_media_storage_options', [$this, 'add_storage_option']);
		add_action('podlove_media_storage_form', [$this, 'add_storage_form_element']);
		add_action('podlove_media_storage_form_end', [$this, 'add_storage_form_scripts']);
	}

	public function init() {
		new ExternalStorage\ExternalMediaMetaBox;
		add_filter('podlove_file_url', [$this, 'podlove_file_url'], 10, 4);
	}

	public function add_storage_option($options) {
		$options[self::key()] = self::description();
		return $options;
	}

	public function podlove_file_url($podcast, $episode, $episode_asset, $file_type) {
		$template = $podcast->get_url_template();
		$template = apply_filters('podlove_file_url_template', $template);
		$template = str_replace('%media_file_base_url%', trailingslashit($podcast->media_file_base_uri), $template);
		$template = str_replace('%episode_slug%',        \Podlove\slugify($episode->slug), $template);
		$template = str_replace('%suffix%',              $episode_asset->suffix, $template);
		$template = str_replace('%format_extension%',    $file_type->extension, $template);
		return $template;
	}

	public function add_storage_form_element($wrapper) {
		$wrapper->string('media_file_base_uri', [
			'label'       => __('Upload Location', 'podlove'),
			'description' => __('Fully qualified URL. Example: http://cdn.example.com/pod/', 'podlove'),
			'html' => ['class' => 'regular-text required podlove-check-input', 'data-podlove-input-type' => 'url']
		]);
	}

	public function add_storage_form_scripts() {
		?>
<style type="text/css">
/* hide by default to prevent flickering */
.row_podlove_podcast_media_file_base_uri { display: none; } 
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
var $select = $("#podlove_podcast_media_storage"),
    $external = $("#podlove_podcast_media_file_base_uri").closest("tr");

	$select.on("change", function () {
		var value = $(this).val();

		if (value === 'external') {
			$external.show();
		} else {
			$external.hide();
		}

	}).change();

});
</script>
		<?php
	}
}
