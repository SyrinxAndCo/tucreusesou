<?php

namespace TuCreusesOu\Controller;

use TuCreusesOu\Enum\Erreurs;
use TuCreusesOu\Enum\ViewBlocks;
use TuCreusesOu\Helper\Constantes;
use TuCreusesOu\Helper\ModelsHelper;
use TuCreusesOu\Model\Contrat;
use TuCreusesOu\Model\Departement;
use TuCreusesOu\Model\Profil;
use TuCreusesOu\View\ProfilView;

class ProfilController extends Controller {
    private ?Profil $profil;
    private array $paramsView = [];
    public const NOM_SESSION_TOKEN_PROFIL = 'tokenProfil';
    public const NOM_SESSION_ERREUR_PROFIL = 'erreurProfil';

    public function __construct(?ProfilView $view, ?ModelsHelper $modelsHelper) {
        parent::__construct($view ?? new ProfilView(), $modelsHelper);
        if (!isset($_SESSION['profil'])) {
            $this->redirect('/');
        } else {
            $this->profil = $this->modelsHelper->getProfilParId($_SESSION['profil']->getId());
            if ($this->profil === null) {
                $this->redirect('/');
            } else {
                if (isset($_SESSION[self::NOM_SESSION_ERREUR_PROFIL])) {
                    $this->paramsView['erreur'] = $this->getMessageErreur($_SESSION[self::NOM_SESSION_ERREUR_PROFIL]);
                    unset($_SESSION[self::NOM_SESSION_ERREUR_PROFIL]);
                }
            }
        }
    }

    /**
     * Page du profil
     * @return void
     */
    public function indexAction(): void {
        $this->paramsView['profil'] = $this->profil;
        $this->view->setTemplate(
            ViewBlocks::CONTENU,
            'profil/profil.twig',
            'profil',
            $this->paramsView
        );
        $this->view->render();
    }

    /**
     * Page listant tous les amis du profil
     * @return void
     */
    public function listeAmisAction(): void {
        $_SESSION[self::NOM_SESSION_TOKEN_PROFIL] = uniqid();
        $this->paramsView = [
            'token' => $_SESSION[self::NOM_SESSION_TOKEN_PROFIL],
            'listeAmis' => $this->profil->getProfilsAmis(),
            'listeProfilsNonAmis' => $this->profil->getProfilsNonAmis()
        ];
        $this->view->ajouteScript('listeAmis.js');
        $this->view->setTemplate(
            ViewBlocks::CONTENU,
            'profil/listeAmis.twig',
            'listeAmis',
            $this->paramsView
        );
        $this->view->render();
    }

