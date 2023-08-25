<?php
$teamSite = 'buero';
if (isset($_GET['do'])) {
    $teamSite = $_GET['do'];
}


?>

<div>
    <?php
    switch ($teamSite) {
        case 'buero':
            /* Overview - Büro */
            include('officeOverview.php');
            break;
        case 'personal':
            /* Personal */
            include('personal.php');
            break;
        case 'friendly':
            include('friendly.php');
            break;
        default:
            break;
    }
    ?>
</div>