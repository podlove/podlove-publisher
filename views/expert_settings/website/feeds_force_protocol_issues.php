<?php if (count($issues)): ?>
	<h4>
		<?php echo __('Possible Issues', 'podlove-podcasting-plugin-for-wordpress'); ?>
	</h4>
	<p>
		<ul>
			<?php foreach ($issues as $issue): ?>
				<li><?php echo $issue; ?></li>
			<?php endforeach ?>
		</ul>
	</p>
	<p>
		<em><?php 
			echo sprintf(
				__('If you are unclear about what the WordPress Address or Site Address is or where to set them, please read %sCodex: Changing the Site URL%s.', 'podlove-podcasting-plugin-for-wordpress'),
				'<a href="https://codex.wordpress.org/Changing_The_Site_URL" target="_blank">',
				'</a>'
			) 
		?></em>
	</p>
<?php endif ?>
