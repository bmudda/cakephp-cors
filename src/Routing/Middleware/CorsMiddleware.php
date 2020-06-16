<?php
namespace Cors\Routing\Middleware;

use Cake\Core\Configure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Cake\Http\Response;

class CorsMiddleware implements MiddlewareInterface
{

    private const OPTIONS_METHOD = 'OPTIONS';
    private const ORIGIN_HEADER = 'Origin';

    /**
     * Process an incoming server request.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getHeader(Self::ORIGIN_HEADER) && strtoupper($request->getMethod()) === self::OPTIONS_METHOD) {
            $response = new Response();
            return $this->getResponseWithHeaders($request, $response, self::OPTIONS_METHOD);
        }

        $response = $handler->handle($request);

        return $this->getResponseWithHeaders($request, $response);
    }

    /**
     * Add headers to reponse object
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string $method
     * @return void
     */
    public function getResponseWithHeaders($request, $response, $method = null): ResponseInterface
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
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return string
     */
    private function _allowOrigin($request): ?string
    {
        $allowOrigin = Configure::read('Cors.AllowOrigin');
        $origin = $request->getHeader(Self::ORIGIN_HEADER);

        if ($allowOrigin === true || $allowOrigin === '*') {
            return $this->_flatten($origin);
        }

        if (is_array($allowOrigin)) {
            $origin = (array) $origin;

            foreach ($origin as $o) {
                if (in_array($o, $allowOrigin)) {
                    return $this->_flatten($origin);
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
    public function _allowMethods(): string
    {
        return $this->_flatten((array) Configure::read('Cors.AllowMethods'));
    }

    /**
     * Get the Access-Control-Allow-Headers header value
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return string
     */
    private function _allowHeaders($request): string
    {
        $allowHeaders = Configure::read('Cors.AllowHeaders');

        if ($allowHeaders === true) {
            return $this->_flatten($request->getHeader('Access-Control-Request-Headers'));
        }

        return $this->_flatten((array) $allowHeaders);
    }

    /**
     * Get the Access-Control-Expose-Headers header value
     *
     * @return string
     */
    private function _exposeHeaders(): ?string
    {
        $exposeHeaders = Configure::read('Cors.ExposeHeaders');

        if (is_string($exposeHeaders) || is_array($exposeHeaders)) {
            return $this->_flatten((array) $exposeHeaders);
        }

        return '';
    }

    /**
     * Get the Access-Control-Max-Age header value
     *
     * @return string
     */
    private function _maxAge(): ?string
    {
        $maxAge = (string) Configure::read('Cors.MaxAge');

        return ($maxAge) ?: '0';
    }

    /**
     * Method to flatten an array into a string
     *
     * @param array $value
     * @return string|null
     */
    private function _flatten($value): ?string
    {
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        return $value;
    }
}
