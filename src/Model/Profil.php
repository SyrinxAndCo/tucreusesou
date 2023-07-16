<?php

namespace TuCreusesOu\Model;

class Profil extends Model {
    private const TABLE = 'profil';
    private const TABLE_AMIS = 'amis';
    private ?int $id;
    private string $nom;
    private string $prenom;
    private ?string $description;
    private string $mdp;
    private string $mail;
    private array $amis;
    private ?Contrat $contrat;

    public function __construct(
        string  $nom,
        string  $prenom,
        string  $mdp,
        string  $mail,
        array   $amis,
        ?string $description = null,
        ?Contrat $contrat = null,
        ?int     $id = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->description = $description;
        $this->mdp = $mdp;
        $this->mail = $mail;
        $this->amis = $amis;
        $this->contrat = $contrat;
        parent::__construct();
    }

    /**
     * Récupère en BDD le profil correspondant à l'id passé en paramètre
     * @param int $id
     * @return Profil|null null en cas d'échec
     */
    public static function getProfilParId(int $id): ?Profil {
        $query = self::getDB()->prepare('SELECT ' . self::TABLE . '.id, ' . self::TABLE . '.nom, ' . self::TABLE . '.prenom, ' . self::TABLE . '.mdp, ' . self::TABLE . '.mail, ' . self::TABLE . '.description, ' . self::TABLE_AMIS . '.idAmi FROM ' . self::TABLE . ' LEFT JOIN ' . self::TABLE_AMIS . ' ON ' . self::TABLE . '.id = ' . self::TABLE_AMIS . '.idSource WHERE ' . self::TABLE . '.id = :id');
        if ($query) {
            if ($query->execute(['id' => $id])) {
                $profils = $query->fetchAll();
                if (count($profils) > 0) {
                    $amis = [];
                    foreach ($profils as $profil) {
                        if ($profil['idAmi']) {
                            $amis[] = $profil['idAmi'];
                        }
                    }
                    return new Profil($profils[0]['nom'], $profils[0]['prenom'], $profils[0]['mdp'], $profils[0]['mail'], $amis, $profils[0]['description'], Contrat::getContratParIdProfil($profils[0]['id']), $profils[0]['id']);
                }
            }
        }

        return null;
    }

    /**
     * Récupère en BDD le profil correspondant au mail passé en paramètre
     * @param string $mail
     * @return Profil|null
     */
    public static function getProfilParMail(string $mail): ?Profil {
        $query = self::getDB()->prepare('SELECT id FROM ' . self::TABLE . ' WHERE mail = :mail');
        if ($query) {
            if ($query->execute(['mail' => $mail])) {
                $res = $query->fetch();
                if ($res) {
                    return self::getProfilParId($res['id']);
                }
            }
        }

        return null;
    }

    /**
     * Sauvegarde le profil en BDD
     * @return bool
     */
    public function sauvegarde(): bool {
        if ($this->id) {
            $query = self::getDB()->prepare('UPDATE ' . self::TABLE . ' SET nom = :nom, prenom = :prenom, mdp = :mdp, mail = :mail, description = :description WHERE id = :id');
            return $query && $query->execute(
                    [
                        'nom' => $this->nom,
                        'prenom' => $this->prenom,
                        'mdp' => $this->mdp,
                        'mail' => $this->mail,
                        'description' => $this->description,
                        'id' => $this->id
                    ]
                );
        } else {
            $query = self::getDB()->prepare('INSERT INTO ' . self::TABLE . '(nom, prenom, mdp, mail, description) VALUES (:nom, :prenom, :mdp, :mail, :description)');
            return $query && $query->execute(
                    [
                        'nom' => $this->nom,
                        'prenom' => $this->prenom,
                        'mdp' => $this->mdp,
                        'mail' => $this->mail,
                        'description' => $this->description
                    ]
                );
        }
    }

    /**
     * Supprime le profil de la BDD
     * @return bool
     */
    public function supprime(): bool {
        $query = self::getDB()->prepare('DELETE FROM ' . self::TABLE_AMIS . ' WHERE idSource = :idSource OR idAmi = :idAmi');
        if ($query) {
            $res = $query->execute(
                [
                    'idSource' => $this->id,
                    'idAmi' => $this->id
                ]
            );
        } else {
            return false;
        }
        $contrat = Contrat::getContratParIdProfil($this->id);
        if ($contrat) {
            $res = $res && $contrat->supprime();
        }
        $query = self::getDB()->prepare('DELETE FROM ' . self::TABLE . ' WHERE id = :id');
        if ($query) {
            $res = $res && $query->execute(
                    [
                        'id' => $this->id
                    ]
                );
        } else {
            return false;
        }
        return $res;
    }

