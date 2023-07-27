<?php

namespace TuCreusesOu\Controller;

use TuCreusesOu\Enum\ViewBlocks;
use TuCreusesOu\Helper\ModelsHelper;
use TuCreusesOu\View\AideView;

class AideController extends Controller {

    public function __construct(AideView $view, ?ModelsHelper $modelsHelper) {
        parent::__construct($view, $modelsHelper);
    }

    public function indexAction(): void {
        $this->view->setTemplate(
            ViewBlocks::CONTENU,
            'aide/index.twig',
            'aideIndex'
        );
        $this->view->render();
    }
}