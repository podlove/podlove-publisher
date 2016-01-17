<?php
namespace Podlove;

class PhpDeprecationWarning {

	public static $target_version = "5.4";

	public static function init() {

		$correct_php_version = version_compare( phpversion(), self::$target_version, ">=" );

		if ($correct_php_version)
			return;

		// don't show on non-podlove pages
		if (!isset($_GET['page']) || false === stristr($_GET['page'], 'podlove'))
			return;

		add_action( 'admin_notices', array(__CLASS__, 'show_warning') );
	}

	public static function show_warning() {
		?>
		<div id="message" class="error">
			<p>
				<strong><?php echo __( 'Please upgrade your PHP as soon as possible.', 'podlove-podcasting-plugin-for-wordpress' ) ?></strong>
			</p>
			<p>
				<?php echo sprintf(
					__( 'You are running PHP %s, which is deprecated.', 'podlove-podcasting-plugin-for-wordpress' ),
					phpversion()
				); ?>
				<?php echo sprintf(
					__('Read %sour blogpost%s for further details.'),
					'<a target="_blank" href="http://podlove.org/2014/08/14/podlove-publisher-2-phasing-out-php-5-3/">',
					'</a>'
				); ?>
			</p>
			<p>
				<?php echo __('As long as you opt to not upgrade, do not attempt to update to Podlove Publisher 2.0 and above.', 'podlove-podcasting-plugin-for-wordpress') ?>
			</p>
		</div>
		<?php
	}

}