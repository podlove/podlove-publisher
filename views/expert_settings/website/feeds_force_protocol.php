<h4 style="margin-top: 5px;">
	<?php echo __('How is your website configured and how do you want your feeds to behave?', 'podlove-podcasting-plugin-for-wordpress'); ?>
</h4>
<p>
<?php echo __( 'If you are using a certificate provider not supported by iTunes, you need to select option 3. Podlove Publisher will then force feed URLs as well as enclosure and image URLs within the feed to be http only. Please verify then that your webserver does not redirect http links back to https, otherwide the feed will not be accessible.', 'podlove-podcasting-plugin-for-wordpress' ) ?></p>
<h4>
	<?php echo __('Useful Resources', 'podlove-podcasting-plugin-for-wordpress'); ?>
</h4>
<p>
	<ul class="podlove-disc-list">
		<li>
			<a target="_blank" href="https://make.wordpress.org/support/user-manual/web-publishing/https-for-wordpress/"><?php echo __('HTTPS for WordPress', 'podlove-podcasting-plugin-for-wordpress') ?></a>
		</li>
		<li>
			<a target="_blank" href="http://itunespartner.apple.com/en/podcasts/faq"><?php echo __('List of certificate providers supported by iTunes', 'podlove-podcasting-plugin-for-wordpress') ?></a>
		</li>
		<li>
			<a target="_blank" href="https://letsencrypt.org/">letsencrypt.org</a>
		</li>
	</ul>
</p>

<select name="podlove_website[feeds_force_protocol]" id="feeds_force_protocol">
	<?php foreach ($options as $key => $text): ?>
		<option value="<?php echo esc_attr($key) ?>" <?php selected(\Podlove\get_setting('website', 'feeds_force_protocol'), $key) ?>><?php echo esc_html($text); ?></option>
	<?php endforeach ?>
</select>
