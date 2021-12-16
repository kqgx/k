<?php

/*
 * (c) overtrue <i@overtrue.me>
 *
 * Modified By yansongda <me@yansongda.cn>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Yansongda\Pay\Traits;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

require_once __DIR__ . '/../../Psr/Http/Message/MessageInterface.php';
require_once __DIR__ . '/../../Psr/Http/Message/UriInterface.php';
require_once __DIR__ . '/../../Psr/Http/Message/RequestInterface.php';
require_once __DIR__ . '/../../Psr/Http/Message/StreamInterface.php';
require_once __DIR__ . '/../../Psr/Http/Message/ResponseInterface.php';
require_once __DIR__ . '/../../Guzzle/Promises/functions_include.php';
require_once __DIR__ . '/../../Guzzle/Promises/PromiseInterface.php';
require_once __DIR__ . '/../../Guzzle/Promises/TaskQueueInterface.php';
require_once __DIR__ . '/../../Guzzle/Promises/FulfilledPromise.php';
require_once __DIR__ . '/../../Guzzle/Promises/TaskQueue.php';
require_once __DIR__ . '/../../Guzzle/Promises/Promise.php';
require_once __DIR__ . '/../../Guzzle/Psr7/functions_include.php';
require_once __DIR__ . '/../../Guzzle/Psr7/MessageTrait.php';
require_once __DIR__ . '/../../Guzzle/Psr7/Uri.php';
require_once __DIR__ . '/../../Guzzle/Psr7/UriResolver.php';
require_once __DIR__ . '/../../Guzzle/Psr7/Request.php';
require_once __DIR__ . '/../../Guzzle/Psr7/Stream.php';
require_once __DIR__ . '/../../Guzzle/Psr7/Response.php';
require_once __DIR__ . '/../../Guzzle/PrepareBodyMiddleware.php';
require_once __DIR__ . '/../../Guzzle/functions_include.php';
require_once __DIR__ . '/../../Guzzle/RequestOptions.php';
require_once __DIR__ . '/../../Guzzle/RedirectMiddleware.php';
require_once __DIR__ . '/../../Guzzle/ClientInterface.php';
require_once __DIR__ . '/../../Guzzle/Client.php';
require_once __DIR__ . '/../../Guzzle/Middleware.php';
require_once __DIR__ . '/../../Guzzle/HandlerStack.php';
require_once __DIR__ . '/../../Guzzle/Handler/EasyHandle.php';
require_once __DIR__ . '/../../Guzzle/Handler/CurlMultiHandler.php';
require_once __DIR__ . '/../../Guzzle/Handler/CurlFactoryInterface.php';
require_once __DIR__ . '/../../Guzzle/Handler/CurlFactory.php';
require_once __DIR__ . '/../../Guzzle/Handler/Proxy.php';
require_once __DIR__ . '/../../Guzzle/Handler/CurlHandler.php';
require_once __DIR__ . '/../../Guzzle/Handler/StreamHandler.php';

trait HasHttpRequest
{
    /**
     * Make a get request.
     *
     * @param string $endpoint
     * @param array  $query
     * @param array  $headers
     *
     * @return array
     */
    protected function get($endpoint, $query = [], $headers = [])
    {
        return $this->request('get', $endpoint, [
            'headers' => $headers,
            'query'   => $query,
        ]);
    }

    /**
     * Make a post request.
     *
     * @param string $endpoint
     * @param mixed  $params
     * @param array  $options
     *
     * @return string
     */
    protected function post($endpoint, $params = [], ...$options)
    {
        $options = isset($options[0]) ? $options[0] : [];

        if (!is_array($params)) {
            $options['body'] = $params;
        } else {
            $options['form_params'] = $params;
        }

        return $this->request('post', $endpoint, $options);
    }

    /**
     * Make a http request.
     *
     * @param string $method
     * @param string $endpoint
     * @param array  $options  http://docs.guzzlephp.org/en/latest/request-options.html
     *
     * @return array
     */
    protected function request($method, $endpoint, $options = [])
    {
        return $this->unwrapResponse($this->getHttpClient($this->getBaseOptions())->{$method}($endpoint, $options));
    }

    /**
     * Return base Guzzle options.
     *
     * @return array
     */
    protected function getBaseOptions()
    {
        $options = [
            'base_uri' => method_exists($this, 'getBaseUri') ? $this->getBaseUri() : '',
            'timeout'  => property_exists($this, 'timeout') ? $this->timeout : 5.0,
        ];

        return $options;
    }

    /**
     * Return http client.
     *
     * @param array $options
     *
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient(array $options = [])
    {
        return new Client($options);
    }

    /**
     * Convert response contents to json.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return array
     */
    protected function unwrapResponse(ResponseInterface $response)
    {
        $contentType = $response->getHeaderLine('Content-Type');
        $contents = $response->getBody()->getContents();

        if (false !== stripos($contentType, 'json') || stripos($contentType, 'javascript')) {
            return json_decode($contents, true);
        } elseif (false !== stripos($contentType, 'xml')) {
            return json_decode(json_encode(simplexml_load_string($contents)), true);
        }

        return $contents;
    }
}
