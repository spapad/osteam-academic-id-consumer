<?php

namespace Gr\Gov\Minedu\Osteam\Slim;

use Interop\Container\ContainerInterface;
use Slim\Http\Body;
use Gr\Gov\Minedu\Osteam\Slim\Client;

/**
 * Παρέχει μεθόδους ευκολίας για τις εφαρμογές. 
 * Πιθανές εφαρμογές μπορούν να κάνουν etxend αυτή την κλάση.
 *
 * @author spapad
 */
class BaseApp
{
    
    protected $ci = null;
    protected $logger = null;
    protected $debug = false;
    protected $client = null;

    /**
     * Κατασκευή της κλάσης και φύλαξη του container και του logger.
     */    
    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
        if (($logger = $this->ci->get('logger')) != null) {
            $this->logger = $logger;
        }
        $this->client = new Client([
            'NO_SAFE_CURL' => (isset($settings['verify_ssl']) ? $settings['verify_ssl'] === false : false),
        ]);
    }

    /**
     * Παρέχει τις ελάχιστες ιδιότητες για το απαντητικό μήνυμα. 
     * success: true ή false 
     * timestamp: date σε μορφή ISO8601 
     *
     * @param boolean $success Επιτυχής ή όχι κλήση 
     * @return array 
     */
    public static function coreResponseData($success)
    {
        return [
            'success' => $success === true,
            'timestamp' => date('c')
            // 'client' => 'php ' . getHostByName(getHostName())
        ];
    }

    /**
    * Αποστολή έτοιμου διαμορφωμένου json response.
    *
    * @param  Response $res
    * @param  mixed $data Προδιαμορφωμένο json κείμενο
    * @param  int $status HTTP status code για αποστολή
    * @return response
    */
    public function withJsonReady($res, $data, $status = null)
    {
        $response = $res->withBody(new Body(fopen('php://temp', 'r+')));
        $response->getBody()->write($data);
        
        $jsonResponse = $response->withHeader('Content-Type', 'application/json;charset=utf-8');
        if (isset($status)) {
            return $jsonResponse->withStatus($status);
        }
        return $jsonResponse;
    }
    
    /**
     * Αποστολή response απλού κειμένου.
     *
     * @param  Response $res
     * @param  mixed $data Το κείμενο προς αποστολή
     * @param  int $status HTTP status code για αποστολή
     * @return response
     */
    public function withTextReady($res, $data, $status = null)
    {
        $response = $res->withBody(new Body(fopen('php://temp', 'r+')));
        $response->getBody()->write($data);
        
        $textResponse = $response->withHeader('Content-Type', 'text/plain');
        if (isset($status)) {
            return $textResponse->withStatus($status);
        }
        return $textResponse;
    }

    protected function log($msg)
    {
        if ($this->logger) {
            $this->logger->info($msg);
        }
    }
    
    /**
     * 
     */
    public function setDebug($debug = true)
    {
        $this->debug = ($debug === true);
    }

    /**
     * Αλλαγή χαρακτήρων ώστε το όνομα που παρέχεται να είναι ασφαλές για 
     * χρήση ως όνομα αρχείου.
     *
     * @param string $filename 
     * @return string 
     */
    protected function sanitizeFilename($filename) 
    {
        return mb_ereg_replace("([^\w\s\d\-_,.])", '_', $filename);
    }

}
