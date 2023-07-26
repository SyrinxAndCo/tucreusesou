<?php

namespace TuCreusesOu\Controller;

function uniqid(): string {
    return 'token';
}

function session_destroy(): void {
    $_SESSION = null;
}

namespace TuCreusesOu\Test\Helper;

use TuCreusesOu\Model\Contrat;
use TuCreusesOu\Model\Departement;
use TuCreusesOu\Model\Profil;

class TestHelper {
    public static function defaultProfil(): Profil {
        return new Profil(
            'nom',
            'prenom',
            password_hash('mdp', PASSWORD_DEFAULT),
            'mail',
            [],
            0,
            'description',
            new Contrat(
                0,
                0,
                0,
                new Departement(
                    0,
                    'numero',
                    'nom'
                ),
                true,
                0
            ),
            0
        );
    }
}