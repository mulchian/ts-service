<?php
$teamSite = 'roster';
if (isset($_GET['do'])) {
    $teamSite = $_GET['do'];
}


?>

<div>
    <?php
    switch ($teamSite) {
        case 'roster':
            /* Overview - Roster */
            include('pages/player/playerModal.php');
            include('roster.php');
            break;
        case 'train':
            /* Training */
            include('pages/player/playerModal.php');
            include('training.php');
            break;
        case 'lineup':
            /* Aufstellung */
            include('lineup/lineupModal.php');
            include('lineup/lineup.php');
            break;
        case 'coaching':
            /* Settings for Coaching */
            include('coaching/coachingInfo.php');
            include('coaching/coaching.php');
            break;
        default:
            break;
    }
    ?>
</div>

<script src="/app/scriptsipts/team/team.js"></script>