    /**
     * Ajoute un ami à la liste d'amis du profil
     * @param int $id
     * @return bool
     */
    public function ajouteAmi(int $id): bool {
        $this->amis[] = $id;
        $query = self::getDB()->prepare('INSERT INTO ' . self::TABLE_AMIS . '(idSource, idAmi) VALUES (:idSource, :idAmi)');
        if ($query) {
            return $query->execute(
                [
                    'idSource' => $this->id,
                    'idAmi' => $id
                ]
            );
        }
        return false;
    }

    /**
     * retire un ami à la liste d'amis du profil
     * @param int $id
     * @return bool
     */
    public function retireAmi(int $id): bool {
        foreach ($this->amis as $cle => $idAmi) {
            if ($idAmi == $id) {
                unset ($this->amis[$cle]);
                $query = self::getDB()->prepare('DELETE FROM ' . self::TABLE_AMIS . ' WHERE idSource = :idSource AND idAmi = :idAmi');
                if ($query) {
                    return $query->execute(
                        [
                            'idSource' => $this->id,
                            'idAmi' => $id
                        ]
                    );
                }
                return false;
            }
        }
        return false;
    }

    /**
     * Vérifie si le profil est ami avec un autre profil par son identifiant
     * @param int $id
     * @return bool
     */
    public function estAmiAvec(int $id): bool {
        return in_array($id, $this->amis);
    }

    /**
     * Vérifie si les deux profils sont amis l'un avec l'autre
     * @param int $idAmi
     * @return bool
     */
    public function estAmitie(int $idAmi): bool {
        $query = self::getDB()->prepare('SELECT EXISTS(SELECT id FROM ' . self::TABLE_AMIS . ' WHERE idSource = :idSource AND idAmi = :idAmi) AND EXISTS(SELECT id FROM ' . self::TABLE_AMIS . ' WHERE idSource = :idSource2 AND idAmi = :idAmi2)');
        if ($query) {
            if ($query->execute(
                [
                    'idSource' => $this->id,
                    'idAmi' => $idAmi,
                    'idSource2' => $idAmi,
                    'idAmi2' => $this->id
                ]
            )) {
                return $query->fetch()[0];
            }
        }
        return false;
    }

    /**
     * Récupère la liste des profils de tous les amis
     * @return array
     */
    public function getProfilsAmis(): array {
        $res = [];
        foreach ($this->amis as $idAmi) {
            $res[] = Profil::getProfilParId($idAmi);
        }
        return $res;
    }

    /**
     * Vérifie si une inscription est déjà en cours avec le mail passé en paramètre
     * @param string $mail
     * @return bool
     */
    public static function mailDejaPris(string $mail): bool {
        $query = self::getDB()->prepare('SELECT EXISTS(SELECT 1 FROM ' . self::TABLE . ' WHERE mail = :mail)');
        if ($query) {
            if ($query->execute(['mail' => $mail])) {
                $res = $query->fetch();
                if ($res) {
                    return $res[0];
                }
            }
        }

        return false;
    }

    /**
     * Renvoie la liste de tous les profils enregistrés correspondants à la recherche
     * @param string|null $recherche
     * @return array
     */
    public function getProfilsNonAmis(?string $recherche = null): array {
        $query = self::getDB()->prepare(
            'SELECT id FROM ' . self::TABLE .
            ' WHERE id NOT IN (' . $this->id . (count($this->amis) > 0 ? ',' . implode(',', $this->amis) : '') . ') ' .
            ($recherche ? ' AND (nom LIKE :recherche1 OR prenom LIKE :recherche2 ) ' : '') .
            'ORDER BY nom ASC'
        );
        if ($query) {
            if ($query->execute($recherche ? ['recherche1' => '%' . $recherche . '%', 'recherche2' => '%' . $recherche . '%'] : [])) {
                $res = $query->fetchAll();
                $listeProfils = [];
                foreach ($res as $profil) {
                    $listeProfils[$profil['id']] = Profil::getProfilParId($profil['id']);
                }
                return $listeProfils;
            }
        }
        return [];
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
     * @return string
     */
    public function getPrenom(): string {
        return $this->prenom;
    }

    /**
     * @param string $prenom
     */
    public function setPrenom(string $prenom): void {
        $this->prenom = $prenom;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getMdp(): string {
        return $this->mdp;
    }

    /**
     * @param string $mdp
     */
    public function setMdp(string $mdp): void {
        $this->mdp = $mdp;
    }

    /**
     * @return string
     */
    public function getMail(): string {
        return $this->mail;
    }

    /**
     * @param string $mail
     */
    public function setMail(string $mail): void {
        $this->mail = $mail;
    }

    /**
     * @return array
     */
    public function getAmis(): array {
        return $this->amis;
    }

    /**
     * @param array $amis
     */
    public function setAmis(array $amis): void {
        $this->amis = $amis;
    }

    /**
     * @return Contrat|null
     */
    public function getContrat(): ?Contrat {
        return $this->contrat;
    }

    /**
     * @param Contrat $contrat
     */
    public function setContrat(Contrat $contrat): void {
        $this->contrat = $contrat;
    }
}