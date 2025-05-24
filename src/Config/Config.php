<?php
/**
 * This file is part of Mikeotizels M-PESA API SDK PHP package.
 *
 * (c) 2022-2024 Michael Otieno <mikeotizels@gmail.com>
 *
 * For the full copyright and license information, please view 
 * the LICENSE file that was distributed with this source code.
 */

namespace Mikeotizels\Mpesa\Config;

use ArrayAccess;
use Closure;
use RuntimeException;
use LogicException;
use ReturnTypeWillChange;

/**
 * Class Config
 * 
 * Configuration file management class.
 * 
 * Implements the PHP ArrayAccess functionality. Allows the use of the "dot" 
 * notation with multidimensional arrays. 
 * 
 * @since 2.0.0
 */
class Config implements ArrayAccess
{
    /**
     * Internal collection of all of the configuration items.
     * 
     * @since 2.0.0
     *
     * @var array
     */
    protected $items = [];

    /**
     * Builds the path to the configuration file directory and creates a new 
     * configuration repository.
     * 
     * @since 2.0.0
     *
     * @param array $options An array of supplemental configuration options. 
     *                       These will take precedence over the defaults.
     *
     * @throws RuntimeException
     * 
     * @return void
     */
    public function __construct(array $options = [])
    {
        $configDir     =  __DIR__ . '/../../config';
        $configDefault = []; 
        $configLocal   = [];
        $configCustom  = [];

        if (!is_dir($configDir)) {
            throw new RuntimeException(
                sprintf('Configuration Error: The system cannot find the M-PESA APIs SDK configuration file directory: %s. It might have been moved, removed, renamed, or the directory is not readable by the server.', $configDir)
            );
        }

        // Default config that contains default settings for all config options.
        $configFileDefault = $configDir . '/' . 'default.inc.php';

        if (!file_exists($configFileDefault)) {
            throw new RuntimeException(
                sprintf('Configuration Error: The system cannot find the M-PESA API SDK default configuration file: %s. It might have been moved, removed, renamed, or the directory is not readable by the server.', $configFileDefault)
            );
        }

        if (!is_readable($configFileDefault)) {
            throw new RuntimeException(
                sprintf('Configuration Error: The system cannot read the M-PESA API SDK default configuration file: %s. Please check that the directory or the file have the correct access permissions.', $configFileDefault)
            );
        }
        
        // Load the default config.
        // Don't remove the Output Buffering (ob_) due to BOM issues.
        if (is_file($configFileDefault)) {
            @ob_start();
            $configDefault = require $configFileDefault;
            @ob_end_clean();
        }

        // The local config that contains the user preferences.
        $configFileLocal =  $configDir . '/' . 'config.inc.php';

        // Load the local config.
        // TODO: Should we log error if the local config file doesn't exist or 
        //       is not readable?
        // Don't remove the Output Buffering (ob_) due to BOM issues.
        if (is_file($configFileLocal)) {
            @ob_start();
            $configLocal = include $configFileLocal;
            @ob_end_clean();
        }

        $configDefault = array_merge($configDefault, $configLocal);

        // The custom configs passed by the $options parameter.
        if (!empty($options)) {
            // TODO: Sanitize and validate the custom options.
            $configCustom = $options;
        }
        
        $this->items = array_merge($configDefault, $configCustom);
    }

    /**
     * Gets an item from the configuration array using the "dot" notation.
     * 
     * @since 2.0.0
     *
     * @param string $key
     * @param mixed  $default
     * 
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (empty($key)) {
            return $this->value($default);
        }
        
        $key = strtolower($key);
        $array = $this->items;

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return isset($array[$key]) ? $array[$key] : $this->value($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (static::isAccessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return $this->value($default);
            }
        }
 
        return $array;
    }

    /**
     * Returns the default value of the given value.
     * 
     * @since 2.0.0
     *
     * @param mixed $value
     * 
     * @return mixed
     */
    public function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }

    /**
     * Determines whether a given key exists in the provided array.
     * 
     * @since 2.0.0
     *
     * @param ArrayAccess|array $array
     * @param string|integer    $key
     * 
     * @return boolean
     */
    private static function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }
 
        return array_key_exists($key, $array);
    }

    /**
     * Determines whether a given value is array accessible.
     * 
     * @since 2.0.0
     *
     * @param mixed $value
     * 
     * @return boolean
     */
    private static function isAccessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }
    
    //-------------------------------------------------------------------------
    // ArrayAccess Methods
    //-------------------------------------------------------------------------  

    /**
     * Determines whether a given configuration option exists.
     * 
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * 
     * @since 2.0.0
     *
     * @param string $offset An offset to check for.
     *
     * @return boolean True if exists or false if not.
     */
    #[ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * Gets a configuration option.
     * 
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * 
     * @since 2.0.0
     *
     * @param string $offset The offset to retrieve.
     * 
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Sets a configuration option.
     * 
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * 
     * @since 2.0.0
     *
     * @param string $offset The offset to assign the value to.
     * @param mixed  $value  The value to set.
     * 
     * @throws LogicException
     * 
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new LogicException(
            sprintf('Cannot set values of properties of %s as it is immutable.', static::class)
        );
    }

    /**
     * Unset a configuration option.
     * 
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * 
     * @since 2.0.0
     *
     * @param string $offset The offset to unset.
     * 
     * @throws LogicException
     * 
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new LogicException(
            sprintf('Cannot unset values of properties of %s as it is immutable.', static::class)
        );
    }
}