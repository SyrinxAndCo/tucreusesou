<?php

namespace TuCreusesOu\Controller;

use TuCreusesOu\Enum\Erreurs;
use TuCreusesOu\Enum\ViewBlocks;
use TuCreusesOu\Exceptions\InscriptionCodeInconnuException;
use TuCreusesOu\Exceptions\InscriptionDelaiException;
use TuCreusesOu\Helper\Constantes;
use TuCreusesOu\Helper\Mailer;
use TuCreusesOu\Helper\ModelsHelper;
use TuCreusesOu\Model\Inscription;
use TuCreusesOu\Model\Profil;
use TuCreusesOu\View\InscriptionView;

class InscriptionController extends Controller {
    public const NOM_SESSION_TOKEN_INSCRIPTION = 'tokenInscription';
    public const NOM_SESSION_ERREUR_INSCRIPTION = 'erreurInscription';
    public const NOM_SESSION_POST_INSCRIPTION = 'postInscription';
    private Mailer $mailer;

    public function __construct(?InscriptionView $view, ?ModelsHelper $modelsHelper, ?Mailer $mailer = null) {
        if (isset($_SESSION['profil'])) {
            $this->redirect('/profil');
        }
        $this->mailer = $mailer ?? new Mailer();
        parent::__construct($view ?? new InscriptionView(), $modelsHelper);
    }

