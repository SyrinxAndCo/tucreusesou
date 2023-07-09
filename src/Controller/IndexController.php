<?php

namespace TuCreusesOu\Controller;

use TuCreusesOu\View\IndexView;

class IndexController extends Controller {

    public function __construct() {
        $this->view = new IndexView();
        parent::__construct();
    }

    public function indexAction(): void {
        $this->view->render();
    }

    protected function getMessageErreur(string $code): string {
        return '';
    }
}