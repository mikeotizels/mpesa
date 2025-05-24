<?php
require_once dirname(__DIR__) . '/bootstrap.php';

try {
    $response = $mpesa->accountBalanceQuery();

    echo json_encode($response);
}
catch (Exception $e) {
    echo $e->getMessage();
}