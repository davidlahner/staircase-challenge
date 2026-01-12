<?php
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

/**
 * Fetch all pages of plays from BoardGameGeek API for a user and date range
 * Returns the combined XML content as a string
 */
function fetchAllPlaysXml($username, $fromDate, $toDate, $subtype, $authorizationToken) {
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

    $allXmlContent = '';
    $currentPage = 1;
    $hasMorePages = true;
    $totalPlays = 0;

    while ($hasMorePages) {
        $apiUrl = "https://boardgamegeek.com/xmlapi2/plays?username=" . urlencode($username)
            . "&mindate=" . urlencode($fromDate)
            . "&maxdate=" . urlencode($toDate)
            . "&subtype=" . urlencode($subtype)
            . "&page=" . urlencode($currentPage);

        $xmlContent = @file_get_contents($apiUrl, false, $context);
        if ($xmlContent === false) {
            redirectWithError('Failed to connect to BoardGameGeek API. Please try again later.');
        }

        libxml_use_internal_errors(true);
        $pageXml = simplexml_load_string($xmlContent);
        if ($pageXml === false) {
            redirectWithError('Failed to parse API response. Please check your username and try again.');
        }
        if (!isset($pageXml['username'])) {
            redirectWithError('Username not found. Please check the username and try again.');
        }
        if (!isset($pageXml->play) || count($pageXml->play) === 0) {
            $hasMorePages = false;
        } else {
            if ($currentPage === 1) {
                $allXmlContent = $xmlContent;
                $totalPlays = count($pageXml->play);
            } else {
                $mainXml = simplexml_load_string($allXmlContent);
                foreach ($pageXml->play as $play) {
                    $newPlay = $mainXml->addChild('play');
                    foreach ($play->attributes() as $key => $value) {
                        $newPlay->addAttribute($key, $value);
                    }
                    foreach ($play->children() as $child) {
                        copyXmlNode($child, $newPlay);
                    }
                    $totalPlays++;
                }
                $allXmlContent = $mainXml->asXML();
            }
            $currentPage++;
        }
        usleep(500000); // 0.5 second delay
    }
    return $allXmlContent;
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

/**
 * Copy XML node with all attributes and children
 */
function copyXmlNode($source, $target) {
    $newNode = $target->addChild($source->getName(), (string)$source);

    // Copy attributes
    foreach ($source->attributes() as $key => $value) {
        $newNode->addAttribute($key, $value);
    }

    // Copy children recursively
    foreach ($source->children() as $child) {
        copyXmlNode($child, $newNode);
    }
}

/**
 * Aggregate plays by game objectId
 */
function aggregatePlays($xml) {
    $games = [];
    $processedCount = 0;
    $skippedCount = 0;

    foreach ($xml->play as $play) {
        // Check if play is incomplete - skip if incomplete != 0
        $incomplete = (string)$play['incomplete'];
        if ($incomplete !== '0') {
            $skippedCount++;
            continue;
        }

        if (isset($play->item)) {
            $item = $play->item;
            $objectId = (string)$item['objectid'];
            $name = (string)$item['name'];

            // Validate that we have the required attributes
            if (empty($objectId) || empty($name)) {
                $skippedCount++;
                continue;
            }

            if (!isset($games[$objectId])) {
                $games[$objectId] = [
                    'id' => $objectId,
                    'name' => $name,
                    'plays' => 0
                ];
            }

            $games[$objectId]['plays']++;
            $processedCount++;
        } else {
            $skippedCount++;
        }
    }

    // Debug info could be logged here if needed
    // error_log("Processed: $processedCount, Skipped: $skippedCount");

    return $games;
}

/**
 * Generate staircase from games
 * Ensures each step has a game with at least that many plays
 */
function generateStaircase($games) {
    $staircase = [];
    $usedGames = [];
    $step = 1;

    // Sort games: first by play count (ascending), then by name (ascending)
    usort($games, function($a, $b) {
        if ($b['plays'] !== $a['plays']) {
            return $a['plays'] - $b['plays'];
        }
        return strcmp($a['name'], $b['name']);
    });

    while (true) {
        $found = false;

        foreach ($games as $game) {
            // Skip if already used
            if (in_array($game['id'], $usedGames)) {
                continue;
            }

            // Check if game has at least the required number of plays
            if ($game['plays'] >= $step) {
                $staircase[] = [
                    'step' => $step,
                    'id' => $game['id'],
                    'name' => $game['name'],
                    'plays' => $game['plays']
                ];
                $usedGames[] = $game['id'];
                $found = true;
                break;
            }
        }

        // Stop if no game found for current step
        if (!$found) {
            break;
        }

        $step++;
    }

    return $staircase;
}

/**
 * Generate BBCode from staircase
 */
function generateBBCode($staircase, $emoji) {
    $lines = [];

    foreach ($staircase as $entry) {
        $stepNum = str_pad($entry['step'], 2, '0', STR_PAD_LEFT);
        $dice = str_repeat($emoji, $entry['step']);
        $thing = "[thing={$entry['id']}][/thing]";
        $plays = "({$entry['plays']})";

        $lines[] = "{$stepNum}. {$dice}{$thing} {$plays}";
    }

    return implode("\n", $lines);
}

/**
 * Validate date format (yyyy-mm-dd)
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Redirect with error message
 */
function redirectWithError($message) {
    $params = [
        'error' => $message,
        'username' => $_POST['username'] ?? '',
        'from' => $_POST['from_date'] ?? '',
        'to' => $_POST['to_date'] ?? ''
    ];
    header('Location: index.php?' . http_build_query($params));
    exit;
}

/**
 * Redirect with success message and BBCode
 */
function redirectWithSuccess($bbcode, $username, $fromDate, $toDate) {
    $params = [
        'success' => '1',
        'bbcode' => $bbcode,
        'username' => $username,
        'from' => $fromDate,
        'to' => $toDate
    ];
    header('Location: index.php?' . http_build_query($params));
    exit;
}
