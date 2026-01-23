<?php
require_once 'functions.php';

// Usage: php persistPlaysForYear.php <username> <year>
if ($argc < 3) {
    fwrite(STDERR, "Usage: php persistPlaysForYear.php <username> <year>\n");
    exit(1);
}

$username = $argv[1];
$year = $argv[2];

if (!preg_match('/^\\d{4}$/', $year)) {
    fwrite(STDERR, "Year must be in YYYY format.\n");
    exit(1);
}

$fromDate = "$year-01-01";
$toDate = "$year-12-31";
$authorizationToken = '<AuthorizationToken>';

// Fetch all plays for boardgame and boardgameexpansion
$xmlContent = fetchAllPlaysXml($username, $fromDate, $toDate, 'boardgame', $authorizationToken);
$expansionXmlContent = fetchAllPlaysXml($username, $fromDate, $toDate, 'boardgameexpansion', $authorizationToken);

if ($xmlContent === '' && $expansionXmlContent === '') {
    fwrite(STDERR, "No plays found for the specified date range.\n");
    exit(1);
}

// Merge XML if both present
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

libxml_use_internal_errors(true);
$xml = simplexml_load_string($xmlContent);
if ($xml === false) {
    fwrite(STDERR, "Failed to parse API response.\n");
    exit(1);
}
if (!isset($xml['username'])) {
    fwrite(STDERR, "Username not found.\n");
    exit(1);
}
if (!isset($xml->play) || count($xml->play) === 0) {
    fwrite(STDERR, "No plays found for the specified date range.\n");
    exit(1);
}

$outputFile = "plays_{$username}_{$year}.xml";
$resultXml = $xml->asXML();

file_put_contents("plays/$outputFile", $resultXml);
echo "Plays saved to $outputFile\n";
