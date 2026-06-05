<?php
/**
 * Shared functions for the Staircase Challenge
 */

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
