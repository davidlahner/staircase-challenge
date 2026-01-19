<?php
require_once 'functions.php';
/**
 * Process the Staircase Challenge request
 * Fetches plays from BoardGameGeek API and generates BBCode
 */

// Get form data
$username = $_POST['username'] ?? '';
$fromDate = $_POST['from_date'] ?? '';
$toDate = $_POST['to_date'] ?? '';
$authorizationToken = 'your_api_token_here'; // Replace with your actual token

// Get emoji from POST, default to 🎲
$emoji = isset($_POST['emoji']) && $_POST['emoji'] !== '' ? $_POST['emoji'] : '🎲';

// Validate inputs for API mode
if (empty($username) || empty($fromDate) || empty($toDate)) {
    redirectWithError('All fields are required.');
}

// Validate date format
if (!validateDate($fromDate) || !validateDate($toDate)) {
    redirectWithError('Invalid date format. Please use yyyy-mm-dd.');
}

// Fetch all pages from BoardGameGeek API for boardgames
$xmlContent = fetchAllPlaysXml($username, $fromDate, $toDate, 'boardgame', $authorizationToken);

// Fetch all pages for boardgameexpansion and add to $xmlContent
$expansionXmlContent = fetchAllPlaysXml($username, $fromDate, $toDate, 'boardgameexpansion', $authorizationToken);

if ($xmlContent === '' && $expansionXmlContent === '') {
    redirectWithError('No plays found for the specified date range.');
}

// Merge the two XML contents if both are present
if ($xmlContent !== '' && $expansionXmlContent !== '') {
    $mainXml = simplexml_load_string($xmlContent);
    $expansionXml = simplexml_load_string($expansionXmlContent);
    if ($mainXml && $expansionXml) {
        foreach ($expansionXml->play as $play) {
            $newPlay = $mainXml->addChild('play');
            foreach ($play->attributes() as $key => $value) {
                $newPlay->addAttribute($key, $value);
            }
            foreach ($play->children() as $child) {
                copyXmlNode($child, $newPlay);
            }
        }
        $xmlContent = $mainXml->asXML();
    }
} elseif ($expansionXmlContent) {
    $xmlContent = $expansionXmlContent;
}

// Parse XML
libxml_use_internal_errors(true);
$xml = simplexml_load_string($xmlContent);

if ($xml === false) {
    redirectWithError('Failed to parse API response. Please check your username and try again.');
}

// Check if username exists (API returns empty plays element for non-existent users)
if (!isset($xml['username'])) {
    redirectWithError('Username not found. Please check the username and try again.');
}

// Check if there are any plays
if (!isset($xml->play) || count($xml->play) === 0) {
    redirectWithError('No plays found for the specified date range.');
}

// Aggregate plays by game
$games = aggregatePlays($xml);

if (empty($games)) {
    redirectWithError('No valid games found in the play data.');
}

// Generate staircase
$staircase = generateStaircase($games);

if (empty($staircase)) {
    redirectWithError('Could not generate staircase. No games with sufficient play counts found.');
}

// Generate BBCode
$bbcode = generateBBCode($staircase, $emoji);

// Redirect back with success and BBCode
redirectWithSuccess($bbcode, $username, $fromDate, $toDate);