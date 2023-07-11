<?php

namespace TuCreusesOu\View;

use TuCreusesOu\Enum\ViewBlocks;
use TuCreusesOu\TwigExtension\TwigFunctions;
use TuCreusesOu\TwigExtension\TwigFilters;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Extension\CoreExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;

abstract class View {
    protected Environment $twig;
    private array $scriptsTwig = [];
    private array $stylesTwig = [];
    private array $scriptsSource = [];
    private array $scripts = [];
    private array $styles = [];
    private array $blocks = [];
    private string $titre = 'Tu creuses où ?';
    private array $bodyParams = [];
    private string $onLoad = '';

    protected function __construct() {
        $loader = new FilesystemLoader('../templates');
        $this->twig = new Environment($loader, []);
        $this->twig->addExtension(new TwigFilters());
        $this->twig->addExtension(new TwigFunctions());
        $this->twig->getExtension(CoreExtension::class)
                   ->setNumberFormat(2, ',', ' ');
        $this->twig->addExtension(new IntlExtension());
        $this->setTemplate(ViewBlocks::BANNIERE, 'banniere.twig', 'banniere');
        $this->setTemplate(ViewBlocks::PIED_DE_PAGE, 'piedDePage.twig', 'piedDePage');
    }

    public function render(): void {
        $params = [
            'onLoad' => $this->onLoad,
            'scriptsSource' => $this->scriptsSource,
            'scriptsTwig' => $this->scriptsTwig,
            'stylesTwig' => $this->stylesTwig,
            'scripts' => $this->scripts,
            'styles' => $this->styles,
            'blocks' => $this->blocks,
            'titre' => $this->titre
        ];
        foreach ($this->blocks as $block) {
            if ($block !== []) {
                $params = array_merge($params, $block['params']);
            }
        }
        foreach ($this->stylesTwig as $styleParams) {
            $params = array_merge($params, $styleParams);
        }
        foreach ($this->scriptsTwig as $scriptParams) {
            $params = array_merge($params, $scriptParams);
        }
        try {
            echo $this->twig->render(
                'base.twig',
                array_merge($params, $this->bodyParams)
            );
        } catch (Error $e) {
            echo 'Grosse erreur twig....<br>';
            echo $e->getMessage() . '<br>';
            echo 'Line ' .  $e->getLine() . ' in ' . $e->getFile();
            die;
        }
    }

    /**
     * Ajoute un fichier CSS à la page
     * @param string $path
     * @return void
     */
    public function ajouteStyle(string $path): void {
        $this->styles[] = (str_starts_with($path, '/') ? '' : '/styles/') . $path;
    }

    /**
     * Ajoute un fichier javascript à la page
     * @param string $path
     * @return void
     */
    public function ajouteScript(string $path): void {
        $this->scripts[] = (str_starts_with($path, '/') ? '' : '/scripts/') . $path;
    }

    /**
     * @param string $titre
     */
    public function setTitre(string $titre): void {
        $this->titre = $titre;
    }

    /**
     * @param ViewBlocks $blockName Nom du bloc à écraser
     * @param string $twigFile Fichier twig d'où sortir le block
     * @param string $blockNameInFile Nom du bloc dans le fichier twig
     * @param array $params Paramètres du bloc
     * @return void
     */
    public function setTemplate(ViewBlocks $blockName, string $twigFile, string $blockNameInFile, array $params = []): void {
        $this->blocks[$blockName->value] = [
            'file' => $twigFile,
            'blockName' => $blockNameInFile,
            'params' => $params
        ];
    }

    /**
     * Ajoute un fichier CSS Twig à la page
     * @param string $path
     * @param array $params
     * @return void
     */
    public function ajouteStyleTwig(string $path, array $params): void {
        $this->stylesTwig[$path] = $params;
    }

    /**
     * Ajoute un fichier javascript Twig à la page
     * @param string $path
     * @param array $params
     * @return void
     */
    public function ajouteScriptTwig(string $path, array $params): void {
        $this->scriptsTwig[$path] = $params;
    }

    /**
     * Ajoute une source javascript à la page
     * @param string $source
     * @return void
     */
    public function ajouteScriptSource(string $source): void {
        $this->scriptsSource[] = $source;
    }

    /**
     * Ajoute un paramètre body à la page
     *
     * @param string $param attribut
     * @param string $val Valeur de l'attribut
     */
    public function ajouteParam(string $param, string $val): void {
        $this->bodyParams[] = $param . '="' . $val . '"';
    }

    /**
     * Ajoute une fonction onload au body
     * @param string $action
     * @return void
     */
    public function ajouteOnLoad(string $action): void {
        $this->onLoad .= ';' . $action;
    }
}