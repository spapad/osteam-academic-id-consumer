<?php

namespace Gr\Gov\Minedu\Osteam\Slim;

use Interop\Container\ContainerInterface;
use Slim\Http\Body;
use Gr\Gov\Minedu\Osteam\Slim\BaseApp;
use Gr\Gov\Minedu\Osteam\Slim\Client;

/**
 * Description of app
 *
 * @author spapad
 */
class App extends BaseApp
{
    protected $username = '';
    protected $password = ''; 
    protected $secureEndpointUsername = '';
    protected $secureEndpointPassword = '';
    protected $urlAcademicId = '';
    protected $urlAmka = '';

    public function __construct(ContainerInterface $ci)
    {
        parent::__construct($ci);

        $settings = $this->ci->get('settings');
        if (isset($settings['app'])) {
            $this->username = (isset($settings['app']['username']) ? $settings['app']['username'] : '');
            $this->password = (isset($settings['app']['password']) ? $settings['app']['password'] : 0);
            $this->secureEndpointUsername = (isset($settings['app']['secure_endpoint_username']) ? $settings['app']['secure_endpoint_username'] : '');
            $this->secureEndpointPassword = (isset($settings['app']['secure_endpoint_password']) ? $settings['app']['secure_endpoint_password'] : '');
            $this->urlAcademicId = (isset($settings['app']['ws_endpoint_academic_id']) ? $settings['app']['ws_endpoint_academic_id'] : '');
            $this->urlAmka = (isset($settings['app']['ws_endpoint_amka']) ? $settings['app']['ws_endpoint_amka'] : '');
        }
    }

    protected function generateAuth() 
    {
        $pass_md5 = md5($this->password);
        return "Basic " . base64_encode("{$this->username}:{$pass_md5}");
    }

    /**
     *
     * @param Psr\Http\Message\ServerRequestInterface $req
     * @param Psr\Http\Message\ResponseInterface $res
     * @param $args
     * @throws \Exception
     * @return Response
     */
    public function queryID($req, $res, $args) 
    {
        $identity = $req->getQueryParam('id', 0);
        if (preg_match('/[^0-9]/', $identity) === 1) {
            return $res->withJson(array_merge(BaseApp::coreResponseData(false), [
                'message' => 'Error: Service Call Parameters Error, academic id or student amka id must be a number'
            ]), 400);
        } elseif (preg_match('/^[0-9]{12}$/', $identity) === 1) {
            $data = json_encode([
                "SubmissionCode" => $identity
            ]);
            $endpoint = $this->urlAcademicId;
        } elseif (preg_match('/^[0-9]{11}$/', $identity) === 1) {
            $data = json_encode([
                "AMKA" => $identity
            ]);
            $endpoint = $this->urlAmka;
        } else {
            return $res->withJson(array_merge(BaseApp::coreResponseData(false), [
                'message' => 'Error: Service Call Parameters Error, academic id must be a 12 digit number, amka id must be a 11 digit number'
            ]), 400);
        }

        $username = $req->getQueryParam('username', null);
        $password = $req->getQueryParam('password', null);
        if ($username !== null && $password !== null) {
            $this->username = $username;
            $this->password = $password;
        }

        $this->logger->info("queryID::{$identity}");

        $auth = $this->generateAuth();
        $headers = [
            "Authorization: {$auth}",
            'Content-Type: application/json',
            'Accept: */*',
            'User-Agent: Academic ID SLIM Client/v1.0 osteam'
        ];

        $results = $this->client->post($endpoint, $data, $headers);
        if ($results['success'] === false) {
                return $this->withJsonReady($res, $results['response'], $results['http_status']);
        }

        $fields_requested = $req->getQueryParam('fields', null);
        $field_names = [];
        if ($fields_requested) {
            $field_names = explode(',', $fields_requested);
        }

        if (count($field_names) > 0) { 
            // return specific fields
            $parsed_result = json_decode($results['response'], true);
            if ($parsed_result === false) {
                return $this->withJsonReady($res, $results['response'], $results['http_status']);
            }
            if ($parsed_result['response'] === 'SUCCESS' 
                && $parsed_result['errorReason'] === null 
                && isset($parsed_result['inspectionResult']) 
                && $parsed_result['inspectionResult']['webServiceSuccess'] === true) {
                $response_texts = array_map(function ($v) use ($parsed_result) {
                    return isset($parsed_result['inspectionResult'][$v]) ? 
                        $parsed_result['inspectionResult'][$v] :
                        ''; // null
                }, $field_names);
                // if (in_array(null, $response_texts)) {
                //     return $res->withJson(array_merge(BaseApp::coreResponseData(false), [
                //         'message' => 'Error: Service Call Parameters Error, fields query parameter has unknown fields'
                //     ]), 400);
                // } else {
                    return $this->withTextReady($res, implode(',', $response_texts), 200);
                // }
            }
            return $this->withJsonReady($res, $results['response'], $results['http_status']);
        } else {
            // return whole response
            $result = $results['response'];
            return $this->withJsonReady($res, $result);
        }
    }

