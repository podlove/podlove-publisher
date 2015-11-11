<?php
namespace Podlove\Modules\RelatedEpisodes;

use Podlove\Model;
use Podlove\Modules\RelatedEpisodes\Model\EpisodeRelation;
use Podlove\Modules\RelatedEpisodes\TemplateExtensions;

class Related_Episodes extends \Podlove\Modules\Base {

		protected $module_name = 'Related Episodes';
		protected $module_description = 'Create related pairs of episodes. Display with shortcode <code>[podlove-related-episodes]</code>';
		protected $module_group = 'metadata';

		public function load() {
			add_action( 'podlove_module_was_activated_related_episodes', [$this, 'was_activated'] );
			add_action( 'admin_print_styles', [$this, 'admin_print_styles'] );

			\Podlove\Template\Episode::add_accessor(
				'relatedEpisodes', ['\Podlove\Modules\RelatedEpisodes\TemplateExtensions', 'accessorRelatedEpisodes'], 5
			);

			add_filter('podlove_twig_file_loader', function($file_loader) {
				$file_loader->addPath(implode(DIRECTORY_SEPARATOR, [\Podlove\PLUGIN_DIR, 'lib', 'modules', 'related_episodes', 'templates']), 'related-episodes');
				return $file_loader;
			});

			Shortcodes::init();
			new MetaBox();
		}

		public function was_activated( $module_name ) {
			EpisodeRelation::build();
		}

		public function admin_print_styles() {
			wp_register_script(
				'podlove_related_episodes',
				$this->get_module_url() . '/js/admin.js',
				['jquery', 'podlove_admin_data_table'],
				\Podlove\get_plugin_header( 'Version' )
			);
			wp_enqueue_script('podlove_related_episodes');
		}

}