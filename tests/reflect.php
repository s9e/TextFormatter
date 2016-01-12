<?php

ob_start('ob_gzhandler');
echo serialize([
	'input'   => file_get_contents('php://input'),
	'_SERVER' => $_SERVER
]);