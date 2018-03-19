<?php

namespace AppBundle\Extension;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
/**
 *
 */
class HttpClientExtension
{
    const CURL_TIMEOUT_MESSAGE = 'cURL error 28';

    var $maxRetries;
    var $connectTimeout;
    var $timeout;

    var $logger;

    public function __construct($maxRetries, $connectTimeout, $timeout) {
        $this->maxRetries = $maxRetries;
        $this->connectTimeout = $connectTimeout;
        $this->timeout = $timeout;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }


    public function createHttpClientWithRetryHandler($uri)
    {
        $stack = HandlerStack::create();
        $stack->push(Middleware::retry($this->createRetryHandler()));


        if($uri != null)
        {

            return new Client([
                'handler' => $stack,
                'base_uri' => $uri,
                'connect_timeout' => $this->connectTimeout,
                'timeout' => $this->timeout,
                'defaults' => [
                    'exceptions' => false,
                    'http_errors' => true
                ]
            ]);
        }
        else {
            return new Client([
                'handler' => $stack,
                'connect_timeout' => $this->connectTimeout,
                'timeout' => $this->timeout,
                'defaults' => [
                    'exceptions' => false,
                    'http_errors' => true
                ]
            ]);
        }

    }

    private function createRetryHandler()
    {
        return function (
            $retries,
            Request $request,
            Response $response = null,
            RequestException $exception = null
        ) {
            $maxRetries = $this->maxRetries;

            if ($retries >= $maxRetries) {
                return false;
            }

            // In case of connection error, server error, curl timeout
            if(false == ($this->isConnectError($exception)
                || $this->isCurlTimeoutError($exception)
                || $this->isServerError($response)))
            {
                // Need to log this situation, what's going on?
                return false;
            }

            /*
            $logger->log(sprintf(
                'Retrying %s %s %s/%s, %s',
                $request->getMethod(),
                $request->getUri(),
                $retries + 1,
                $maxRetries,
                $response ? 'status code: ' . $response->getStatusCode() : $exception->getMessage()
            ));
            */
            return true;
        };
    }

    /**
     * @param Response $response
     * @return bool
     */
    protected function isServerError(Response $response = null)
    {
        return $response && $response->getStatusCode() >= 400;
    }
    /**
     * @param RequestException $exception
     * @return bool
     */
    protected function isConnectError(RequestException $exception = null)
    {
        return $exception instanceof ConnectException;
    }

    protected function isCurlTimeoutError(\Exception $exception = null)
    {
        if($exception == null)
            return false;

        $message = $exception->getMessage();
        if($message != null && strpos($message, self::CURL_TIMEOUT_MESSAGE) != false )
        {
            return true;
        }
        return false;
    }

}