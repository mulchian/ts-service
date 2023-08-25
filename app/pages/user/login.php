<?php

use touchdownstars\player\PlayerController;
use touchdownstars\team\TeamController;
use touchdownstars\user\UserController;

$username = '';
$password = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['username'], $_POST['password'], $pdo, $log)) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $userController = new UserController($pdo, $log);
        $user = $userController->fetchUser($username, $password);

        if ($user) {
            $_SESSION['created'] = time();

            $teamController = new TeamController($pdo, $log);
            $team = $teamController->fetchTeam($user->getId());
            if ($team) {
                $log->debug('Last-Active-Date: ' . date('Ymd', $user->getLastActiveTime()));
                $log->debug('Yesterday-Date: ' . date('Ymd', strtotime('yesterday')));
                if (date('Ymd', $user->getLastActiveTime()) <= date('Ymd', strtotime('yesterday'))) {
                    $log->debug('reset Trainings');
                    $playerController = new PlayerController($pdo, $log);
                    foreach ($team->getPlayers() as $player) {
                        $log->debug('Player ' . $player->getId() . ' Trainings: ' . $player->getNumberOfTrainings());
                        $player->setNumberOfTrainings(0);
                        $playerController->updateNumberOfTrainings($player);
                    }
                }
            }

            $user->setLastActiveTime(time());
            $user->setStatus('online');
            $log->debug('User: ' . print_r($user, true));
            $userController->saveUser($user);
            $_SESSION['user'] = $user;

            if ($team) {
                $team->setUser($user);
                $_SESSION['team'] = $team;
            }

            redirect('index.php');
        } else {
            $error = 'Der Username oder das Passwort sind falsch.';
        }
    }
}

?>

<div class="d-flex justify-content-center form_container">
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']); ?>" method="post">
        <?php if (isset($_SESSION['changedPassword']) && $_SESSION['changedPassword'] == true) :
            unset($_SESSION['changedPassword']);
            ?>
            <div class="card border-secondary mb-3" style="max-width: 18rem;">
                <div class="card-body">
                    <p class="card-text">Das Passwort wurde erfolgreich geändert. Du kannst dich jetzt anmelden.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="input-group mb-3">
            <div class="input-group-append">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
            </div>
            <input type="text" placeholder="Username" value="<?php echo $username ?>" name="username" id="username"
                   class="form-control input_user" pattern="^([a-zA-Z1-9]+)$"
                   autocomplete="off" required="required" autofocus>
        </div>
        <div class="input-group mb-3">
            <div class="input-group-append">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
            </div>
            <input type="password" placeholder="********" value="<?php echo $password ?>" name="password" id="password"
                   class="form-control input_pass" required="required"
                <?php echo isset($passwordPattern) ? 'pattern="' . $passwordPattern . '"' : ''; ?> autocomplete="off">
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
            <button type="submit" name="login" id="login" class="btn login_btn">Login</button>
        </div>
    </form>
</div>
<div class="mt-4">
    <div class="d-flex justify-content-center links">
        Noch keinen Account? <a href="index.php?lgn=1" class="ml-2">Registriere dich!</a>
    </div>
    <div class="d-flex justify-content-center">
        <a href="index.php?lgn=2">Passwort vergessen?</a>
    </div>
</div>
