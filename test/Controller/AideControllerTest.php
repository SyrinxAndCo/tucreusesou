<?php

namespace TuCreusesOu\Test\Controller;

use PHPUnit\Framework\TestCase;
use TuCreusesOu\Controller\AideController;
use TuCreusesOu\Enum\ViewBlocks;
use TuCreusesOu\Helper\ModelsHelper;
use TuCreusesOu\View\AideView;

class AideControllerTest extends TestCase {
    private AideView $view;
    private AideController $controller;
    private ModelsHelper $modelsHelper;
    public function setUp(): void {
        $this->view = $this->createMock(AideView::class);
        $this->modelsHelper = $this->createMock(ModelsHelper::class);
        $this->controller = $this->getMockBuilder(AideController::class)
                                 ->setConstructorArgs([$this->view, $this->modelsHelper])
                                 ->onlyMethods(['redirect'])
                                 ->getMock();
    }

    public function testIndexAction(): void {
        $this->view->expects($this->once())
            ->method('setTemplate')
            ->with(
                ViewBlocks::CONTENU,
                'aide/index.twig',
                'aideIndex'
            );
        $this->view->expects($this->once())
            ->method('render');
        $this->controller->indexAction();
    }
}