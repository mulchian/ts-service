<?php

use Monolog\Logger;
use touchdownstars\main\MainController;
use touchdownstars\team\TeamController;
use touchdownstars\user\UserController;

//error_reporting(E_ERROR | E_PARSE);
//ini_set("display_errors", 1);

$startingTime = microtime(true);

include('init.php');

session_start();
?>
<!DOCTYPE html>
<html lang="de">
<?php
include('pages/elements/head.php');

$allowedSites = array('impressum', 'agb', 'about');

if (isset($pdo, $log, $_GET['activate'])) {
    $userId = $_GET['activate'];
    $log->debug('Aktivierungs-ID: ' . $userId);
    $userController = new UserController($pdo, $log);
    $user = $userController->fetchUserById($userId);
    if (isset($user)) {
        $user->setActivated(true);
        $userController->saveUser($user);
        $_SESSION['user'] = $user;
    }
}

if (isset($pdo, $log, $_GET['logout']) || (isset($pdo, $log, $_SESSION['created']) && (time() - $_SESSION['created']) >= 172800)) {
    if (isset($_SESSION['user'])) {
        $userController = new UserController($pdo, $log);
        $user = $_SESSION['user'];
        $user->setStatus('offline');
        $userController->saveUser($user);
    }
    unset($_SESSION);
    session_unset();
    session_destroy();
    redirect('index.php');
}

if (isset($log)) {
    $log->debug('Session-ID: ' . session_id());
    if (isset($_SESSION) && !empty($_SESSION['created'])) {
        $log->debug('Session created: ' . $_SESSION['created']);
    } else {
        $log->debug('Session created: keine Session vorhanden');
    }
}

if (isset($log) && (!isset($_SESSION) || !isset($_SESSION['user']))) {
    $log->info('Keine Session bzw. kein User: ' . print_r($_SESSION ?? array(), true));
    $log->debug('POST: ' . print_r($_POST, true));
    $log->debug('POST-Length: ' . count($_POST));
    $log->debug('GET: ' . print_r($_GET, true));
    $log->debug('GET-Length: ' . count($_GET));
    if (!isset($_SESSION['user']) && count($_GET) > 0 && isset($_GET['site']) && !in_array($_GET['site'], $allowedSites)) {
        redirect('index.php');
    }
}

if (isset($pdo, $log)) {
    // Zeitstempel heute um Mitternacht
    $today = strtotime('today + 1 minute');
    // Wenn die Session älter als heute Morgen ist, müssen Saison und Spieltag aktualisiert werden.
    // Lese Main-Information aus: Saison und Spieltag
    if (!isset($_SESSION['season']) && !isset($_SESSION['gameday'])) {
        updateMainInformation($pdo, $log);
    } else if (!empty($_SESSION['created']) && $_SESSION['created'] < $today) {
        updateMainInformation($pdo, $log);
    }
}

function updateMainInformation(PDO $pdo, Logger $log): void
{
    $mainController = new MainController($pdo, $log);
    $arrSeasonAndGameday = $mainController->fetchSeasonAndGameday();
    $_SESSION['season'] = $arrSeasonAndGameday['season'];
    $_SESSION['gameday'] = $arrSeasonAndGameday['gameday'];
}

if (isset($pdo, $log) && isset($_SESSION['user']) && !empty(isset($_SESSION['user']))) {
    $user = $_SESSION['user'];
    if (!empty($_SESSION['team'])) {
        $team = isset($_SESSION['team']);
    } else {
        $teamController = new TeamController($pdo, $log);
        $fetchedTeam = $teamController->fetchTeam($user->getId());
        if (!empty($fetchedTeam)) {
            $team = $fetchedTeam;
        }
    }
}

?>

<body>
<?php include('pages/elements/navigation.php'); ?>
<div class="container-fluid">
    <div class="row my-1 justify-content-center">
        <div class="col-sm content-justified-center text-center">
            <?php

            if (empty($_GET['site'])) {
                if (isset($user) && isset($_SESSION['created']) && (time() - $_SESSION['created']) < 172800) {
                    if (isset($team)) {
                        include_once('pages/home/home.php');
                    } else {
                        include_once('pages/team/teamRegistration.php');
                    }
                } else {
                    if (isset($pdo, $log, $_GET['changePassword'])) {
                        $_GET['lgn'] = '2';
                    }
                    include_once('pages/user/loginController.php');
                }
            } else {
                $site = $_GET['site'];
                if (isset($_GET['pkg'])) {
                    $pkg = $_GET['pkg'];
                } else {
                    $end = strlen($site);
                    $pkg = substr($site, 0, $end);
                }
                if (!@include('pages/' . $pkg . '/' . $site . '.php')) {
                    include_once('pages/error/notFound.php');
                } else {
                    include_once('pages/' . $pkg . '/' . $site . '.php');
                }
            }

            ?>
        </div>
    </div>
</div>
<br/>
<?php
$endTime = microtime(true);

$differenceTime = $endTime - $startingTime;
$differenceTime = round($differenceTime, 3);
?>
<!-- footer -->
<footer class="footer opacity mt-3" id="footer">
    <div class="container">
        <span class="text-muted"><?php echo '© ' . date('Y') . ' Copyright J. Wolf'; ?></span>
        <span class="text-muted text-devider">|</span>
        <span class="text-muted"><a class="text-reset" href="index.php?site=impressum">Impressum</a></span>
        <span class="text-muted text-devider">|</span>
        <span class="text-muted"><a class="text-reset" href="index.php?site=agb">AGB</a></span>
        <span class="text-muted text-devider">|</span>
        <span class="text-muted"><a class="text-reset" href="index.php?site=about">Über uns</a></span>
        <span class="text-muted text-devider">|</span>
        <span id="lblCallingTime" class="text-muted"><?php echo $differenceTime . ' Sekunden'; ?></span>
    </div>
</footer>

<script type="text/javascript">
    <?php if (isset($_SESSION['user'])) : ?>
    const AJAX_USER_URL = window.location.origin + '/ajax/user/';
    const LAST_ACTIVE_TIME = Date.now();
    let idleInterval = null;

    $(document).ready(function () {
        let idleState = false;
        let idleTimer = null;
        $('*').bind('mousemove click mouseup mousedown keydown keypress keyup submit change mouseenter scroll resize dblclick', function () {
            clearTimeout(idleTimer);
            clearInterval(idleInterval);
            if (idleState === true) {
                updateActivity('online');
            }
            idleState = false;
            idleTimer = setTimeout(function () {
                updateActivity('abwesend');
                idleInterval = setInterval(function () {
                    updateActivity('abwesend');
                }, 300000);
                idleState = true;
            }, 60000);
        });
        updateActivity('online');
    });

    function updateActivity(status) {
        let now = Date.now();
        if ((LAST_ACTIVE_TIME + 60000) < now) {
            $.ajax({
                type: 'POST',
                url: AJAX_USER_URL + 'saveUserStatus.php',
                data: {
                    lastActiveTime: Math.floor(now / 1000),
                    status: status
                },
                dataType: 'JSON'
            });
        }
    }
    <?php endif; ?>
    $(window).scroll(function () {
        let top = $('#navigation').height();
        $('.fix-sticky').css("right", "15px").css("left", "15px").css("top", top + "px");
    });
</script>
</body>
</html>
