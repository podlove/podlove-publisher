<?php
$spyc_class_file = "vendor/mustangostang/spyc/Spyc.php";

$handle = fopen($spyc_class_file, "r+");
$content = fread($handle, filesize($spyc_class_file));

// if the check has not already been added only
if (!stripos($content, "if (!class_exists('Spyc'))")) {
	$content = str_replace("class Spyc", "if (!class_exists('Spyc')) {\nclass Spyc", $content);
	$content .= "\n}\n";

	rewind($handle);
	fwrite($handle, $content);
}

fclose($handle);