    /**
     * Formulaire d'inscription
     * @return void
     */
    public function indexAction(): void {
        $_SESSION[self::NOM_SESSION_TOKEN_INSCRIPTION] = uniqid();
        $paramsView = ['token' => $_SESSION[self::NOM_SESSION_TOKEN_INSCRIPTION]];
        if (isset($_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION])) {
            $paramsView['erreur'] = $this->getMessageErreur($_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION]);
            $paramsView['post'] = $_SESSION[self::NOM_SESSION_POST_INSCRIPTION] ?? [];
            unset($_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION]);
            unset($_SESSION[self::NOM_SESSION_POST_INSCRIPTION]);
        }
        $this->view->setTemplate(
            ViewBlocks::CONTENU,
            'inscription/inscription.twig',
            'inscriptionFormulaire',
            $paramsView
        );
        $this->view->render();
    }

    /**
     * URL de soumission du formulaire
     * @return void
     */
    public function postAction(): void {
        $_SESSION[self::NOM_SESSION_POST_INSCRIPTION] = $_POST;
        if (!isset($_POST['token']) || !isset($_SESSION[self::NOM_SESSION_TOKEN_INSCRIPTION]) || $_POST['token'] !== $_SESSION[self::NOM_SESSION_TOKEN_INSCRIPTION]) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = Erreurs::FORMULAIRE_NON_VALIDE;
            $this->redirect('/inscription');
        } elseif (!isset($_POST['nom']) || !isset($_POST['prenom']) || !isset($_POST['email']) || !isset($_POST['mdp']) || !isset($_POST['mdp2'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = Erreurs::CHAMP_MANQUANT;
            $this->redirect('/inscription');
        } elseif ($_POST['mdp'] !== $_POST['mdp2']) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = Erreurs::MOT_DE_PASSE_DIFFERENT;
            $this->redirect('/inscription');
        } elseif (!preg_match(Constantes::REGEX_EMAIL, $_POST['email'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = Erreurs::EMAIL_INVALIDE;
            $this->redirect('/inscription');
        } elseif (!preg_match(Constantes::REGEX_NOM, $_POST['nom']) || !preg_match(Constantes::REGEX_NOM, $_POST['prenom'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = Erreurs::CARACTERES_INTERDITS_NOM;
            $this->redirect('/inscription');
        } elseif (!preg_match(Constantes::REGEX_TEXT, $_POST['mdp'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = Erreurs::MDP_INTERDIT;
            $this->redirect('/inscription');
        } elseif (strlen($_POST['mdp']) < 8) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = Erreurs::MDP_TROP_COURT;
            $this->redirect('/inscription');
        } elseif ($this->modelsHelper->mailDejaPris($_POST['email'])) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = Erreurs::MAIL_DEJA_PRIS;
            $this->redirect('/inscription');
        } else {
            unset($_SESSION[self::NOM_SESSION_POST_INSCRIPTION]);
            unset($_SESSION[self::NOM_SESSION_TOKEN_INSCRIPTION]);
            $inscription = $this->modelsHelper->initInscription($_POST['nom'], $_POST['prenom'], password_hash($_POST['mdp'], PASSWORD_DEFAULT), $_POST['email']);
            $code = $inscription->sauvegarde();
            if ($code) {
                $this->mailer->envoieMailInscription($inscription->getPrenom() . ' ' . $inscription->getNom(), $inscription->getMail(), $code);
                $this->redirect('/inscription/validation');
            } else {
                $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = Erreurs::INSCRIPTION_GENERIQUE;
                $this->redirect('/inscription');
            }
        }
    }

    /**
     * Page de validation du formulaire
     * @return void
     */
    public function validationAction(): void {
        $this->view->setTemplate(
            ViewBlocks::CONTENU,
            'inscription/validation.twig',
            'inscriptionValidation'
        );
        $this->view->render();
    }

    /**
     * URL de validation de l'email
     * @param string|null $code
     * @return void
     */
    public function validermailAction(?string $code = null): void {
        if (!$code) {
            $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = Erreurs::CODE_EMAIL_MANQUANT;
            $this->redirect('/inscription');
        } else {
            try {
                if (!$this->modelsHelper->valideInscription($code)) {
                    $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = Erreurs::CODE_EMAIL_GENERIQUE;
                    $this->redirect('/inscription');
                } else {
                    $this->view->setTemplate(
                        ViewBlocks::CONTENU,
                        'inscription/email.twig',
                        'inscriptionEmailValidation'
                    );
                    $this->view->render();
                }
            } catch (InscriptionDelaiException) {
                $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = Erreurs::CODE_EMAIL_DELAI_DEPASSE;
                $this->redirect('/inscription');
            } catch (InscriptionCodeInconnuException) {
                $_SESSION[self::NOM_SESSION_ERREUR_INSCRIPTION] = Erreurs::CODE_EMAIL_INCONNU;
                $this->redirect('/inscription');
            }
        }
    }

    protected function getMessageErreur(Erreurs $erreur): string {
        return match ($erreur) {
            Erreurs::FORMULAIRE_NON_VALIDE => "Votre formulaire d'inscription était non valide, veuillez réessayer.",
            Erreurs::CHAMP_MANQUANT => "Il manque un champ requis dans votre formulaire.",
            Erreurs::MOT_DE_PASSE_DIFFERENT => "Le mot de passe entré en vérification est différent du mot de passe donné.",
            Erreurs::EMAIL_INVALIDE => "L'email fourni est invalide.",
            Erreurs::CARACTERES_INTERDITS_NOM => "Votre nom ou votre prénom contiennent des caractères interdits. Si ces caractères sont légitimes, veuillez contacter l'administratrice du site en donnant vos noms et prénoms afin d'ouvrir la possibilité d'utiliser les caractères manquants. Les mesures de sécurité sont parfois un peu ennuyantes, veuillez nous excuser.",
            Erreurs::MDP_INTERDIT => "Votre mot de passe contient des caractères interdits.",
            Erreurs::CODE_EMAIL_MANQUANT => "Il manque le code de validation de votre email.",
            Erreurs::CODE_EMAIL_DELAI_DEPASSE => "Le délai de validation de votre inscription a été dépassée, veuillez renouveler votre inscription.",
            Erreurs::CODE_EMAIL_INCONNU => "Le code de validation de votre mail est inconnu.",
            Erreurs::CODE_EMAIL_GENERIQUE => "Quelque chose s'est mal passé durant la validation de votre adresse mail, veuillez réessayer.",
            Erreurs::MAIL_DEJA_PRIS => "Ce mail est déjà pris, vous avez donc sans doute déjà un compte actif ou une inscription en attente. Si tel n'est pas le cas, veuillez contacter l'administratrice du site.",
            Erreurs::MDP_TROP_COURT => "Je sais, c'est ennuyant... Mais il faut bien au moins 8 caractères pour votre mot de passe.",
            Erreurs::INSCRIPTION_GENERIQUE => "Quelque chose s'est très mal passé... Veuillez réessayer ou contacter l'administratrice du site.",
            default => "",
        };
    }
}
