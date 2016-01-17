<?php

add_action('admin_init', 'podlove_about_page_init');
add_action('admin_init', 'podlove_maybe_redirect_to_about_page', 20); // run after migrations

/**
 * Redirects to about page once.
 * 
 * To reset before a major/minor release, 
 * add `delete_site_option("podlove_seen_about")` as a migration.
 */
function podlove_maybe_redirect_to_about_page() {
	
	if (!podlove_should_see_about_page())
		return;

	// show only once per upgrade and network
	update_site_option('podlove_seen_about', true);

	wp_safe_redirect( admin_url( 'admin.php?page=podlove_settings_handle&about' ) );
}

function podlove_should_see_about_page() {
	global $pagenow;

	if (!current_user_can('manage_options'))
		return false;

	if (in_array($pagenow, ['update.php', 'update-core.php', 'plugins.php', 'plugin-install.php']))
		return false;

	if (get_site_option('podlove_seen_about'))
		return false;

	return true;
}

function podlove_about_page_init() {

	if (filter_input(INPUT_GET, 'page') !== 'podlove_settings_handle')
		return;

	if (!isset($_GET['about']))
		return;

	// hide all admin notices
	add_action('admin_notices', function() {
		remove_all_actions('admin_notices');
	}, -1);

	add_filter('podlove_dashboard_page', 'podlove_about_page');

	wp_register_style('podlove-about', \Podlove\PLUGIN_URL . '/css/about.css', [], \Podlove\get_plugin_header('Version'));
    wp_enqueue_style('podlove-about');
}

function podlove_about_page($_) {
	?>

<div class="wrap podlove-about-wrap">
	
	<h1>
		<?php printf(__('Welcome to Podlove Publisher&nbsp;%s', 'podlove-podcasting-plugin-for-wordpress'), \Podlove\get_plugin_header('Version')); ?>
	</h1>

	<div class="about-text">
		<?php printf( __( 'Thank you for updating! This version focuses on podcasting in WordPress Multisite environments.', 'podlove-podcasting-plugin-for-wordpress' ) ); ?>
	</div>

	<div class="podlove-badge"></div>

	<h2 class="nav-tab-wrapper">
		<a href="#" class="nav-tab nav-tab-active">
			<?php _e( 'What&#8217;s New', 'podlove-podcasting-plugin-for-wordpress' ); ?>
		</a>
	</h2>

	<div class="changelog headline-feature">
		<h2><?php _e( 'Networks: WordPress Multisite Support is Here', 'podlove-podcasting-plugin-for-wordpress' ); ?></h2>
		
		<!-- <div class="featured-image">
			<img src="//s.w.org/images/core/4.1/theme.png?0" />
		</div> -->

		<div class="feature-section top-feature">

			<img src="<?php echo \Podlove\PLUGIN_URL . '/images/about/network.png' ?>" style="width: 50%; margin-left: 25%; margin-top: 1em" />

			<h3>
				Podlove Publisher joins the networks section.<br>
				Get an overview of your network and quickly switch between podcasts.
			</h3>
			<p>
				<ul class="ul-disc">
					<li>The network dashboard provides a birds-eye view over your podcast empire.</li>
					<li>Manage templates in your network and access them in all podcasts.</li>
					<li>Create podcast lists and use them in templates spanning multiple podcasts, for example to list the 10 latest episodes in your network.</li>
				</ul>
			</p>

		</div>

		<div class="clear"></div>

		<hr />

		<div class="feature-section">
			<div class="col">
				<h3>
					A new Home &amp; Professional Support
				</h3>
				<p>
					Podlove Podcast Publishing for WordPress has a new home at 
					<a href="//publisher.podlove.org" target="_blank">publisher.podlove.org</a>. Come visit us :)
				</p>
				<p>
					It’s not just a pretty page, it has something new to offer: 
					<a href="//publisher.podlove.org/support/" target="_blank">Professional Support</a>. 
					If you need quick, private and competent support, this is the place to go.
					Subscriptions start at an affordable 5&#x20AC; per month.
				</p>
				<p>
					Here’s an overview of our support &amp; communication channels:

					<ul class="ul-disc">
						<li><a target="_blank" href="//github.com/podlove/podlove-publisher/issues">GitHub</a> will continue to be the bug tracker.</li>
						<li><a target="_blank" href="//community.podlove.org">Podlove Community</a> is the best place to find answers, ask the community for help and discuss features.</li>
						<li><a target="_blank" href="//trello.com/board/podlove-publisher/508293f65573fa3f62004e0a">Trello</a> shows our roadmap.</li>
						<li>Finally, <a target="_blank" href="//publisher.podlove.org/support/">Professional Support</a> is the new place to get quick help, privately.</li>
					</ul>
				</p>
			</div>
			<div class="col">
				<img style="padding-top: 60px;" src="<?php echo \Podlove\PLUGIN_URL . '/images/about/home.png' ?>" />
			</div>
		</div>
	</div>

	<hr />

	<div class="feature-section col two-col">
		<h2>Upgrade Notices</h2>

		<div>
			<h4>Custom Template Parameters are Handled Differently.</h4>
			<p>
				This section is relevant if you are using templates with custom variables passed in shortcodes, like this: 
			</p>
			<p>
				<code>[podlove-template template="example" param="foo" dog="wow"]</code>
			</p>
			<p>
				Before 2.1 you have accessed those variables simply by calling <code>param</code> and <code>dog</code>.
				For compatibility, all shortcode options are now prefixed with <code>option.</code>, 
				so you need to change those calls to <code>option.param</code> and <code>option.dog</code> etc.
			</p>
		</div>

		<div class="last-feature">
			<h4>Other</h4>
			<p>
				The Flattr parameter in <code>[podlove-episode-contributor-list]</code> now defaults to "no". If you like to include Flattr, use <code>[podlove-episode-contributor-list flattr="yes"]</code>
			</p>
			<p>
				<code>[podlove-web-player]</code> was renamed to <code>[podlove-episode-web-player]</code> to avoid clashes with the standalone web player plugin. For now, the old shortcode still works.
			</p>
			<p>
				<code>[podlove-subscribe-button]</code> was renamed to <code>[podlove-podcast-subscribe-button]</code> to avoid clashes with the standalone button plugin. For now, the old shortcode still works.
			</p>
			<p>
				It is now preferred to reference templates using the <code>template</code> parameter instead of <code>id</code>: <code>[podlove-template template="example"]</code>.
			</p>
		</div>
	</div>

	<hr />

	<div class="return-to-dashboard">
	
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=podlove_settings_handle' ) ); ?>"><?php
			_e( 'Go to Podlove Dashboard &rarr;' );
		?></a>
	</div>

</div>

<style type="text/css">
#screen-meta-links { display: none; }
</style>

	<?php
	return true;
}