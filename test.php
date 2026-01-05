<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Staircase Challenge</title>
</head>
<body>
    <h1>Testing BoardGameGeek API</h1>

    <?php
    // Test API connection with a known username
    $testUsername = 'Sepiroth';
    $testFrom = '2024-01-01';
    $testTo = '2024-12-31';

    $apiUrl = "https://boardgamegeek.com/xmlapi2/plays?username=" . urlencode($testUsername)
        . "&mindate=" . urlencode($testFrom)
        . "&maxdate=" . urlencode($testTo);

    echo "<h2>Test Parameters:</h2>";
    echo "<p><strong>Username:</strong> {$testUsername}</p>";
    echo "<p><strong>Date Range:</strong> {$testFrom} to {$testTo}</p>";
    echo "<p><strong>API URL:</strong> <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></p>";

    echo "<h2>API Response:</h2>";

    $xmlContent = @file_get_contents($apiUrl);

    if ($xmlContent === false) {
        echo "<p style='color: red;'>Failed to fetch data from API</p>";
    } else {
        echo "<p style='color: green;'>Successfully fetched data from API</p>";

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            echo "<p style='color: red;'>Failed to parse XML</p>";
            foreach(libxml_get_errors() as $error) {
                echo "<p>" . htmlspecialchars($error->message) . "</p>";
            }
        } else {
            echo "<p style='color: green;'>Successfully parsed XML</p>";

            if (isset($xml['username'])) {
                echo "<p><strong>Username confirmed:</strong> {$xml['username']}</p>";
            }

            if (isset($xml->play)) {
                $playCount = count($xml->play);
                echo "<p><strong>Total plays found:</strong> {$playCount}</p>";

                // Show first few plays
                echo "<h3>Sample plays:</h3>";
                echo "<ul>";
                $count = 0;
                foreach ($xml->play as $play) {
                    if ($count >= 5) break;
                    if (isset($play->item)) {
                        $item = $play->item;
                        $name = (string)$item['name'];
                        $objectId = (string)$item['objectid'];
                        echo "<li>Game: {$name} (ID: {$objectId})</li>";
                        $count++;
                    }
                }
                echo "</ul>";
            } else {
                echo "<p>No plays found</p>";
            }
        }
    }

    echo "<hr>";
    echo "<p><a href='index.php'>Go to Main Application</a></p>";
    ?>
</body>
</html>

