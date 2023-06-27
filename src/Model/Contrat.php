<?php

namespace TuCreusesOu\Model;

class Contrat extends Model {
    private const TABLE = "contrat";
    private const TABLE_DEPARTEMENT = "departement";
    private ?int $id;
    private int $idProfil;
    private int $dateDebut;
    private ?int $dateFin;
    private Departement $departement;

    /**
     * @param ?int $id
     * @param int $idProfil
     * @param int $dateDebut
     * @param ?int $dateFin
     * @param Departement $departement
     */
    public function __construct(?int $id, int $idProfil, int $dateDebut, ?int $dateFin, Departement $departement) {
        $this->id = $id;
        $this->idProfil = $idProfil;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->departement = $departement;
        parent::__construct();
    }

    /**
     * Renvoie le Contrat correspondant à l'identifiant passé en paramètre
     * @param int $id
     * @return Contrat|null
     */
    public static function getContratParId(int $id): ?Contrat {
        return self::getContratParX('id', $id);
    }

    /**
     * Renvoie le Contrat correspondant au numéro de profil passé en paramètre
     * @param int $idProfil
     * @return Contrat|null
     */
    public static function getContratParIdProfil(int $idProfil): ?Contrat {
        return self::getContratParX('idProfil', $idProfil);
    }

    /**
     * Renvoie le Contrat correspondant à un couple clé-valeur
     * @param string $cle
     * @param int $valeur
     * @return Contrat|null
     */
    private static function getContratParX(string $cle, int $valeur): ?Contrat {
        $query = self::getDB()->prepare(
            'SELECT c.id, c.idProfil, c.dateDebut, c.dateFin, c.idDepartement, d.numero AS numeroDepartement, d.nom AS nomDepartement FROM ' . self::TABLE . ' AS c' .
            ' LEFT JOIN ' . self::TABLE_DEPARTEMENT . ' AS d ON c.idDepartement = d.id WHERE c.' . $cle . ' = :' . $cle
        );
        if ($query) {
            if ($query->execute([$cle => $valeur])) {
                $res = $query->fetch();
                return new Contrat($res['id'], $res['idProfil'], $res['dateDebut'], $res['dateFin'], new Departement($res['idDepartement'], $res['numeroDepartement'], $res['nomDepartement']));
            }
        }
        return null;
    }

    /**
     * Sauvegarde le contrat en BDD
     * @return bool
     */
    public function sauvegarde(): bool {
        if ($this->id) {
            $query = self::getDB()->prepare('UPDATE ' . self::TABLE . ' SET (idProfil = :idProfil, dateDebut = :dateDebut, dateFin = :dateFin, idDepartement = :idDepartement) WHERE id = :id');
            return $query->execute(
                [
                    "id" => $this->id,
                    "idProfil" => $this->idProfil,
                    "dateDebut" => $this->dateDebut,
                    "dateFin" => $this->dateFin,
                    "idDepartement" => $this->departement->getId(),
                ]
            );
        } else {
            $query = self::getDB()->prepare('INSERT INTO ' . self::TABLE . '(idProfil, dateDebut, dateFin, idDepartement) VALUES (:idProfil, :dateDebut, :dateFin, :idDepartement)');
            if ($query) {
                return $query->execute(
                    [
                        "idProfil" => $this->idProfil,
                        "dateDebut" => $this->dateDebut,
                        "dateFin" => $this->dateFin,
                        "idDepartement" => $this->departement->getId(),
                    ]
                );
            }
        }
        return false;
    }

    /**
     * @return ?int
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getIdProfil(): int {
        return $this->idProfil;
    }

    /**
     * @param int $idProfil
     */
    public function setIdProfil(int $idProfil): void {
        $this->idProfil = $idProfil;
    }

    /**
     * @return int
     */
    public function getDateDebut(): int {
        return $this->dateDebut;
    }

    /**
     * @param int $dateDebut
     */
    public function setDateDebut(int $dateDebut): void {
        $this->dateDebut = $dateDebut;
    }

    /**
     * @return ?int
     */
    public function getDateFin(): ?int {
        return $this->dateFin;
    }

    /**
     * @param ?int $dateFin
     */
    public function setDateFin(?int $dateFin): void {
        $this->dateFin = $dateFin;
    }

    /**
     * @return Departement
     */
    public function getDepartement(): Departement {
        return $this->departement;
    }

    /**
     * @param Departement $departement
     */
    public function setDepartement(Departement $departement): void {
        $this->departement = $departement;
    }
}
