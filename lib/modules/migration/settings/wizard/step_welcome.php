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
					<?php echo __( 'Don\'t know how to do a backup? Try ' ) . '<a href="' . admin_url( 'plugin-install.php?tab=search&s=BackWPup' ) . '" target="_blank">BackWPup</a>.' ?>
				</p>
			</div>
			<div class="span6">
				<h3>System Check</h3>
				<p>
					<?php 
					$r = new \Podlove\SystemReport;
					$report = $r->render();
					?>
					<textarea style="width: 100%" readonly cols="100" rows="<?php echo substr_count( $report, "\n" )+1; ?>"><?php echo $report ?></textarea>
				</p>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span12">
				<a href="<?php echo self::get_page_link( 2 ) ?>" class="btn btn-primary btn-large btn-block">
					<?php echo __( 'Let\'s do this!', 'podlove-podcasting-plugin-for-wordpress' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

}
