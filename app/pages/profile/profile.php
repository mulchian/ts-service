<?php

use touchdownstars\team\TeamController;
use touchdownstars\user\UserController;

if (isset($_GET['id'])) {
    $userId = $_GET['id'];
}

if (isset($pdo, $log)) :
    $userController = new UserController($pdo, $log);
    $teamController = new TeamController($pdo, $log);

    if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
        $self = $_SESSION['user'];
    }

    // Codedurchführung, falls das Form-Submit für das Profile-Pic angesteuert wird.
    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($self)) {
        if (isset($_FILES['uploadProfilePic']) && !$_FILES['uploadProfilePic']['error']) {
            $file = $_FILES['uploadProfilePic'];
            $log->debug('File: ' . print_r($file, true));
            // file name, type, size, temporary name
            $fileName = $file['name'];
            $fileType = $file['type'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];

            $imageInfo = getimagesize($fileTmpName);
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            if ($file['size'] < 1000000 && $height <= 200 && $width <= 200) {

                $targetDir = '/resources/profilePics/';
                $pictureLocation = $_SERVER['DOCUMENT_ROOT'] . $targetDir . $fileName;

                if (move_uploaded_file($fileTmpName, $pictureLocation)) {
                    $userController->uploadProfilePic($self, $targetDir . $fileName, $height, $width);
                } else {
                    $log->debug('File konnte nicht verschoben werden. TmpName: ' . $fileTmpName . ' pictureLocation: ' . $pictureLocation);
                }
            } else {
                $log->debug('File ' . $fileName . ' ist zu groß. Size: ' . $file['size'] . ' | Height: ' . $height . ' | Width: ' . $width);
            }
        } else {
            $log->debug('File konnte nicht hochgeladen werden. Error: ' . $_FILES['uploadProfilePic']['error']);
            header('Location: /index.php?site=profile&id=' . $self->getId());
        }
    }

    if (isset($userId)) :
        $user = null;
        $team = null;

        if (!empty($_SESSION['user'])) {
            $self = $_SESSION['user'];
            if ($self->getId() == $userId) {
                $user = $self;
                // eigenes Profil, also kann Team aus Session genommen werden
                if (!empty($_SESSION['team'])) {
                    $team = $_SESSION['team'];
                } else {
                    $team = $teamController->fetchTeam($self->getId());
                }
            }
        }

        if (!isset($user) || !isset($team)) {
            // fremdes Profil -> User und Team lesen
            $user = $userController->fetchUserById($userId);
            $team = $teamController->fetchTeam($userId);
        }

        $picture = $user->getProfilePicture();
        ?>

        <div class="panel panel-default opacity">
            <div class="row justify-content-center">
                <div class="col-4">
                    <div class="card bg-dark text-white">
                        <div class="card-header">
                            <?php echo $user->getUsername(); ?>
                        </div>
                        <div class="card-body">
                            <div class="form-group row">
                                <label for="teamname" class="col-sm-4 col-form-label">Team</label>
                                <div class="col-sm-6">
                                    <input type="text" readonly class="form-control-plaintext text-white" id="teamname"
                                           value="<?php echo $team->getName(); ?>">
                                </div>
                                <div class="col-sm-2">
                                    <button class="btn btn-secondary"><i class="fas fa-edit"></i></button>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="realname" class="col-sm-4 col-form-label">Realname</label>
                                <div class="col-sm-6">
                                    <input type="text" readonly class="form-control-plaintext text-white" id="realname"
                                           value="<?php echo $user->getRealname(); ?>">
                                </div>
                                <div class="col-sm-2">
                                    <button class="btn btn-secondary"><i class="fas fa-edit"></i></button>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="gender" class="col-sm-4 col-form-label">Geschlecht</label>
                                <div class="col-sm-6">
                                    <input type="text" readonly class="form-control-plaintext text-white" id="gender"
                                           value="<?php echo $user->getGender(); ?>">
                                </div>
                                <div class="col-sm-2">
                                    <button class="btn btn-secondary"><i class="fas fa-edit"></i></button>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="livingPlace" class="col-sm-4 col-form-label">Wohnort</label>
                                <div class="col-sm-6">
                                    <input type="text" readonly class="form-control-plaintext text-white" id="livingPlace"
                                           value="<?php echo $user->getCity(); ?>">
                                </div>
                                <div class="col-sm-2">
                                    <button class="btn btn-secondary"><i class="fas fa-edit"></i></button>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="birthday" class="col-sm-4 col-form-label">Geburtsdatum</label>
                                <div class="col-sm-6">
                                    <input type="text" readonly class="form-control-plaintext text-white" id="birthday"
                                           value="<?php echo date('d.m.Y', strtotime($user->getBirthday())); ?>">
                                </div>
                                <div class="col-sm-2">
                                    <button class="btn btn-secondary"><i class="fas fa-edit"></i></button>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="registerDate" class="col-sm-4 col-form-label">Registriert seit</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control-plaintext text-white" id="registerDate"
                                           value="<?php echo date('d.m.Y', strtotime($user->getRegisterDate())); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-4">
                    <div class="row mb-2 justify-content-center">
                        <div class="card">
                            <div class="card-body bg-dark text-white">
                                <?php if (strlen($picture['pictureLocation']) > 0): ?>
                                    <img src="<?php echo $picture['pictureLocation']; ?>"
                                         alt="<?php echo 'Profilbild von ' . $user->getUsername(); ?>"
                                         height="<?php echo $picture['height']; ?>" width="<?php echo $picture['width']; ?>">
                                <?php else: ?>
                                    <i class="fas fa-user fa-10x"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-dark">
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']); ?>" method="post"
                                      enctype="multipart/form-data">
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input bg-secondary text-white" id="uploadProfilePic"
                                                   name="uploadProfilePic" aria-describedby="uploadProfilePic">
                                            <label class="custom-file-label ignore-file-label text-left bg-secondary text-white"
                                                   for="uploadProfilePic" id="lblProfilePic">Auswählen</label>
                                        </div>
                                        <div class="input-group-append">
                                            <button class="btn btn-secondary" type="submit" id="inputGroupFileAddon04">Ändern</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php if (isset($self, $user) && $self->getId() != $user->getId()): ?>
                        <div class="row mb-2">
                            <button type="button" class="btn btn-secondary btn-lg btn-block" id="addAsFriend" onclick="addAsFriend()">als Freund
                                hinzufügen
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg btn-block" id="inviteForFriendly" onclick="goToFriendly()">zum
                                Freundschaftsspiel einladen
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-4">
                    <div class="card bg-dark text-white">
                        <div class="card-header">
                            <?php echo $team->getName(); ?>
                        </div>
                        <div class="card-body">

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="/scripts/profile/profile.js"></script>
    <?php
    endif; endif;
?>
