<?php
namespace Cors\Routing\Middleware;

use Cake\Core\Configure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsMiddleware implements MiddlewareInterface
{
    private const OPTIONS_METHOD = 'OPTIONS';

    /**
     * Process an incoming server request.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getHeader('Origin') && strtoupper($request->getMethod()) === self::OPTIONS_METHOD) {
            $response = new \Cake\Http\Response();
            return $this->_getResponseWithHeaders($request, $response, self::OPTIONS_METHOD);
        }

        $response = $handler->handle($request);

        if ($request->getHeader('Origin')) {
            $response = $this->_getResponseWithHeaders($request, $response);
        }

        return $response;
    }

    /**
     * Add headers to reponse object
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $method
     * @return ResponseInterface
     */
    private function _getResponseWithHeaders($request, $response, $method = null): ResponseInterface
    {
        if ($method === self::OPTIONS_METHOD) {

            $response = $response
                ->withHeader('Access-Control-Allow-Headers', $this->_allowHeaders($request))
                ->withHeader('Access-Control-Allow-Methods', $this->_allowMethods());
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $this->_allowOrigin($request))
            ->withHeader('Access-Control-Allow-Credentials', $this->_allowCredentials())
            ->withHeader('Access-Control-Max-Age', $this->_maxAge())
            ->withHeader('Access-Control-Expose-Headers', $this->_exposeHeaders());

        return $response;

    }

    /**
     * Get the Access-Control-Allow-Origin header value
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    private function _allowOrigin($request): string
    {
        $allowOrigin = Configure::read('Cors.AllowOrigin');
        $origin = $request->getHeader('Origin');

        if ($allowOrigin === true || $allowOrigin === '*') {
            return $origin;
        }

        if (is_array($allowOrigin)) {
            $origin = (array) $origin;

            foreach ($origin as $o) {
                if (in_array($o, $allowOrigin)) {
                    return $origin;
                }
            }

            return '';
        }

        return (string) $allowOrigin;
    }

    /**
     * Get the Access-Control-Allow-Credentials header value
     *
     * @return string
     */
    private function _allowCredentials(): string
    {
        return (Configure::read('Cors.AllowCredentials')) ? 'true' : 'false';
    }

    /**
     * Get the Access-Control-Allow-Methods header value
     *
     * @return string
     */
    private function _allowMethods(): string
    {
        return implode(', ', (array) Configure::read('Cors.AllowMethods'));
    }

    /**
     * Get the Access-Control-Allow-Headers header value
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    private function _allowHeaders($request): string
    {
        $allowHeaders = Configure::read('Cors.AllowHeaders');

        if ($allowHeaders === true) {
            return $request->getHeader('Access-Control-Request-Headers');
        }

        return implode(', ', (array) $allowHeaders);
    }

    /**
     * Get the Access-Control-Expose-Headers header value
     *
     * @return string
     */
    private function _exposeHeaders(): string
    {
        $exposeHeaders = Configure::read('Cors.ExposeHeaders');

        if (is_string($exposeHeaders) || is_array($exposeHeaders)) {
            return implode(', ', (array) $exposeHeaders);
        }

        return '';
    }

    /**
     * Get the Access-Control-Max-Age header value
     *
     * @return string
     */
    private function _maxAge(): string
    {
        $maxAge = (string) Configure::read('Cors.MaxAge');

        return ($maxAge) ?: '0';
    }
}
