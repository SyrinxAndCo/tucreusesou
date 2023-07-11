<?php

namespace TuCreusesOu\Controller;

use TuCreusesOu\Enum\Erreurs;
use TuCreusesOu\Enum\ViewBlocks;
use TuCreusesOu\Model\Profil;
use TuCreusesOu\View\ProfilView;

class ProfilController extends Controller {
    private ?Profil $profil;
    private const NOM_SESSION_TOKEN_PROFIL = 'tokenProfil';
    const NOM_SESSION_ERREUR_PROFIL = 'erreurProfil';

    public function __construct(?ProfilView $view) {
        if (!isset($_SESSION['profil'])) {
            $this->redirect('/');
        }
        $this->profil = Profil::getProfilParId($_SESSION['profil']->getId());
        if ($this->profil === null) {
            $this->redirect('/');
        }
        parent::__construct($view ?? new ProfilView());
    }

    /**
     * Page du profil
     * @return void
     */
    public function indexAction(): void {
        $this->view->setTemplate(
            ViewBlocks::CONTENU,
            'profil/profil.twig',
            'profil',
            [
                'profil' => $this->profil
            ]
        );
        $this->view->render();
    }

    /**
     * Page listant tous les amis du profil
     * @return void
     */
    public function listeAmisAction(): void {
        $_SESSION[self::NOM_SESSION_TOKEN_PROFIL] = uniqid();
        $paramsView = [
            'token' => $_SESSION[self::NOM_SESSION_TOKEN_PROFIL],
            'listeAmis' => $this->profil->getProfilsAmis()
        ];
        if (isset($_SESSION[self::NOM_SESSION_ERREUR_PROFIL])) {
            $paramsView['erreur'] = $this->getMessageErreur($_SESSION[self::NOM_SESSION_ERREUR_PROFIL]);
            unset($_SESSION[self::NOM_SESSION_ERREUR_PROFIL]);
        }
        $this->view->setTemplate(
            ViewBlocks::CONTENU,
            'profil/listeAmis.twig',
            'listeAmis',
            $paramsView
        );
        $this->view->render();
    }

    /**
     * Page de soumission des formulaires
     * @return void
     */
    public function postAction() {
        if (!isset($_POST['token']) || !isset($_SESSION[self::NOM_SESSION_TOKEN_PROFIL]) || $_POST['token'] !== $_SESSION[self::NOM_SESSION_TOKEN_PROFIL]) {
            $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::FORMULAIRE_NON_VALIDE;
            $this->redirect('/profil');
        }
        if (isset($_POST['supprimerAmi'])) {
            if (!preg_match('/\d+/', $_POST['supprimerAmi'])) {
                $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::IDENTIFIANT_AMI_INCONNU;
                $this->redirect('/profil/listeAmis');
            }
            $this->profil->retireAmi($_POST['supprimerAmi']);
            $this->redirect('/profil/listeAmis');
        }
    }

    protected function getMessageErreur(Erreurs $erreur): string {
        return match ($erreur) {
            Erreurs::FORMULAIRE_NON_VALIDE => "Votre formulaire était non valide, veuillez réessayer.",
            Erreurs::IDENTIFIANT_AMI_INCONNU => "L'identifiant fourni pour la suppression de l'ami est inconnu",
            default => ""
        };
    }
}