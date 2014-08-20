<?php 
namespace Podlove\Settings\Expert\Tab;
use \Podlove\Settings\Settings;
use \Podlove\Settings\Expert\Tab;

class Redirects extends Tab {
	public function init() {
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
							<th><?php echo __( 'Active', 'podlove' ) ?></th>
							<th><?php echo __( 'From URL', 'podlove' ) ?></th>
							<th><?php echo __( 'To URL', 'podlove' ) ?></th>
							<th><?php echo __( 'Redirect Method', 'podlove' ) ?></th>
							<th class="delete"></th>
							<th class="move"></th>
						</tr>
					</thead>
					<tbody id="podlove-redirects-table-body" style="min-height: 50px;">
						<tr style="display: none;">
							<td><em><?php echo __('No redirects were added yet.', 'podlove') ?></em></td>
						</tr>
					</tbody>
				</table>

				<script type="text/template" id="redirect-row-template">
				<tr data-index="{{index}}">
					<td>
						<input type="checkbox" name="podlove_redirects[podlove_setting_redirect][{{index}}][active]" value="active">
					</td>
					<td>
						<input type="text" name="podlove_redirects[podlove_setting_redirect][{{index}}][from]" value="{{redirect-from}}">
					</td>
					<td>
						<input type="text" name="podlove_redirects[podlove_setting_redirect][{{index}}][to]" value="{{redirect-to}}">
					</td>
					<td>
						<select name="podlove_redirects[podlove_setting_redirect][{{index}}][code]">
							<option value="307"><?php echo __('Temporary Redirect (HTTP Status 307)', 'podlove') ?></option>
							<option value="301"><?php echo __('Permanent Redirect (HTTP Status 301)', 'podlove') ?></option>
						</select>
					</td>
					<td class="delete">
						<a href="#" class="button delete"><?php echo __( 'delete', 'podlove' ) ?></a>
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

						$row = $(row);
						$row.find("select option[value=\"" + data.code + "\"]").prop("selected", true);

						if (data.active) {
							$row.find("input[type=\"checkbox\"]").prop("checked", true);
						}

						$("tbody", container_id).append($row);

						$row.find("input[type=text]:first").focus();
					}

					$(document).ready(function() {

						$.each(existing_redirects, function(index, entry) {
							add_row(index, entry);
						});

						$("#podlove_add_new_rule").on("click", function () {
							add_row($("tbody tr", container_id).length, {active: "active"});
						});

						$(container_id).on("click", "td.delete a", function(e) {
							e.preventDefault();
							$(this).closest("tr").remove();
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
					<a href="#" id="podlove_add_new_rule" class="button"><?php echo __( 'Add new rule' ); ?></a>
				</p>
				<p class="description">
					<?php echo __( 'Create custom permanent redirects. URLs can be absolute like <code>http://example.com/feed</code> or relative to the website like <code>/feed</code>.', 'podlove' ) ?>
				</p>

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