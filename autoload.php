<?php
/**
 * A custom autoloader for the Mikeotizels M-PESA API SDK PHP package, based 
 * on the PSR-4 standards.
 * 
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md
 * 
 * @package   Mikeotizels/SDKs/ThirdParty/Safaricom/Mpesa
 * @author    Michael Otieno <mikeotizels@gmail.com>
 * @copyright Copyright 2022-2024 Michael Otieno
 * @license   Licensed under The MIT License (MIT). For the license terms, 
 *            please see the LICENSE file that was distributed with this 
 *            source code or visit <https://opensource.org/licenses/MIT>.
 * @since     1.5.0 
 */
spl_autoload_register(function ($class) {
    // Classes namespace prefix (PSR-4 compliant)
    $prefix = 'Mikeotizels\\Mpesa\\';

    // Base directory for the namespace prefix
    $path =  __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    
    // Continue if the class is not in the specified namespace prefix.
    if (0 !== strpos($class, $prefix)) {
        return;
    }

    // Build the relative class filename suffix to append to the path.
    $suffix = ltrim(str_replace($prefix, '', $class), '\\');

    // Replace the namespace prefix with the base directory, replace 
    // namespace separators with directory separators in the relative 
    // class name, and append with .php.
    $suffix = DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $suffix) . '.php';
    
    // Load the class file if it exists.
    if (file_exists($file = realpath($path) . $suffix)) {
        include_once $file;
    }
}, true, true);