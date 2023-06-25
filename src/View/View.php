<?php

namespace TuCreusesOu\View;

use TuCreusesOu\TwigExtension\TwigFunctions;
use TuCreusesOu\TwigExtension\TwigFilters;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Extension\CoreExtension;
use Twig\Loader\FilesystemLoader;

abstract class View {
    const LISTE_BLOCKS = ["header", "footer", "banniere", "menuGauche", "menuDroite", "contenu", "pied2page"];
    protected Environment $twig;
    private array $scriptsTwig = [];
    private array $stylesTwig = [];
    private array $scriptsSource = [];
    private array $scripts = [];
    private array $styles = [];
    private array $blocks = [];
    private string $titre = 'Tu creuses où ? (Vive les archéologues)';
    private array $bodyParams = [];
    private string $onLoad = '';

    protected function __construct() {
        $loader = new FilesystemLoader('../templates');
        $this->twig = new Environment($loader, []);
        $this->twig->addExtension(new TwigFilters());
        $this->twig->addExtension(new TwigFunctions());
        $this->twig->getExtension(CoreExtension::class)
                   ->setNumberFormat(2, ',', ' ');
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
        $this->styles[] = (strpos($path, '/') === 0 ? '' : '/styles/') . $path;
    }

    /**
     * Ajoute un fichier javascript à la page
     * @param string $path
     * @return void
     */
    public function ajouteScript(string $path): void {
        $this->scripts[] = (strpos($path, '/') === 0 ? '' : '/scripts/') . $path;
    }

    /**
     * @param string $titre
     */
    public function setTitre(string $titre): void {
        $this->titre = $titre;
    }

    /**
     * @param string $blockName Nom du bloc à écraser
     * @param string $twigFile Fichier twig d'où sortir le block
     * @param string $blockNameInFile Nom du bloc dans le fichier twig
     * @param array $params Paramètres du bloc
     * @return void
     */
    public function setTemplate(string $blockName, string $twigFile, string $blockNameInFile, array $params = []): void {
        if (in_array($blockName, self::LISTE_BLOCKS)) {
            $this->blocks[$blockName] = [
                'file' => $twigFile,
                'blockName' => $blockNameInFile,
                'params' => $params
            ];
        } else {
            throw new \Error("Le block " . $blockName . " n'existe pas dans le template de base");
        }
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
    public function ajouteParam(string $param, string $val) {
        $this->bodyParams[] = $param . '="' . $val . '"';
    }

    /**
     * Ajoute une fonction onload au body
     * @param string $action
     * @return void
     */
    public function ajouteOnLoad(string $action) {
        $this->onLoad .= ';' . $action;
    }
}