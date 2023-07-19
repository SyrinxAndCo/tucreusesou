<?php

namespace TuCreusesOu\Test\Controller;

use PHPUnit\Framework\TestCase;
use TuCreusesOu\Controller\InscriptionController;
use TuCreusesOu\Enum\Erreurs;
use TuCreusesOu\Enum\ViewBlocks;
use TuCreusesOu\Exceptions\InscriptionCodeInconnuException;
use TuCreusesOu\Exceptions\InscriptionDelaiException;
use TuCreusesOu\Helper\Mailer;
use TuCreusesOu\Helper\ModelsHelper;
use TuCreusesOu\Model\Inscription;
use TuCreusesOu\View\InscriptionView;

class InscriptionControllerTest extends TestCase {
    private InscriptionView $view;
    private InscriptionController $controller;
    private ModelsHelper $modelsHelper;
    private Mailer $mailer;
    private const GOOD_POST = [
        'token' => 'token',
        'nom' => 'nom',
        'prenom' => 'prenom',
        'email' => 'mail@mail.com',
        'mdp' => 'motdepasse',
        'mdp2' => 'motdepasse'
    ];

    public function setUp(): void {
        $this->view = $this->createMock(InscriptionView::class);
        $this->modelsHelper = $this->createMock(ModelsHelper::class);
        $this->mailer = $this->createMock(Mailer::class);
        $this->controller = $this->getMockBuilder(InscriptionController::class)
                                 ->setConstructorArgs([$this->view, $this->modelsHelper, $this->mailer])
                                 ->onlyMethods(['redirect'])
                                 ->getMock();
        unset($_SESSION[InscriptionController::NOM_SESSION_POST_INSCRIPTION]);
        unset($_SESSION[InscriptionController::NOM_SESSION_ERREUR_INSCRIPTION]);
        unset($_SESSION[InscriptionController::NOM_SESSION_TOKEN_INSCRIPTION]);
        unset($_SESSION['profil']);
    }

    public function testConstructorRedirect(): void {
        $_SESSION['profil'] = 'ok';
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/profil');
        $this->controller->__construct($this->view, $this->modelsHelper, $this->mailer);
    }

    public function testIndexAction(): void {
        $this->view->expects($this->once())
                   ->method('setTemplate')
                   ->with(
                       ViewBlocks::CONTENU,
                       'inscription/inscription.twig',
                       'inscriptionFormulaire',
                       [
                           'token' => 'token'
                       ]
                   );
        $this->view->expects($this->once())
                   ->method('render');
        $this->controller->indexAction();
        $this->assertEquals('token', $_SESSION[InscriptionController::NOM_SESSION_TOKEN_INSCRIPTION]);
    }

    /**
     * @dataProvider erreursMessages
     * @param Erreurs $code
     * @param string $message
     * @return void
     */
    public function testIndexActionWithError(Erreurs $code, string $message): void {
        $_SESSION[InscriptionController::NOM_SESSION_ERREUR_INSCRIPTION] = $code;
        $_SESSION[InscriptionController::NOM_SESSION_POST_INSCRIPTION] = 'post';
        $this->view->expects($this->once())
                   ->method('setTemplate')
                   ->with(
                       ViewBlocks::CONTENU,
                       'inscription/inscription.twig',
                       'inscriptionFormulaire',
                       [
                           'token' => 'token',
                           'erreur' => $message,
                           'post' => 'post'
                       ]
                   );
        $this->view->expects($this->once())
                   ->method('render');
        $this->controller->indexAction();
        $this->assertEquals('token', $_SESSION[InscriptionController::NOM_SESSION_TOKEN_INSCRIPTION]);
        $this->assertNull($_SESSION[InscriptionController::NOM_SESSION_ERREUR_INSCRIPTION] ?? null);
        $this->assertNull($_SESSION[InscriptionController::NOM_SESSION_POST_INSCRIPTION] ?? null);
    }

    public function testValidationAction(): void {
        $this->view->expects($this->once())
                   ->method('setTemplate')
                   ->with(
                       ViewBlocks::CONTENU,
                       'inscription/validation.twig',
                       'inscriptionValidation'
                   );
        $this->view->expects($this->once())
                   ->method('render');
        $this->controller->validationAction();
    }

    public function testValidermailActionSansCode(): void {
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/inscription');
        $this->controller->validermailAction();
        $this->assertEquals(Erreurs::CODE_EMAIL_MANQUANT, $_SESSION[InscriptionController::NOM_SESSION_ERREUR_INSCRIPTION]);
    }

    public function testValidermailActionErreurCodeInconnue(): void {
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/inscription');
        $this->modelsHelper->expects($this->once())
                           ->method('valideInscription')
                           ->with('code')
                           ->willReturn(false);
        $this->controller->validermailAction('code');
        $this->assertEquals(Erreurs::CODE_EMAIL_GENERIQUE, $_SESSION[InscriptionController::NOM_SESSION_ERREUR_INSCRIPTION]);
    }

