<?php

namespace TuCreusesOu\Controller;

function uniqid(): string {
    return 'token';
}

namespace TuCreusesOu\Test\Helper;

use TuCreusesOu\Model\Contrat;
use TuCreusesOu\Model\Departement;
use TuCreusesOu\Model\Profil;

class TestHelper {
    public function defaultProfil(): Profil {
        return new Profil(
            'nom',
            'prenom',
            password_hash('mdp', PASSWORD_DEFAULT),
            'mail',
            [],
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