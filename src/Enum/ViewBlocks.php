<?php

namespace TuCreusesOu\Enum;

enum ViewBlocks: string {
    case HEADER = 'header';
    case FOOTER = 'footer';
    case BANNIERE = 'banniere';
    case MENU_GAUCHE = 'menuGauche';
    case MENU_DROITE = 'menuDroite';
    case CONTENU = 'contenu';
    case PIED_DE_PAGE = 'piedDePage';
}