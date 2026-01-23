<?php
require_once 'functions.php';

// Usage: php lifetime_staircase.php <username> <start_year>
if ($argc < 3) {
    fwrite(STDERR, "Usage: php lifetime_staircase.php <username> <start_year>\n");
    exit(1);
}

$username = $argv[1];
$startYear = (int)$argv[2];
$currentYear = (int)date('Y');
$authorizationToken = '<AuthorizationToke>';

// 1. Load all local XMLs from startYear up to last year
$allPlaysXml = null;
for ($year = $startYear; $year < $currentYear; $year++) {
    $file = "plays/plays_{$username}_{$year}.xml";
    if (file_exists($file)) {
        $xml = simplexml_load_file($file);
        if ($xml !== false) {
            if ($allPlaysXml === null) {
                $allPlaysXml = new SimpleXMLElement('<plays username="' . htmlspecialchars($username) . '"/>');
            }
            foreach ($xml->play as $play) {
                $newPlay = $allPlaysXml->addChild('play');
                foreach ($play->attributes() as $key => $value) {
                    $newPlay->addAttribute($key, $value);
                }
                foreach ($play->children() as $child) {
                    copyXmlNode($child, $newPlay);
                }
            }
        }
    }
}

// 2. Load current year plays from BGG
$fromDate = "$currentYear-01-01";
$toDate = "$currentYear-12-31";
$xmlContent = fetchAllPlaysXml($username, $fromDate, $toDate, 'boardgame', $authorizationToken);
$expansionXmlContent = fetchAllPlaysXml($username, $fromDate, $toDate, 'boardgameexpansion', $authorizationToken);

if ($xmlContent !== '' || $expansionXmlContent !== '') {
    $mainXml = $xmlContent !== '' ? simplexml_load_string($xmlContent) : null;
    $expansionXml = $expansionXmlContent !== '' ? simplexml_load_string($expansionXmlContent) : null;
    if ($mainXml) {
        if ($expansionXml) {
            foreach ($expansionXml->play as $play) {
                $newPlay = $mainXml->addChild('play');
                foreach ($play->attributes() as $key => $value) {
                    $newPlay->addAttribute($key, $value);
                }
                foreach ($play->children() as $child) {
                    copyXmlNode($child, $newPlay);
                }
            }
        }
        if ($allPlaysXml === null) {
            $allPlaysXml = new SimpleXMLElement('<plays username="' . htmlspecialchars($username) . '"/>');
        }
        foreach ($mainXml->play as $play) {
            $newPlay = $allPlaysXml->addChild('play');
            foreach ($play->attributes() as $key => $value) {
                $newPlay->addAttribute($key, $value);
            }
            foreach ($play->children() as $child) {
                copyXmlNode($child, $newPlay);
            }
        }
    } elseif ($expansionXml) {
        if ($allPlaysXml === null) {
            $allPlaysXml = new SimpleXMLElement('<plays username="' . htmlspecialchars($username) . '"/>');
        }
        foreach ($expansionXml->play as $play) {
            $newPlay = $allPlaysXml->addChild('play');
            foreach ($play->attributes() as $key => $value) {
                $newPlay->addAttribute($key, $value);
            }
            foreach ($play->children() as $child) {
                copyXmlNode($child, $newPlay);
            }
        }
    }
}

if ($allPlaysXml === null || !isset($allPlaysXml->play) || count($allPlaysXml->play) === 0) {
    fwrite(STDERR, "No plays found for the specified range.\n");
    exit(1);
}

// 3. Aggregate plays and generate staircase
$games = aggregatePlays($allPlaysXml);
if (empty($games)) {
    fwrite(STDERR, "No valid games found in the play data.\n");
    exit(1);
}
$staircase = generateStaircase($games);
$staircase = array_reverse(generateStaircase($games));
if (empty($staircase)) {
    fwrite(STDERR, "Could not generate staircase. No games with sufficient play counts found.\n");
    exit(1);
}
$bbcode = generateBBCode($staircase, '');
echo $bbcode . "\n";

