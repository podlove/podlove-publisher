<?php
namespace Podlove;

/**
 * One-time migration that deletes HEAD entries from DownloadIntent table.
 *
 * Cannot be done through a normal migration since it takes too long.
 */
class DeleteHeadRequests {

	public static function init()
	{
		if (!get_option('podlove_tracking_delete_head_requests'))
			return;

		add_action('admin_notices', array(__CLASS__, 'show_admin_notice'));
		add_action( 'wp_ajax_podlove-tracking-delete-head-requests', array(__CLASS__, 'ajax_delete') );
	}

	public static function ajax_delete() {
		global $wpdb;

		$send_response = function ($todo) {

			if (!$todo)
				delete_option('podlove_tracking_delete_head_requests');

			\Podlove\AJAX\Ajax::respond_with_json(array('todo' => $todo));
		};

		// get user agent IDs to delete
		$sql = "
			SELECT
				id 
			FROM
				" . \Podlove\Model\UserAgent::table_name() . " ua
			WHERE
				user_agent LIKE \"libwww-perl/%\" 
				OR user_agent LIKE \"curl/%\" 
				OR user_agent LIKE \"PritTorrent/%\"
		";
		$user_agent_ids = $wpdb->get_col($sql);

		if (!count($user_agent_ids))
			$send_response(0);

		// delete
		$sql = "
		DELETE
			FROM " . \Podlove\Model\DownloadIntent::table_name() . "
			WHERE user_agent_id IN (" . implode(",", $user_agent_ids) . ")
			LIMIT 25000
		";
		$wpdb->query($sql);

		// see how much is left to delete
		$sql = "
		SELECT
			COUNT(*) 
		FROM
			" . \Podlove\Model\DownloadIntent::table_name() . " 
		WHERE
			user_agent_id IN (" . implode(",", $user_agent_ids) . ")
		";

		$send_response($wpdb->get_var($sql));
	}

	public static function show_admin_notice() {
		
		?>
		<div class="update-nag">
			<?php
			echo __('To prepare for the Podlove Analytics release, your tracking database needs an update.', 'podlove');
			?>
			<a id="podlove-start-tracking-migration" href="#"><?php echo __('Please update now') ?></a>.
			<span id="podlove-migration-status"></span>
		</div>

		<script type="text/javascript">
		jQuery(function($){

			function delete_entries() {
				$.ajax({
					url: ajaxurl,
					data: {
						action: 'podlove-tracking-delete-head-requests'
					},
					dataType: 'json',
					success: function(result) {
						if (result.todo == "0") {
							$("#podlove-migration-status i").hide();
							$("#podlove-migration-status .status").html("Done!");
							$("#podlove-migration-status").parent(".update-nag").hide();
						} else {
							$("#podlove-migration-status .status").html("Rows to update: " + result.todo);
							delete_entries();
						}
					}
				});
			}

			$("#podlove-start-tracking-migration").on("click", function(e) {
				e.preventDefault();

				$("#podlove-migration-status").html("<br><i class=\"podlove-icon-spinner rotate\"></i> <em class=\"status\">Waiting ...</span>");
				delete_entries();
			});
		});
		</script>
		<?php
	}

}