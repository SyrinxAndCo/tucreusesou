<?php

namespace TuCreusesOu\View;

class IndexView extends View {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Page de connexion
     * @param string $token
     * @param string|null $erreur
     * @return void
     */
    public function renderConnexion(string $token, ?string $erreur = null): void {
        $this->setTemplate(
            'contenu',
            'connexion.twig',
            'connexion',
            [
                'token' => $token,
                'erreur' => $erreur
            ]
        );
        $this->render();
    }
}