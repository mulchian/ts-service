<?php

use touchdownstars\player\Player;

function getCorrectLineupPosition(PLayer $player): string {
    $lineupPosition = $player->getLineupPosition();
    if (strpos($lineupPosition, 'MLB') !== false) {
        $lineupPosition = 'MLB';
    } elseif (strpos($lineupPosition, 'RB') !== false) {
        $lineupPosition = 'RB';
    }
    return $lineupPosition;
}