<?php

use touchdownstars\player\Player;

if (isset($startingPlayers)) :
    ?>

    <ul class="list-group">
        <?php foreach ($startingPlayers as $player): ?>
            <li class="list-group-item text-white bg-dark d-flex justify-content-between align-items-center">
                <span><?php echo getCorrectLineupPosition($player); ?></span>
                <?php echo $player->getLastName(); ?>
                <span class="badge badge-secondary"><?php echo $player->getOVR(); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

<?php endif; ?>

<?php
function getCorrectLineupPosition(PLayer $player): string {
    $lineupPosition = $player->getLineupPosition();
    if (strpos($lineupPosition, 'MLB') !== false) {
        $lineupPosition = 'MLB';
    } elseif (strpos($lineupPosition, 'RB') !== false) {
        $lineupPosition = 'RB';
    }
    return $lineupPosition;
}
?>
