<?php

namespace TuCreusesOu\Controller;

use TuCreusesOu\Enum\Erreurs;
use TuCreusesOu\Enum\ViewBlocks;
use TuCreusesOu\Helper\Constantes;
use TuCreusesOu\Helper\ModelsHelper;
use TuCreusesOu\View\IndexView;

class IndexController extends Controller {
    public const NOM_SESSION_TOKEN_CONNEXION = 'tokenConnexion';
    public const NOM_SESSION_ERREUR_CONNEXION = 'erreurConnexion';

    public function __construct(?IndexView $view, ?ModelsHelper $modelsHelper) {
        if (isset($_SESSION['profil'])) {
            $this->redirect('/profil');
        }
        parent::__construct($view ?? new IndexView(), $modelsHelper);
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
            ViewBlocks::CONTENU,
            'connexion.twig',
            'connexion',
            $paramsView
        );
        $this->view->render();
    }

    public function postAction(): void {
        if (!isset($_POST['token']) || !isset($_SESSION[self::NOM_SESSION_TOKEN_CONNEXION]) || $_POST['token'] !== $_SESSION[self::NOM_SESSION_TOKEN_CONNEXION]) {
            $_SESSION[self::NOM_SESSION_ERREUR_CONNEXION] = Erreurs::FORMULAIRE_NON_VALIDE;
            $this->redirect('/');
        } elseif (!isset($_POST['email']) || !isset($_POST['mdp'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_CONNEXION] = Erreurs::CHAMP_MANQUANT;
            $this->redirect('/');
        } elseif (!preg_match(Constantes::REGEX_EMAIL, $_POST['email'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_CONNEXION] = Erreurs::EMAIL_INVALIDE;
            $this->redirect('/');
        } else {
            $profil = $this->modelsHelper->getProfilParMail($_POST['email']);
            if ($profil === null) {
                $_SESSION[self::NOM_SESSION_ERREUR_CONNEXION] = Erreurs::EMAIL_INCONNU;
                $this->redirect('/');
            } elseif (!password_verify($_POST['mdp'], $profil->getMdp())) {
                $_SESSION[self::NOM_SESSION_ERREUR_CONNEXION] = Erreurs::MAUVAIS_MDP;
                $this->redirect('/');
            } else {
                $_SESSION['profil'] = $profil;
                $this->redirect('/profil');
            }
        }
    }

    protected function getMessageErreur(Erreurs $erreur): string {
        return match ($erreur) {
            Erreurs::FORMULAIRE_NON_VALIDE => "Votre formulaire de connexion était invalide, veuillez réessayer.",
            Erreurs::EMAIL_INVALIDE => "L'email de connexion fourni est invalide.",
            Erreurs::CHAMP_MANQUANT => "Il manque un champ requis dans votre formulaire de connexion.",
            Erreurs::EMAIL_INCONNU, Erreurs::MAUVAIS_MDP => "La combinaison EMail/Mot de passe est inconnue.",
            default => "",
        };
    }
}