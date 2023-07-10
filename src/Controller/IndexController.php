<?php

namespace TuCreusesOu\Controller;

use TuCreusesOu\Model\Profil;
use TuCreusesOu\View\IndexView;

class IndexController extends Controller {
    private const NOM_SESSION_TOKEN_CONNEXION = 'tokenConnexion';
    private const NOM_SESSION_ERREUR_CONNEXION = 'erreurConnexion';
    private const ERREUR_FORMULAIRE_NON_VALIDE = 0;
    private const ERREUR_EMAIL_INVALIDE = 1;
    private const ERREUR_CHAMP_MANQUANT = 2;
    private const ERREUR_EMAIL_INCONNU = 3;
    private const ERREUR_MAUVAIS_MDP = 4;

    public function __construct(?IndexView $view) {
        if (isset($_SESSION['profil'])) {
            $this->redirect('/profil');
        }
        parent::__construct($view ?? new IndexView());
    }

    /**
     * Page de connexion
     * @return void
     */
    public function indexAction(): void {
        $_SESSION[self::NOM_SESSION_TOKEN_CONNEXION] = uniqid();
        $paramsView = [
            'token' => $_SESSION[self::NOM_SESSION_TOKEN_CONNEXION]
        ];
        if (isset($_SESSION[self::NOM_SESSION_ERREUR_CONNEXION])) {
            $paramsView['erreur'] = $this->getMessageErreur($_SESSION[self::NOM_SESSION_ERREUR_CONNEXION]);
            unset($_SESSION[self::NOM_SESSION_ERREUR_CONNEXION]);
        }
        $this->view->setTemplate(
            'contenu',
            'connexion.twig',
            'connexion',
            $paramsView
        );
        $this->view->render();
    }

    public function postAction(): void {
        if (!isset($_POST['token']) || !isset($_SESSION[self::NOM_SESSION_TOKEN_CONNEXION]) || $_POST['token'] !== $_SESSION[self::NOM_SESSION_TOKEN_CONNEXION]) {
            $_SESSION[self::NOM_SESSION_ERREUR_CONNEXION] = self::ERREUR_FORMULAIRE_NON_VALIDE;
            $this->redirect('/');
        }
        if (!isset($_POST['email']) || !isset($_POST['mdp'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_CONNEXION] = self::ERREUR_CHAMP_MANQUANT;
            $this->redirect('/');
        }
        if (!preg_match(Controller::REGEX_EMAIL, $_POST['email'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_CONNEXION] = self::ERREUR_EMAIL_INVALIDE;
            $this->redirect('/');
        }
        $profil = Profil::getProfilParMail($_POST['email']);
        if ($profil === null) {
            $_SESSION[self::NOM_SESSION_ERREUR_CONNEXION] = self::ERREUR_EMAIL_INCONNU;
            $this->redirect('/');
        }
        if (!password_verify($_POST['mdp'], $profil->getMdp())) {
            $_SESSION[self::NOM_SESSION_ERREUR_CONNEXION] = self::ERREUR_MAUVAIS_MDP;
            $this->redirect('/');
        }
        $_SESSION['profil'] = $profil;
        $this->redirect('/profil');
    }

    protected function getMessageErreur(string $code): string {
        switch($code) {
            case self::ERREUR_FORMULAIRE_NON_VALIDE:
                return "Votre formulaire de connexion était invalide, veuillez réessayer.";
            case self::ERREUR_EMAIL_INVALIDE:
                return "L'email de connexion fourni est invalide.";
            case self::ERREUR_CHAMP_MANQUANT:
                return "Il manque un champ requis dans votre formulaire de connexion.";
            case self::ERREUR_EMAIL_INCONNU:
            case self::ERREUR_MAUVAIS_MDP:
                return "La combinaison EMail/Mot de passe est inconnue.";
            default:
                return "";
        }
    }
}