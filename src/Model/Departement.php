<?php

namespace TuCreusesOu\Model;

class Departement extends Model {
    private const TABLE = "departement";
    private int $id;
    private string $numero;
    private string $nom;

    /**
     * @param int $id
     * @param string $numero
     * @param string $nom
     */
    public function __construct(int $id, string $numero, string $nom) {
        $this->id = $id;
        $this->numero = $numero;
        $this->nom = $nom;
        parent::__construct();
    }

    /**
     * Renvoie le département correspondant à l'identifiant passé en paramètre
     * @param int $id
     * @return Departement|null
     */
    public static function getDepartementParId(int $id): ?Departement {
        $query = self::getDB()->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = :id');
        if ($query) {
            if ($query->execute(['id' => $id])) {
                $res = $query->fetch();
                return new Departement($res['id'], $res['numero'], $res['nom']);
            }
        }
        return null;
    }

    /**
     * Renvoie la liste de tous les départements enregistrés
     * @return array
     */
    public static function getTousDepartements(): array {
        $query = self::getDB()->query('SELECT * FROM ' . self::TABLE);
        if ($query) {
            $res = $query->fetchAll();
            $listeDepartements = [];
            foreach ($res as $departement) {
                $listeDepartements[$departement['numero']] = new Departement($departement['id'], $departement['numero'], $departement['nom']);
            }
            return $listeDepartements;
        }
        return [];
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getNumero(): string {
        return $this->numero;
    }

    /**
     * @param string $numero
     */
    public function setNumero(string $numero): void {
        $this->numero = $numero;
    }

    /**
     * @return string
     */
    public function getNom(): string {
        return $this->nom;
    }

    /**
     * @param string $nom
     */
    public function setNom(string $nom): void {
        $this->nom = $nom;
    }

    /**
     * Visualisation du département sous forme de chaine de caractères
     * @return string
     */
    public function __toString(): string {
        return $this->numero . ' - ' . $this->nom;
    }
}