<?php

namespace Bezdomni\IsaacRebirth;

use PDO;

/**
 * Database CRUD for savegames.
 */
class Archiver
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Saves a savegame to database.
     *
     * @param  string $data Binary savegame data.
     */
    public function save($data)
    {
        $hash = md5($data);
        $base64 = base64_encode($data);
        $uploaded = date('c');

        $sql = "INSERT INTO savegame (hash, data, uploaded)
                VALUES (?, ?, ?);";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hash, $base64, $uploaded]);
    }

    /**
     * Loads a savegame from database.
     *
     * @param  string $hash The savegame md5 hash.
     *
     * @return object       The savegame db record.
     */
    public function load($hash)
    {
        $sql = "SELECT * FROM savegame WHERE hash = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hash]);
        $rows = $stmt->fetchAll();

        return empty($rows) ? null : $rows[0];
    }

    /**
     * Checks whenter a savegame exists in the database.
     *
     * @param  string $hash The savegame md5 hash.
     */
    public function exists($hash)
    {
        $sql = "SELECT count(*) AS count FROM savegame WHERE hash = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hash]);
        $rows = $stmt->fetchAll();

        return $rows[0]->count > 0;
    }

    /**
     * Fetches most recently uploaded savegames.
     *
     * @param  integer $count Max number of records to fetch.
     *
     * @return object[]       An array of savegame db records.
     */
    public function recent($count = 10)
    {
        $sql = "SELECT  hash, uploaded
                FROM savegame
                ORDER BY uploaded DESC
                LIMIT ?;";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$count]);

        return $stmt->fetchAll();
    }
}
