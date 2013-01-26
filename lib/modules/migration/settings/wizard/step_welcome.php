<?php
namespace Podlove\Modules\Migration\Settings\Wizard;

class StepWelcome extends Step {

	public $title = 'Welcome';
	
	public function template() {
		?>
		<div class="hero-unit">
			<h1>
				<?php echo __( 'Hi, Let\'s Migrate!', 'podlove' ); ?>
			</h1>
			<p>
				<?php echo __( 'My name is Miggy and I\'m your Migration Assistant for today. Cool, huh?
				I\'m able to help you if you\'re currently using PodPress, PowerPress or any other podcasting setup which manages episodes as posts with enclosures.', 'podlove' ); ?>
			</p>
			<p>
				<?php echo __( 'Before we start, <strong><em>please backup your database!</em></strong>
				I won\'t edit or delete any of your existing data but, you know, nobody has ever lost any data by backing up. Play it safe.', 'podlove' ); ?>
			</p>
			<p>
				<a href="<?php echo self::get_page_link( 2 ) ?>" class="btn btn-primary btn-large">
					<?php echo __( 'Let\'s do This!', 'podlove' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

}