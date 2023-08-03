<?php

namespace TuCreusesOu\Helper;

use TuCreusesOu\Exceptions\InscriptionCodeInconnuException;
use TuCreusesOu\Exceptions\InscriptionDelaiException;
use TuCreusesOu\Model\Departement;
use TuCreusesOu\Model\Inscription;
use TuCreusesOu\Model\Profil;

class ModelsHelper {
    public function __construct() {}

    /**
     * Récupère en BDD le profil correspondant à l'id passé en paramètre
     * @param int $id
     * @return Profil|null null en cas d'échec
     */
    public function getProfilParId(int $id): ?Profil {
        return Profil::getProfilParId($id);
    }

    /**
     * Récupère en BDD le profil correspondant au mail passé en paramètre
     * @param string $mail
     * @return Profil|null
     */
    public function getProfilParMail(string $mail): ?Profil {
        return Profil::getProfilParMail($mail);
    }

    /**
     * Vérifie si un mail est déjà utilisé
     * @param string $mail
     * @return bool
     */
    public function mailDejaPris(string $mail): bool {
        return Profil::mailDejaPris($mail) || Inscription::mailDejaPris($mail);
    }

    /**
     * Crée une inscription à partir des informations fournies
     * @param string $nom
     * @param string $prenom
     * @param string $mdp
     * @param string $email
     * @return Inscription
     */
    public function initInscription(string $nom, string $prenom, string $mdp, string $email): Inscription {
        return new Inscription($nom, $prenom, $mdp, $email);
    }

    /**
     * @param string $code
     * @return bool
     * @throws InscriptionCodeInconnuException
     * @throws InscriptionDelaiException
     */
    public function valideInscription(string $code): bool {
        return Inscription::valideInscription($code);
    }

    /**
     * Renvoie la liste de tous les départements enregistrés
     * @return array
     */
    public function getTousDepartements(): array {
        return Departement::getTousDepartements();
    }

    /**
     * Renvoie le département correspondant à l'identifiant passé en paramètre
     * @param int $id
     * @return Departement|null
     */
    public function getDepartementParId(int $id): ?Departement {
        return Departement::getDepartementParId($id);
    }

    /**
     * Vérifie si un identifiant de département existe bien en base
     * @param int $id
     * @return bool
     */
    public function existeDepartementId(int $id): bool {
        return Departement::existeId($id);
    }

    /**
     * Renvoie la liste de tous les profils enregistrés inscrits à la newsletter
     * @return array
     */
    public function getProfilsNewsletter(): array {
        return Profil::getProfilsNewsletter();
    }
}