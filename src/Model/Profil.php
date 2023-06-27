<?php

namespace TuCreusesOu\Model;

class Profil extends Model {
    private const TABLE = 'profil';
    private int $id;
    private string $nom;
    private string $prenom;
    private ?string $description;
    private string $mdp;
    private string $mail;

    public function __construct(
        int     $id,
        string  $nom,
        string  $prenom,
        string  $mdp,
        string  $mail,
        ?string $description = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->description = $description;
        $this->mdp = $mdp;
        $this->mail = $mail;
        parent::__construct();
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
     * Récupère en BDD le profil correspondant à l'id passé en paramètre
     * @param int $id
     * @return Profil|null null en cas d'échec
     */
    public static function getProfilParId(int $id): ?Profil {
        $query = self::getDB()->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = :id');
        if ($query) {
            if ($query->execute(['id' => $id])) {
                $profil = $query->fetch();
                if ($profil) {
                    return new Profil($profil['id'], $profil['nom'], $profil['prenom'], $profil['mdp'], $profil['mail'], $profil['description']);
                }
            }
        }

        return null;
    }
}