    /**
     * Page de soumission des formulaires
     * @return void
     */
    public function postAction(): void {
        if (!isset($_POST['token']) || !isset($_SESSION[self::NOM_SESSION_TOKEN_PROFIL]) || $_POST['token'] !== $_SESSION[self::NOM_SESSION_TOKEN_PROFIL]) {
            $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::FORMULAIRE_NON_VALIDE;
            $this->redirect('/profil');
        } elseif (isset($_POST['supprimerAmi'])) {
            if (!preg_match('/\d+/', $_POST['supprimerAmi'])) {
                $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::IDENTIFIANT_AMI_INCONNU;
            } else {
                $this->profil->retireAmi($_POST['supprimerAmi']);
            }
            $this->redirect('/profil/listeAmis');
        } elseif (isset($_POST['ajouterAmi'])) {
            if (!preg_match('/\d+/', $_POST['ajouterAmi'])) {
                $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::IDENTIFIANT_AMI_INCONNU;
            } else {
                $this->profil->ajouteAmi($_POST['ajouterAmi']);
            }
            $this->redirect('/profil/listeAmis');
        } elseif (isset($_POST['supprimerProfil'])) {
            if (!isset($_POST['mdp']) || !password_verify($_POST['mdp'], $this->profil->getMdp())) {
                $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::MAUVAIS_MDP;
                $this->redirect('/profil/supprimer');
            } else {
                $this->profil->supprime();
                session_destroy();
                $this->redirect('/');
            }
        } elseif (isset($_POST['supprimerContrat'])) {
            $this->profil->getContrat()?->supprime();
            $this->redirect('/profil');
        } elseif (isset($_POST['editerProfil'])) {
            if (isset($_POST['mdp']) && $_POST['mdp'] !== "") {
                if (!isset($_POST['mdp2'])) {
                    $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::CHAMP_MDP_MANQUANT;
                    $this->redirect('/profil/editer');
                } elseif ($_POST['mdp'] !== $_POST['mdp2']) {
                    $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::MOT_DE_PASSE_DIFFERENT;
                    $this->redirect('/profil/editer');
                } elseif (!preg_match(Constantes::REGEX_TEXT, $_POST['mdp'])) {
                    $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::MDP_INTERDIT;
                    $this->redirect('/profil/editer');
                } elseif (strlen($_POST['mdp']) < 8) {
                    $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::MDP_TROP_COURT;
                    $this->redirect('/profil/editer');
                } else {
                    $this->profil->setMdp(password_hash($_POST['mdp'], PASSWORD_DEFAULT));
                }
            }
            if (isset($_POST['nom']) && $_POST['nom'] !== "") {
                if (!preg_match(Constantes::REGEX_NOM, $_POST['nom'])) {
                    $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::CARACTERES_INTERDITS_NOM;
                    $this->redirect('/profil/editer');
                } else {
                    $this->profil->setNom($_POST['nom']);
                }
            }
            if (isset($_POST['prenom']) && $_POST['prenom'] !== "") {
                if (!preg_match(Constantes::REGEX_NOM, $_POST['prenom'])) {
                    $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::CARACTERES_INTERDITS_NOM;
                    $this->redirect('/profil/editer');
                } else {
                    $this->profil->setPrenom($_POST['prenom']);
                }
            }
            if (isset($_POST['description']) && $_POST['description'] !== "") {
                if (!preg_match(Constantes::REGEX_TEXT, $_POST['description'])) {
                    $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::CARACTERES_INTERDITS_DESCRIPTION;
                    $this->redirect('/profil/editer');
                } elseif (strlen($_POST['description']) > 300) {
                    $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::DESCRIPTION_TROP_LONGUE;
                    $this->redirect('/profil/editer');
                } else {
                    $this->profil->setDescription($_POST['description']);
                }
            }
            if (isset($_POST['dateDebut']) && $_POST['dateDebut'] !== "") {
                if (!preg_match(Constantes::REGEX_DATE, $_POST['dateDebut']) || strtotime($_POST['dateDebut']) < 0) {
                    $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::DATE_INVALIDE;
                    $this->redirect('/profil/editer');
                } else {
                    $dateDebut = strtotime($_POST['dateDebut']);
                    if (!isset($_POST['cdi']) && isset($_POST['enActivite'])) {
                        if (!isset($_POST['dateFin'])) {
                            $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::CDD_SANS_FIN;
                            $this->redirect('/profil/editer');
                        } elseif (!preg_match(Constantes::REGEX_DATE, $_POST['dateFin']) || strtotime($_POST['dateFin']) < 0) {
                            $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::DATE_INVALIDE;
                            $this->redirect('/profil/editer');
                        } else {
                            $dateFin = strtotime($_POST['dateFin']);
                            if ($dateFin < time()) {
                                $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::DATE_FIN_PASSEE;
                                $this->redirect('/profil/editer');
                            }
                        }
                    } else {
                        $dateFin = null;
                    }
                    if (!isset($_SESSION[self::NOM_SESSION_ERREUR_PROFIL])) {
                        if (!isset($_POST['idDepartement'])) {
                            $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::CHAMP_MANQUANT;
                            $this->redirect('/profil/editer');
                        } elseif (!is_numeric($_POST['idDepartement']) || !$this->modelsHelper->existeDepartementId($_POST['idDepartement'])) {
                            $_SESSION[self::NOM_SESSION_ERREUR_PROFIL] = Erreurs::DEPARTEMENT_INCONNU;
                            $this->redirect('/profil/editer');
                        } else {
                            $contrat = $this->profil->getContrat() ?? new Contrat($this->profil->getId(), 0, null, new Departement(-1, '', ''), true);
                            $contrat->setDateDebut($dateDebut);
                            $contrat->setDateFin($dateFin);
                            $contrat->setDepartement($this->modelsHelper->getDepartementParId($_POST['idDepartement']));
                            $contrat->setEnActivite(isset($_POST['enActivite']));
                            $contrat->sauvegarde();
                        }
                    }
                }
            }
            if (isset($_POST['newsletter'])) {
                $this->profil->setNewsletter(true);
            } else {
                $this->profil->setNewsletter(false);
            }
            if (!isset($_SESSION[self::NOM_SESSION_ERREUR_PROFIL])) {
                $this->profil->sauvegarde();
                $this->redirect('/profil');
            }
        }
    }

