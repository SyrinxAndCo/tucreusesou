<?php


use TuCreusesOu\Controller\Controller;
use TuCreusesOu\Controller\IndexController;
use TuCreusesOu\Helper\ModelsHelper;
use TuCreusesOu\View\IndexView;

include_once '../vendor/autoload.php';
include_once '../config.php';

$requestUri = $_SERVER['REQUEST_URI'];

if (!session_start()) {
    echo "Quelque chose s'est très mal passé...";
    http_response_code(500);
    die;
}

$controllersFiles = scandir('../src/Controller');
$controllers = [];
foreach ($controllersFiles as $controller) {
    if (strpos($controller, 'Controller.php') > 0) {
        $controllerName = str_replace('Controller.php', '', $controller);
        $controllers[strtolower($controllerName)] = '\TuCreusesOu\Controller\\' . $controllerName . 'Controller';
    }
}
$viewsFiles = scandir('../src/View');
$views = [];
foreach ($viewsFiles as $view) {
    if (strpos($view, 'View.php') > 0) {
        $viewName = str_replace('View.php', '', $view);
        $views[strtolower($viewName)] = '\TuCreusesOu\View\\' . $viewName . 'View';
    }
}
$request = explode('/', explode('#', explode('?', $requestUri)[0])[0]);
if (array_key_exists($request[1], $controllers) && array_key_exists($request[1], $views)) {
    /**
     * @var Controller $controller
     */
    $controller = new $controllers[$request[1]](new $views[$request[1]](), new ModelsHelper());
    if (count($request) > 2) {
        $methodName = $request[2] . 'Action';
        if (method_exists($controller, $methodName)) {
            unset($request[0]);
            unset($request[1]);
            unset($request[2]);
            $controller->$methodName(...$request);
        } else {
            $controller->indexAction();
        }
    } else {
        $controller->indexAction();
    }
} else {
    $controller = new IndexController(new IndexView(), new ModelsHelper());
    $controller->indexAction();
}