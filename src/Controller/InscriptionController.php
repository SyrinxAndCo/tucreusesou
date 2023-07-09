<?php

namespace TuCreusesOu\Controller;

use TuCreusesOu\Exceptions\InscriptionCodeInconnuException;
use TuCreusesOu\Exceptions\InscriptionDelaiException;
use TuCreusesOu\Helper\Mailer;
use TuCreusesOu\Model\Inscription;
use TuCreusesOu\Model\Profil;
use TuCreusesOu\View\InscriptionView;

class InscriptionController extends Controller {
    private const NOM_SESSION_TOKEN_INSCRIPTION = 'tokenInscription';
    private const NOM_SESSION_ERREUR_INSCRIPTION = 'erreurInscription';
    private const NOM_SESSION_POST_INSCRIPTION = 'postInscription';
    private const ERREUR_FORMULAIRE_NON_VALIDE = 0;
    private const ERREUR_CHAMP_MANQUANT = 1;
    private const ERREUR_MOT_DE_PASSE_DIFFERENT = 2;
    private const ERREUR_EMAIL_INVALIDE = 3;
    private const ERREUR_CARACTERES_INTERDITS = 4;
    private const ERREUR_MDP_INTERDIT = 5;
    private const ERREUR_CODE_EMAIL_MANQUANT = 6;
    private const ERREUR_CODE_EMAIL_DELAI_DEPASSE = 7;
    private const ERREUR_CODE_EMAIL_INCONNU = 8;
    private const ERREUR_CODE_EMAIL_GENERIQUE = 9;
    private const ERREUR_MAIL_DEJA_PRIS = 10;
    private const ERREUR_MDP_TROP_COURT = 11;

    public function __construct() {
        $this->view = new InscriptionView();
        parent::__construct();
    }

