<?php

namespace TuCreusesOu\Controller;

use TuCreusesOu\Enum\Erreurs;
use TuCreusesOu\Helper\ModelsHelper;
use TuCreusesOu\View\View;

abstract class Controller {
    protected View $view;
    protected ModelsHelper $modelsHelper;

    protected function __construct(View $view, ?ModelsHelper $modelsHelper) {
        $this->view = $view;
        $this->modelsHelper = $modelsHelper ?? new ModelsHelper();
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

    protected function getMessageErreur(Erreurs $erreur): string {
        return "";
    }
}