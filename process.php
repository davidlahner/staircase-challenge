<?php
require_once 'functions.php';

header('Content-Type: application/json');

$username = $_GET['username'] ?? '';
$fromDate = $_GET['from'] ?? '';
$toDate = $_GET['to'] ?? '';
$page = intval($_GET['page'] ?? 1);
$subtype = $_GET['subtype'] ?? 'boardgame';
$authorizationToken = 'your_api_token_here'; // Replace with your actual token

if (!$username || !$fromDate || !$toDate) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

// Fetch ONE page only

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Authorization: Bearer ' . $authorizationToken,
            'User-Agent: StaircaseChallenge/1.0'
        ],
        'timeout' => 30
    ]
]);

$apiUrl = "https://boardgamegeek.com/xmlapi2/plays?username=" . urlencode($username)
    . "&mindate=" . urlencode($fromDate)
    . "&maxdate=" . urlencode($toDate)
    . "&subtype=" . urlencode($subtype)
    . "&page=" . $page;

$xmlContent = @file_get_contents($apiUrl, false, $context);

if ($xmlContent === false) {
    echo json_encode(["error" => "Failed to fetch API"]);
    exit;
}

libxml_use_internal_errors(true);
$xml = simplexml_load_string($xmlContent);

if (!$xml || !isset($xml['total'])) {
    echo json_encode(["error" => "Invalid response"]);
    exit;
}

// aggregate this page only
$aggregated = aggregatePlays($xml);

echo json_encode($aggregated);