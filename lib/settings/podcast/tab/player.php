<?php
namespace Podlove\Settings\Podcast\Tab;
use \Podlove\Settings\Podcast\Tab;

class Player extends Tab {

	public function init() {
		add_action( $this->page_hook, array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	public function process_form() {
		if (!isset($_POST['podlove_webplayer_settings']) || !$this->is_active())
			return;

		$formKeys = [ 'inject', 'chaptersVisible', 'version', 'playerv3theme' ];

		$settings = get_option('podlove_webplayer_settings');
		foreach ($formKeys as $key) {
			$settings[$key] = $_POST['podlove_webplayer_settings'][$key];
		}

		update_option('podlove_webplayer_settings', $settings);
		\Podlove\Cache\TemplateCache::get_instance()->setup_purge();

		header('Location: ' . $this->get_url());
	}

	public static function get_form_data() {
		
		$theme_options = [];
		$player_css_dir = \Podlove\PLUGIN_DIR . 'lib/modules/podlove_web_player/player_v3/css/';
		$dir = new \DirectoryIterator($player_css_dir);
		foreach ($dir as $fileinfo) {
			if ($fileinfo->getExtension() == 'css') {
				$filename = $fileinfo->getFilename();
				$filetitle = str_replace(".css", "", $filename);
				$filetitle = str_replace(".min", "", $filetitle);
				$filetitle = str_replace("-", " ", $filetitle);
				$filetitle = str_replace("pwp", "PWP", $filetitle);
				$theme_options[$filename] = $filetitle;
			}
		}

		$form_data = [
			[
				'type' => 'select',
				'key'  => 'version',
				'options' => [
					'label'       => __( 'Web Player', 'podlove-podcasting-plugin-for-wordpress' ),
					'description' => '',
					'options' => [
						'player_v2' => __( 'Podlove Web Player 2', 'podlove-podcasting-plugin-for-wordpress' ),
						'player_v3' => __( 'Podlove Web Player 3 (unstable beta, don\'t use in production)', 'podlove-podcasting-plugin-for-wordpress' )
					]
				],
				'position' => 1000
			],
			[
				'type' => 'select',
				'key' => 'inject',
				'options' => [
					'label'       => __( 'Insert player automatically', 'podlove-podcasting-plugin-for-wordpress' ),
					'description' => __( 'Automatically insert web player shortcode at beginning or end of an episode. Alternatvely, use the shortcode <code>[podlove-episode-web-player]</code> or templates.', 'podlove-podcasting-plugin-for-wordpress' ),
					'options'     => array(
						'manually'  => __( 'no automatic insertion', 'podlove-podcasting-plugin-for-wordpress' ),
						'beginning' => __( 'insert at the beginning', 'podlove-podcasting-plugin-for-wordpress' ),
						'end'       => __( 'insert at the end', 'podlove-podcasting-plugin-for-wordpress' )
					)
				],
				'position' => 100
			],
			[
				'type' => 'select',
				'key' => 'playerv3theme',
				'options' => [
					'label' => 'Web Player Theme',
					'description' => 'For Web Player V3 only.',
					'options' => $theme_options
				],
				'position' => 500
			],
			[
				'type' => 'select',
				'key' => 'chaptersVisible',
				'options' => array(
					'label'   => __( 'Chapters Visibility', 'podlove-podcasting-plugin-for-wordpress' ),
					'options' => array(
						'true'  => __( 'Visible when player loads', 'podlove-podcasting-plugin-for-wordpress' ),
						'false' => __( 'Hidden when player loads', 'podlove-podcasting-plugin-for-wordpress' )
					)
				),
				'position' => 300
			]
		];

		// allow modules to add / change the form
		$form_data = apply_filters('podlove_player_form_data', $form_data);

		// sort entities by position
		usort($form_data, array(__CLASS__, 'compare_by_position'));

		return $form_data;
	}

	public static function compare_by_position($a, $b) {
		$pos_a = isset($a['position']) ? (int) $a['position'] : 0;
		$pos_b = isset($b['position']) ? (int) $b['position'] : 0;

		if ($a == $b || $pos_a == $pos_b)
			return 0;

		return ($pos_a < $pos_b) ? 1 : -1;
	}

	public function register_page() {

		$form_attributes = array(
			'context' => 'podlove_webplayer_settings',
			'action'  => $this->get_url()
		);

		$form_data = self::get_form_data();

		\Podlove\Form\build_for( (object) \Podlove\get_webplayer_settings(), $form_attributes, function ( $form ) use ($form_data) {
			$wrapper = new \Podlove\Form\Input\TableWrapper($form);
			
			foreach ($form_data as $entry) {
				$wrapper->{$entry['type']}($entry['key'], $entry['options']);
			}
		});	
	}
}