    /**
     * Formulaire d'inscription
     * @return void
     */
    public function indexAction(): void {
        $_SESSION[self::NOM_SESSION_TOKEN_INSCRIPTION] = uniqid();
        if (!isset($_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION])) {
            $this->view->renderIndex(
                $_SESSION[self::NOM_SESSION_TOKEN_INSCRIPTION]
            );
        } else {
            $this->view->renderIndex(
                $_SESSION[self::NOM_SESSION_TOKEN_INSCRIPTION],
                $this->getMessageErreur($_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION]),
                $_SESSION[self::NOM_SESSION_POST_INSCRIPTION] ?? []
            );
            unset($_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION]);
            unset($_SESSION[self::NOM_SESSION_POST_INSCRIPTION]);
        }
    }

    /**
     * URL de soumission du formulaire
     * @return void
     */
    public function postAction(): void {
        $_SESSION[self::NOM_SESSION_POST_INSCRIPTION] = $_POST;
        if (!isset($_POST['token']) || !isset($_SESSION[self::NOM_SESSION_TOKEN_INSCRIPTION]) || $_POST['token'] !== $_SESSION[self::NOM_SESSION_TOKEN_INSCRIPTION]) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = self::ERREUR_FORMULAIRE_NON_VALIDE;
            $this->redirect('/inscription');
        }
        if (!isset($_POST['nom']) || !isset($_POST['prenom']) || !isset($_POST['email']) || !isset($_POST['mdp']) || !isset($_POST['mdp2'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = self::ERREUR_CHAMP_MANQUANT;
            $this->redirect('/inscription');
        }
        if ($_POST['mdp'] !== $_POST['mdp2']) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = self::ERREUR_MOT_DE_PASSE_DIFFERENT;
            $this->redirect('/inscription');
        }
        if (!preg_match('/^[\w\-.]+@([\w\-]+\.)+[\w\-]{2,4}$/', $_POST['email'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = self::ERREUR_EMAIL_INVALIDE;
            $this->redirect('/inscription');
        }
        if (!preg_match('/^[a-zA-ZÀ-ÿ\-. ]*$/', $_POST['nom']) || !preg_match('/^[a-zA-ZÀ-ÿ\-. ]*$/', $_POST['prenom'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = self::ERREUR_CARACTERES_INTERDITS;
            $this->redirect('/inscription');
        }
        if (!preg_match('/^[a-zA-ZÀ-ÿ\-. ,!*\/+#?%$£^¨°_|&]*$/', $_POST['mdp'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = self::ERREUR_MDP_INTERDIT;
            $this->redirect('/inscription');
        }
        if (strlen($_POST['mdp']) < 8) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = self::ERREUR_MDP_TROP_COURT;
            $this->redirect('/inscription');
        }
        if (Inscription::mailDejaPris($_POST['email']) || Profil::mailDejaPris($_POST['email'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = self::ERREUR_MAIL_DEJA_PRIS;
            $this->redirect('/inscription');
        }
        unset($_SESSION[self::NOM_SESSION_POST_INSCRIPTION]);
        unset($_SESSION[self::NOM_SESSION_TOKEN_INSCRIPTION]);
        $inscription = new Inscription($_POST['nom'], $_POST['prenom'], password_hash($_POST['mdp'],  PASSWORD_DEFAULT), $_POST['email']);
        $code = $inscription->sauvegarde();
        $mailer = new Mailer();
        $mailer->envoieMailInscription($inscription->getPrenom() . ' ' . $inscription->getNom(), $inscription->getMail(), $code);

        $this->redirect('/inscription/validation');
    }

    /**
     * Page de validation du formulaire
     * @return void
     */
    public function validationAction(): void {
        $this->view->renderValidation();
    }

    /**
     * URL de validation de l'email
     * @param string|null $code
     * @return void
     */
    public function validermailAction(?string $code = null): void {
        if (!$code) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = self::ERREUR_CODE_EMAIL_MANQUANT;
            $this->redirect('/inscription');
        }
        try {
            if (!Inscription::valideInscription($code)) {
                $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = self::ERREUR_CODE_EMAIL_GENERIQUE;
                $this->redirect('/inscription');
            }
            $this->view->renderEmailValidation();
        } catch(InscriptionDelaiException $_) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = self::ERREUR_CODE_EMAIL_DELAI_DEPASSE;
            $this->redirect('/inscription');
        } catch(InscriptionCodeInconnuException $_) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = self::ERREUR_CODE_EMAIL_INCONNU;
            $this->redirect('/inscription');
        }
    }

    protected function getMessageErreur(string $code): string {
        switch ($code) {
            case self::ERREUR_FORMULAIRE_NON_VALIDE:
                return "Votre formulaire d'inscription était non valide, veuillez réessayer.";
            case self::ERREUR_CHAMP_MANQUANT:
                return "Il manque un champ requis dans votre formulaire.";
            case self::ERREUR_MOT_DE_PASSE_DIFFERENT:
                return "Le mot de passe entré en vérification est différent du mot de passe donné.";
            case self::ERREUR_EMAIL_INVALIDE:
                return "L'email fourni est invalide.";
            case self::ERREUR_CARACTERES_INTERDITS:
                return "Votre nom ou votre prénom contiennent des caractères interdits. Si ces caractères sont légitimes, veuillez contacter l'administratrice du site en donnant vos noms et prénoms afin d'ouvrir la possibilité d'utiliser les caractères manquants. Les mesures de sécurité sont parfois un peu ennuyantes, veuillez nous excuser.";
            case self::ERREUR_MDP_INTERDIT:
                return "Votre mot de passe contient des caractères interdits.";
            case self::ERREUR_CODE_EMAIL_MANQUANT:
                return "Il manque le code de validation de votre email.";
            case self::ERREUR_CODE_EMAIL_DELAI_DEPASSE:
                return "Le délai de validation de votre inscription a été dépassée, veuillez renouveler votre inscription.";
            case self::ERREUR_CODE_EMAIL_INCONNU:
                return "Le code de validation de votre mail est inconnu.";
            case self::ERREUR_CODE_EMAIL_GENERIQUE:
                return "Quelque chose s'est mal passé durant la validation de votre adresse mail, veuillez réessayer.";
            case self::ERREUR_MAIL_DEJA_PRIS:
                return "Ce mail est déjà pris, vous avez donc sans doute déjà un compte actif ou une inscription en attente. Si tel n'est pas le cas, veuillez contacter l'administratrice du site.";
            case self::ERREUR_MDP_TROP_COURT:
                return "Je sais, c'est ennuyant... Mais il faut bien au moins 8 caractères pour votre mot de passe.";
            default:
                return "";
        }
    }
}
