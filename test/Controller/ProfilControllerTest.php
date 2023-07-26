<?php

namespace TuCreusesOu\Test\Controller;

use DateInterval;
use DateTime;
use PHPUnit\Framework\TestCase;
use TuCreusesOu\Controller\ProfilController;
use TuCreusesOu\Enum\Erreurs;
use TuCreusesOu\Enum\ViewBlocks;
use TuCreusesOu\Helper\ModelsHelper;
use TuCreusesOu\Model\Contrat;
use TuCreusesOu\Model\Departement;
use TuCreusesOu\Model\Profil;
use TuCreusesOu\View\ProfilView;

class ProfilControllerTest extends TestCase {
    private ProfilView $view;
    private ProfilController $controller;
    private ModelsHelper $modelsHelper;
    private Profil $profil;
    private string $aujourdhui;
    private string $hier;
    private string $demain;

    public function __construct(?string $name = null, array $data = [], $dataName = '') {
        $this->aujourdhui = (new DateTime())->format('Y-m-d');
        $interval = new DateInterval('P1D');
        $demain = (new DateTime())->add($interval);
        $this->demain = $demain->format('Y-m-d');
        $hier = (new DateTime())->sub($interval);
        $this->hier = $hier->format('Y-m-d');
        parent::__construct($name, $data, $dataName);
    }

    public function setUp(): void {
        $this->profil = $this->createMock(Profil::class);
        $this->profil->expects($this->atLeastOnce())
                     ->method('getId')
                     ->willReturn(0);
        $_SESSION['profil'] = $this->profil;
        $this->view = $this->createMock(ProfilView::class);
        $this->modelsHelper = $this->createMock(ModelsHelper::class);
        $this->modelsHelper->expects($this->atLeastOnce())
                           ->method('getProfilParId')
                           ->with(0)
                           ->willReturn($this->profil);
        $this->controller = $this->getMockBuilder(ProfilController::class)
                                 ->setConstructorArgs([$this->view, $this->modelsHelper])
                                 ->onlyMethods(['redirect'])
                                 ->getMock();
        unset($_SESSION[ProfilController::NOM_SESSION_ERREUR_PROFIL]);
        unset($_SESSION[ProfilController::NOM_SESSION_TOKEN_PROFIL]);
    }

    public function testConstructorRedirectNonConnecte(): void {
        unset($_SESSION['profil']);
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/');
        $this->controller->__construct($this->view, $this->modelsHelper);
    }

    public function testConstructorRedirectProfilInconnu(): void {
        $this->modelsHelper = $this->createMock(ModelsHelper::class);
        $this->modelsHelper->expects($this->once())
                           ->method('getProfilParId')
                           ->with(0)
                           ->willReturn(null);
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/');
        $this->controller->__construct($this->view, $this->modelsHelper);
    }

    /**
     * @dataProvider erreursMessages
     * @param Erreurs|null $code
     * @param string|null $message
     * @return void
     */
    public function testConstructorErreurs(?Erreurs $code, ?string $message): void {
        if ($code) {
            $_SESSION[ProfilController::NOM_SESSION_ERREUR_PROFIL] = $code;
        }
        $this->controller->__construct($this->view, $this->modelsHelper);
        $this->assertEquals($message, $this->controller->getParamsView()['erreur'] ?? null);
    }

    public function testIndexAction(): void {
        $this->view->expects($this->once())
                   ->method('setTemplate')
                   ->with(
                       ViewBlocks::CONTENU,
                       'profil/profil.twig',
                       'profil',
                       [
                           'profil' => $this->profil
                       ]
                   );
        $this->view->expects($this->once())
                   ->method('render');
        $this->controller->indexAction();
    }

    public function testListeAmisAction(): void {
        $this->profil->expects($this->once())
                     ->method('getProfilsAmis')
                     ->willReturn([]);
        $this->profil->expects($this->once())
                     ->method('getProfilsNonAmis')
                     ->willReturn([]);
        $this->view->expects($this->once())
                   ->method('ajouteScript')
                   ->with('listeAmis.js');
        $this->view->expects($this->once())
                   ->method('setTemplate')
                   ->with(
                       ViewBlocks::CONTENU,
                       'profil/listeAmis.twig',
                       'listeAmis',
                       [
                           'token' => 'token',
                           'listeAmis' => [],
                           'listeProfilsNonAmis' => []
                       ]
                   );
        $this->view->expects($this->once())
                   ->method('render');
        $this->controller->listeAmisAction();
        $this->assertEquals('token', $_SESSION[ProfilController::NOM_SESSION_TOKEN_PROFIL]);
    }

