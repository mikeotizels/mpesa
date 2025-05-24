<?php
// Load the SDK autoload.php file.
require_once dirname(__DIR__) . '/autoload.php';

// Instantiate the Mpesa object.
$mpesa = new Mikeotizels\Mpesa\Mpesa([
	'environment' => 'testing'
]);