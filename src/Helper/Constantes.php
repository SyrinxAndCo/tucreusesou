<?php

namespace TuCreusesOu\Helper;

class Constantes {
    public const REGEX_EMAIL = '/^[\w\-.]+@([\w\-]+\.)+[\w\-]{2,4}$/';
    public const REGEX_NOM = '/^[a-zA-ZÀ-ÿ\-. ]*$/';
    public const REGEX_TEXT = '/^[a-zA-ZÀ-ÿ\-. ,!*\/+#?%$£^¨°_|&]*$/';
    public const REGEX_DATE = '/^\d{4}-\d{2}-\d{2}$/';
}