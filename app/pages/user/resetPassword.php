<?php

use PHPMailer\PHPMailer\PHPMailer;
use touchdownstars\user\UserController;

$username = '';
$email = '';
$password = '';
$passwordRepeat = '';
$error = '';
$isError = true;
$mailSent = false;
$changePassword = false;

if (isset($pdo, $log)) {
    $userController = new UserController($pdo, $log);
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['sendNewPassword'])) {
            if (isset($_POST['username'])) {
                $username = check_input($_POST['username']);
                if (preg_match('/^[a-zA-Z1-9]*$/', $username)) {
                    $isError = false;
                } else {
                    $isError = true;
                }
            }
            if (isset($_POST['email'])) {
                $email = check_input($_POST['email']);
                if (PHPMailer::validateAddress($email)) {
                    $isError = false;
                } else {
                    $isError = true;
                }
            }

            if (!$isError) {
                $user = $userController->fetchUserByNameOrMail($username, $email);

                if ($user) {
                    $mailSent = $userController->sendNewPasswordMail($user);
                } else {
                    $error = 'Der User und die E-Mail-Adresse sind nicht vorhanden.';
                }
            } else {
                $error = 'Kein User zu dem Nutzernamen oder der E-Mail gefunden.';
            }
        } elseif (isset($_POST['changePassword'])) {
            $isError = checkPasswords($_POST);
            if (!$isError) {
                $userController->changeUserPassword($_SESSION['user'], $_POST['password']);
                unset($_SESSION['user']);
                $_SESSION['changedPassword'] = true;
                redirect('index.php');
            }
        }
    }

    if (isset($_GET['changePassword'], $_GET['valid'])) {
        $userId = $_GET['changePassword'];
        $validUntil = $_GET['valid'];
        $log->debug('changePassword-ID: ' . $userId);
        $user = $userController->fetchUserById($userId);
        if (isset($user) && time() <= $validUntil) {
            $changePassword = true;
            $_SESSION['user'] = $user;
        }
    }
}
if (isset($passwordPattern, $emailPattern)):
    ?>

    <div class="d-flex justify-content-center form_container">
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']); ?>" method="post">
            <?php if (!$mailSent && !$changePassword) : ?>
                <div class="input-group mb-3">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                    </div>
                    <input type="text" placeholder="Username" value="<?php echo $username ?>" name="username" id="username"
                           class="form-control input_user" pattern="^([a-zA-Z1-9]+)$"
                           autocomplete="off" required="required" autofocus>
                </div>
                <div class="input-group mb-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    </div>
                    <input type="text" placeholder="E-Mail" value="<?php echo $email ?>" name="email" id="email"
                           class="form-control input_email" required="required"
                           pattern="<?php echo $emailPattern; ?>" autocomplete="off">
                </div>
                <div class="d-flex justify-content-center mt-3 login_container">
                    <button type="submit" name="sendNewPassword" id="sendNewPassword" class="btn login_btn">neues Passwort anfordern</button>
                </div>
            <?php elseif ($changePassword) : ?>
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
                <div class="d-flex justify-content-center mt-3 login_container">
                    <button type="submit" name="changePassword" id="changePassword" value="changePassword" class="btn login_btn">Passwort ändern</button>
                </div>
            <?php else : ?>
                <div class="card border-danger mb-3" style="max-width: 18rem;">
                    <div class="card-header"><i class="fas fa-exclamation-triangle"></i> Neues Passwort angefordert</div>
                    <div class="card-body">
                        <p class="card-text">Bitte prüfe dein E-Mail-Postfach '<?php echo $email; ?>' für den Link zum Ändern deines Passwortes.
                            Schaue auch im Spam-Ordner nach.</p>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (strlen($error) > 0) : ?>
                <div class="input-group mb-3">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-exclamation-triangle"></i></span>
                    </div>
                    <label class="form-control text-danger" readonly><?php echo $error; ?></label>
                </div>
            <?php endif; ?>
        </form>
    </div>
<?php endif; ?>