<?php

use Podlove\Model\UserAgent;

$agent = new UserAgent();
$agent->user_agent = trim($args[1]);
$agent->parse();

print_r($agent);
