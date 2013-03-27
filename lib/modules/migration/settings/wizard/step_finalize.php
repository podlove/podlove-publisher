<?php
namespace Podlove\Modules\Migration\Settings\Wizard;
use Podlove\Modules\Migration;

class StepFinalize extends Step {

	public $title = 'Finalize';
	
	public function template() {
		global $wpdb;

		if ( isset( $_REQUEST['pull_the_trigger'] ) ) {
			$args = array(
				'post_type'      => 'podcast',
				'posts_per_page' => -1
			);
			$query = new \WP_Query( $args );

			while ( $query->have_posts() ) {
				$query->the_post();
				$post = get_post();

				// publicize episode
				wp_update_post( array(
					'ID'          => $post->ID,
					'post_status' => 'publish'
				) );

				// put legacy post in trash
				wp_delete_post( $post->post_parent );
			}

			wp_reset_postdata();

			// cleanup temporary migration settings
			// delete_option( 'podlove_module_migration' );
			delete_option( 'podlove_migration' );
			delete_option( 'podlove_migration_validation_cache' );
			delete_option( 'podlove_migrated_posts_cache' );
		}

		$unpublished_episodes = $wpdb->get_var("
			SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'podcast' AND post_status = 'pending'
		");
		?>

		<?php if ( $unpublished_episodes > 0 ): ?>
			<div class="hero-unit">
				<h1>
					<?php echo __( 'Nearly done!', 'podlove' ); ?>
				</h1>
				<p>
					All your episodes are migrated and marked as <em>pending</em>. Your original posts are still <em>published</em>.
					You can now <a href="<?php echo admin_url( 'edit.php?post_type=podcast' ) ?>">preview your episodes</a> and adjust them to your liking. Then it's time to pull the trigger.
				</p>
				<p>
					Pushing this button depublicizes all migrated posts and publishes all episodes at once.
				</p>
				<p>
					Ready?
				</p>
				<p>
					<form method="GET">
						<input type="submit" name="pull_the_trigger" class="btn btn-danger" value="Switch to Podlove Publisher Episodes">
						<input type="hidden" name="page" value="podlove_settings_migration_handle" />
						<input type="hidden" name="step" value="<?php echo Migration::instance()->get_module_option( 'current_step', 1 ) ?>" />
					</form>
				</p>
			</div>
		<?php else: ?>
			<img src="<?php echo \Podlove\Modules\Migration\Migration::instance()->get_module_url() ?>/success.jpg" class="img-polaroid">
		<?php endif; ?>
		<?php
	}

}