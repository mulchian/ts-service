<?php


namespace touchdownstars\gametext {


    use PDO;
    use Monolog\Logger;
    use touchdownstars\player\Player;
    use touchdownstars\team\Team;

    class GametextController
    {
        private Logger $log;
        private PDO $pdo;

        public function __construct(PDO $pdo, Logger $log = null)
        {
            $this->pdo = $pdo;
            if (isset($log)) {
                $this->log = $log;
            }
        }

        public function fetchAllSituationalTexts(string $situation, string $language = 'de'): array
        {
            $selectStmt = 'SELECT * FROM `t_gametexts` WHERE language = :language AND situation = :situation;';

            $stmt = $this->pdo->prepare($selectStmt);
            $stmt->execute(['language' => $language, 'situation' => $situation]);
            $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\gametext\\Gametext');
            return $stmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\gametext\\Gametext');
        }

        /**
         * Gibt alle Texte zum Gameplay aus (Run- oder Pass-Texte)
         * @param string $gameplay - Gameplay -> Run, Pass oder Special
         * @param string $language - Sprache für den Text | Standard 'de'
         * @return array - Array von Gametext mit den Text-Objekten
         */
        public function fetchAllGameplayTexts(string $gameplay, string $language = 'de'): array
        {
            $selectStmt = 'SELECT * FROM `t_gametexts` WHERE language = :language AND gameplay = :gameplay;';

            $stmt = $this->pdo->prepare($selectStmt);
            $stmt->execute(['language' => $language, 'gameplay' => $gameplay]);
            $stmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\gametext\\Gametext');
            return $stmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\gametext\\Gametext');
        }

        public function changeNamePosInText(string $text, Player $player, ?Player $tacklingPlayer = null, ?Player $quarterback = null, ?Team $offTeam = null, int $yards = 0): string
        {
            $playerName = $player->getFirstName() . ' ' . $player->getLastName();
            $text = str_replace('[name]', $playerName, $text);

            $position = $player->getType()->getPosition();
            $text = str_replace('[pos]', $position->getPosition(), $text);
            $text = str_replace('[posl]', $position->getDescription(), $text);

            $lineupPosition = $player->getLineupPosition();
            if (str_contains($lineupPosition, 'RB')) {
                $lineupPosition = 'RB';
            } elseif (str_contains($lineupPosition, 'MLB')) {
                $lineupPosition = 'MLB';
            }
            $text = str_replace('[luPos]', $lineupPosition, $text);

            if (null != $tacklingPlayer) {
                $playerName = $tacklingPlayer->getFirstName() . ' ' . $tacklingPlayer->getLastName();
                $text = str_replace('[defName]', $playerName, $text);

                $position = $tacklingPlayer->getType()->getPosition();
                $text = str_replace('[defPos]', $position->getPosition(), $text);
                $text = str_replace('[defPosl]', $position->getDescription(), $text);

                $lineupPosition = str_contains($tacklingPlayer->getLineupPosition(), 'MLB') ? 'MLB' : $tacklingPlayer->getLineupPosition();
                $text = str_replace('[defLuPos]', $lineupPosition, $text);
            }

            if (null != $quarterback) {
                $playerName = $quarterback->getFirstName() . ' ' . $quarterback->getLastName();
                $text = str_replace('[qbName]', $playerName, $text);

                $position = $quarterback->getType()->getPosition();
                $text = str_replace('[qbPos]', $position->getPosition(), $text);
            }

            if (null != $offTeam) {
                $text = str_replace('[teamName]', $offTeam->getName(), $text);
            }

            $text = str_replace('[yards]', $yards, $text);

            if ($yards == 1) {
                $text = str_replace('Yards', 'Yard', $text);
            }

            return $text;
        }
    }
}