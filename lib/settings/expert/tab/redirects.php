<?php 
namespace Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;

class Redirects extends Tab {
	public function init() {
		add_settings_section(
			/* $id 		 */ 'podlove_settings_redirects',
			/* $title 	 */ '',	
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
				$redirect_settings = \Podlove\get_setting( 'redirects', 'podlove_setting_redirect' );

				if ( ! is_array( $redirect_settings ) )
					$redirect_settings = array();

				?>
				<table class="wp-list-table widefat podlove_redirects">
					<thead>
						<tr>
							<th><?php echo __( 'From URL', 'podlove' ) ?></th>
							<th><?php echo __( 'To URL', 'podlove' ) ?></th>
							<th><?php echo __( 'Redirect Method', 'podlove' ) ?></th>
							<th class="delete"></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$index = 0;
						foreach ( $redirect_settings as $index => $redirect_setting ) {
							
							if ( $redirect_setting['from'] || $redirect_setting['to'] || $redirect_setting['code']) {
								?>
								<tr data-index="<?php echo $index ?>">
									<td>
										<input type="text" name="podlove_redirects[podlove_setting_redirect][<?php echo $index ?>][from]" value="<?php echo $redirect_setting['from'] ?>">
									</td>
									<td>
										<input type="text" name="podlove_redirects[podlove_setting_redirect][<?php echo $index ?>][to]" value="<?php echo $redirect_setting['to'] ?>">
									</td>
									<td>
										<select name="podlove_redirects[podlove_setting_redirect][<?php echo $index ?>][code]">
											<option value="307" <?php echo $redirect_setting['code'] == 307 ? 'selected' : '' ?>><?php echo __('Temporary Redirect (HTTP Status 307)', 'podlove') ?></option>
											<option value="301" <?php echo $redirect_setting['code'] == 301 ? 'selected' : '' ?>><?php echo __('Permanent Redirect (HTTP Status 301)', 'podlove') ?></option>
										</select>
									</td>
									<td class="delete">
										<a href="#" class="button delete"><?php echo __( 'delete', 'podlove' ) ?></a>
									</td>
								</tr>
								<?php
							}
						}
						?>
					</tbody>
				</table>

				<p>
					<a href="#" id="podlove_add_new_rule" class="button"><?php echo __( 'Add new rule' ); ?></a>
				</p>
				<p class="description">
					<?php echo __( 'Create custom permanent redirects. URLs can be absolute like <code>http://example.com/feed</code> or relative to the website like <code>/feed</code>.', 'podlove' ) ?>
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

							var index = $(".podlove_redirects tr").length,
							    html = '';

							html += "<tr data-index=\"" + index + "\">";
							html += "<td><input type=\"text\" name=\"podlove_redirects[podlove_setting_redirect][" + index + "][from]\"></td>";
							html += "<td><input type=\"text\" name=\"podlove_redirects[podlove_setting_redirect][" + index + "][to]\"></td>";
							html += "<td><select name=\"podlove_redirects[podlove_setting_redirect][" + index + "][code]\"><option value=\"307\" selected>Temporary Redirect (HTTP Status 307)</option><option value=\"301\">Permanent Redirect (HTTP Status 301)</option></select></td>";
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

		register_setting( Settings::$pagehook, 'podlove_redirects' );
	}
}
