<?php


namespace touchdownstars\penalty;


use PDO;
use Monolog\Logger;

class PenaltyController
{
    private PDO $pdo;
    private Logger $log;

    public function __construct(PDO $pdo, Logger $log = null)
    {
        $this->pdo = $pdo;
        if (isset($log)) {
            $this->log = $log;
        }
    }

    /**
     * @param string $gameplay - Run oder Pass
     * @param string $teamPart - Offense ode Defense
     * @param string $name - Name des Penalties (z.B. Offense Holding)
     * @return Penalty|null - Penalty oder false, falls kein Ergebnis
     */
    public function fetchPenalty(string $gameplay, string $teamPart, string $name): ?Penalty
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `t_penalty` WHERE gameplay = :gameplay AND teamPart = :teamPart AND penalty = :penalty;');
        $stmt->execute(['gameplay' => $gameplay, 'teamPart' => $teamPart, 'penalty' => $name]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\penalty\\Penalty');
        return $stmt->fetch(PDO::FETCH_CLASS);
    }

    public function fetchAllPenalties(string $gameplay = null, string $teamPart = null): array
    {
        $selectStmt = 'SELECT * FROM `t_penalty`;';
        if (isset($gameplay, $teamPart)) {
            $selectStmt = 'SELECT * FROM `t_penalty` WHERE gameplay = :gameplay AND teamPart = :teamPart;';
        } elseif (isset($teamPart)) {
            $selectStmt = 'SELECT * FROM `t_penalty` WHERE teamPart = :teamPart;';
        } elseif (isset($gameplay)) {
            $selectStmt = 'SELECT * FROM `t_penalty` WHERE gameplay = :gameplay;';
        }

        $stmt = $this->pdo->prepare($selectStmt);
        if (isset($gameplay, $teamPart)) {
            $stmt->execute(['gameplay' => $gameplay, 'teamPart' => $teamPart]);
        } elseif (isset($teamPart)) {
            $stmt->execute(['teamPart' => $teamPart]);
        } elseif (isset($gameplay)) {
            $stmt->execute(['gameplay' => $gameplay]);
        } else {
            $stmt->execute();
        }
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\penalty\\Penalty');
        return $stmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\penalty\\Penalty');
    }
}