<?php

namespace TuCreusesOu\Controller;

use TuCreusesOu\Enum\Erreurs;
use TuCreusesOu\View\View;

abstract class Controller {
    protected View $view;

    protected function __construct(View $view) {
        $this->view = $view;
    }

    abstract public function indexAction(): void;

    /**
     * Redirige sur une autre page
     * @param string $url
     * @param bool $permanent
     * @return void
     */
    protected function redirect(string $url, bool $permanent = false): void {
        if (headers_sent()) {
            echo '<script type="text/javascript">location.replace("' . $url . '");</script>';
        } else {
            if ($permanent) {
                header("Status: 301 Moved Permanently", false, 301);
            }
            header('Location: ' . $url);
            exit();
        }
    }

    abstract protected function getMessageErreur(Erreurs $erreur): string;
}