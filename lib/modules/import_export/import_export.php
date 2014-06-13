<?php
namespace Podlove\Modules\ImportExport;

use Podlove\Modules\ImportExport\Exporter;

class Import_Export extends \Podlove\Modules\Base {

	protected $module_name = 'Import &amp; Export';
	protected $module_description = 'Import &amp; export podlove data for backup or migration to another WordPress instance.';
	protected $module_group = 'system';

	public function load() {
		
		// hook into export feature
		add_action('init', function() {

			if (!is_admin())
				return;

			if (isset($_GET['podlove_export']) && $_GET['podlove_export']) {
				$exporter = new Exporter;
				$exporter->download();
				exit;
			}

		});

		// ensure the importer keeps the mapping id for old<->new post id
		add_filter( 'wp_import_post_meta', function($postmetas, $post_id, $post) {
			$postmetas[] = array(
				'key' => 'import_id',
				'value' => $post_id
			);
			return $postmetas;
		}, 10, 3 );

		add_action( 'admin_menu', array( $this, 'register_menu' ), 250 );
	}

	public function register_menu() {
		new Settings\Settings( \Podlove\Podcast_Post_Type::SETTINGS_PAGE_HANDLE );
	}

}