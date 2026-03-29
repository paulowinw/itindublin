<?php

namespace ForminatorGoogleAddon;

if (\class_exists('ForminatorGoogleAddon\\Google_Client', \false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}
$classMap = ['ForminatorGoogleAddon\\Google\\Client' => 'Google_Client', 'ForminatorGoogleAddon\\Google\\Service' => 'Google_Service', 'ForminatorGoogleAddon\\Google\\AccessToken\\Revoke' => 'Google_AccessToken_Revoke', 'ForminatorGoogleAddon\\Google\\AccessToken\\Verify' => 'Google_AccessToken_Verify', 'ForminatorGoogleAddon\\Google\\Model' => 'Google_Model', 'ForminatorGoogleAddon\\Google\\Utils\\UriTemplate' => 'Google_Utils_UriTemplate', 'ForminatorGoogleAddon\\Google\\AuthHandler\\Guzzle6AuthHandler' => 'Google_AuthHandler_Guzzle6AuthHandler', 'ForminatorGoogleAddon\\Google\\AuthHandler\\Guzzle7AuthHandler' => 'Google_AuthHandler_Guzzle7AuthHandler', 'ForminatorGoogleAddon\\Google\\AuthHandler\\AuthHandlerFactory' => 'Google_AuthHandler_AuthHandlerFactory', 'ForminatorGoogleAddon\\Google\\Http\\Batch' => 'Google_Http_Batch', 'ForminatorGoogleAddon\\Google\\Http\\MediaFileUpload' => 'Google_Http_MediaFileUpload', 'ForminatorGoogleAddon\\Google\\Http\\REST' => 'Google_Http_REST', 'ForminatorGoogleAddon\\Google\\Task\\Retryable' => 'Google_Task_Retryable', 'ForminatorGoogleAddon\\Google\\Task\\Exception' => 'Google_Task_Exception', 'ForminatorGoogleAddon\\Google\\Task\\Runner' => 'Google_Task_Runner', 'ForminatorGoogleAddon\\Google\\Collection' => 'Google_Collection', 'ForminatorGoogleAddon\\Google\\Service\\Exception' => 'Google_Service_Exception', 'ForminatorGoogleAddon\\Google\\Service\\Resource' => 'Google_Service_Resource', 'ForminatorGoogleAddon\\Google\\Exception' => 'Google_Exception'];
foreach ($classMap as $class => $alias) {
    \class_alias($class, $alias);
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class Google_Task_Composer extends \ForminatorGoogleAddon\Google\Task\Composer
{
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
\class_alias('ForminatorGoogleAddon\\Google_Task_Composer', 'Google_Task_Composer', \false);
/** @phpstan-ignore-next-line */
if (\false) {
    class Google_AccessToken_Revoke extends \ForminatorGoogleAddon\Google\AccessToken\Revoke
    {
    }
    class Google_AccessToken_Verify extends \ForminatorGoogleAddon\Google\AccessToken\Verify
    {
    }
    class Google_AuthHandler_AuthHandlerFactory extends \ForminatorGoogleAddon\Google\AuthHandler\AuthHandlerFactory
    {
    }
    class Google_AuthHandler_Guzzle6AuthHandler extends \ForminatorGoogleAddon\Google\AuthHandler\Guzzle6AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle7AuthHandler extends \ForminatorGoogleAddon\Google\AuthHandler\Guzzle7AuthHandler
    {
    }
    class Google_Client extends \ForminatorGoogleAddon\Google\Client
    {
    }
    class Google_Collection extends \ForminatorGoogleAddon\Google\Collection
    {
    }
    class Google_Exception extends \ForminatorGoogleAddon\Google\Exception
    {
    }
    class Google_Http_Batch extends \ForminatorGoogleAddon\Google\Http\Batch
    {
    }
    class Google_Http_MediaFileUpload extends \ForminatorGoogleAddon\Google\Http\MediaFileUpload
    {
    }
    class Google_Http_REST extends \ForminatorGoogleAddon\Google\Http\REST
    {
    }
    class Google_Model extends \ForminatorGoogleAddon\Google\Model
    {
    }
    class Google_Service extends \ForminatorGoogleAddon\Google\Service
    {
    }
    class Google_Service_Exception extends \ForminatorGoogleAddon\Google\Service\Exception
    {
    }
    class Google_Service_Resource extends \ForminatorGoogleAddon\Google\Service\Resource
    {
    }
    class Google_Task_Exception extends \ForminatorGoogleAddon\Google\Task\Exception
    {
    }
    interface Google_Task_Retryable extends \ForminatorGoogleAddon\Google\Task\Retryable
    {
    }
    class Google_Task_Runner extends \ForminatorGoogleAddon\Google\Task\Runner
    {
    }
    class Google_Utils_UriTemplate extends \ForminatorGoogleAddon\Google\Utils\UriTemplate
    {
    }
}
