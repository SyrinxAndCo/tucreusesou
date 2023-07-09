<?php

namespace TuCreusesOu\Controller;

use TuCreusesOu\View\View;

abstract class Controller {
    public const REGEX_EMAIL = '/^[\w\-.]+@([\w\-]+\.)+[\w\-]{2,4}$/';

    protected View $view;

    protected function __construct() {
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

    abstract protected function getMessageErreur(string $code): string;
}