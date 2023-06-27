<?php

namespace TuCreusesOu\View;

use TuCreusesOu\Model\Profil;

class ProfilView extends View {

    public function __construct() {
        parent::__construct();
    }

    public function renderProfil(Profil $profil): void {
        $this->setTemplate(
            'contenu',
            'profil.twig',
            'profil',
            [
                'profil' => $profil
            ]
        );
        $this->render();
    }
}