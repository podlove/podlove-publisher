var PODLOVE = PODLOVE || {};

(function($) {
	PODLOVE.ProtectFeed = function() {
		var $protection = $("#podlove_feed_protected"),
			$protection_row = $("tr.row_podlove_feed_protection_type"),
			$protection_type = $("#podlove_feed_protection_type"),
			$credentials = $("tr.row_podlove_feed_protection_password,tr.row_podlove_feed_protection_user");

		var protectionIsActive = function() {
			return $protection.is(":checked");
		};

		var isCustomLogin = function() {
			return $protection_type.val() == "0";
		};

		if (protectionIsActive()) {
			$protection_row.show();
		}
		
		if (protectionIsActive() && isCustomLogin()) {
			$credentials.show();
		}

		$("#podlove_feed_protected").on("change", function() {
			if (protectionIsActive()) {
				$protection_row.show();
				if (isCustomLogin()) {
					$credentials.show();
				} 
			} else {
				$protection_row.hide();
				$credentials.hide();
			}
		});	

		$protection_type.change(function() {
			if (protectionIsActive() && isCustomLogin()) {
				$credentials.show();
			} else {
				$credentials.hide();
			}
		});
	}
}(jQuery));