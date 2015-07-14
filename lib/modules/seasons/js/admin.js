jQuery(document).ready(function($) {
	var $start_date = $("#podlove_season_start_date")
    $start_date.datepicker({
    	dateFormat: $.datepicker.ISO_8601,
		changeMonth: true,
		changeYear: true
    });

    $start_date.closest("div").on("click", function() {
    	$start_date.datepicker("show");
    });
});
