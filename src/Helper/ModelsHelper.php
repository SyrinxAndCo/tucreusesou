<?php

namespace TuCreusesOu\Helper;

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
}