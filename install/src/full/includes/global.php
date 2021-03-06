<?php

use App\Template;
use Install\InstallManager;

if (file_exists(SCRIPT_ROOT . "install/error")) {
    /** @var InstallManager $installManager */
    $installManager = app()->make(InstallManager::class);
    $installManager->showError();
}

if (file_exists(SCRIPT_ROOT . "install/block")) {
    output_page("Instalacja została już przeprowadzona. Jeżeli chcesz dokonać jej ponownie, usuń plik 'block' z folderu install.");
}

if (file_exists(SCRIPT_ROOT . "install/progress")) {
    output_page("Instalacja trwa, lub została błędnie przeprowadzona.");
}

// Tworzymy obiekt szablonów
$templates = new Template();

// Którym plikom / folderom trzeba nadać uprawnienia do zapisywania
$files_priv = [];
if (file_exists(SCRIPT_ROOT . "install/storage/full/files_priv.txt")) {
    $files_priv = explode(
        "\n", str_replace("\n\r", "\n", file_get_contents(SCRIPT_ROOT . "install/storage/full/files_priv.txt"))
    );
}
$files_priv[] = "install";

// Wymagane moduły
$modules = [
    [
        'text'    => "PHP v5.6.0 lub wyższa",
        'value'   => PHP_VERSION_ID >= 50600,
        'must-be' => false,
    ],

    [
        'text'    => "Moduł cURL",
        'value'   => function_exists('curl_version'),
        'must-be' => true,
    ],
];
