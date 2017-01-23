<?php
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Model;
use Podlove\Modules\ImportExport\Export\PodcastExporter;
use \Podlove\Jobs\CronJobRunner;
use Podlove\Model\Job;

class PodcastImporter {

	// path to import file
	private $file;

	// SimpleXML document of import file
	private $xml;

	public static function init()
	{
		if (!is_admin())
			return;

		if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'podlove_tools_settings_handle') {
			add_action('admin_notices', [__CLASS__, 'render_import_progress']);
		}

		if (!isset($_FILES['podlove_import']))
			return;

		// allow xml+gz uploads
		add_filter('upload_mimes', function ($mimes) {
		    return array_merge($mimes, array(
		    	'xml' => 'application/xml',
		    	'gz|gzip' => 'application/x-gzip'
		    ));
		});

		require_once ABSPATH . '/wp-admin/includes/file.php';
		 
		$file = wp_handle_upload($_FILES['podlove_import'], array('test_form' => false));
		
		update_option('podlove_import_file', $file['file']);
		if (!($file = get_option('podlove_import_file')))
			return;

		CronJobRunner::create_job('\Podlove\Modules\ImportExport\Import\PodcastImporterJob');

		$redirect_url = 'admin.php?page=podlove_tools_settings_handle';
		wp_redirect(admin_url($redirect_url));
		exit;

	}

	public static function render_import_progress()
	{
		$job = Job::find_one_recent_unfinished_job('Podlove\Modules\ImportExport\Import\PodcastImporterJob');

		if (!$job)
			return;

		?>
		<div class="updated">
			<p>
				<strong>Import is running, please wait.</strong>
			</p>
			<p>
				Progress: <span id="import-progress-notice" data-job-id="<?php echo $job->id; ?>"></span> <i class="podlove-icon-spinner rotate"></i>
			</p>
		</div>

<script type="text/javascript">
var updateImportProgressTimer;
var updateImportProgress = function() {

	var job_element = document.getElementById('import-progress-notice');
	var job_id = job_element.getAttribute('data-job-id');

    PODLOVE.Jobs.getStatus(job_id, function(status) {
        if (status.error) {
            job_element.innerHTML = status.error;
            return;
        }

        var percent = 100 * (status.steps_progress / status.steps_total);

        percent = Math.round(percent * 10) / 10;

        if (!percent && status.steps_total > 0) {
        	job_element.innerHTML = " starting…";
        } else if (percent < 100 && status.steps_total > 0) {
        	job_element.innerHTML = " " + percent + "%";
        } else {
            job_element.parentElement.parentElement.remove();
            return;
        }

        if (status.error) {
            console.error("job error", job_id, status.error);
            return;
        }

        // stop when done
        if (parseInt(status.steps_progress, 10) >= parseInt(status.steps_total, 10))
            return;

        updateImportProgressTimer = window.setTimeout(updateImportProgress, 2500);
    });
};
updateImportProgress();
</script>
		<?php
	}

	public function __construct($file) {
		$this->file = $file;

		// It might not look like it, but it is actually compatible to 
		// uncompressed files.
		$gzFileHandler = gzopen($this->file, 'r');
		$decompressed = gzread($gzFileHandler, self::gzfilesize($this->file));
		gzclose($gzFileHandler);

		$this->xml = simplexml_load_string($decompressed);

		$this->xml->registerXPathNamespace('wpe', PodcastExporter::XML_NAMESPACE);
	}
	
	private static function gzfilesize($filename) {
		if (($zp = fopen($filename, 'r'))!==FALSE) {
			if (@fread($zp, 2) == "\x1F\x8B") { // this is a gzip'd file
				fseek($zp, -4, SEEK_END);
				if (strlen($datum = @fread($zp, 4))==4)
				  extract(unpack('Vgzfs', $datum));
			}
			else // not a gzip'd file, revert to regular filesize function
				$gzfs = filesize($filename);
			fclose($zp);
		}
		return($gzfs);
	}

	public function importEpisodes()
	{
		Model\Episode::delete_all();

		$episodes = $this->xml->xpath('//wpe:episode');
		foreach ($episodes as $episode) {
			$new_episode = new Model\Episode;

			foreach ($episode->children('wpe', true) as $attribute) {
				$new_episode->{$attribute->getName()} = self::escape((string) $attribute);
			}

			if ($new_post_id = $this->getNewPostId($new_episode->post_id)) {
				$new_episode->post_id = $new_post_id;
				$new_episode->save();
			} else {
				\Podlove\Log::get()->addWarning('Importer: no matching post for (old) post_id=' . $new_episode->post_id);
			}
		}
	}

	public function importOptions()
	{
		$wpe_options = $this->xml->xpath('//wpe:options');
		$options = $wpe_options[0]->children('wpe', true);
		foreach ($options as $option) {
			$option_string = (string) $option;

			// Replace lone '&' characters with '&amp;'.
			// Why? When exporting, the same conversion needs to be done to
			// make strings XML compatible. When importing, it is automatically
			// converted back to '&' which breaks `maybe_unserialize` (because
			// it changes the length of the content). So we need to convert it back.
			if (strpos($option_string, '&') !== false) {
				$option_string = preg_replace('/&([^#])(?![a-z1-4]{1,8};)/i', '&amp;$1', $option_string);
			}

			update_option($option->getName(), maybe_unserialize($option_string));
		}
	}

	public function importAssets() {
		self::importTable($this->xml, 'asset', '\Podlove\Model\EpisodeAsset');
	}

	public function importFeeds() {
		self::importTable($this->xml, 'feed', '\Podlove\Model\Feed');
	}

	public function importFileTypes() {
		self::importTable($this->xml, 'filetype', '\Podlove\Model\FileType');	
	}

	public function importMediaFiles() {
		self::importTable($this->xml, 'mediafile', '\Podlove\Model\MediaFile');
	}

	public function importTrackingArea() {
		self::importTable($this->xml, 'geoarea', '\Podlove\Model\GeoArea');
		Model\UserAgent::reparse_all();
	}

	public function importTrackingAreaName() {
		self::importTable($this->xml, 'geoareaname', '\Podlove\Model\GeoAreaName');
		Model\UserAgent::reparse_all();
	}

	public function importTrackingUserAgent() {
		self::importTable($this->xml, 'useragent', '\Podlove\Model\UserAgent');
		Model\UserAgent::reparse_all();
	}

	public function importTemplates() {
		self::importTable($this->xml, 'template', '\Podlove\Model\Template');
	}

	public function importOther() {
		do_action('podlove_xml_import', $this->xml);
	}

	public static function importTable($xml, $item_name, $table_class) {
		$table_class::delete_all();

		$group = $xml->xpath('//wpe:' . $item_name);

		foreach ($group as $item) {
			$new_item = new $table_class;

			foreach ($item->children('wpe', true) as $attribute) {
				$new_item->{$attribute->getName()} = self::escape((string) $attribute);
			}

			$new_item->save();
		}	
	}

	private static function escape($value) {
		global $wpdb;
		$wpdb->escape_by_ref($value);
		return $value;
	}

	/**
	 * Get mapping for post id after post import.
	 *
	 * When importing posts, their IDs might change.
	 * This function maps an existing post id to the new one.
	 * 
	 * @param  int      $old_post_id
	 * @return int|null post_id on success, otherwise null.
	 */
	private function getNewPostId($old_post_id)
	{
		$query_for_post_id = new \WP_Query(array(
			'post_type' => 'podcast',
			'meta_query' => array(
				array(
					'key' => 'import_id',
					'value' => $old_post_id,
					'compare' => '='
				)
			)
		));

		if ($query_for_post_id->have_posts()) {
			$p = $query_for_post_id->next_post();
			return $p->ID;
		} else {
			return null;
		}
	}

}
