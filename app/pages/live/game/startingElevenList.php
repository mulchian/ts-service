<?php
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


