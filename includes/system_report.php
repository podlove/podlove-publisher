<?php

/**
 * System Report needs to be run whenever a setting has changed that could effect something critical.
 */
function podlove_run_system_report() {
	$report = new Podlove\SystemReport;
	$report->run();
}

add_action( 'update_option_permalink_structure', 'podlove_run_system_report' );
add_action( 'update_option_podlove', 'podlove_run_system_report' );