<?php
add_action('admin_notices', function () {

	$module_loaded     = in_array('curl', get_loaded_extensions());
	$function_disabled = stripos(ini_get('disable_functions'), 'curl_exec') !== false;

	?>
	<?php if (!$module_loaded): ?>
		<div class="notice notice-error">
			<p>
				<strong><?php echo __('Podlove Publisher Error', 'podlove-podcasting-plugin-for-wordpress'); ?></strong>
				<br>
				<?php echo __('Required PHP extension "curl" is not installed. Common solution:', 'podlove-podcasting-plugin-for-wordpress'); ?>
				<blockquote><code>sudo apt-get install php5-curl</code></blockquote>
				<?php echo __('Then you need to restart you webserver:', 'podlove-podcasting-plugin-for-wordpress'); ?>
				<blockquote><code>sudo service apache2 restart</code></blockquote> 
				<?php echo __('or', 'podlove-podcasting-plugin-for-wordpress'); ?>
				<blockquote><code>sudo service php5-fpm restart</code></blockquote> 
				<?php
				echo sprintf(
					__('If this does not help, visit %s for assistance.', 'podlove-podcasting-plugin-for-wordpress'),
					'<a href="https://community.podlove.org/c/podlove-publisher" target="_blank">community.podlove.org</a>'
				);
				?>
			</p>
		</div>
	<?php endif ?>
	<?php if ($function_disabled): ?>
		<div class="notice notice-error">
			<p>
				<strong><?php echo __('Podlove Publisher Error', 'podlove-podcasting-plugin-for-wordpress'); ?></strong>
				<br>
				<?php echo __('Required PHP function "curl_exec" is disabled. You need to remove it from the list in the "disable_functions" setting in your php.ini. ', 'podlove-podcasting-plugin-for-wordpress');
				?>
			</p>
		</div>		
	<?php endif ?>
	<?php
});
