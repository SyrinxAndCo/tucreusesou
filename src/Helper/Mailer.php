<?php

namespace TuCreusesOu\Helper;

use Exception;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use GuzzleHttp\Client;
use SendinBlue\Client\Model\SendSmtpEmail;

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
}