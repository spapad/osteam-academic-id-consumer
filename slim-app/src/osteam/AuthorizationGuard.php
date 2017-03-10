<?php

namespace Gr\Gov\Minedu\Osteam\Slim;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

if (!function_exists('getallheaders'))
{
    function getallheaders()
    {
       $headers = '';
       foreach ($_SERVER as $name => $value)
       {
           if (substr($name, 0, 5) == 'HTTP_')
           {
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
           }
       }
       return $headers;
    }
} 

class AuthorizationGuard
{
    private $_username;
    private $_password;

    public function __construct($username, $password)
    {
        $this->_username = $username;
        $this->_password = $password;
    }

    /**
     * Check for authorization basic token
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {

        $auth = true;
        $headers = getallheaders();
        if (array_key_exists('Authorization', $headers)) {
            $header = $headers['Authorization'];
            $auth_parts = [];
            if (preg_match('/^Basic (.+)$/', $header, $auth_parts) === 1) {
                if ($auth_parts[1] !== base64_encode("{$this->_username}:{$this->_password}")) {
                    $auth = [
                        "message" => "Error: Invalid Credentials"
                    ];
                }
            } else {
                $auth = [
                    "message" => "Error: Mallformed Authorization Header"
                ];
            }
        } else {
            $auth = [
                "message" => "Error: Missing Authorization Header"
            ];
        }

        if ($auth !== true) {
            return $response->withJson(array_merge(BaseApp::coreResponseData(false), $auth), 401);
        } else {
            return $next($request, $response);
        }
    }
}
