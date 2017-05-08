<?php
namespace Gr\Gov\Minedu\Osteam\Slim\Handlers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Handlers\AbstractHandler;
use Gr\Gov\Minedu\Osteam\Slim\BaseApp;

/**
 * JSON not found handler.
 */
class NotFound extends AbstractHandler
{
    /**
     * Invoke not found handler
     *
     * @param  ServerRequestInterface $request  The most recent Request object
     * @param  ResponseInterface      $response The most recent Response object
     *
     * @return ResponseInterface
     * @throws UnexpectedValueException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->withJson(array_merge(BaseApp::coreResponseData(false), [
            'success' => false,
            'timestamp' => date('c'),
            'message' => 'Not found'
        ]), 404);
    }

}
