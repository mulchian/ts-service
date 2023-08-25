<?php


namespace touchdownstars\player\character;


use PDO;
use touchdownstars\player\Player;

class CharacterController
{
    private PDO $pdo;

    /**
     * CharacterController constructor.
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function fetchCharacter(int $idPlayer): Character
    {
        $stmt = $this->pdo->prepare('SELECT tc.* FROM `t_character` tc INNER JOIN `t_player` tp ON tc.id = tp.idCharacter WHERE tp.id = :idPlayer ;');
        $stmt->execute(['idPlayer' => $idPlayer]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\player\\character\\Character');
        return $stmt->fetch(PDO::FETCH_CLASS);
    }

    private function getAllCharacters(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `t_character`;');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\player\\character\\Character');
    }

    public function getRandomCharacter(bool $isFreePlayer, Player $player): Character
    {
        $characters = $this->getAllCharacters();

        foreach ($characters as $key => $character) {
            if ($character->getDescription() === 'Kultstatus') {
                unset($characters[$key]);
                break;
            }
        }

        if ($isFreePlayer || (null !== $player->getDraftposition() && $player->getDraftposition()->getRound() !== 1)) {
            foreach ($characters as $key => $character) {
                if ($character->getDescription() === '1st Rounder') {
                    unset($characters[$key]);
                    break;
                }
            }
        }

        return $characters[array_rand($characters)];
    }
}