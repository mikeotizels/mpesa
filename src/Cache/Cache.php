<?php
/**
 * This file is part of Mikeotizels M-PESA API SDK PHP package.
 *
 * (c) 2022-2024 Michael Otieno <mikeotizels@gmail.com>
 *
 * For the full copyright and license information, please view 
 * the LICENSE file that was distributed with this source code.
 */

namespace Mikeotizels\Mpesa\Cache;

use Mikeotizels\Mpesa\Config\Config;
use DateTime;

/**
 * Class Cache
 * 
 * Basic file-based cache management class. Use this with care, and be sure to 
 * benchmark your application, as a point can come where disk I/O will negate 
 * positive gains by caching. 
 * 
 * @since 2.0.0
 */
class Cache
{
    /**
     * Path to the directory where the cache files should be stored.
     * 
     * This cache directory needs to be really writable by the application.
     * 
     * @since 2.0.0
     *
     * @var string
     */
    private $path;

    /**
     * Constructor.
     * 
     * Sets up the config object and the cache file path.
     * 
     * @since 2.0.0
     * 
     * @param Config $config An instance of the Config class.
     * 
     * @throws ErrorException
     */
    public function __construct(Config $config)
    { 
        $enable = $config->get('cache.enable', true);

        if (!$enable) {
            return;
        }

        $this->path = $config->get('cache.path', '');

        if (empty($this->path)) {
            $this->path = __DIR__ . '/../../cache';
        }

        if (!is_dir($this->path)) {
            @mkdir($this->path, 0755, true);
        }
         
        if (!is_dir($this->path)) {
            trigger_error(
                sprintf('Cache Error: The system cannot find the directory "%s" for the M-PESA API SDK cache files. Additionally, creating the directory with the default access permissions failed. Perhaps, the file permissions on the disk might be blocking access.', $this->path), 
                E_USER_WARNING
            );
            return;
        }

        if (!is_writable($this->path)) {
            trigger_error(
                sprintf('Cache Error: The directory "%s" for storing the M-PESA API SDK cache files is not writable. The M-PESA API library is unable to cache files and may be slow because of this.', $this->path), 
                E_USER_WARNING
            );
            return;
        }
        
        // TODO: Sanitize and normalize the directory path
        $this->path = rtrim($this->path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Attempts to fetch an item from the cache store.
     * 
     * @since 2.0.0
     *
     * @param string $key     Cache item name.
     * @param mixed  $default Default value to return if the item is not found.
     *
     * @return mixed Item value or $default value if not found.
     */
    public function get(string $key, $default = null)
    {
        $file = $this->path . '.' . md5($key) . '.tmp';

        if (!is_file($file)) {
            return $default;
        }

        $cache = unserialize(file_get_contents($file));
        $cache = $this->clean($cache, $file);

        if (!isset($cache[$key])) {
            return $default;
        }

        return $cache[$key]['v'];
    }

    /**
     * Saves an item to the cache store.
     * 
     * The $value argument may be any item that can be serialized by PHP.
     * 
     * @since 2.0.0
     *
     * @param string  $key   Cache item name.
     * @param mixed   $value The serializable value to be stored.
     * @param integer $ttl   Time-to-live, in seconds, after which the item is 
     *                       considered expired. Default 60.
     * 
     * @todo Make $ttl \DateTimeInterface|\DateInterval|float|integer
     *
     * @return boolean True on success or false on failure.
     */
    public function save(string $key, $value, int $ttl = 60)
    {
        $file    = $this->path . '.' . md5($key) . '.tmp';
        $initial = [];

        if (is_file($file)) {
            $initial = unserialize(file_get_contents($file));
            $initial = $this->clean($initial, $file);
        }

        $date    = new DateTime();
        $minutes = $ttl / 60;
        $expiry  = $date->modify((int) $minutes . ' minutes')->format('Y-m-d H:i:s');
        $payload = [$key => ['v' => $value, 'e' => $expiry]];
        $payload = serialize(array_merge($payload, $initial));

        file_put_contents($file, $payload);
        chmod($file, 0640);
    }

    /**
     * @since 2.0.0 
     */
    private function clean($initial, $filename)
    {
        $initial = array_filter($initial, function ($value) {
            if (!$value['e']) {
                return true;
            }

            $currentDate = new DateTime();
            $expiryDate  = new DateTime($value['e']);

            if ($currentDate > $expiryDate) {
                return false;
            }

            return true;
        });

        file_put_contents($filename, serialize($initial));

        return $initial;
    }
}