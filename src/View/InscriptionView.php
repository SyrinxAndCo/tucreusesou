<?php

namespace TuCreusesOu\View;

class InscriptionView extends View {
    public function __construct() {
        parent::__construct();
    }

    /**
     * Page de formulaire d'inscription
     * @param string $token
     * @param string|null $erreur
     * @param array $post
     * @return void
     */
    public function renderIndex(string $token, ?string $erreur = null, array $post = []): void {
        $this->setTemplate(
            'contenu',
            'inscription/index.twig',
            'inscriptionFormulaire',
            [
                'token' => $token,
                'erreur' => $erreur,
                'post' => $post
            ]
        );
        $this->render();
    }

    /**
     * Page de validation du formulaire d'inscription
     * @return void
     */
    public function renderValidation(): void {
        $this->setTemplate(
            'contenu',
            'inscription/validation.twig',
            'inscriptionValidation'
        );
        $this->render();
    }

    public function renderEmailValidation(): void {
        $this->setTemplate(
            'contenu',
            'inscription/email.twig',
            'inscriptionEmailValidation'
        );
        $this->render();
    }
}