<?php

namespace TuCreusesOu\Controller;

function uniqid(): string {
    return 'token';
}

namespace TuCreusesOu\Test\Controller;

use PHPUnit\Framework\TestCase;
use TuCreusesOu\Controller\IndexController;
use TuCreusesOu\Enum\Erreurs;
use TuCreusesOu\Enum\ViewBlocks;
use TuCreusesOu\Helper\ModelsHelper;
use TuCreusesOu\Model\Contrat;
use TuCreusesOu\Model\Departement;
use TuCreusesOu\Model\Profil;
use TuCreusesOu\View\IndexView;

class IndexControllerTest extends TestCase {
    private IndexView $view;
    private IndexController $controller;
    private ModelsHelper $modelsHelper;
    private Profil $defaultProfil;

    public function __construct(?string $name = null, array $data = [], $dataName = '') {
        $this->defaultProfil = new Profil(
            'nom',
            'prenom',
            password_hash('mdp', PASSWORD_DEFAULT),
            'mail',
            [],
            'description',
            new Contrat(
                0,
                0,
                0,
                new Departement(
                    0,
                    'numero',
                    'nom'
                ),
                true,
                0
            ),
            0
        );
        parent::__construct($name, $data, $dataName);
    }

    public function setUp(): void {
        $this->view = $this->createMock(IndexView::class);
        $this->modelsHelper = $this->createMock(ModelsHelper::class);
        $this->controller = $this->getMockBuilder(IndexController::class)
                                 ->setConstructorArgs([$this->view, $this->modelsHelper])
                                 ->onlyMethods(['redirect'])
                                 ->getMock();
        unset($_SESSION[IndexController::NOM_SESSION_ERREUR_CONNEXION]);
        unset($_SESSION['profil']);
    }

    /**
     * Test redirection du constructeur
     * @return void
     */
    public function testConstructRedirect(): void {
        $_SESSION['profil'] = $this->defaultProfil;
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/profil');
        $this->controller->__construct($this->view, $this->modelsHelper);

    }

    /**
     * Action index simple
     * @return void
     */
    public function testIndexAction(): void {
        $this->view->expects($this->once())
                   ->method('setTemplate')
                   ->with(
                       ViewBlocks::CONTENU,
                       'connexion.twig',
                       'connexion',
                       [
                           'token' => 'token'
                       ]
                   );
        $this->view->expects($this->once())
                   ->method('render');
        $this->controller->indexAction();
        $this->assertEquals('token', $_SESSION[IndexController::NOM_SESSION_TOKEN_CONNEXION]);
    }

    /**
     * Action index avec des erreurs
     * @dataProvider erreursMessages
     * @param Erreurs $code
     * @param string $message
     * @return void
     */
    public function testIndexActionWithError(Erreurs $code, string $message): void {
        $_SESSION[IndexController::NOM_SESSION_ERREUR_CONNEXION] = $code;
        $this->view->expects($this->once())
                   ->method('setTemplate')
                   ->with(
                       ViewBlocks::CONTENU,
                       'connexion.twig',
                       'connexion',
                       [
                           'token' => 'token',
                           'erreur' => $message
                       ]
                   );
        $this->view->expects($this->once())
                   ->method('render');
        $this->controller->indexAction();
        $this->assertEquals('token', $_SESSION[IndexController::NOM_SESSION_TOKEN_CONNEXION]);
        $this->assertNull($_SESSION[IndexController::NOM_SESSION_ERREUR_CONNEXION] ?? null);
    }

    /**
     * Action post
     * @dataProvider postsCasesNoMail
     * @param array $post
     * @param Erreurs|null $erreur
     * @return void
     */
    public function testPostActionNoMail(array $post, ?Erreurs $erreur): void {
        $_SESSION[IndexController::NOM_SESSION_TOKEN_CONNEXION] = 'token';
        $_POST = $post;
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/');
        $this->controller->postAction();
        $this->assertEquals($erreur, $_SESSION[IndexController::NOM_SESSION_ERREUR_CONNEXION] ?? null);
    }

    /**
     * Action post
     * @dataProvider postsCasesMail
     * @param array $post
     * @param Erreurs|null $erreur
     * @return void
     */
    public function testPostActionMail(array $post, ?Erreurs $erreur): void {
        $_SESSION[IndexController::NOM_SESSION_TOKEN_CONNEXION] = 'token';
        $_POST = $post;
        $this->modelsHelper->expects($this->once())
                           ->method('getProfilParMail')
                           ->with('mail@mail.com')
                           ->willReturn($erreur === Erreurs::EMAIL_INCONNU ? null : $this->defaultProfil);
        if ($erreur) {
            $this->controller->expects($this->once())
                             ->method('redirect')
                             ->with('/');
        } else {
            $this->controller->expects($this->once())
                             ->method('redirect')
                             ->with('/profil');
        }
        $this->controller->postAction();
        $this->assertEquals($erreur, $_SESSION[IndexController::NOM_SESSION_ERREUR_CONNEXION] ?? null);
        if (!$erreur) {
            $this->assertEquals($this->defaultProfil, $_SESSION['profil']);
        }
    }

    /**
     * Les différentes erreurs prises en charge
     * @return array[]
     */
    public function erreursMessages(): array {
        return [
            [Erreurs::FORMULAIRE_NON_VALIDE, "Votre formulaire de connexion était invalide, veuillez réessayer."],
            [Erreurs::EMAIL_INVALIDE, "L'email de connexion fourni est invalide."],
            [Erreurs::CHAMP_MANQUANT, "Il manque un champ requis dans votre formulaire de connexion."],
            [Erreurs::EMAIL_INCONNU, "La combinaison EMail/Mot de passe est inconnue."],
            [Erreurs::MAUVAIS_MDP, "La combinaison EMail/Mot de passe est inconnue."]
        ];
    }

    /**
     * Les différents cas de posts gérés (sans l'accès par mail)
     * @return array
     */
    public function postsCasesNoMail(): array {
        return [
            [
                [],
                Erreurs::FORMULAIRE_NON_VALIDE
            ],
            [
                ['token' => '0'],
                Erreurs::FORMULAIRE_NON_VALIDE
            ],
            [
                ['token' => 'token'],
                Erreurs::CHAMP_MANQUANT
            ],
            [
                [
                    'token' => 'token',
                    'email' => 'mail'
                ],
                Erreurs::CHAMP_MANQUANT
            ],
            [
                [
                    'token' => 'token',
                    'email' => 'mail',
                    'mdp' => 'mdp'
                ],
                Erreurs::EMAIL_INVALIDE
            ]
        ];
    }

    /**
     * Les différents cas de posts gérés (avec l'accès par mail)
     * @return array
     */
    public function postsCasesMail(): array {
        return [
            [
                [
                    'token' => 'token',
                    'email' => 'mail@mail.com',
                    'mdp' => 'mdp'
                ],
                Erreurs::EMAIL_INCONNU
            ],
            [
                [
                    'token' => 'token',
                    'email' => 'mail@mail.com',
                    'mdp' => 'mauvais'
                ],
                Erreurs::MAUVAIS_MDP
            ],
            [
                [
                    'token' => 'token',
                    'email' => 'mail@mail.com',
                    'mdp' => 'mdp'
                ],
                null
            ]
        ];
    }
}