<?php

/**
 * Convinience function to send a json encoded response and exit
 * 
 * @param $response array Array containing the response to json encode
 * @param $error_code HTTP STATUS response code 
 */
function error_response($response, $error_code = 200) 
{
    http_response_code($error_code);
    header("Content-Type: application/json");
    echo json_encode($response);
    exit(0);
}

//

$settings_file = __DIR__ . '/settings.php';
if (is_readable($settings_file)) {
    $settings = require($settings_file);
} else {
    error_response(['message' => 'Error: Application Server (Internal Error, cannot read file system or missing property file)'], 500);
}

/**
 * Get params.
 * operation == queryID || echo 
 */
$params = [
    'username' => ($username = filter_input(INPUT_GET, 'username')) ? $username : $settings['username'],
    'password' => ($password = filter_input(INPUT_GET, 'password')) ? $password : $settings['password'],
    'identity' => ($identity = filter_input(INPUT_GET, 'identity')) ? $identity : '-1',
    'operation' => (in_array($operation = filter_input(INPUT_GET, 'operation'), [
        'testServiceStatus',
        'queryIDnoCD', 'queryID',
        'echo',
    ])) ? $operation : 'queryID',
    'secure_endpoint_username' => isset($settings['secure_endpoint_username']) ? $settings['secure_endpoint_username'] : 'n/a',
    'secure_endpoint_password' => isset($settings['secure_endpoint_password']) ? $settings['secure_endpoint_password'] : 'n/a',
];

/**
 * Call remote ws 
 */
function wscall($params)
{
    /**
     * Prep auth 
     */
    $pass_md5 = md5($params['password']);
    $auth = "Basic " . base64_encode("{$params['username']}:{$pass_md5}");

    /**
     * Do the call 
     */
    $ch = curl_init();

    $payload = json_encode(array("SubmissionCode" => $params['identity']));

    curl_setopt($ch, CURLOPT_URL, "https://academicidapp.grnet.gr/admin/web/ws/users/inspectAcademicID");
    // curl_setopt($ch, CURLOPT_URL, "https://academicidapp.grnet.gr/admin/web/ws/users/inspectAMKA");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: {$auth}",
        'Content-Type: application/json',
        'Accept: */*',
        'User-Agent: AcademicIDClientTestPHP/v1.0 osteam'
        ]
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        error_response(['message' => 'Error: EDET Web Service Unreachable'], 500);
    }
    if (intval(($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) / 100) != 2) {
        http_response_code($http_code);            
    }

    curl_close($ch);
    return $result;
}

/**
 * Get http request header
 */
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

/**
 * Check the input 
 * 
 * @return true|mixed True in case of valid input, or response and exit
 */ 
function check_input($identity)
{
    $valid = true;

    if (preg_match('/^[0-9]{12}$/', $identity) !== 1) {
        error_response(['message' => 'Error: Service Call Parameters Error, academic id must be 12 digit number'], 500);
    }

    return true;
}

/**
 * Check the authentication header
 * 
 * @return true|mixed True in case of valid auth header, or response and exit
 */
function check_authentication_header($username, $password) 
{
    $auth = true;
    $headers = getallheaders();
    if (array_key_exists('Authorization', $headers)) {
        $header = $headers['Authorization'];
        $auth_parts = [];
        if (preg_match('/^Basic (.+)$/', $header, $auth_parts) === 1) {
            if ($auth_parts[1] !== base64_encode("{$username}:{$password}")) {
                error_response(['message' => 'Error: Invalid or Missing Basic Authorization Credentials'], 401);
            }
        } else {
            error_response(['message' => 'Error: Invalid or Missing Basic Authorization Credentials'], 401);
        }
    } else {
        error_response(['message' => 'Error: Missing Basic Authorization Header'], 401);
    }

    return true;
}

/**
 * 
 */
switch ($params['operation']) {
    case 'queryID':
        check_authentication_header($params['secure_endpoint_username'], $params['secure_endpoint_password']);
        header("Content-Type: application/json");
        $result = wscall($params);
        break;
    case 'queryIDnoCD':
        check_authentication_header($params['secure_endpoint_username'], $params['secure_endpoint_password']);
        check_input($params['identity']);
        header("Content-Type: text/plain");
        $result = json_decode(wscall($params), true);
        $IDis = $result !== null &&
            isset($result['response']) && $result['response'] == 'SUCCESS' &&
            isset($result['inspectionResult']['webServiceSuccess']) && 
            $result['inspectionResult']['webServiceSuccess'] == true;
        $result = "isStudent:" . ($IDis ? 'true' : 'false');
        break;
    case 'testServiceStatus':
        check_authentication_header($params['secure_endpoint_username'], $params['secure_endpoint_password']);
        header("Content-Type: text/plain");
        $result = "StudentID sent was:" . trim(filter_input(INPUT_GET, 'id'));
        break;
    case 'echo':
    default:
        header("Content-Type: text/plain");
        unset($_GET['operation']);
        $result = http_build_query($_GET);
        break;
}

echo $result;
exit(0);
