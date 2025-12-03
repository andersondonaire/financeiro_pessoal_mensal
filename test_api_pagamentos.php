<?php
session_start();
$_SESSION['usuario_id'] = 1;

$ch = curl_init('http://localhost:3030/api/pagamentos.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
curl_close($ch);

echo $response;
