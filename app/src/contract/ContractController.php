<?php


namespace touchdownstars\contract;


use PDO;

class ContractController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createContract($marketvalue, $salary, $endOfContract): ?Contract
    {
        $contract = new Contract();
        $contract->setSalary($salary);
        $contract->setSigningBonus($this->calcSigningBonus($marketvalue, $endOfContract));
        $contract->setEndOfContract($endOfContract);
        $id = $this->saveContract($contract);

        if ($id > 0) {
            $contract->setId($id);
            return $contract;
        }
        return null;
    }

    /**
     * Speichert oder aktualisiert den mitgegebenen Contract.
     * @param Contract $contract
     * @return int - lastInsertId: ID des neu eingefügten Teams oder des aktualisierten Teams. 0 bei Fehler.
     */
    public function saveContract(Contract $contract): int
    {
        $id = $contract->getId();

        $saveContract = 'INSERT INTO `t_contract` (id, salary, signingBonus, endOfContract) values (:id, :salary, :signingBonus, :endOfContract) 
                            ON DUPLICATE KEY UPDATE salary = :newSalary, signingBonus = :newSigningBonus, endOfContract = :newEndOfContract;';
        $saveStmt = $this->pdo->prepare($saveContract);
        $saveStmt->execute([
            'id' => $id ?? null,
            'salary' => $contract->getSalary(),
            'signingBonus' => $contract->getSigningBonus(),
            'endOfContract' => $contract->getEndOfContract(),
            'newSalary' => $contract->getSalary(),
            'newSigningBonus' => $contract->getSigningBonus(),
            'newEndOfContract' => $contract->getEndOfContract()
        ]);

        return $this->pdo->lastInsertId();
    }

    public function fetchContractOfEmployee(int $idEmployee): ?Contract
    {
        $selectStmt = $this->pdo->prepare('SELECT tc.* FROM `t_contract` tc JOIN `t_employee` te ON tc.id = te.idContract WHERE te.id = :idEmployee;');
        $selectStmt->execute(['idEmployee' => $idEmployee]);
        $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\contract\\Contract');
        $contract = $selectStmt->fetch(PDO::FETCH_CLASS);
        if (isset($contract) && !empty($contract)) {
            return $contract;
        }
        return null;
    }

    public function fetchContractOfPlayer(int $idPlayer): ?Contract
    {
        $selectStmt = $this->pdo->prepare('SELECT tc.* FROM `t_contract` tc JOIN `t_player` tp ON tc.id = tp.idContract WHERE tp.id = :idPlayer;');
        $selectStmt->execute(['idPlayer' => $idPlayer]);
        $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\contract\\Contract');
        $contract = $selectStmt->fetch(PDO::FETCH_CLASS);
        if (isset($contract) && !empty($contract)) {
            return $contract;
        }
        return null;
    }

    public function deleteContract(int $idContract): int
    {
        $deleteStmt = $this->pdo->prepare('DELETE FROM `t_contract` WHERE id = :id;');
        $deleteStmt->execute(['id' => $idContract]);
        return $deleteStmt->rowCount();
    }

    public function calcSigningBonus(int $marketvalue, int $timeOfContract): int
    {
        return (int)floor(($marketvalue * (5 * $timeOfContract) / 100) * $timeOfContract);
    }
}