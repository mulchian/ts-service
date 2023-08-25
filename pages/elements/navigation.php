<?php

use touchdownstars\league\LeagueController;

$budget = 0;
$salaryCap = 0;
if (isset($pdo, $log)):
    $leagueController = new LeagueController($pdo, $log);

    if (isset($_SESSION['season']) && !empty($_SESSION['season']) && isset($_SESSION['gameday']) && !empty($_SESSION['gameday'])) {
        $season = $_SESSION['season'];
        $gameday = $_SESSION['gameday'];
    } else {
        $season = 1;
        $gameday = 0;
    }
    if (isset($_SESSION['user']) && !empty($_SESSION['user']) && isset($_SESSION['team']) && !empty($_SESSION['team'])) {
        $user = $_SESSION['user'];
        $team = $_SESSION['team'];
        $budget = $team->getBudget();
        $salaryCap = $team->getSalaryCap();
    } else {
        if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) && count($_GET) == 0) {
            header("Location: index.php");
        }
    }
    ?>
    <div class="fixed-top" id="navigation">
        <nav class="navbar navbar-expand-lg navbar-toggleable-md navbar-dark bg-dark">

            <button type="button" class="navbar-toggler navbar-toggler-right" data-toggle="collapse"
                    data-target="#navbarCollapse" aria-expanded="false" aria-controls="navbarCollapse"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- navbar-brand = place for logo and name -->
            <a class="navbar-brand logo mb-0 h1 text-center" href="/index.php" style="margin-left: 10px">
                <img src="/resources/logo_transparent.png" alt="Touchdown Stars" width="48" height="27"
                     style="margin-left: 10px"><br/>Touchdown Stars
            </a>

            <div class="collapse navbar-collapse w-100 order-1 order-md-0 flex-column ml-lg-0 ml-3" id="navbarCollapse">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item mr-1">
                        <a class="nav-link" href="/index.php"><i class="fa-solid fa-house fa-xl"></i><br/>Home</a>
                    </li>
                    <?php if (isset($user) && isset($team)): ?>
                    <li class="nav-item mr-1">
                        <a class="nav-link" href="/index.php?site=buero"><i class="fa-solid fa-briefcase fa-xl"></i><br/>Büro</a>
                    </li>
                    <li class="nav-item mr-1">
                        <a class="nav-link" href="/index.php?site=finanzen"><i class="fa-solid fa-wallet fa-xl"></i><br/>Finanzen</a>
                    </li>
                    <li class="nav-item mr-1">
                        <a class="nav-link" href="/index.php?site=team"><i class="fa-solid fa-users fa-xl"></i><br/>Team</a>
                    </li>
                    <li class="nav-item mr-1">
                        <a class="nav-link" href="/index.php?site=league"><i class="fa-solid fa-table fa-xl"></i><br/>Liga</a>
                    </li>
                    <?php if ($leagueController->isLive($team)): ?>
                        <li class="nav-item mr-1">
                            <a class="nav-link" href="/index.php?site=live"><i class="fa-solid fa-football-ball fa-xl"></i><br/>Live</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="mx-auto w-50 order-2 order-md-1">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a id="lblMainInformation" class="nav-link" data-gameday="<?php echo $gameday; ?>" data-season="<?php echo $season; ?>"
                           href="/index.php"><?php echo $team->getName() . '<br/>Regular Season<br/>Saison ' . $season . ($gameday > 0 ? (' - Spieltag ' . $gameday) : ''); ?></a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="collapse navbar-collapse w-100 order-3" id="navbarCollapse">
                <ul class="navbar-nav ml-auto">
                    <?php if (isset($user)): ?>
                        <li class="nav-item mr-1">
                            <a class="nav-link" href="/index.php?site=messages"><i class="fa-solid fa-envelope fa-xl"></i><br/>Nachrichten</a>
                        </li>
                        <li class="nav-item mr-1">
                            <a class="nav-link" href="/index.php?site=forum"><i class="fa-solid fa-comments fa-xl"></i><br/>Forum</a>
                        </li>
                        <li class="nav-item mr-1">
                            <a class="nav-link" href="/index.php?site=profile&id=<?php echo $user->getId(); ?>"><i class="fa-solid fa-user fa-xl"></i><br/>Profil</a>
                        </li>
                        <?php if ($user->isAdmin()): ?>
                            <li class="nav-item mr-1">
                                <a class="nav-link" href="/index.php?site=admin"><i class="fa-solid fa-user-cog fa-xl"></i><br/>Administration</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item mr-1">
                            <a class="nav-link" href="/index.php?site=options"><i class="fa-solid fa-cogs fa-xl"></i><br/>Optionen</a>
                        </li>
                        <li class="nav-item mr-1">
                            <a class="nav-link" href="/index.php?logout"><i class="fa-solid fa-sign-out-alt fa-xl"></i><br/>Logout</a>
                        </li>
                    <?php
                    endif;
                    ?>
                </ul>
            </div>
        </nav>
        <?php
        if (isset($user) && isset($team)) :
            ?>
            <nav class="nav navbar-expand-lg navbar-dark bg-dark">
                <?php if (isset($_GET['site']) && ($_GET['site'] === 'buero' || $_GET['site'] === 'team' || $_GET['site'] === 'finanzen' || $_GET['site'] === 'league')) : ?>
                    <div class="w-100 order-1 order-md-0 flex-column ml-lg-0 ml-3" style="padding-left: 10%">
                        <ul class="navbar-nav mr-auto">
                            <?php if ($_GET['site'] === 'team') : ?>
                                <li class="nav-item">
                                    <a class="nav-link py-1 pr-3"
                                       href="/index.php?site=team&do=roster&table=overview">Roster</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 pr-3"
                                       href="/index.php?site=team&do=roster&table=contract">Verträge</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 pr-3" href="/index.php?site=team&do=lineup">Aufstellung</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 pr-3" href="/index.php?site=team&do=coaching">Coaching</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 pr-3" href="/index.php?site=team&do=train">Training</a>
                                </li>
                            <?php elseif ($_GET['site'] === 'buero') : ?>
                                <li class="nav-item">
                                    <a class="nav-link py-1 pr-3" href="/index.php?site=buero&do=personal">Personal</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 pr-3" href="/index.php?site=buero&do=friendly">Freundschaftsspiel planen</a>
                                </li>
                            <?php elseif ($_GET['site'] === 'league') : ?>
                                <li class="nav-item">
                                    <a class="nav-link py-1 pr-3" href="/index.php?site=league&do=table">Tabelle</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 pr-3" href="/index.php?site=league&do=schedule">Spielplan</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 pr-3" href="/index.php?site=league&do=friendlyResult">Freundschaftsspiel</a>
                                </li>
                            <?php endif; ?>

                        </ul>
                    </div>
                <?php endif; ?>
                <div class="w-100 order-2 order-md-0 flex-column ml-lg-0 ml-3">
                    <ul class="navbar-nav justify-content-end ml-auto">
                        <li class="nav-item">
                            <span id="lblCredits" class="navbar-text py-1 pr-3"
                                  data-credits="<?php echo $team->getCredits(); ?>">TS Coins: <?php echo $team->getCredits(); ?></span>
                        </li>
                        <li class="nav-item">
                            <span id="lblBudget" class="navbar-text py-1 pr-3"
                                  data-budget="<?php echo $budget; ?>">Budget: <?php echo getFormattedCurrency($budget); ?></span>
                        </li>
                        <li class="nav-item">
                            <span id="lblSalaryCap" class="navbar-text py-1 pr-3"
                                  data-salary_cap="<?php echo $salaryCap; ?>">Salary Cap: <?php echo getFormattedCurrency($salaryCap); ?></span>
                        </li>
                    </ul>
                </div>
            </nav>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- loading scripts -->
