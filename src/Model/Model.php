<?php

namespace Model;

use PDO;
use PDOException;

abstract class Model {
    protected static ?PDO $pdoInstance = null;

    protected function __construct() {
    }

    protected static function getDB(string $host = BDD_SERVER, string $dbName = BDD_NAME, string $user = BDD_USER, string $password = BDD_PASSWORD) {
        if (self::$pdoInstance === null) {
            try {
                self::$pdoInstance = new PDO('mysql:host=' . $host . ';dbname=' . $dbName, $user, $password);
            } catch (PDOException $e) {
                //TODO Error management
                var_dump($e);
                die;
            }
        }
        return self::$pdoInstance;
    }
}