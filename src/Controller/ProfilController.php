<?php

namespace TuCreusesOu\Controller;

use TuCreusesOu\Model\Profil;
use TuCreusesOu\View\ProfilView;

class ProfilController extends Controller {

    public function __construct() {
        $this->view = new ProfilView();
        parent::__construct();
    }

    public function indexAction(): void {
        // TODO: Implement indexAction() method.
    }

    public function eleonoreAction(): void {
        $profil = Profil::getProfilParId(1);
        $this->view->renderProfil($profil);
    }

    public function getMessageErreur(): string {
        return '';
    }
}