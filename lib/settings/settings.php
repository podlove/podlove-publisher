<?php
namespace Podlove\Settings;

class Settings {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Settings::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Expert Settings',
			/* $menu_title */ 'Expert Settings',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_settings_settings_handle',
			/* $function   */ array( $this, 'page' )
		);

		add_settings_section(
			/* $id 		 */ 'podlove_settings_general',
			/* $title 	 */ __( '', 'podlove' ),	
			/* $callback */ function () { echo '<h3>' . __( 'General', 'podlove' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_merge_episodes',
			/* $title    */ sprintf(
				'<label for="merge_episodes">%s</label>',
				__( 'Display episodes on front page together with blog posts', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove[merge_episodes]" id="merge_episodes" type="checkbox" <?php checked( \Podlove\get_setting( 'merge_episodes' ), 'on' ) ?>>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_hide_wp_feed_discovery',
			/* $title    */ sprintf(
				'<label for="hide_wp_feed_discovery">%s</label>',
				__( 'Hide default WordPress Feeds for blog and comments (no auto-discovery).', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove[hide_wp_feed_discovery]" id="hide_wp_feed_discovery" type="checkbox" <?php checked( \Podlove\get_setting( 'hide_wp_feed_discovery' ), 'on' ) ?>>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);
		
		add_settings_field(
			/* $id       */ 'podlove_setting_custom_episode_slug',
			/* $title    */ sprintf(
				'<label for="custom_episode_slug">%s</label>',
				__( 'URL scheme for episodes', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove[custom_episode_slug]" id="custom_episode_slug" type="text" value="<?php echo \Podlove\get_setting( 'custom_episode_slug' ) ?>">
				<p>
					<span class="description">
						<?php echo __( '
							Placeholders: %podcast% (post name slug), %post_id%, %year%, %monthnum%, %day%, %hour%, %minute%, %second%, %category%, %author%<br>
							Example schemes: <code>%podcast%</code>, <code>episode/%podcast%</code>, <code>%year%/%monthnum%/%podcast%</code>', 'podlove' );
						?>
					</span>
				</p>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_url_template',
			/* $title    */ sprintf(
				'<label for="url_template">%s</label>',
				__( 'Episode Asset URL Template.', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<input name="podlove[url_template]" id="url_template" type="text" value="<?php echo \Podlove\get_setting( 'url_template' ) ?>" class="large-text">
				<p>
					<span class="description">
						<?php echo __( 'Is used to generate URLs. You probably don\'t want to change this.', 'podlove' ); ?>
					</span>
				</p>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_general'
		);

		add_settings_section(
			/* $id 		 */ 'podlove_settings_episode',
			/* $title 	 */ __( '', 'podlove' ),	
			/* $callback */ function () { echo '<h3>' . __( 'Episodes', 'podlove' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_episode_record_date',
			/* $title    */ sprintf(
				'<label for="enable_episode_record_date">%s</label>',
				__( 'Enable recording date field.', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label>
					<input name="podlove[enable_episode_record_date]" id="enable_episode_record_date" type="radio" value="1" <?php checked( \Podlove\get_setting( 'enable_episode_record_date' ), 1 ) ?> /> <?php echo __( 'enable', 'podlove' ) ?>
				</label>
				<label>
					<input name="podlove[enable_episode_record_date]" id="enable_episode_record_date" type="radio" value="0" <?php checked( \Podlove\get_setting( 'enable_episode_record_date' ), 0 ) ?> /> <?php echo __( 'disable', 'podlove' ) ?>
				</label>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_episode_publication_date',
			/* $title    */ sprintf(
				'<label for="enable_episode_publication_date">%s</label>',
				__( 'Enable publication date field.', 'podlove' )
			),
			/* $callback */ function () {
				?>
				<label>
					<input name="podlove[enable_episode_publication_date]" id="enable_episode_publication_date" type="radio" value="1" <?php checked( \Podlove\get_setting( 'enable_episode_publication_date' ), 1 ) ?> /> <?php echo __( 'enable', 'podlove' ) ?>
				</label>
				<label>
					<input name="podlove[enable_episode_publication_date]" id="enable_episode_publication_date" type="radio" value="0" <?php checked( \Podlove\get_setting( 'enable_episode_publication_date' ), 0 ) ?> /> <?php echo __( 'disable', 'podlove' ) ?>
				</label>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_episode'
		);

		add_settings_section(
			/* $id 		 */ 'podlove_settings_redirects',
			/* $title 	 */ __( '', 'podlove' ),	
			/* $callback */ function () { echo '<h3>' . __( 'Redirects', 'podlove' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_redirect',
			/* $title    */ sprintf(
				'<label for="podlove_setting_redirect">%s</label>',
				__( 'Permanent URL Redirects', 'podlove' )
			),
			/* $callback */ function () {
				$redirect_settings = \Podlove\get_setting( 'podlove_setting_redirect' );

				if ( ! is_array( $redirect_settings ) )
					$redirect_settings = array();

				?>
				<table class="wp-list-table widefat podlove_redirects">
					<thead>
						<tr>
							<th><?php echo __( 'From URL', 'podlove' ) ?></th>
							<th><?php echo __( 'To URL', 'podlove' ) ?></th>
							<th class="delete"></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$index = 0;
						foreach ( $redirect_settings as $index => $redirect_setting ) {
							if ( $redirect_setting['from'] || $redirect_setting['to'] ) {
								?>
								<tr data-index="<?php echo $index ?>">
									<td>
										<input type="text" name="podlove[podlove_setting_redirect][<?php echo $index ?>][from]" value="<?php echo $redirect_setting['from'] ?>">
									</td>
									<td>
										<input type="text" name="podlove[podlove_setting_redirect][<?php echo $index ?>][to]" value="<?php echo $redirect_setting['to'] ?>">
									</td>
									<td class="delete">
										<a href="#" class="button delete"><?php echo __( 'delete', 'podlove' ) ?></a>
									</td>
								</tr>
								<?php
							}
						}
						?>
						<tr data-index="<?php echo $index + 1 ?>">
							<td>
								<input type="text" name="podlove[podlove_setting_redirect][<?php echo $index + 1 ?>][from]">
							</td>
							<td>
								<input type="text" name="podlove[podlove_setting_redirect][<?php echo $index + 1 ?>][to]">
							</td>
							<td class="delete">
								<a href="#" class="button delete"><?php echo __( 'delete', 'podlove' ) ?></a>
							</td>
						</tr>
					</tbody>
				</table>

				<p>
					<a href="#" id="podlove_add_new_rule" class="button"><?php echo __( 'Add new rule' ); ?></a>
				</p>

				<script type="text/javascript">
				jQuery(function($) {
					$(document).ready(function() {

						$(".podlove_redirects").on("click", "td.delete a", function(e) {
							e.preventDefault();
							$(this).closest("tr").remove();
							return false;
						});

						$("#podlove_add_new_rule").on("click", function(e) {
							e.preventDefault();

							var index = $(".podlove_redirects tr:last").data("index") + 1,
							    html = '';

							html += "<tr data-index=\"" + index + "\">";
							html += "<td><input type=\"text\" name=\"podlove[podlove_setting_redirect][" + index + "][from]\"></td>";
							html += "<td><input type=\"text\" name=\"podlove[podlove_setting_redirect][" + index + "][to]\"></td>";
							html += "<td class=\"delete\"><a href=\"#\" class=\"button\"><?php echo __( 'delete', 'podlove' ) ?></a></td>";
							html += "</tr>";


							$(".podlove_redirects tbody").append(html);

							return false;
						});
					});
				});
				</script>

				<style type="text/css">
				.podlove_redirects th.delete, .podlove_redirects td.delete {
					width: 60px;
					text-align: right;
				}
				.podlove_redirects td input {
					width: 100%;
				}
				</style>
				<?php
			},
			/* $page     */ Settings::$pagehook,  
			/* $section  */ 'podlove_settings_redirects'
		);

		register_setting( Settings::$pagehook, 'podlove' );
		
	}
	
	function page() {
		// hack: always flush rewrite rules here for custom_episode_slug setting
		flush_rewrite_rules();
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Expert Settings' ) ?></h2>

			<form method="post" action="options.php">
				<?php settings_fields( Settings::$pagehook ); ?>
				<?php do_settings_sections( Settings::$pagehook ); ?>
				
				<?php submit_button( __( 'Save Changes' ), 'button-primary', 'submit', TRUE ); ?>
			</form>
		</div>	
		<?php
	}
	
}