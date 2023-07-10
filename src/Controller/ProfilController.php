<?php

namespace TuCreusesOu\Controller;

use TuCreusesOu\Model\Profil;
use TuCreusesOu\View\ProfilView;

class ProfilController extends Controller {

    public function __construct(?ProfilView $view) {
        parent::__construct($view ?? new ProfilView());
    }

    public function indexAction(): void {
        if (!isset($_SESSION['profil'])) {
            $this->redirect('/');
        }
        $this->view->setTemplate(
            'contenu',
            'profil.twig',
            'profil',
            [
                'profil' => $_SESSION['profil']
            ]
        );
        $this->view->render();
    }

    protected function getMessageErreur(string $code): string {
        return '';
    }
}