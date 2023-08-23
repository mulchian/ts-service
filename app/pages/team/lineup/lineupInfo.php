<?php

use touchdownstars\player\Player;

function getPlayerInfo(array $players, string $position, string $lineupPosition, int $number = 0): string
{
    $btnChange = '<button type="button" class="btn btn-secondary mt-2" ' .
        'data-toggle="modal" data-target="#lineupModal" ' .
        'data-position="' . $position . '" data-lineup_position="' . ((strpos($lineupPosition, 'RB') !== false || strpos($lineupPosition, 'MLB') !== false) ? substr($lineupPosition, 0, -1) : $lineupPosition) . '">' .
        'ÄNDERN' .
        '</button>';

    $player = array_values(array_filter($players, function (Player $player) use ($lineupPosition) {
        return $player->getLineupPosition() == $lineupPosition;
    }))[$number];

    if ($player) {
        $talent = '';
        for ($i = 0; $i < floor($player->getTalent() / 2); $i++) {
            $talent .= '<i class="fas fa-star"></i>';
        }
        if ($player->getTalent() % 2 !== 0) {
            $talent .= '<i class="far fa-star"></i>';
        }

        return '<div class="card-title">' . $player->getFirstName() . ' ' . $player->getLastName() . ($lineupPosition == 'R' ? ' (' . $player->getType()->getPosition()->getPosition() . ')' : '') . '</div>' .
            '<div class="card-text">' .
            '<div class="row">' .
            '<div class="col-6 text-warning">' . $player->getOVR() . '</div>' .
            '<div class="col-6 text-warning">' . $player->getAge() . '</div>' .
            '</div>' .
            '<div class="row">' .
            '<div class="col-6"><small>OVR</small></div>' .
            '<div class="col-6"><small>ALTER</small></div>' .
            '</div>' .
            '<div class="row">' .
            '<div class="col text-warning">' . $talent . '</div>' .
            '</div>' .
            '<div class="row">' .
            '<div class="col"><small>TALENT</small></div>' .
            '</div>' .
            '</div>' . $btnChange;
    }
    return $btnChange;
}