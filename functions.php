<?php
/**
 * Shared functions for the Staircase Challenge
 */

function fetchAllPlaysXml($username, $fromDate, $toDate, $subtype, $authorizationToken)
{
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

function copyXmlNode($source, $target)
{
    $newNode = $target->addChild($source->getName(), (string)$source);
    foreach ($source->attributes() as $key => $value) {
        $newNode->addAttribute($key, $value);
    }
    foreach ($source->children() as $child) {
        copyXmlNode($child, $newNode);
    }
}

function aggregatePlays($xml)
{
    $games = [];
    foreach ($xml->play as $play) {
        $incomplete = (string)$play['incomplete'];
        if ($incomplete !== '0') {
            continue;
        }
        if (isset($play->item)) {
            $item = $play->item;
            $objectId = (string)$item['objectid'];
            $name = (string)$item['name'];
            if (empty($objectId) || empty($name)) {
                continue;
            }
            if (!isset($games[$objectId])) {
                $games[$objectId] = [
                    'id' => $objectId,
                    'name' => $name,
                    'plays' => 0
                ];
            }
            $quantity = isset($play['quantity']) ? (int)$play['quantity'] : 1;
            $games[$objectId]['plays'] += $quantity;
        }
    }
    return $games;
}

function generateStaircase($games)
{
    $staircase = [];
    $games = sortGamesByPlaysAscending($games);
    $maxLength = getMaximumPossibleStaircaseLength($games);
    $games = sortGamesByPlaysDescending($games);
    $selectedGames = array_slice($games, 0, $maxLength);
    $step = 1;
    $selectedGames = sortGamesByPlaysAscending($selectedGames);
    foreach ($selectedGames as $game) {
        $staircase[] = [
            'step' => $step,
            'id' => $game['id'],
            'name' => $game['name'],
            'plays' => $game['plays']
        ];
        $step++;
        if (count($staircase) >= $maxLength) {
            break;
        }
    }
    return $staircase;
}

function getMaximumPossibleStaircaseLength($games): int
{
    $maxLength = 0;
    $step = 1;
    $gamesCopy = $games;
    $usedIds = [];
    while (true) {
        $found = false;
        foreach ($gamesCopy as $game) {
            if (in_array($game['id'], $usedIds)) {
                continue;
            }
            if ($game['plays'] >= $step) {
                $usedIds[] = $game['id'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            break;
        }
        $maxLength++;
        $step++;
    }
    return $maxLength;
}

function sortGamesByPlaysAscending($selectedGames)
{
    usort($selectedGames, function ($a, $b) {
        if ($b['plays'] !== $a['plays']) {
            return $a['plays'] - $b['plays'];
        }
        return strcmp($a['name'], $b['name']);
    });
    return $selectedGames;
}

function sortGamesByPlaysDescending($games)
{
    usort($games, function ($a, $b) {
        if ($b['plays'] !== $a['plays']) {
            return $b['plays'] - $a['plays'];
        }
        return strcmp($a['name'], $b['name']);
    });
    return $games;
}

function generateBBCode($staircase, $emoji)
{
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

function validateDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function redirectWithError($message)
{
    $params = [
        'error' => $message,
        'username' => $_POST['username'] ?? '',
        'from' => $_POST['from_date'] ?? '',
        'to' => $_POST['to_date'] ?? ''
    ];
    header('Location: index.php?' . http_build_query($params));
    exit;
}

function redirectWithSuccess($bbcode, $username, $fromDate, $toDate)
{
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