    public function testValidermailActionErreurCodeInvalide(): void {
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/inscription');
        $this->modelsHelper->expects($this->once())
                           ->method('valideInscription')
                           ->with('code')
                           ->willThrowException(new InscriptionCodeInconnuException());
        $this->controller->validermailAction('code');
        $this->assertEquals(Erreurs::CODE_EMAIL_INCONNU, $_SESSION[InscriptionController::NOM_SESSION_ERREUR_INSCRIPTION]);
    }

    public function testValidermailActionErreurCodeDelaiDepasse(): void {
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/inscription');
        $this->modelsHelper->expects($this->once())
                           ->method('valideInscription')
                           ->with('code')
                           ->willThrowException(new InscriptionDelaiException());
        $this->controller->validermailAction('code');
        $this->assertEquals(Erreurs::CODE_EMAIL_DELAI_DEPASSE, $_SESSION[InscriptionController::NOM_SESSION_ERREUR_INSCRIPTION]);
    }

    public function testValidermailActionReussie(): void {
        $this->modelsHelper->expects($this->once())
                           ->method('valideInscription')
                           ->with('code')
                           ->willReturn(true);
        $this->view->expects($this->once())
                   ->method('setTemplate')
                   ->with(
                       ViewBlocks::CONTENU,
                       'inscription/email.twig',
                       'inscriptionEmailValidation'
                   );
        $this->view->expects($this->once())
                   ->method('render');
        $this->controller->validermailAction('code');
    }

    /**
     * @dataProvider postCases
     * @param array $post
     * @param Erreurs|null $erreur
     * @return void
     */
    public function testPostAction(array $post, ?Erreurs $erreur): void {
        $_SESSION[InscriptionController::NOM_SESSION_TOKEN_INSCRIPTION] = 'token';
        $_POST = $post;
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/inscription');
        $this->controller->postAction();
        $this->assertEquals($erreur, $_SESSION[InscriptionController::NOM_SESSION_ERREUR_INSCRIPTION] ?? null);
        $this->assertEquals($post, $_SESSION[InscriptionController::NOM_SESSION_POST_INSCRIPTION]);
    }

    public function testPostActionMailDejaPris(): void {
        $post = self::GOOD_POST;
        $_SESSION[InscriptionController::NOM_SESSION_TOKEN_INSCRIPTION] = 'token';
        $_POST = $post;
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/inscription');
        $this->modelsHelper->expects($this->once())
                           ->method('mailDejaPris')
                           ->with('mail@mail.com')
                           ->willReturn(true);
        $this->controller->postAction();
        $this->assertEquals(Erreurs::MAIL_DEJA_PRIS, $_SESSION[InscriptionController::NOM_SESSION_ERREUR_INSCRIPTION] ?? null);
        $this->assertEquals($post, $_SESSION[InscriptionController::NOM_SESSION_POST_INSCRIPTION]);
    }

    public function testPostActionErreurInconnue(): void {
        $post = self::GOOD_POST;
        $_SESSION[InscriptionController::NOM_SESSION_TOKEN_INSCRIPTION] = 'token';
        $_POST = $post;
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/inscription');
        $this->modelsHelper->expects($this->once())
                           ->method('mailDejaPris')
                           ->with('mail@mail.com')
                           ->willReturn(false);
        $inscription = $this->createMock(Inscription::class);
        $inscription->expects($this->once())
                    ->method('sauvegarde')
                    ->willReturn(null);
        $this->modelsHelper->expects($this->once())
                           ->method('initInscription')
                           ->with()
                           ->willReturn($inscription);
        $this->controller->postAction();
        $this->assertEquals(Erreurs::INSCRIPTION_GENERIQUE, $_SESSION[InscriptionController::NOM_SESSION_ERREUR_INSCRIPTION] ?? null);
        $this->assertNull($_SESSION[InscriptionController::NOM_SESSION_POST_INSCRIPTION] ?? null);
        $this->assertNull($_SESSION[InscriptionController::NOM_SESSION_TOKEN_INSCRIPTION] ?? null);
    }

    public function testPostActionReussie(): void {
        $post = self::GOOD_POST;
        $_SESSION[InscriptionController::NOM_SESSION_TOKEN_INSCRIPTION] = 'token';
        $_POST = $post;
        $this->controller->expects($this->once())
                         ->method('redirect')
                         ->with('/inscription/validation');
        $this->modelsHelper->expects($this->once())
                           ->method('mailDejaPris')
                           ->with('mail@mail.com')
                           ->willReturn(false);
        $inscription = $this->createMock(Inscription::class);
        $inscription->expects($this->once())
                    ->method('sauvegarde')
                    ->willReturn('code');
        $inscription->expects($this->once())
                    ->method('getNom')
                    ->willReturn($post['nom']);
        $inscription->expects($this->once())
                    ->method('getPrenom')
                    ->willReturn($post['prenom']);
        $inscription->expects($this->once())
                    ->method('getMail')
                    ->willReturn($post['email']);
        $this->modelsHelper->expects($this->once())
                           ->method('initInscription')
                           ->with()
                           ->willReturn($inscription);
        $this->mailer->expects($this->once())
                     ->method('envoieMailInscription')
                     ->with(
                         $post['prenom'] . ' ' . $post['nom'],
                         $post['email'],
                         'code'
                     );
        $this->controller->postAction();
        $this->assertNull($_SESSION[InscriptionController::NOM_SESSION_POST_INSCRIPTION] ?? null);
        $this->assertNull($_SESSION[InscriptionController::NOM_SESSION_TOKEN_INSCRIPTION] ?? null);
    }