    /**
     * Page de suppression de profil
     * @return void
     */
    public function supprimerAction(): void {
        $_SESSION[self::NOM_SESSION_TOKEN_PROFIL] = uniqid();
        $this->paramsView['token'] = $_SESSION[self::NOM_SESSION_TOKEN_PROFIL];
        $this->view->setTemplate(
            ViewBlocks::CONTENU,
            'profil/supprimer.twig',
            'supprimerProfil',
            $this->paramsView
        );
        $this->view->render();
    }

    /**
     * Page de modification du profil
     * @return void
     */
    public function editerAction(): void {
        $_SESSION[self::NOM_SESSION_TOKEN_PROFIL] = uniqid();
        $this->paramsView['token'] = $_SESSION[self::NOM_SESSION_TOKEN_PROFIL];
        $this->paramsView['profil'] = $this->profil;
        $this->paramsView['listeDepartements'] = $this->modelsHelper->getTousDepartements();
        $this->view->setTemplate(
            ViewBlocks::CONTENU,
            'profil/editer.twig',
            'editerProfil',
            $this->paramsView
        );
        $this->view->render();
    }

    /**
     * Système de recherche
     * @param string $recherche
     * @return void
     */
    public function rechercheAction(string $recherche = ""): void {
        $this->paramsView = [
            'listeProfilsNonAmis' => $this->profil->getProfilsNonAmis($recherche)
        ];
        $this->view->renderPart(
            'profil/listeNonAmis.twig',
            $this->paramsView
        );
    }

    protected function getMessageErreur(Erreurs $erreur): string {
        return match ($erreur) {
            Erreurs::FORMULAIRE_NON_VALIDE => "Votre formulaire était non valide, veuillez réessayer.",
            Erreurs::IDENTIFIANT_AMI_INCONNU => "L'identifiant fourni pour la suppression de l'ami est inconnu.",
            Erreurs::MAUVAIS_MDP => "Le mot de passe renseigné n'est pas le bon.",
            Erreurs::CHAMP_MDP_MANQUANT => "Il faut confirmer votre mot de passe pour le modifier.",
            Erreurs::MOT_DE_PASSE_DIFFERENT => "Le mot de passe de confirmation est différent du mot de passe fourni.",
            Erreurs::MDP_INTERDIT => "Votre mot de passe contient des caractères interdits.",
            Erreurs::MDP_TROP_COURT => "Je sais, c'est ennuyant... Mais il faut bien au moins 8 caractères pour votre mot de passe.",
            Erreurs::CARACTERES_INTERDITS_NOM => "Votre nom ou votre prénom contiennent des caractères interdits. Si ces caractères sont légitimes, veuillez contacter l'administratrice du site en donnant vos noms et prénoms afin d'ouvrir la possibilité d'utiliser les caractères manquants. Les mesures de sécurité sont parfois un peu ennuyantes, veuillez nous excuser.",
            Erreurs::CARACTERES_INTERDITS_DESCRIPTION => "Votre description contient des caractères interdits.",
            Erreurs::DESCRIPTION_TROP_LONGUE => "Votre description ne doit pas dépasser 300 caractères.",
            Erreurs::DATE_INVALIDE => "L'une des dates renseignées est dans un format invalide.",
            Erreurs::CDD_SANS_FIN => "Si vous ne cochez pas la case CDI, vous devez renseigner une date de fin de contrat.",
            Erreurs::DATE_FIN_PASSEE => "Vous ne pouvez pas renseigner une date de fin de contrat dans le passé.",
            Erreurs::CHAMP_MANQUANT => "Vous devez renseigner un département pour votre contrat.",
            Erreurs::DEPARTEMENT_INCONNU => "Le département renseigné est inconnu.",
            default => ""
        };
    }

    /**
     * Renvoie les paramètres passés à la vue
     * @return array
     */
    public function getParamsView(): array {
        return $this->paramsView;
    }
}