<?php

use PHPMailer\PHPMailer\PHPMailer;
use touchdownstars\user\UserController;

$username = '';
$password = '';
$passwordRepeat = '';
$email = '';
$emailRepeat = '';
$gender = 'Männlich';
$isError = true;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($pdo, $log)) {
    if (isset($_POST['username'])) {
        $username = check_input($_POST['username']);
        if (preg_match('/^[a-zA-Z1-9]*$/', $username)) {
            $isError = false;
        } else {
            $isError = true;
            $error = 'Der Username ist fehlerhaft.';
        }
    }
    $isError = checkPasswords($_POST);
    if (isset($_POST['email'])) {
        $email = check_input($_POST['email']);
        if (PHPMailer::validateAddress($email)) {
            $isError = false;
        } else {
            $isError = true;
            $error = 'Die E-Mail-Adresse ist fehlerhaft.';
        }
    }
    if (isset($_POST['emailRepeat'])) {
        $emailRepeat = check_input($_POST['emailRepeat']);
        if (isset($email) && PHPMailer::validateAddress($emailRepeat) && $emailRepeat === $email) {
            $isError = false;
        } else {
            $isError = true;
            $error = 'Die E-Mail-Adressen sind nicht identisch.';
        }
    }

    if (isset($_POST['genderSelect'])) {
        $gender = check_input($_POST['genderSelect']);
        if (isset($gender) && !empty($gender)) {
            $isError = false;
        } else {
            $isError = true;
        }
    }

    // Beta-Code für die Closed-Beta
    if (isset($_POST['betaCode'])) {
        $betaCode = check_input($_POST['betaCode']);
        if (isset($betaCode) && !empty($betaCode) && $betaCode === 'Cl0s3dB3t4') {
            $isError = false;
        } else {
            $isError = true;
            $error = 'Der Beta-Code ist fehlerhaft.';
        }
    }

    if (!$isError) {
        $userController = new UserController($pdo, $log);
        $user = $userController->registerNewUser($username, $email, $password, $gender);

        if ($user) {
            $_SESSION['created'] = time();
            $_SESSION['user'] = $user;
            redirect('index.php');
        } else {
            $error = 'Der User oder die E-Mail-Adresse sind schon registriert.';
        }
    } else {
        if (strlen($error) <= 0) {
            $error = 'Die Eingaben sind fehlerhaft.';
        }
    }
}
if (isset($passwordPattern, $emailPattern)):
    ?>

    <div class="d-flex justify-content-center form_container">
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']); ?>" method="post">
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                </div>
                <input type="text" placeholder="Username" value="<?php echo $username ?>" name="username" id="username"
                       class="form-control input_user tooltip-custom" pattern="^([a-zA-Z1-9]+)$" required="required"
                       data-tooltip-content="#username_tooltip" autocomplete="off">
                <div class="tooltip-content">
                    <span id="username_tooltip">Der Username darf nur aus <u>Buchstaben</u> und <u>Zahlen</u> bestehen.</span>
                </div>
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                </div>
                <input type="password" placeholder="Passwort" value="<?php echo $password ?>" name="password"
                       id="password" class="form-control input_pass tooltip-password"
                       pattern="<?php echo $passwordPattern; ?>" required="required"
                       autocomplete="off">
                <span class="input-group-text icon_in_input" id="showPassword"><i class="fas fa-eye"></i></span>
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                </div>
                <input type="password" placeholder="Passwort wiederholen" value="<?php echo $passwordRepeat ?>"
                       name="passwordRepeat" id="passwordRepeat"
                       class="form-control input_pass tooltip-password" required="required" pattern="<?php echo $passwordPattern; ?>"
                       autocomplete="off">
                <span class="input-group-text icon_in_input" id="showPasswordRepeat"><i class="fas fa-eye"></i></span>
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                </div>
                <input type="text" placeholder="E-Mail" value="<?php echo $email ?>" name="email" id="email"
                       class="form-control input_email" required="required"
                       pattern="<?php echo $emailPattern; ?>" autocomplete="off">
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                </div>
                <input type="text" placeholder="E-Mail wiederholen" value="<?php echo $emailRepeat ?>" name="emailRepeat"
                       id="emailRepeat" class="form-control input_email" required="required"
                       pattern="<?php echo $emailPattern; ?>" autocomplete="off">
            </div>
            <div class="input-group mb-2">
                <div class="input-group-append">
                    <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                </div>
                <select class="custom-select" id="genderSelect" name="genderSelect">
                    <option <?php if (isset($gender) && $gender === 'Männlich') echo 'selected'; ?> value="Männlich">
                        Männlich
                    </option>
                    <option <?php if (isset($gender) && $gender === 'Weiblich') echo 'selected'; ?> value="Weiblich">
                        Weiblich
                    </option>
                    <option <?php if (isset($gender) && $gender === 'Divers') echo 'selected'; ?> value="Divers">Divers
                    </option>
                </select>
            </div>
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-file-code"></i></span>
                </div>
                <input type="text" placeholder="Beta-Code" name="betaCode" id="betaCode"
                       class="form-control input_user" required="required" autocomplete="off">
            </div>
            <div class="d-flex justify-content-center mt-3 login_container">
                <button type="submit" name="register" id="register" class="btn login_btn">Registrieren</button>
            </div>
        </form>
    </div>
    <?php if (strlen($error) > 0) : ?>
    <div class="mt-2">
        <div class="input-group mb-2">
            <div class="input-group-append">
                <span class="input-group-text"><i class="fas fa-exclamation-triangle"></i></span>
            </div>
            <input class="form-control" type="text" placeholder="<?php echo $error; ?>" readonly>
        </div>
        <div class="d-flex justify-content-center links">
            <a href="/index.php?lgn=0" class="ml-2">Log dich ein!</a>
        </div>
    </div>
<?php endif; ?>
    <div class="mt-2">
        <div class="d-flex justify-content-end">
            <a href="/index.php?lgn=0" class="btn small_back_Btn">Zurück</a>
        </div>
    </div>

    <script src="../scripts/util/tooltipCustom.js"></script>
    <script src="../scripts/user/registration.js"></script>
    <script type="text/javascript">
        $(function () {
            $('#username').focus();
        });
    </script>
<?php
endif;
?>