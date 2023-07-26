<?php

namespace TuCreusesOu\Helper;

use Exception;
use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use GuzzleHttp\Client;
use Brevo\Client\Model\SendSmtpEmail;

class Mailer {
    static ?Mailer $instance = null;
    private const ID_TEMPLATE_INSCRIPTION = 2;
    private TransactionalEmailsApi $api;

    public function __construct() {
        $credentials = Configuration::getDefaultConfiguration()->setApiKey('api-key', BREVO_MAIL_API_KEY);
        $this->api = new TransactionalEmailsApi(new Client(), $credentials);
    }

    /**
     * Renvoie l'instance du Helper Mailer
     * @return Mailer
     */
    public static function getInstance(): Mailer {
        if (self::$instance === null) {
            self::$instance = new Mailer();
        }
        return self::$instance;
    }

    /**
     * Envoie le mail de confirmation d'inscription
     * @param string $nom
     * @param string $mail
     * @param string $code
     * @return void
     */
    public function envoieMailInscription(string $nom, string $mail, string $code): void {
        $sendSmtpEmail = new SendSmtpEmail();
        $sendSmtpEmail['to'] = [['name' => $nom, 'email' => $mail]];
        $sendSmtpEmail['templateId'] = self::ID_TEMPLATE_INSCRIPTION;
        $sendSmtpEmail['params'] = [
            'nom' => $nom,
            'host' => HOST,
            'code' => $code
        ];

        try {
            $this->api->sendTransacEmail($sendSmtpEmail);
        } catch (Exception $e) {
            echo $e->getMessage(), PHP_EOL;
        }
    }

    /**
     * Envoie la newsletter
     * @param string $nom
     * @param string $mail
     * @param string $content
     * @return void
     */
    public function envoieMailNewsletter(string $nom, string $mail, string $content): void {
        $sendSmtpEmail = new SendSmtpEmail([
            'sender' => ['name' => 'No Reply - Tu Creuses Où ?', 'email' => 'no-reply@tucreusesou.fr'],
            'to' => [['name' => $nom, 'email' => $mail]],
            'htmlContent' => $content,
            'subject' => 'Newsletter - Tu Creuses Où ?'
        ]);

        try {
            $this->api->sendTransacEmail($sendSmtpEmail);
        } catch (Exception $e) {
            echo $e->getMessage(), PHP_EOL;
        }
    }
// Note pour plus tard
//        $this->view->parsePart(
//            'mails/newsletter.twig',
//            [
//                'dateDebut' => time() - 60 * 60 * 24 * 6,
//                'dateFin' => time(),
//                'nouveauxMembres' => Profil::getProfilsInscritsDepuis(time() - 60 * 60 * 24 * 6),
//                'profil' => $this->profil
//            ]
//        )
}