    public function testSupprimerAction(): void {
        $this->view->expects($this->once())
                   ->method('setTemplate')
                   ->with(
                       ViewBlocks::CONTENU,
                       'profil/supprimer.twig',
                       'supprimerProfil',
                       [
                           'token' => 'token'
                       ]
                   );
        $this->view->expects($this->once())
                   ->method('render');
        $this->controller->supprimerAction();
        $this->assertEquals('token', $_SESSION[ProfilController::NOM_SESSION_TOKEN_PROFIL]);
    }

    public function testEditerAction(): void {
        $this->modelsHelper->expects($this->once())
                           ->method('getTousDepartements')
                           ->willReturn([]);
        $this->view->expects($this->once())
                   ->method('setTemplate')
                   ->with(
                       ViewBlocks::CONTENU,
                       'profil/editer.twig',
                       'editerProfil',
                       [
                           'token' => 'token',
                           'profil' => $this->profil,
                           'listeDepartements' => []
                       ]
                   );
        $this->view->expects($this->once())
                   ->method('render');
        $this->controller->editerAction();
        $this->assertEquals('token', $_SESSION[ProfilController::NOM_SESSION_TOKEN_PROFIL]);
    }

    public function testRechercheAction(): void {
        $recherche = 'recherche';
        $this->profil->expects($this->once())
                     ->method('getProfilsNonAmis')
                     ->with($recherche)
                     ->willReturn([]);
        $this->view->expects($this->once())
                   ->method('renderPart')
                   ->with(
                       'profil/listeNonAmis.twig',
                       [
                           'listeProfilsNonAmis' => []
                       ]
                   );
        $this->controller->rechercheAction($recherche);
    }

    /**
     * @dataProvider postTests
     * @param array $post
     * @param Erreurs $code
     * @param string $redirect
     * @return void
     */
    public function testPostAction(array $post, Erreurs $code, string $redirect): void {
        $_POST = $post;
        $_SESSION[ProfilController::NOM_SESSION_TOKEN_PROFIL] = 'token';
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with($redirect);
        $this->profil->expects($this->any())
                     ->method('getMdp')
                     ->willReturn(password_hash('mdp', PASSWORD_DEFAULT));
        $this->controller->postAction();
        $this->assertEquals($code, $_SESSION[ProfilController::NOM_SESSION_ERREUR_PROFIL]);
    }

    public function testPostActionSuprimerAmi(): void {
        $_POST = [
            'token' => 'token',
            'supprimerAmi' => '0'
        ];
        $_SESSION[ProfilController::NOM_SESSION_TOKEN_PROFIL] = 'token';
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/profil/listeAmis');
        $this->profil->expects($this->once())
                     ->method('retireAmi')
                     ->with('0');
        $this->controller->postAction();
        $this->assertNull($_SESSION[ProfilController::NOM_SESSION_ERREUR_PROFIL] ?? null);
    }

    public function testPostActionAjouterAmi(): void {
        $_POST = [
            'token' => 'token',
            'ajouterAmi' => '0'
        ];
        $_SESSION[ProfilController::NOM_SESSION_TOKEN_PROFIL] = 'token';
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/profil/listeAmis');
        $this->profil->expects($this->once())
                     ->method('ajouteAmi')
                     ->with('0');
        $this->controller->postAction();
        $this->assertNull($_SESSION[ProfilController::NOM_SESSION_ERREUR_PROFIL] ?? null);
    }

    public function testPostActionSupprimerProfil(): void {
        $_POST = [
            'token' => 'token',
            'supprimerProfil' => true,
            'mdp' => 'mdp'
        ];
        $_SESSION[ProfilController::NOM_SESSION_TOKEN_PROFIL] = 'token';
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/');
        $this->profil->expects($this->once())
                     ->method('getMdp')
                     ->willReturn(password_hash('mdp', PASSWORD_DEFAULT));
        $this->profil->expects($this->once())
                     ->method('supprime');
        $this->controller->postAction();
        $this->assertNull($_SESSION[ProfilController::NOM_SESSION_ERREUR_PROFIL] ?? null);
        $this->assertNull($_SESSION);
    }

    public function testPostActionSupprimerContrat(): void {
        $_POST = [
            'token' => 'token',
            'supprimerContrat' => true
        ];
        $_SESSION[ProfilController::NOM_SESSION_TOKEN_PROFIL] = 'token';
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/profil');
        $contrat = $this->createMock(Contrat::class);
        $contrat->expects($this->once())
                ->method('supprime');
        $this->profil->expects($this->once())
                     ->method('getContrat')
                     ->willReturn($contrat);
        $this->controller->postAction();
        $this->assertNull($_SESSION[ProfilController::NOM_SESSION_ERREUR_PROFIL] ?? null);
    }

