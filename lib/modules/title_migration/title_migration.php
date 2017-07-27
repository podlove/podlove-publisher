<?php
namespace Podlove\Modules\TitleMigration;

use Podlove\Model;

class Title_Migration extends \Podlove\Modules\Base {

	protected $module_name = 'Title Migration';
	protected $module_description = 'Tool to help you fill episode number and title fields introduced in Publisher 2.7 for new Apple iOS 11 podcast feed extensions.';
	protected $module_group = 'system';

	protected $state;
	protected $notices;

	public function load() 
	{
		add_action('admin_init', [$this, 'add_tools_section'] );

		$this->state = new State;
		$this->notices = new Notices;

		if (isset($_POST['action']) && $_POST['action'] === 'podlove_migrate_titles') {
			$this->handle_migration();
		}

		if (isset($_REQUEST['podlove_set_title_migration_state'])) {
			$this->state->set_current_state($_REQUEST['podlove_set_title_migration_state']);
		}

		if (isset($_REQUEST['podlove_disable_title_migration_module']) && $_REQUEST['podlove_disable_title_migration_module']) {
			$this->state->set_current_state(State::FINISHED_HIDDEN);
			self::deactivate('title_migration');
			add_action( 'admin_notices', function () use ( $module_name ) {
				?>
				<div id="message" class="notice notice-success">
					<p>
						<strong><?php echo sprintf(
							__( 'Module "%s" was deactivated.', 'podlove-podcasting-plugin-for-wordpress' ),
							$this->get_module_name()
						) ?></strong>
					</p>
				</div>
				<?php
			} );
		}

		if ($this->state->is_initialized()) {
			$this->notices->register_init_notice();
		} elseif ($this->state->is_finished()) {
			$this->notices->register_finished_notice();
		}
	}

	public function add_tools_section()
	{
		\Podlove\add_tools_section(
			'title-migration', 
			__('Migrate Episode Titles', 'podlove-podcasting-plugin-for-wordpress'),
			[$this, 'the_tools_section']
		);
	}

	public function handle_migration()
	{
		if (!$this->nonce_is_valid()) {
			print 'Sorry, your nonce did not verify.';
			exit;
		}

		if (!isset($_POST['migrate']) || !is_array($_POST['migrate']))
			return;

		$episodes = $_POST['migrate'];

		foreach ($episodes as $episode_id => $data) {

			$type = in_array($data['type'], ['full', 'trailer', 'bonus']) ? $data['type'] : 'full';

			$episode = Model\Episode::find_by_id($episode_id);
			$episode->number = (int) $data['number'];
			$episode->title = trim($data['title']);
			$episode->type  = $type;
			$episode->save();
		}

		$this->state->set_current_state(State::FINISHED);
	}

	public function nonce_is_valid()
	{
		return isset($_POST['podlove_migrate_titles_nonce']) 
		    && wp_verify_nonce($_POST['podlove_migrate_titles_nonce'], 'podlove_migrate_titles');
	}

	public function the_tools_section()
	{
		$episodes = Model\Episode::find_all_by_time();
		$episodes = array_map(function ($episode) {

			$post_title = get_post($episode->post_id)->post_title;
			$guess = $this->guess_metadata_from_title($post_title);

			return [
				'episode'      => $episode,
				'post_title'   => $post_title,
				'title_guess'  => $guess['title'],
				'number_guess' => $guess['number']
			];
		}, $episodes);

		?>
<div id="the_tools_section"></div>
<form method="POST">

	<input type="hidden" name="action" value="podlove_migrate_titles">
	<?php wp_nonce_field( 'podlove_migrate_titles', 'podlove_migrate_titles_nonce' ); ?>

	<p class="description">
		<?php echo __('There are new fields in podcast feeds for episode numbers and clean titles. You can edit them one by one using the episode screen, or use this tool to update them all at once.', 'podlove-podcasting-plugin-for-wordpress') ?>
	</p>
	<p class="description">
		<?php echo sprintf(
			__('Once you are done here, you might find it useful to know that the Publisher is capable of automatically generating blog titles based on episode number and title. You can enable it in %sExpert Settings > Website > Blog Episode Titles%s.', 'podlove-podcasting-plugin-for-wordpress'), 
			'<a href="' . admin_url('admin.php?page=podlove_settings_settings_handle') . '">',
			'</a>'
		) ?>
		
	</p>

	<table>
		<thead>
			<tr style="text-align: left">
				<th><?php echo __('Current Post Title', 'podlove-podcasting-plugin-for-wordpress') ?></th>
				<th><?php echo __('Episode Number', 'podlove-podcasting-plugin-for-wordpress') ?></th>
				<th><?php echo __('Episode Title', 'podlove-podcasting-plugin-for-wordpress') ?></th>
				<th><?php echo __('Type', 'podlove-podcasting-plugin-for-wordpress') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($episodes as $episode): ?>
				<tr>
					<td>
						<?php echo $episode['post_title'] ?>
					</td>
					<td>
						<input type="text" value="<?php echo esc_attr($episode['number_guess']) ?>" name="migrate[<?php echo (int) $episode['episode']->id ?>][number]" class="regular-text" style="width: 125px" />
					</td>
					<td>
						<input type="text" value="<?php echo esc_attr($episode['title_guess']) ?>" name="migrate[<?php echo (int) $episode['episode']->id ?>][title]" class="regular-text" />
					</td>
					<td>
						<select name="migrate[<?php echo (int) $episode['episode']->id ?>][type]">
							<option value="full" selected>full</option>
							<option value="trailer">trailer</option>
							<option value="bonus">bonus</option>
						</select>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>

	<button class="button button-primary">
		<?php echo __('Migrate Post Titles', 'podlove-podcasting-plugin-for-wordpress') ?>
	</button>

</form>	
		<?php
	}

	public function guess_metadata_from_title($post_title)
	{
		if (preg_match('/\d+/', $post_title, $matches, PREG_OFFSET_CAPTURE)) {
			$number = (int) $matches[0][0];
			$offset = $matches[0][1] + strlen($matches[0][0]);
		} else {
			$number = nil;
			$offset = 0;
		}

		$title = substr($post_title, $offset);
		$title = trim($title);
		$title = preg_replace("/^[-–~\s|]+/", "", $title);

		return [
			'title'  => $title,
			'number' => $number
		];
	}

}