    private function erreursMessages(): array {
        return [
            [Erreurs::FORMULAIRE_NON_VALIDE, "Votre formulaire d'inscription était non valide, veuillez réessayer."],
            [Erreurs::CHAMP_MANQUANT, "Il manque un champ requis dans votre formulaire."],
            [Erreurs::MOT_DE_PASSE_DIFFERENT, "Le mot de passe entré en vérification est différent du mot de passe donné."],
            [Erreurs::EMAIL_INVALIDE, "L'email fourni est invalide."],
            [Erreurs::CARACTERES_INTERDITS_NOM, "Votre nom ou votre prénom contiennent des caractères interdits. Si ces caractères sont légitimes, veuillez contacter l'administratrice du site en donnant vos noms et prénoms afin d'ouvrir la possibilité d'utiliser les caractères manquants. Les mesures de sécurité sont parfois un peu ennuyantes, veuillez nous excuser."],
            [Erreurs::MDP_INTERDIT, "Votre mot de passe contient des caractères interdits."],
            [Erreurs::CODE_EMAIL_MANQUANT, "Il manque le code de validation de votre email."],
            [Erreurs::CODE_EMAIL_DELAI_DEPASSE, "Le délai de validation de votre inscription a été dépassée, veuillez renouveler votre inscription."],
            [Erreurs::CODE_EMAIL_INCONNU, "Le code de validation de votre mail est inconnu."],
            [Erreurs::CODE_EMAIL_GENERIQUE, "Quelque chose s'est mal passé durant la validation de votre adresse mail, veuillez réessayer."],
            [Erreurs::MAIL_DEJA_PRIS, "Ce mail est déjà pris, vous avez donc sans doute déjà un compte actif ou une inscription en attente. Si tel n'est pas le cas, veuillez contacter l'administratrice du site."],
            [Erreurs::MDP_TROP_COURT, "Je sais, c'est ennuyant... Mais il faut bien au moins 8 caractères pour votre mot de passe."],
            [Erreurs::INSCRIPTION_GENERIQUE, "Quelque chose s'est très mal passé... Veuillez réessayer ou contacter l'administratrice du site."],
            [Erreurs::MAUVAIS_MDP, ""]
        ];
    }

    private function postCases(): array {
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
                    'nom' => 'nom',
                ],
                Erreurs::CHAMP_MANQUANT
            ],
            [
                [
                    'token' => 'token',
                    'nom' => 'nom',
                    'prenom' => 'prenom'
                ],
                Erreurs::CHAMP_MANQUANT
            ],
            [
                [
                    'token' => 'token',
                    'nom' => 'nom',
                    'prenom' => 'prenom',
                    'email' => 'mail'
                ],
                Erreurs::CHAMP_MANQUANT
            ],
            [
                [
                    'token' => 'token',
                    'nom' => 'nom',
                    'prenom' => 'prenom',
                    'email' => 'mail',
                    'mdp' => 'mdp'
                ],
                Erreurs::CHAMP_MANQUANT
            ],
            [
                [
                    'token' => 'token',
                    'nom' => 'nom',
                    'prenom' => 'prenom',
                    'email' => 'mail',
                    'mdp' => 'mdp',
                    'mdp2' => 'mdp2'
                ],
                Erreurs::MOT_DE_PASSE_DIFFERENT
            ],
            [
                [
                    'token' => 'token',
                    'nom' => 'nom',
                    'prenom' => 'prenom',
                    'email' => 'mail',
                    'mdp' => 'mdp',
                    'mdp2' => 'mdp'
                ],
                Erreurs::EMAIL_INVALIDE
            ],
            [
                [
                    'token' => 'token',
                    'nom' => 'nom>',
                    'prenom' => 'prenom',
                    'email' => 'mail@mail.com',
                    'mdp' => 'mdp',
                    'mdp2' => 'mdp'
                ],
                Erreurs::CARACTERES_INTERDITS_NOM
            ],
            [
                [
                    'token' => 'token',
                    'nom' => 'nom',
                    'prenom' => 'prenom>',
                    'email' => 'mail@mail.com',
                    'mdp' => 'mdp',
                    'mdp2' => 'mdp'
                ],
                Erreurs::CARACTERES_INTERDITS_NOM
            ],
            [
                [
                    'token' => 'token',
                    'nom' => 'nom',
                    'prenom' => 'prenom',
                    'email' => 'mail@mail.com',
                    'mdp' => 'mdp>',
                    'mdp2' => 'mdp>'
                ],
                Erreurs::MDP_INTERDIT
            ],
            [
                [
                    'token' => 'token',
                    'nom' => 'nom',
                    'prenom' => 'prenom',
                    'email' => 'mail@mail.com',
                    'mdp' => 'mdp',
                    'mdp2' => 'mdp'
                ],
                Erreurs::MDP_TROP_COURT
            ]
        ];
    }
}