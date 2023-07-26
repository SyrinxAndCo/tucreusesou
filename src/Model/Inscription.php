<?php

namespace TuCreusesOu\Model;

use TuCreusesOu\Exceptions\InscriptionCodeInconnuException;
use TuCreusesOu\Exceptions\InscriptionDelaiException;

class Inscription extends Model {
    private const TABLE = 'inscription';
    private const DELAI_VALIDATION = 30 * 60;
    private ?int $id;
    private string $nom;
    private string $prenom;
    private string $mdp;
    private string $mail;
    private ?string $code;
    private ?int $timestamp;

    public function __construct(
        string  $nom,
        string  $prenom,
        string  $mdp,
        string  $mail,
        ?int    $id = null,
        ?string $code = null,
        ?int $timestamp = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->mdp = $mdp;
        $this->mail = $mail;
        $this->code = $code;
        $this->timestamp = $timestamp;
        parent::__construct();
    }

    /**
     * Récupère en BDD l'inscription correspondant au code passé en paramètre
     * @param string $code
     * @return Inscription|null null en cas d'échec
     */
    public static function getInscriptionParCode(string $code): ?Inscription {
        $query = self::getDB()->prepare('SELECT * FROM ' . self::TABLE . ' WHERE code = :code');
        if ($query) {
            if ($query->execute(['code' => $code])) {
                $inscription = $query->fetch();
                if ($inscription) {
                    return new Inscription($inscription['nom'], $inscription['prenom'], $inscription['mdp'], $inscription['mail'], $inscription['id'], $inscription['code'], $inscription['timestamp']);
                }
            }
        }

        return null;
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
                $inscription = $query->fetch();
                if ($inscription) {
                    return $inscription[0];
                }
            }
        }

        return false;
    }

    /**
     * Sauvegarde l'inscription en BDD
     * @return string|null
     */
    public function sauvegarde(): ?string {
        if ($this->id === null) {
            $query = self::getDB()->prepare('INSERT INTO ' . self::TABLE . '(nom, prenom, mdp, mail, code, timestamp) VALUES (:nom, :prenom, :mdp, :mail, :code, :timestamp)');
            if ($query) {
                $uuid = uniqid();
                if ($query->execute(
                    [
                        'nom' => $this->nom,
                        'prenom' => $this->prenom,
                        'mdp' => $this->mdp,
                        'mail' => $this->mail,
                        'code' => $uuid,
                        'timestamp' => time()
                    ]
                )) {
                    return $uuid;
                }
            }
        }
        return null;
    }

    /**
     * Supprime l'inscription de la BDD
     * @return bool
     */
    public function supprime(): bool {
        if ($this->id) {
            $query = self::getDB()->prepare('DELETE FROM ' . self::TABLE . ' WHERE id = :id');
            if ($query) {
                return $query->execute(
                    [
                        'id' => $this->id
                    ]
                );
            }
        }
        return false;
    }

    /**
     * Valide l'inscription en utilisant le code de validation
     * @param string $code
     * @return bool
     * @throws InscriptionCodeInconnuException
     * @throws InscriptionDelaiException
     */
    public static function valideInscription(string $code): bool {
        $inscription = self::getInscriptionParCode($code);
        if ($inscription) {
            if ($inscription->getTimestamp() + self::DELAI_VALIDATION < time()) {
                $inscription->supprime();
                throw new InscriptionDelaiException('Délai de validation de l\'inscription dépassé, veuillez renouveler votre inscription.');
            }
            $profil = new Profil($inscription->nom, $inscription->prenom, $inscription->mdp, $inscription->mail, [], 0);
            if ($profil->sauvegarde()) {
                return $inscription->supprime();
            }
        } else {
            throw new InscriptionCodeInconnuException('Ce code de validation d\'inscription n\'existe pas.');
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
     * @return string|null
     */
    public function getCode(): ?string {
        return $this->code;
    }

    /**
     * @param string|null $code
     */
    public function setCode(?string $code): void {
        $this->code = $code;
    }

    /**
     * @return int|null
     */
    public function getTimestamp(): ?int {
        return $this->timestamp;
    }

    /**
     * @param int|null $timestamp
     */
    public function setTimestamp(?int $timestamp): void {
        $this->timestamp = $timestamp;
    }
}