    /**
     *
     * @param Psr\Http\Message\ServerRequestInterface $req
     * @param Psr\Http\Message\ResponseInterface $res
     * @param $args
     * @throws \Exception
     * @return Response
     */
    public function queryIDnoCD($req, $res, $args) 
    {
        $identity = $req->getQueryParam('id', 0);
        if (preg_match('/[^0-9]/', $identity) === 1) {
            return $res->withJson(array_merge(BaseApp::coreResponseData(false), [
                'message' => 'Error: Service Call Parameters Error, academic id or student amka id must be a number'
            ]), 400);
        } elseif (preg_match('/^[0-9]{12}$/', $identity) === 1) {
            $data = json_encode([
                "SubmissionCode" => $identity
            ]);
            $endpoint = $this->urlAcademicId;
        } elseif (preg_match('/^[0-9]{11}$/', $identity) === 1) {
            $data = json_encode([
                "AMKA" => $identity
            ]);
            $endpoint = $this->urlAmka;
        } else {
            return $res->withJson(array_merge(BaseApp::coreResponseData(false), [
                'message' => 'Error: Service Call Parameters Error, academic id must be a 12 digit number, amka id must be a 11 digit number'
            ]), 400);
        }

        $this->logger->info("queryIDnoCD::{$identity}");

        $auth = $this->generateAuth();
        $headers = [
            "Authorization: {$auth}",
            'Content-Type: application/json',
            'Accept: */*',
            'User-Agent: Academic ID SLIM Client/v1.0 osteam'
        ];

        $results = $this->client->post($endpoint, $data, $headers);
        if ($results['success'] === false) {
                return $this->withJsonReady($res, $results['response'], $results['http_status']);
        }

        $parsed_result = json_decode($results['response'], true);
        if ($parsed_result === false) {
            return $this->withTextReady($res, 'Unknown', 502);
        }
        if ($parsed_result['response'] === 'SUCCESS' && $parsed_result['errorReason'] === null) {
            if (isset($parsed_result['inspectionResult']) 
                && $parsed_result['inspectionResult']['webServiceSuccess'] === true
                && $endpoint == $this->urlAcademicId) {
                return $this->withTextReady($res, 'isStudent:true');
            } elseif (isset($parsed_result['inspectionResult']) 
                && $parsed_result['inspectionResult']['webServiceSuccess'] === true
                && $endpoint == $this->urlAmka 
                && is_array($parsed_result['inspectionResult']['beneficiaryNames'])
                && count($parsed_result['inspectionResult']['beneficiaryNames']) > 0) {
                if (is_array($bNames = $parsed_result['inspectionResult']['beneficiaryNames'][0]) && 
                    isset($bNames['beneficiaryName'])) {
                    return $this->withTextReady($res, 'isStudent:true');
                }
            }
        }
        return $this->withTextReady($res, 'isStudent:false');
    }

    /**
     *
     * @param Psr\Http\Message\ServerRequestInterface $req
     * @param Psr\Http\Message\ResponseInterface $res
     * @param $args
     * @throws \Exception
     * @return Response
     */
    public function student($req, $res, $args) 
    {
        $identity = $args['identity'];
        if (preg_match('/^[0-9]{11}$/', $identity) !== 1) {
            return $res->withJson(array_merge(BaseApp::coreResponseData(false), [
                'message' => 'Error: Service Call Parameters Error, student amka id must be 11 digit number'
            ]), 400);
        }

        $data = json_encode([
            "AMKA" => $identity
        ]);

        $this->logger->info("student::{$identity}");

        $auth = $this->generateAuth();
        $headers = [
            "Authorization: {$auth}",
            'Content-Type: application/json',
            'Accept: */*',
            'User-Agent: Academic ID SLIM Client/v1.0 osteam'
        ];

        $results = $this->client->post($this->urlAmka, $data, $headers);
        if ($results['success'] === false) {
            return $this->withJsonReady($res, $results['response'], $results['http_status']);
        }

        $fields_requested = $req->getQueryParam('fields', null);
        if (($fields_requested !== null) && ($fields_requested !== 'academicID')) {
            return $res->withJson(array_merge(BaseApp::coreResponseData(false), [
                'message' => 'Error: Service Call Parameters Error, fields query parameter has wrong value'
            ]), 400);
        }

        // έλεγχος επιστρεφόμενης τιμής και αποστολή αποτελέσματος
        $parsed_result = json_decode($results['response'], true);
        if ($parsed_result === false) {
            return $this->withTextReady($res, 'Unknown', 502);
        }
        if ($parsed_result['response'] === 'SUCCESS' && $parsed_result['errorReason'] === null) {
            if (isset($parsed_result['inspectionResult']) 
                && $parsed_result['inspectionResult']['webServiceSuccess'] === true
                && is_array($parsed_result['inspectionResult']['beneficiaryNames'])) {
                if (count($parsed_result['inspectionResult']['beneficiaryNames']) > 0) {
                    if (is_array($bNames = $parsed_result['inspectionResult']['beneficiaryNames'][0]) && 
                        isset($bNames['submissionCode']) && isset($bNames['beneficiaryName'])) {
                        if ($fields_requested === null) {
                            return $this->withTextReady($res, "{$bNames['beneficiaryName']}");
                        } elseif ($fields_requested === 'academicID') {
                            return $this->withTextReady($res, "{$bNames['beneficiaryName']},{$bNames['submissionCode']}");
                        }
                    }
                }
            }
            return $this->withTextReady($res, 'false');
        }
        return $this->withJsonReady($res, $results['response'], $results['http_status']);
    }


    /**
     *
     * @param Psr\Http\Message\ServerRequestInterface $req
     * @param Psr\Http\Message\ResponseInterface $res
     * @param $args
     * @throws \Exception
     * @return Response
     */
    public function testServiceStatus($req, $res, $args) 
    {
        $identity = $req->getQueryParam('id', null);
        if ($identity === null) {
            if (isset($args['identity'])) {
                $identity = $args['identity'];
            } else {
                $identity = '';
            }
        }

        $this->logger->info("testServiceStatus::{$identity}");

        return $this->withTextReady($res, "Student ID sent was: {$identity}");
    }
}
