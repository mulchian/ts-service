<?php

namespace touchdownstars\user;

use PDO;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Monolog\Logger;
use touchdownstars\team\Team;

class UserController
{
    private PDO $pdo;
    private Logger $log;

    public function __construct(PDO $pdo, Logger $log)
    {
        $this->pdo = $pdo;
        $this->log = $log;
    }

    public function fetchUserById(int $userId): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `t_user` WHERE id = :id;');
        $stmt->execute(['id' => $userId]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\user\\User');
        $user = $stmt->fetch(PDO::FETCH_CLASS);
        if ($user) {
            $user->setProfilePicture($this->getProfilePic($user->getId()));
            return $user;
        }
        return null;
    }

    public function fetchUserByTeam(Team $team): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `t_user` tu LEFT JOIN `t_team` tt ON tt.idUser = tu.id WHERE tt.name = :teamname;');
        $stmt->execute(['teamname' => $team->getName()]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\user\\User');
        $user = $stmt->fetch(PDO::FETCH_CLASS);
        if ($user) {
            $user->setProfilePicture($this->getProfilePic($user->getId()));
            return $user;
        }
        return null;
    }

    public function fetchUserByNameOrMail(string $username, string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `t_user` WHERE username = :username OR email = :email LIMIT 1;');
        $stmt->execute(['username' => $username, 'email' => $email]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\user\\User');
        $user = $stmt->fetch(PDO::FETCH_CLASS);
        if ($user) {
            $user->setProfilePicture($this->getProfilePic($user->getId()));
            return $user;
        }
        return null;
    }

    public function fetchUser(string $username, string $password): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `t_user` WHERE LOWER(username) = LOWER(:username) LIMIT 1;');
        $stmt->execute(['username' => $username]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\user\\User');
        $user = $stmt->fetch(PDO::FETCH_CLASS);

        $this->log->debug('User: ' . print_r($user, true));

        if ($user) {
            $passwordHash = $user->getPassword();

            if (password_needs_rehash($passwordHash, PASSWORD_BCRYPT, ['cost' => 8])) {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 8]);
                $this->changeUserPassword($user, $passwordHash);
            }

            $passwordMatch = password_verify($password, $passwordHash);
        } else {
            $passwordMatch = null;
        }

