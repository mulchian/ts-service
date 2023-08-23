<?php

namespace touchdownstars\communication\chat;

use PDO;
use touchdownstars\user\User;

class ChatController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function hasUnreadMessages(Chat $chat): bool
    {

        return false;
    }

    public function lastSentMessageTime(): string
    {
        return '';
    }

    public function getAllChats(User $user): array
    {
        $sql = "SELECT * FROM `t_chat` WHERE idUser1 = :user OR idUser2 = :user";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user' => $user->getName()
        ]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\chat\\Chat');
        return $stmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\chat\\Chat');
    }


}