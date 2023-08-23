<?php

use touchdownstars\team\TeamController;
use touchdownstars\user\UserController;

if (isset($pdo, $log, $_SESSION['user'])) :
    $teamController = new TeamController($pdo, $log);

    $user = $_SESSION['user'];

    $teamName = '';
    $abbreviation = '';
    $country = 'Deutschland';
    $conference = $teamController->getRecommendedConference($country);
    $isError = true;
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['sendNewActivation'])) {
            $userController = new UserController($pdo, $log);
            $userController->sendActivationMail($user);
        } else {
            if (isset($_POST['teamName'])) {
                $teamName = check_input($_POST['teamName']);
                if (preg_match('/^[a-zA-Z1-9 ]*$/', $teamName)) {
                    $isError = false;
                } else {
                    $isError = true;
                }
            }

            if (isset($_POST['abbreviation'])) {
                $abbreviation = check_input($_POST['abbreviation']);
                if (preg_match('/^[a-zA-Z0-9]{1,3}$/', $abbreviation)) {
                    $isError = false;
                } else {
                    $isError = true;
                }
            }

            if (isset($_POST['conferenceSelect'])) {
                $conference = check_input($_POST['conferenceSelect']);
                if (isset($conference) && !empty($conference)) {
                    $isError = false;
                } else {
                    $isError = true;
                }
            }

            if (!$isError) {
                $team = $teamController->registerNewTeam($user, $teamName, $abbreviation, $conference);

                if ($team) {
                    $_SESSION['team'] = $team;
                    redirect('index.php');
                } else {
                    $error = 'Beim Anlegen des Teams ist ein Fehler aufgetreten.';
                }
            }
        }
    }

    ?>
    <div class="container h-100">
        <div class="d-flex justify-content-center h-100">
            <div class="user_card">
                <div class="d-flex justify-content-center">
                    <div class="brand_logo_container">
                        <img src="../resources/logo_transparent.png" class="brand_logo" alt="Pick Six">
                    </div>
                </div>

                <div class="d-flex justify-content-center form_container">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']); ?>"
                          method="post">
                        <?php if ($user->getIsActivated()) : ?>
                            <div class="input-group mb-3">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-users"></i></span>
                                </div>
                                <input type="text" placeholder="Teamname" value="<?php echo $teamName ?>" name="teamName"
                                       id="teamName"
                                       class="form-control input_team tooltip-custom"
                                       pattern="^[a-zA-Z0-9 ]*$"
                                       required="required"
                                       data-tooltip-content="#teamname_tooltip"
                                       autocomplete="off">
                                <div class="tooltip-content">
                                    <span id="teamname_tooltip">Der Teamname darf nur aus <u>Buchstaben</u>, <u>Zahlen</u> und <u>Leerzeichen</u> bestehen.</span>
                                </div>
                            </div>
                            <div class="input-group mb-3">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-filter"></i></span>
                                </div>
                                <input type="text" placeholder="Teamkürzel" value="<?php echo $abbreviation ?>"
                                       name="abbreviation"
                                       id="abbreviation"
                                       class="form-control input_team tooltip-custom"
                                       pattern="^[a-zA-Z0-9]{1,3}$"
                                       required="required"
                                       data-tooltip-content="#abbreviation_tooltip"
                                       autocomplete="off">
                                <div class="tooltip-content">
                                    <span id="abbreviation_tooltip">Das Teamkürzel darf aus <u>maximal 3 Zeichen</u> (Buchstaben/Zahlen) bestehen.</span>
                                </div>
                            </div>
                            <div class="input-group mb-3">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-compass"></i></span>
                                </div>
                                <select class="custom-select tooltip-custom" id="conferenceSelect" name="conferenceSelect"
                                        data-tooltip-content="#conference_tooltip">
                                    <option <?php if (isset($conference) && $conference === 'Conference North') echo 'selected'; ?>
                                            value="Conference North">Conference North
                                    </option>
                                    <option <?php if (isset($conference) && $conference === 'Conference South') echo 'selected'; ?>
                                            value="Conference South">Conference South
                                    </option>
                                </select>
                                <div class="tooltip-content">
                                    <span id="conference_tooltip">Wähle deine bevorzugte Conference. Die zu Beginn angezeigte Conference ist die leichtere.</span>
                                </div>
                            </div>
                            <?php if (strlen($error) > 0) : ?>
                                <div class="input-group mb-3">
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fas fa-exclamation-triangle"></i></span>
                                    </div>
                                    <label class="form-control text-danger" readonly><?php echo $error; ?></label>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-center mt-3 login_container">
                                <button type="submit" name="create" id="create" class="btn login_btn">Team Erstellen</button>
                            </div>

                        <?php else: ?>
                            <div class="card border-danger mb-3" style="max-width: 18rem;">
                                <div class="card-header"><i class="fas fa-exclamation-triangle"></i> Fehlende Aktivierung</div>
                                <div class="card-body text-danger">
                                    <p class="card-text">Aktiviere zuerst deinen Account. Bitte prüfe dein E-Mail-Postfach '<?php echo $user->getEmail(); ?>' für den Aktivierungslink.
                                        Schaue auch im Spam-Ordner nach.</p>
                                    <p class="card-text">Hast du trotzdem keinen Link erhalten?</p>
                                    <button type="submit" name="sendNewActivation" id="sendNewActivation" class="btn login_btn" value="sendNewActivation">Mail
                                        erneut senden
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../scripts/util/tooltipCustom.js"></script>
    <script src="../scripts/util/validation.js"></script>
    <script type="text/javascript">
        $(function () {
            $('#teamName').focus();
        });
    </script>

<?php
endif;
?>