<?php
declare(strict_types=1);

namespace NewRelic\Lib;

use Exception;
use Throwable;

/**
 * Class to help work with NewRelic in PHP
 *
 * @author Christian Winther
 * @see https://docs.newrelic.com/docs/php/the-php-api
 */
class NewRelic
{
    protected static array $ignoredExceptions = [];

    protected static array $ignoredErrors = [];

    protected static array $serverVariables = [];

    protected static array $cookieVariables = [];

    protected static string $currentTransactionName;

    /**
     * Change the application name
     *
     * @param string $name
     * @return void
     */
    public static function applicationName(string $name): void
    {
        if (!static::hasNewRelic()) {
            return;
        }

        newrelic_set_appname($name);
    }

    /**
     * Start a New Relic transaction
     *
     * @param string|null $name
     * @return void
     */
    public static function start(?string $name = null): void
    {
        if (!static::hasNewRelic()) {
            return;
        }

        newrelic_start_transaction(NEW_RELIC_APP_NAME);

        if ($name) {
            static::$currentTransactionName = $name;
        }

        newrelic_name_transaction(static::$currentTransactionName);
    }

    /**
     * End a New Relic transaction
     *
     * @param boolean $ignore Should the statistics NewRelic gathered be discarded?
     * @return void
     */
    public static function stop(bool $ignore = false): void
    {
        if (!static::hasNewRelic()) {
            return;
        }

        newrelic_end_transaction($ignore);
    }

    /**
     * Ignore the current transaction
     *
     * @return void
     */
    public static function ignoreTransaction(): void
    {
        if (!static::hasNewRelic()) {
            return;
        }

        newrelic_ignore_transaction();
    }

    /**
     * Ignore the current apdex
     *
     * @return void
     */
    public static function ignoreApdex(): void
    {
        if (!static::hasNewRelic()) {
            return;
        }

        newrelic_ignore_apdex();
    }

    /**
     * Should NewRelic capture params ?
     *
     * @param boolean $boolean
     * @return void
     */
    public static function captureParams(bool $boolean): void
    {
        if (!static::hasNewRelic()) {
            return;
        }

        newrelic_capture_params($boolean);
    }

    /**
     * Add custom tracer method
     *
     * @param string $method
     */
    public static function addTracer(string $method): void
    {
        if (!static::hasNewRelic()) {
            return;
        }

        newrelic_add_custom_tracer($method);
    }

    /**
     * Add a custom parameter to the New Relic transaction
     *
     * @param string $key
     * @param mixed $value
     * @return false|void
     */
    public static function parameter(string $key, mixed $value)
    {
        if (!static::hasNewRelic()) {
            return false;
        }

        if (!is_scalar($value)) {
            $value = json_encode($value);
        }

        newrelic_add_custom_parameter($key, $value);
    }

    /**
     * Track a custom metric
     *
     * @param string $key
     * @param float|integer $value
     * @return void
     * @throws \Exception
     */
    public static function metric(string $key, float|int $value): void
    {
        if (!static::hasNewRelic()) {
            return;
        }

        if (!is_numeric($value)) {
            throw new Exception('Value must be numeric');
        }

        newrelic_custom_metric($key, $value);
    }

    /**
     * Add a custom method to have traced by NewRelic
     *
     * @param string $method
     * @return void
     */
    public static function tracer(string $method): void
    {
        if (!static::hasNewRelic()) {
            return;
        }

        newrelic_add_custom_tracer($method);
    }

    /**
     * Ignore an exception class
     *
     * @param string $exception
     * @return void
     */
    public static function ignoreException(string $exception): void
    {
        static::$ignoredExceptions = array_merge(static::$ignoredExceptions, (array) $exception);
    }

    /**
     * Ignore error strings
     *
     * @param string $error
     * @return void
     */
    public static function ignoreError(string $error): void
    {
        static::$ignoredErrors = array_merge(static::$ignoredErrors, (array) $error);
    }

    /**
     * Server variables to collect
     *
     * @param  array $variables
     * @return void
     */
    public static function collectServerVariables(array $variables): void
    {
        static::$serverVariables = array_merge(static::$serverVariables, $variables);
    }

    /**
     * Cookie variables to collect
     *
     * @param  array $variables
     * @return void
     */
    public static function collectCookieVariables(array $variables): void
    {
        static::$cookieVariables = array_merge(static::$cookieVariables, $variables);
    }

    /**
     * Send an exception to New Relic
     *
     * @param \Throwable|\Exception $exception
     * @return void
     */
    public static function sendException(Throwable|Exception $exception): void
    {
        if (!static::hasNewRelic()) {
            return;
        }

        $exceptionClass = get_class($exception);
        if (in_array($exceptionClass, static::$ignoredExceptions)) {
            return;
        }

        newrelic_notice_error(0, $exception);
    }

    /**
     * Send an error to New Relic
     *
     * @param mixed $code
     * @param mixed $description
     * @param mixed $file
     * @param mixed $line
     * @param mixed|null $context
     * @return void
     */
    public static function sendError(
        mixed $code,
        mixed $description,
        mixed $file,
        mixed $line,
        mixed $context = null
    ): void {
        if (!static::hasNewRelic()) {
            return;
        }

        foreach (static::$ignoredErrors as $errorMessage) {
            if (str_contains($description, $errorMessage)) {
                return;
            }
        }

        newrelic_notice_error($code, $description, $file, $line, $context);
    }

    /**
     * Set user attributes
     *
     * @param string $user
     * @param string $account
     * @param string $product
     * @return void
     */
    public static function user(string $user, string $account, string $product): void
    {
        if (!static::hasNewRelic()) {
            return;
        }

        newrelic_set_user_attributes($user, $account, $product);
    }

    /**
     * Check if the NewRelic PHP extension is loaded
     *
     * @return boolean
     */
    public static function hasNewRelic(): bool
    {
        return extension_loaded('newrelic');
    }

    /**
     * Collect environmental data for the transaction
     *
     * @return void
     */
    public static function collect(): void
    {
        static::parameter('_get', $_GET);
        static::parameter('_post', $_POST);
        static::parameter('_files', $_FILES);

        foreach ($_SERVER as $key => $value) {
            if (!in_array($key, static::$serverVariables)) {
                continue;
            }
            static::parameter('server_' . strtolower($key), $value);
        }

        foreach ($_COOKIE as $key => $value) {
            if (!in_array($key, static::$cookieVariables)) {
                continue;
            }
            static::parameter('cookie_' . strtolower($key), $value);
        }
    }
}
