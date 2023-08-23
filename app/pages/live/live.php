<?php
$liveMode = 'game';
if (isset($_GET['mode'])) {
    $liveMode = $_GET['mode'];
}
?>

<div>
    <?php
    switch ($liveMode) {
        case 'game':
            /* Live-Spiel */
            include('game.php');
            break;
        case 'draft':
            /* Live-Draft */
            include('draft.php');
            break;
        default:
            break;
    }
    ?>
</div>