<script type="text/javascript" src="/resources/js/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="/resources/js/popper.min.js"></script>
<script type="text/javascript" src="/resources/js/all.min.js"></script>
<script type="text/javascript" src="/resources/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="/resources/js/bootstrap-table.min.js"></script>
<script type="text/javascript" src="/resources/js/bootstrap-table-multiple-sort.js"></script>
<script type="text/javascript" src="/resources/js/bootstrap-table-sticky-header.min.js"></script>
<script type="text/javascript" src="/resources/js/bootstrap4-toggle.min.js"></script>
<script type="text/javascript" src="/resources/js/bootstrap-select.min.js"></script>
<script type="text/javascript" src="/resources/js/moment-with-locales.min.js"></script>
<script type="text/javascript" src="/resources/js/tempus-dominus.min.js"></script>
<script type="text/javascript" src="/resources/js/tempus/locales/de.js"></script>
<script type="text/javascript" src="/resources/js/jQuery-provider.min.js"></script>
<script type="text/javascript" src="/resources/js/i18n/defaults-de_DE.min.js"></script>
<script type="text/javascript" src="/resources/js/tooltipster.main.min.js"></script>
<script type="text/javascript" src="/resources/js/tooltipster.bundle.min.js"></script>
<script type="text/javascript" src="/resources/js/ion.rangeSlider.min.js"></script>
<?php if (str_contains($_SERVER['HTTP_HOST'], 'localhost')): ?>
    <script type="text/javascript" src="/resources/js/pixi.js"></script>
<?php else: ?>
    <script type="text/javascript" src="/resources/js/pixi.min.js"></script>
<?php endif; ?>
