<?php
namespace Podlove\Modules\Migration\Settings\Wizard;

class StepWelcome extends Step {

	public $title = 'Welcome';
	
	public function template() {
		?>
		<div class="row-fluid">
			<div class="span3">
		        <h3>Is this for you?</h3>
				<p>
					This tool is for you if you are using this WordPress setup to publish a podcast right now.
					The assistant has explicit support for <a href="http://wordpress.org/extend/plugins/podpress/" target="_blank">podPress</a> and <a href="http://wordpress.org/extend/plugins/powerpress/" target="_blank">PowerPress</a> setups but it will 
					work with anything that relies on WordPress enclosures.
				</p>
			</div>
			<div class="span3">
				<h3>Preparation</h3>
				<p>
					Before you start, <strong><em>please backup your database!</em></strong>.
					The assistant won't edit or delete any data. However, nobody has ever lost any data by backing up. Play it safe.
				</p>
				<p>
			        	<?php echo sprintf( __( 'Don\'t know how to do a backup? Try <a href="%s" target="_blank">%s</a>?' ) ,admin_url( 'plugin-install.php?tab=search&s=BackWPup' ) , 'BackWPup') ?>

				</p>
			</div>
			<div class="span6">
				<h3>System Check</h3>
				<p>
					<?php 
					$r = new \Podlove\SystemReport;
					echo $r->render();
					?>
				</p>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span12">
				<a href="<?php echo self::get_page_link( 2 ) ?>" class="btn btn-primary btn-large btn-block">
					<?php echo __( 'Let\'s do this!', 'podlove' ); ?>
				</a>
			</div>
		</div>
		<!-- 
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
				<?php echo sprintf( __( 'Don\'t know how to do a backup? Try <a href="%s" target="_blank">%s</a>?' ) ,admin_url( 'plugin-install.php?tab=search&s=BackWPup' ) , 'BackWPup') ?>
			</p>
			<p>
				<a href="<?php echo self::get_page_link( 2 ) ?>" class="btn btn-primary btn-large">
					<?php echo __( 'Let\'s do this!', 'podlove' ); ?>
				</a>
			</p>
		</div>
		-->
		<?php
	}

}