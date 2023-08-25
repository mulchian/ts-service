<?php


namespace touchdownstars\employee\job;


use PDO;

class JobController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function fetchJobs(): array
    {
        $selectStmt = $this->pdo->prepare('SELECT * FROM `t_job`;');
        $selectStmt->execute();
        $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\employee\\job\\Job');
        $jobs = $selectStmt->fetchAll(PDO::FETCH_CLASS, 'touchdownstars\\employee\\job\\Job');
        if (!empty($jobs)) {
            return $jobs;
        } else {
            return array();
        }
    }

    public function fetchJobByName($jobName): ?Job
    {
        if (!empty($jobName)) {
            $selectStmt = $this->pdo->prepare('SELECT * FROM `t_job` WHERE name = :name;');
            $selectStmt->execute(['name' => $jobName]);
            $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\employee\\job\\Job');
            $job = $selectStmt->fetch(PDO::FETCH_CLASS);
            if (!empty($job)) {
                return $job;
            }
        }
        return null;
    }

    public function fetchJobOfEmployee($idEmployee): ?Job
    {
        if (!empty($idEmployee)) {
            $selectStmt = $this->pdo->prepare('SELECT tj.* FROM `t_employee` te JOIN t_job tj ON te.idJob = tj.id WHERE te.id = :id;');
            $selectStmt->execute(['id' => $idEmployee]);
            $selectStmt->setFetchMode(PDO::FETCH_CLASS, 'touchdownstars\\employee\\job\\Job');
            $job = $selectStmt->fetch(PDO::FETCH_CLASS);
            if (!empty($job)) {
                return $job;
            }
        }
        return null;
    }

}