    /**
     * @dataProvider postEditerTests
     * @param array $post
     * @param Erreurs $code
     * @return void
     */
    public function testPostActionEditerProfil(array $post, Erreurs $code): void {
        $_POST = $post;
        $_SESSION[ProfilController::NOM_SESSION_TOKEN_PROFIL] = 'token';
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/profil/editer');
        $this->modelsHelper->expects($this->any())
                           ->method('existeDepartementId')
                           ->with(0)
                           ->willReturn(false);
        $this->controller->postAction();
        $this->assertEquals($code, $_SESSION[ProfilController::NOM_SESSION_ERREUR_PROFIL]);
    }

    /**
     * @dataProvider postReussiProfil
     * @param array $post
     * @param bool $newsletter
     * @return void
     */
    public function testPostActionEditerProfilReussi(array $post, bool $newsletter): void {
        $_POST = $post;
        $_SESSION[ProfilController::NOM_SESSION_TOKEN_PROFIL] = 'token';
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/profil');
        $this->profil->expects($this->once())
                     ->method('setMdp');
        $this->profil->expects($this->once())
                     ->method('setNom')
                     ->with($_POST['nom']);
        $this->profil->expects($this->once())
                     ->method('setPrenom')
                     ->with($_POST['prenom']);
        $this->profil->expects($this->once())
                     ->method('setDescription')
                     ->with($_POST['description']);
        $this->profil->expects($this->once())
                     ->method('setNewsletter')
                     ->with($newsletter);
        $contrat = $this->createMock(Contrat::class);
        $contrat->expects($this->once())
                ->method('setDateDebut')
                ->with(strtotime($_POST['dateDebut']));
        $contrat->expects($this->once())
                ->method('setDateFin')
                ->with(strtotime($_POST['dateFin']));
        $this->modelsHelper->expects($this->once())
                           ->method('existeDepartementId')
                           ->with($_POST['idDepartement'])
                           ->willReturn(true);
        $departement = $this->createMock(Departement::class);
        $this->modelsHelper->expects($this->once())
                           ->method('getDepartementParId')
                           ->with($_POST['idDepartement'])
                           ->willReturn($departement);
        $contrat->expects($this->once())
                ->method('setDepartement')
                ->with($departement);
        $contrat->expects($this->once())
                ->method('setEnActivite')
                ->with(true);
        $contrat->expects($this->once())
                ->method('sauvegarde');
        $this->profil->expects($this->once())
                     ->method('getContrat')
                     ->willReturn($contrat);
        $this->profil->expects($this->once())
                     ->method('sauvegarde');
        $this->controller->postAction();
        $this->assertNull($_SESSION[ProfilController::NOM_SESSION_ERREUR_PROFIL] ?? null);
    }

    private function erreursMessages(): array {
        return [
            [Erreurs::FORMULAIRE_NON_VALIDE, "Votre formulaire était non valide, veuillez réessayer."],
            [Erreurs::IDENTIFIANT_AMI_INCONNU, "L'identifiant fourni pour la suppression de l'ami est inconnu."],
            [Erreurs::MAUVAIS_MDP, "Le mot de passe renseigné n'est pas le bon."],
            [Erreurs::CHAMP_MDP_MANQUANT, "Il faut confirmer votre mot de passe pour le modifier."],
            [Erreurs::MOT_DE_PASSE_DIFFERENT, "Le mot de passe de confirmation est différent du mot de passe fourni."],
            [Erreurs::MDP_INTERDIT, "Votre mot de passe contient des caractères interdits."],
            [Erreurs::MDP_TROP_COURT, "Je sais, c'est ennuyant... Mais il faut bien au moins 8 caractères pour votre mot de passe."],
            [Erreurs::CARACTERES_INTERDITS_NOM, "Votre nom ou votre prénom contiennent des caractères interdits. Si ces caractères sont légitimes, veuillez contacter l'administratrice du site en donnant vos noms et prénoms afin d'ouvrir la possibilité d'utiliser les caractères manquants. Les mesures de sécurité sont parfois un peu ennuyantes, veuillez nous excuser."],
            [Erreurs::CARACTERES_INTERDITS_DESCRIPTION, "Votre description contient des caractères interdits."],
            [Erreurs::DESCRIPTION_TROP_LONGUE, "Votre description ne doit pas dépasser 300 caractères."],
            [Erreurs::DATE_INVALIDE, "L'une des dates renseignées est dans un format invalide."],
            [Erreurs::CDD_SANS_FIN, "Si vous ne cochez pas la case CDI, vous devez renseigner une date de fin de contrat."],
            [Erreurs::DATE_FIN_PASSEE, "Vous ne pouvez pas renseigner une date de fin de contrat dans le passé."],
            [Erreurs::CHAMP_MANQUANT, "Vous devez renseigner un département pour votre contrat."],
            [Erreurs::DEPARTEMENT_INCONNU, "Le département renseigné est inconnu."],
            [Erreurs::INSCRIPTION_GENERIQUE, ""],
            [null, null]
        ];
    }

