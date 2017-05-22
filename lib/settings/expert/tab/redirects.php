<?php 
namespace Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;

class Redirects extends Tab {

	public function get_slug() {
		return 'redirects';
	}

	public function init() {
		add_settings_section(
			/* $id 		 */ 'podlove_settings_redirects',
			/* $title    */ '',	
			/* $callback */ function () { echo '<h3>' . __( 'Redirects', 'podlove-podcasting-plugin-for-wordpress' ) . '</h3>'; },
			/* $page	 */ Settings::$pagehook	
		);

		add_settings_field(
			/* $id       */ 'podlove_setting_redirect',
			/* $title    */ '',
			/* $callback */ function () {
				$redirect_settings = \Podlove\get_setting( 'redirects', 'podlove_setting_redirect' );

				if ( ! is_array( $redirect_settings ) ) {
					$redirect_settings = array();
				} else {
					// avoids array-index-based glitches
					$redirect_settings = array_values($redirect_settings);
				}

				?>

				<table id="podlove-redirects" class="podlove_alternating" border="0" cellspacing="0">
					<thead>
						<tr>
							<th style="width: 55px"><?php _e( 'Active', 'podlove-podcasting-plugin-for-wordpress' ) ?></th>
							<th><?php _e( 'From URL', 'podlove-podcasting-plugin-for-wordpress' ) ?></th>
							<th><?php _e( 'To URL', 'podlove-podcasting-plugin-for-wordpress' ) ?></th>
							<th><?php _e( 'Redirect Method', 'podlove-podcasting-plugin-for-wordpress' ) ?></th>
							<th class="count">
								<?php _e( 'Redirects', 'podlove-podcasting-plugin-for-wordpress' ) ?>
							</th>
							<th class="delete"></th>
							<th class="move"></th>
						</tr>
					</thead>
					<tbody id="podlove-redirects-table-body" style="min-height: 50px;">
						<tr style="display: none;">
							<td><em><?php _e('No redirects were added yet.', 'podlove-podcasting-plugin-for-wordpress') ?></em></td>
						</tr>
					</tbody>
				</table>

				<script type="text/template" id="redirect-row-template">
				<tr data-index="{{index}}">
					<td>
						<input type="checkbox" name="podlove_redirects[podlove_setting_redirect][{{index}}][active]" value="active">
					</td>
					<td>
						<input type="text" class="podlove-check-input" id="podlove_redirects_podlove_setting_redirect_{{index}}_from" name="podlove_redirects[podlove_setting_redirect][{{index}}][from]" value="{{redirect-from}}"><span class="podlove-input-status" data-podlove-input-status-for="podlove_redirects_podlove_setting_redirect_{{index}}_from"></span>
					</td>
					<td>
						<input type="text" class="podlove-check-input" id="podlove_redirects_podlove_setting_redirect_{{index}}_to" name="podlove_redirects[podlove_setting_redirect][{{index}}][to]" value="{{redirect-to}}"><span class="podlove-input-status" data-podlove-input-status-for="podlove_redirects_podlove_setting_redirect_{{index}}_to"></span>
					</td>
					<td>
						<select name="podlove_redirects[podlove_setting_redirect][{{index}}][code]">
							<option value="307"><?php _e('Temporary Redirect (HTTP Status 307)', 'podlove-podcasting-plugin-for-wordpress') ?></option>
							<option value="301"><?php _e('Permanent Redirect (HTTP Status 301)', 'podlove-podcasting-plugin-for-wordpress') ?></option>
						</select>
					</td>
					<td class="count">
						<span data-podlove-input-status-for="podlove_redirects_podlove_setting_redirect_{{index}}_count">{{count}}</span>
						<button class="button reset"><?php _e('reset', 'podlove-podcasting-plugin-for-wordpress') ?></button>
						<input type="hidden" name="podlove_redirects[podlove_setting_redirect][{{index}}][count]" value="{{count}}">
					</td>
					<td class="delete">
						<button class="button delete"><?php _e( 'delete', 'podlove-podcasting-plugin-for-wordpress' ) ?></button>
					</td>
					<td class="move column-move"><i class="reorder-handle podlove-icon-reorder"></i></td>
				</tr>
				</script>

				<script type="text/javascript">
				(function($) {

					var existing_redirects = <?php echo json_encode(array_values($redirect_settings)); ?>;
					var template_id = "#redirect-row-template";
					var container_id = "#podlove-redirects";

					function add_row(index, data) {
						var row = $(template_id).html();

						row = row.replace(/\{\{index\}\}/g, index);
						row = row.replace(/\{\{redirect-from\}\}/g, data.from ? data.from : "");
						row = row.replace(/\{\{redirect-to\}\}/g, data.to ? data.to : "");
						row = row.replace(/\{\{count\}\}/g, data.count ? data.count : "0");

						$row = $(row);
						$row.find("select option[value=\"" + data.code + "\"]").prop("selected", true);

						if (data.active) {
							$row.find("input[type=\"checkbox\"]").prop("checked", true);
						}

						$("tbody", container_id).append($row);

						$row.find("input[type=text]:first").focus();
						clean_up_input();
					}

					$(document).ready(function() {

						$.each(existing_redirects, function(index, entry) {
							add_row(index, entry);
						});

						$("#podlove_add_new_rule").on("click", function () {
							add_row($("tbody tr", container_id).length, {active: "active"});
						});

						$(container_id).on("click", "td.delete button.delete", function(e) {
							e.preventDefault();
							$(this).closest("tr").remove();
							return false;
						});

						$(container_id).on("click", "td.count button.reset", function(e) {
							e.preventDefault();
							var tr = $(this).closest("tr");
							tr.find('.count input[type=hidden]:first').val(0);
							tr.find('.count span:first').html("0");
							return false;
						});

						$("tbody", container_id).sortable({
							handle: ".reorder-handle",
							helper: function(e, tr) {
							    var $originals = tr.children();
							    var $helper = tr.clone();
							    $helper.children().each(function(index) {
							    	// Set helper cell sizes to match the original sizes
							    	$(this).width($originals.eq(index).width());
							    });
							    return $helper.css({
							    	background: '#EAEAEA'
							    });
							},
							update: function() { }
						});

					});
				}(jQuery));
				</script>

				<p>
					<a href="#" id="podlove_add_new_rule" class="button"><?php _e( 'Add new rule', 'podlove-podcasting-plugin-for-wordpress' ); ?></a>
				</p>
				<p class="description">
					<?php _e( 'Create custom permanent redirects. URLs can be absolute like <code>http://example.com/feed</code> or relative to the website like <code>/feed</code>.', 'podlove-podcasting-plugin-for-wordpress' ) ?>
				</p>

				<style type="text/css">
				#podlove-redirects th.count,
				#podlove-redirects td.count,
				#podlove-redirects th.delete,
				#podlove-redirects td.delete,
				#podlove-redirects th.move,
				#podlove-redirects td.move {
					width: 50px;
					text-align: right;
				}

				#podlove-redirects th.count,
				#podlove-redirects td.count {
					width: 100px;
				}

				#podlove-redirects th.delete,
				#podlove-redirects td.delete {
					width: 65px;
				}

				#podlove-redirects td.count span {
					display: inline-block;
					height: 28px;
					line-height: 26px;
					padding-top: 2px;
					padding-bottom: 1px;
					padding-right: 5px;
				}

				#podlove-redirects td input[type="text"] {
					width: 100%;
				}

				.form-table > tbody > tr > th {
					display: none;
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
