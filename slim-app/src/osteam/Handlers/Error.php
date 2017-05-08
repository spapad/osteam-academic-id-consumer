<?php
namespace Gr\Gov\Minedu\Osteam\Slim\Handlers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Handlers\AbstractError;
use Gr\Gov\Minedu\Osteam\Slim\BaseApp;

/**
 * Error handler.
 */
class Error extends AbstractError
{
    /**
     * Invoke error handler
     *
     * @param ServerRequestInterface $request   The most recent Request object
     * @param ResponseInterface      $response  The most recent Response object
     * @param \Exception             $exception The caught Exception object
     *
     * @return ResponseInterface
     * @throws UnexpectedValueException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Exception $exception)
    {
        return $response->withJson(array_merge(BaseApp::coreResponseData(false), [
            'success' => false,
            'timestamp' => date('c'),
            'message' => 'An error occured',
            'in' => $exception->getMessage()
        ]), intval($code = $exception->getCode()) > 0 ? $code : null);
    }

}
