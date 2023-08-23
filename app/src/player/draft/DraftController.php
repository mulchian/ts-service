<?php


namespace touchdownstars\player\draft;


use PDO;

class DraftController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function saveDraftposition(Draftposition $draftposition): int
    {
        $id = $draftposition->getId();

        $saveStmt = $this->pdo->prepare('INSERT INTO `t_draftposition` (id, idLeague, season) VALUES (:id, :idLeague, :season) 
                            ON DUPLICATE KEY UPDATE round = :round, pick = :pick, isDrafted = :isDrafted;');
        $saveStmt->execute([
            'id' => $id ?? null,
            'idLeague' => $draftposition->getLeague()->getId(),
            'season' => $draftposition->getSeason(),
            'round' => $draftposition->getRound(),
            'pick' => $draftposition->getPick(),
            'isDrafted' => $draftposition->isDrafted()
        ]);

        return $this->pdo->lastInsertId();
    }


}