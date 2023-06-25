<?php

namespace Model;

use PDO;
use PDOException;

abstract class Model {
    private const BDD_SERVER = 'localhost';
    private const BDD_NAME = 'tucreusesou';
    private const BDD_USER = 'root';
    private const BDD_PASSWORD = '';
    protected static ?PDO $pdoInstance = null;

    protected function __construct() {
    }

    protected static function getDB(string $host = self::BDD_SERVER, string $dbName = self::BDD_NAME, string $user = self::BDD_USER, string $password = self::BDD_PASSWORD) {
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