    private function postTests(): array {
        return [
            [
                [],
                Erreurs::FORMULAIRE_NON_VALIDE,
                '/profil'
            ],
            [
                [
                    'token' => 'mauvais'
                ],
                Erreurs::FORMULAIRE_NON_VALIDE,
                '/profil'
            ],
            [
                [
                    'token' => 'token',
                    'supprimerAmi' => 'mauvaiseValeur'
                ],
                Erreurs::IDENTIFIANT_AMI_INCONNU,
                '/profil/listeAmis'
            ],
            [
                [
                    'token' => 'token',
                    'ajouterAmi' => 'mauvaiseValeur'
                ],
                Erreurs::IDENTIFIANT_AMI_INCONNU,
                '/profil/listeAmis'
            ],
            [
                [
                    'token' => 'token',
                    'supprimerProfil' => true
                ],
                Erreurs::MAUVAIS_MDP,
                '/profil/supprimer'
            ],
            [
                [
                    'token' => 'token',
                    'supprimerProfil' => true,
                    'mdp' => 'mauvaismdp'
                ],
                Erreurs::MAUVAIS_MDP,
                '/profil/supprimer'
            ]
        ];
    }

    private function postEditerTests(): array {
        return [
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'mdp' => 'mdp'
                ],
                Erreurs::CHAMP_MDP_MANQUANT
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'mdp' => 'mdp',
                    'mdp2' => 'mdp2'
                ],
                Erreurs::MOT_DE_PASSE_DIFFERENT
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'mdp' => 'mdp>',
                    'mdp2' => 'mdp>'
                ],
                Erreurs::MDP_INTERDIT
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'mdp' => 'mdp',
                    'mdp2' => 'mdp'
                ],
                Erreurs::MDP_TROP_COURT
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'nom' => 'nom>'
                ],
                Erreurs::CARACTERES_INTERDITS_NOM
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'prenom' => 'prenom>'
                ],
                Erreurs::CARACTERES_INTERDITS_NOM
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'description' => 'nom>'
                ],
                Erreurs::CARACTERES_INTERDITS_DESCRIPTION
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'description' => str_repeat('a', 301)
                ],
                Erreurs::DESCRIPTION_TROP_LONGUE
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'dateDebut' => 'az'
                ],
                Erreurs::DATE_INVALIDE
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'dateDebut' => '0000-00-00'
                ],
                Erreurs::DATE_INVALIDE
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'dateDebut' => $this->aujourdhui,
                    'enActivite' => true
                ],
                Erreurs::CDD_SANS_FIN
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'dateDebut' => $this->aujourdhui,
                    'enActivite' => true,
                    'dateFin' => 'aa'
                ],
                Erreurs::DATE_INVALIDE
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'dateDebut' => $this->aujourdhui,
                    'enActivite' => true,
                    'dateFin' => '0000-00-00'
                ],
                Erreurs::DATE_INVALIDE
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'dateDebut' => $this->aujourdhui,
                    'enActivite' => true,
                    'dateFin' => $this->hier
                ],
                Erreurs::DATE_FIN_PASSEE
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'dateDebut' => $this->aujourdhui,
                    'enActivite' => true,
                    'dateFin' => $this->demain
                ],
                Erreurs::CHAMP_MANQUANT
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'dateDebut' => $this->aujourdhui,
                    'cdi' => true,
                    'idDepartement' => 'non_numerique'
                ],
                Erreurs::DEPARTEMENT_INCONNU
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'dateDebut' => $this->aujourdhui,
                    'cdi' => true,
                    'idDepartement' => 0
                ],
                Erreurs::DEPARTEMENT_INCONNU
            ]
        ];
    }

    private function postReussiProfil(): array {
        return [
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'mdp' => 'motdepasse',
                    'mdp2' => 'motdepasse',
                    'nom' => 'nom',
                    'prenom' => 'prenom',
                    'description' => 'description',
                    'dateDebut' => $this->aujourdhui,
                    'dateFin' => $this->demain,
                    'enActivite' => true,
                    'idDepartement' => 0,
                    'newsletter' => true,
                ],
                true
            ],
            [
                [
                    'token' => 'token',
                    'editerProfil' => true,
                    'mdp' => 'motdepasse',
                    'mdp2' => 'motdepasse',
                    'nom' => 'nom',
                    'prenom' => 'prenom',
                    'description' => 'description',
                    'dateDebut' => $this->aujourdhui,
                    'dateFin' => $this->demain,
                    'enActivite' => true,
                    'idDepartement' => 0
                ],
                false
            ]
        ];
    }
}