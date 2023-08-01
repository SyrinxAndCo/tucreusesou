<?php

use TuCreusesOu\Helper\Mailer;
use TuCreusesOu\Helper\ModelsHelper;
use TuCreusesOu\Model\Profil;

include_once '../../vendor/autoload.php';
include_once '../../config.php';

$mailer = new Mailer();
$modelsHelper = new ModelsHelper();
$listeProfilsNewsletter = $modelsHelper->getProfilsNewsletter();

/**
 * @var Profil $profil
 */
foreach ($listeProfilsNewsletter as $profil) {
    $mailer->envoieMailNewsletter($profil);
}