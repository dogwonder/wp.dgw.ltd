<?php

namespace GP_Google_Sheets\Dependencies;

if (\class_exists('GP_Google_Sheets\\Dependencies\\Google_Client', \false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}
$classMap = ['GP_Google_Sheets\\Dependencies\\Google\\Client' => 'GP_Google_Sheets\\Dependencies\Google_Client', 'GP_Google_Sheets\\Dependencies\\Google\\Service' => 'GP_Google_Sheets\\Dependencies\Google_Service', 'GP_Google_Sheets\\Dependencies\\Google\\AccessToken\\Revoke' => 'GP_Google_Sheets\\Dependencies\Google_AccessToken_Revoke', 'GP_Google_Sheets\\Dependencies\\Google\\AccessToken\\Verify' => 'GP_Google_Sheets\\Dependencies\Google_AccessToken_Verify', 'GP_Google_Sheets\\Dependencies\\Google\\Model' => 'GP_Google_Sheets\\Dependencies\Google_Model', 'GP_Google_Sheets\\Dependencies\\Google\\Utils\\UriTemplate' => 'GP_Google_Sheets\\Dependencies\Google_Utils_UriTemplate', 'GP_Google_Sheets\\Dependencies\\Google\\AuthHandler\\Guzzle6AuthHandler' => 'GP_Google_Sheets\\Dependencies\Google_AuthHandler_Guzzle6AuthHandler', 'GP_Google_Sheets\\Dependencies\\Google\\AuthHandler\\Guzzle7AuthHandler' => 'GP_Google_Sheets\\Dependencies\Google_AuthHandler_Guzzle7AuthHandler', 'GP_Google_Sheets\\Dependencies\\Google\\AuthHandler\\Guzzle5AuthHandler' => 'GP_Google_Sheets\\Dependencies\Google_AuthHandler_Guzzle5AuthHandler', 'GP_Google_Sheets\\Dependencies\\Google\\AuthHandler\\AuthHandlerFactory' => 'GP_Google_Sheets\\Dependencies\Google_AuthHandler_AuthHandlerFactory', 'GP_Google_Sheets\\Dependencies\\Google\\Http\\Batch' => 'GP_Google_Sheets\\Dependencies\Google_Http_Batch', 'GP_Google_Sheets\\Dependencies\\Google\\Http\\MediaFileUpload' => 'GP_Google_Sheets\\Dependencies\Google_Http_MediaFileUpload', 'GP_Google_Sheets\\Dependencies\\Google\\Http\\REST' => 'GP_Google_Sheets\\Dependencies\Google_Http_REST', 'GP_Google_Sheets\\Dependencies\\Google\\Task\\Retryable' => 'GP_Google_Sheets\\Dependencies\Google_Task_Retryable', 'GP_Google_Sheets\\Dependencies\\Google\\Task\\Exception' => 'GP_Google_Sheets\\Dependencies\Google_Task_Exception', 'GP_Google_Sheets\\Dependencies\\Google\\Task\\Runner' => 'GP_Google_Sheets\\Dependencies\Google_Task_Runner', 'GP_Google_Sheets\\Dependencies\\Google\\Collection' => 'GP_Google_Sheets\\Dependencies\Google_Collection', 'GP_Google_Sheets\\Dependencies\\Google\\Service\\Exception' => 'GP_Google_Sheets\\Dependencies\Google_Service_Exception', 'GP_Google_Sheets\\Dependencies\\Google\\Service\\Resource' => 'GP_Google_Sheets\\Dependencies\Google_Service_Resource', 'GP_Google_Sheets\\Dependencies\\Google\\Exception' => 'GP_Google_Sheets\\Dependencies\Google_Exception'];
foreach ($classMap as $class => $alias) {
    \class_alias($class, $alias);
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class Google_Task_Composer extends \GP_Google_Sheets\Dependencies\Google\Task\Composer
{
}
/** @phpstan-ignore-next-line */
if (\false) {
    class Google_AccessToken_Revoke extends \GP_Google_Sheets\Dependencies\Google\AccessToken\Revoke
    {
    }
    class Google_AccessToken_Verify extends \GP_Google_Sheets\Dependencies\Google\AccessToken\Verify
    {
    }
    class Google_AuthHandler_AuthHandlerFactory extends \GP_Google_Sheets\Dependencies\Google\AuthHandler\AuthHandlerFactory
    {
    }
    class Google_AuthHandler_Guzzle5AuthHandler extends \GP_Google_Sheets\Dependencies\Google\AuthHandler\Guzzle5AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle6AuthHandler extends \GP_Google_Sheets\Dependencies\Google\AuthHandler\Guzzle6AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle7AuthHandler extends \GP_Google_Sheets\Dependencies\Google\AuthHandler\Guzzle7AuthHandler
    {
    }
    class Google_Client extends \GP_Google_Sheets\Dependencies\Google\Client
    {
    }
    class Google_Collection extends \GP_Google_Sheets\Dependencies\Google\Collection
    {
    }
    class Google_Exception extends \GP_Google_Sheets\Dependencies\Google\Exception
    {
    }
    class Google_Http_Batch extends \GP_Google_Sheets\Dependencies\Google\Http\Batch
    {
    }
    class Google_Http_MediaFileUpload extends \GP_Google_Sheets\Dependencies\Google\Http\MediaFileUpload
    {
    }
    class Google_Http_REST extends \GP_Google_Sheets\Dependencies\Google\Http\REST
    {
    }
    class Google_Model extends \GP_Google_Sheets\Dependencies\Google\Model
    {
    }
    class Google_Service extends \GP_Google_Sheets\Dependencies\Google\Service
    {
    }
    class Google_Service_Exception extends \GP_Google_Sheets\Dependencies\Google\Service\Exception
    {
    }
    class Google_Service_Resource extends \GP_Google_Sheets\Dependencies\Google\Service\Resource
    {
    }
    class Google_Task_Exception extends \GP_Google_Sheets\Dependencies\Google\Task\Exception
    {
    }
    interface Google_Task_Retryable extends \GP_Google_Sheets\Dependencies\Google\Task\Retryable
    {
    }
    class Google_Task_Runner extends \GP_Google_Sheets\Dependencies\Google\Task\Runner
    {
    }
    class Google_Utils_UriTemplate extends \GP_Google_Sheets\Dependencies\Google\Utils\UriTemplate
    {
    }
}
