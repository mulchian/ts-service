<?php
$leagueSite = 'table';
if (isset($_GET['do'])) {
    $leagueSite = $_GET['do'];
}


?>

<div>
    <?php
    switch ($leagueSite) {
        case 'table':
            /* Overview - Ligatabelle */
//            include('pages/player/playerModal.php');
            include('table.php');
            break;
        case 'schedule':
            /* Spielplan (Game Schedule) */
            $isLeague = true;
            include('schedule.php');
            break;
        case 'gamecenter':
            include('gamecenter.php');
            break;
        case 'friendlyResult':
            $isLeague = false;
            include('schedule.php');
            break;
        default:
            break;
    }
    ?>
</div>
