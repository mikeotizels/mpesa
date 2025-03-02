<?php
/**
 * Basic autoloader for the Mikeotizels M-PESA APIs package.
 * 
 * @since 1.5.0
 */
spl_autoload_register(function ($class) {
    static $map = array (        
        'Mikeotizels\\Mpesa\\Callback' => 'src/Callback.php',
        'Mikeotizels\\Mpesa\\Mpesa' => 'src/Mpesa.php'
    );

    if (isset($map[$class])) {
        require_once __DIR__ . '/src/' . $map[$class];
    }
}, true, false);