        if ($passwordMatch) {
            $user->setProfilePicture($this->getProfilePic($user->getId()));
            return $user;
        } else {
            return null;
        }
    }

    public function fetchAllUsers(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `t_user`;');
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\user\\User');
        return $stmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\user\\User');
    }

    public function registerNewUser(string $username, string $email, string $password, string $gender = null): ?User
    {
        //Entsprechende Überprüfungen und SQL Queries zum Registrieren des Nutzers.
        //Gibt z.B. true zurück, falls die Registrierung funktioniert hat.

        $selectUser = 'SELECT * FROM `t_user` WHERE LOWER(username) = LOWER(:username) OR email = :email LIMIT 1;';
        $selectStmt = $this->pdo->prepare($selectUser);
        $selectStmt->execute(['username' => $username, 'email' => $email]);
        $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\user\\User');
        $user = $selectStmt->fetch(PDO::FETCH_CLASS);

        if (!$user) {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 8]);
            $insertNewUser = 'INSERT INTO `t_user` (username, email, password, gender, registerDate, lastActiveTime) VALUES (:username, :email, :passwordHash, :gender, NOW(), :lastActiveTime);';
            $stmt = $this->pdo->prepare($insertNewUser);
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'passwordHash' => $passwordHash,
                'gender' => $gender,
                'lastActiveTime' => time()
            ]);

            $user = $this->fetchUser($username, $password);

            $this->sendActivationMail($user);
        } else {
            return null;
        }

        return $user;
    }

    public function saveUser(User $user): string
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_user` where username = :username;');
        $selectStmt->execute(['username' => $user->getUsername()]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveUser = 'INSERT INTO `t_user` (id, username, email, password, realname, city, gender, birthday, registerDate, lastActiveTime, status, isDeactivated, activationIsSent, isActivated) 
                            VALUES (:id, :username, :email, :password, :realname, :city, :gender, :birthday, NOW(), :lastActiveTime, :status, :isDeactivated, :activationIsSent, :isActivated) 
                            ON DUPLICATE KEY UPDATE email = :newEmail, password = :newPassword, realname = :newRealname, city = :newCity, gender = :newGender, birthday = :newBirthday, 
                                                    lastActiveTime = :newLastActiveTime, status = :newStatus, isDeactivated = :newIsDeactivated, activationIsSent = :newActivationIsSent, isActivated = :newIsActivated;';
        $saveStmt = $this->pdo->prepare($saveUser);
        $saveStmt->execute([
            'id' => $id ?? null,
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'realname' => $user->getRealname(),
            'city' => $user->getCity(),
            'gender' => $user->getGender(),
            'birthday' => $user->getBirthday(),
            'lastActiveTime' => time(),
            'status' => $user->getStatus(),
            'isDeactivated' => (int)$user->isDeactivated(),
            'activationIsSent' => (int)$user->isActivationSent(),
            'isActivated' => (int)$user->isActivated(),
            'newEmail' => $user->getEmail(),
            'newPassword' => $user->getPassword(),
            'newRealname' => $user->getRealname(),
            'newCity' => $user->getCity(),
            'newGender' => $user->getGender(),
            'newBirthday' => $user->getBirthday(),
            'newLastActiveTime' => $user->getLastActiveTime(),
            'newStatus' => $user->getStatus(),
            'newIsDeactivated' => (int)$user->isDeactivated(),
            'newActivationIsSent' => (int)$user->isActivationSent(),
            'newIsActivated' => (int)$user->isActivated()
        ]);

        return $this->pdo->lastInsertId();
    }

    public function changeUserPassword(User $user, string $new_password): string
    {
        $passwordHash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 8]);
        //Ändert den Passwort-Hash für den Nutzer $user in der Datenbank
        $updateStmt = $this->pdo->prepare('UPDATE `t_user` SET password = :passwordHash where id = :idUser');
        $updateStmt->execute(['passwordHash' => $passwordHash, 'idUser' => $user->getId()]);
        return $this->pdo->lastInsertId();
    }

    public function sendNewPasswordMail(User $user, string $activationLink = null): bool
    {
        //Sendet dem Benutzer eine Mail zur Passwortrücksetzung zu
        if (!$activationLink) {
            $activationLink = 'https://' . $_SERVER['HTTP_HOST'] . '/index.php?changePassword=' . $user->getId() . '&valid=' . (time() + 86400);
        } else {
            $activationLink .= '?user=' . $user->getId() . '&valid=' . (time() + 86400);
        }

        $content = "Hallo " . $user->getUsername() . ",<br>";
        $content .= "du möchtest dein Passwort ändern. Klicke auf folgenden Link, um dein Passwort zu ändern.<br>";
        $content .= "Der Link ist 24 Stunden gültig.<br>";
        $content .= "<a href=\"" . $activationLink . "\">Passwort ändern</a>";

        $altContent = "Hallo " . $user->getUsername() . ",\r\n";
        $altContent .= "du hast dein Passwort geändert. Kopiere den folgenden Link und öffne ihn, um dein Passwort zu ändern.\r\n";
        $altContent .= "Der Link ist 24 Stunden gültig.\r\n";
        $altContent .= $activationLink;

        $subject = 'Passwort-Änderung - Touchdown Stars';

        if ($this->sendMail($user, $subject, $content, $altContent)) {
            return true;
        }
        return false;
    }

    //FIXME: Is this needed?
    // public function updateProfile(User $user)
    // {
    //     //Aktualisiert den Benutzer mit Werten, wie Realname, Wohnort, Geschlecht
    // }

    public function uploadProfilePic(User $user, string $pictureLocation, int $height, int $width): string
    {
        $selectStmt = $this->pdo->prepare('SELECT id from `t_picture` where idUser = :user;');
        $selectStmt->execute(['user' => $user->getId()]);
        $id = $selectStmt->fetch(PDO::FETCH_ASSOC)['id'];

        $saveUser = 'INSERT INTO `t_picture` (id, idUser, pictureLocation, height, width) 
                            VALUES (:id, :idUser, :pictureLocation, :height, :width) 
                            ON DUPLICATE KEY UPDATE pictureLocation = :newPictureLocation, height = :newHeight, width = :newWidth;';
        $saveStmt = $this->pdo->prepare($saveUser);
        $saveStmt->execute([
            'id' => $id ?? null,
            'idUser' => $user->getId(),
            'pictureLocation' => $pictureLocation,
            'height' => $height,
            'width' => $width,
            'newPictureLocation' => $pictureLocation,
            'newHeight' => $height,
            'newWidth' => $width
        ]);

        return $this->pdo->lastInsertId();
    }

    private function getProfilePic(int $userId): array
    {
        $selectStmt = $this->pdo->prepare('SELECT pictureLocation, height, width from `t_picture` where idUser = :user;');
        $selectStmt->execute(['user' => $userId]);
        $picture = $selectStmt->fetch(PDO::FETCH_ASSOC);
        if ($picture) {
            return $picture;
        }
        return array();
    }

    public function sendActivationMail(User $user): void
    {
        $activationLink = 'https://' . $_SERVER['HTTP_HOST'] . '/index.php?activate=' . $user->getId();
        $content = "Hallo " . $user->getUsername() . ",<br>";
        $content .= "Klicke auf folgenden Link, um deinen Touchdown Stars Account zu aktivieren.<br>";
        $content .= "<a href=\"" . $activationLink . "\">Aktivierungs-Link</a>";

        $altContent = "Hallo " . $user->getUsername() . ",\r\n";
        $altContent .= "Kopiere den folgenden Link und öffne ihn, um deinen Account zu aktivieren.\r\n";
        $altContent .= $activationLink;

        if ($this->sendMail($user, 'Registrierung - Touchdown Stars', $content, $altContent)) {
            $user->setActivationSent(true);
            $this->saveUser($user);
        }
    }

    private function sendMail(User $user, string $subject, string $content, string $altContent): bool
    {
        try {
            $mail = new PHPMailer();
            $mail->setFrom('support@touchdown-stars.com', 'Mulchian');
            $mail->addAddress($user->getEmail(), $user->getUsername());
            $mail->addReplyTo('support@touchdown-stars.com', 'Support');
            $mail->isHTML();
            $mail->Subject = $subject;
            $mail->Body = $content;
            $mail->AltBody = $altContent;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            if ($mail->send()) {
                $this->log->debug('Mail ' . $subject . ' gesendet an: ' . $user->getEmail());
                return true;
            }
        } catch (Exception $e) {
            $this->log->debug($e->errorMessage());
            error_log($e->errorMessage()); //Pretty error messages from PHPMailer
        } catch (\Exception $e) { //The leading slash means the Global PHP Exception class will be caught
            $this->log->debug($e->getMessage());
            error_log($e->getMessage()); //Boring error messages from anything else!
        }
        return false;
    }

}