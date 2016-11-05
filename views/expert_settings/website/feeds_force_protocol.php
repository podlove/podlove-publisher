<h4 style="margin-top: 5px;">
	<?php echo __('How is your website configured and how do you want your feeds to behave?', 'podlove-podcasting-plugin-for-wordpress'); ?>
</h4>
<select name="podlove_website[feeds_force_protocol]" id="feeds_force_protocol">
	<?php foreach ($options as $key => $text): ?>
		<option value="<?php echo esc_attr($key) ?>" <?php selected(\Podlove\get_setting('website', 'feeds_force_protocol'), $key) ?>><?php echo esc_html($text); ?></option>
	<?php endforeach ?>
</select>
<p>
<?php echo __( 'Choose options 1 or 2 if your whole website is either delivered via http or https. In both cases the Publisher will not change any behavior but will do sanity checks that everything works as expected. Delivering everything via https is recommended. However, if you are using a certificate provider not supported by iTunes (like Let\'s Encrypt), you need to select option 3. The Publisher will then force feed URLs as well as enclosure and image URLs within the feed to be http only.', 'podlove-podcasting-plugin-for-wordpress' ) ?>
</p>
<h4>
	<?php echo __('Useful Resources', 'podlove-podcasting-plugin-for-wordpress'); ?>
</h4>
<p>
	<ul>
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
