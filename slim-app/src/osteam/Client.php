<?php

/*
 * 
 */

namespace Gr\Gov\Minedu\Osteam\Slim;

use Exception;

/**
 * Description of Client
 *
 * @author spapad
 */
class Client
{

    private $_debug = false;
    private $_settings = [
        'base_uri' => '' // must set this
    ];

    public function __construct($settings = [])
    {
        $this->_settings = array_merge($this->_settings, $settings);
    }

    protected function setCommonCurlOptions($ch, $uri, $headers)
    {
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, "OSTEAM SLIM client");
        if (isset($this->_settings['NO_SAFE_CURL']) && $this->_settings['NO_SAFE_CURL'] === true) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        if ($this->_debug === true) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }
    }

    public function put($uri, $payload, $headers = [])
    {
        $ch = curl_init();

        $this->setCommonCurlOptions($ch, $uri, $headers);

        // curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("Λάθος κατά την κλήση του {$uri}. Curl error: " . curl_error($ch) . " Curl info: " . var_export(curl_getinfo($ch), true));
        }
        if (intval(($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) / 100) != 2) {
        // πραγματοποιήθηκε κλήση αλλά δεν ήταν "επιτυχής"
            throw new Exception("Αποτυχημένη κλήση. HTTP STATUS {$http_code}. Η απάντηση ήταν: {$result}", $http_code);
        }
        curl_close($ch);
        return $result;
    }

    public function post($uri, $payload, $headers = [])
    {
        $ch = curl_init();

        $this->setCommonCurlOptions($ch, $uri, $headers);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("Λάθος κατά την κλήση του {$uri}. Curl error: " . curl_error($ch) . " Curl info: " . var_export(curl_getinfo($ch), true));
        }
        if (intval(($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) / 100) != 2) {
        // πραγματοποιήθηκε κλήση αλλά δεν ήταν "επιτυχής"
            return [
                'success' => false,
                'http_status' => $http_code,
                'response' => $result
            ];
            // throw new Exception("Αποτυχημένη κλήση. HTTP STATUS {$http_code}. Η απάντηση ήταν: {$result}", $http_code);
        }
        curl_close($ch);
        return [
            'success' => true,
            'http_status' => $http_code,
            'response' => $result
        ];
    }

    public function get($uri, $params = [], $headers = [])
    {
        $ch = curl_init();

        if (is_array($params) && count($params) > 0) {
            $qs = '?' . http_build_query($params);
        } else {
            $qs = '';
        }
        $this->setCommonCurlOptions($ch, "{$uri}{$qs}", $headers);

//        curl_setopt($ch, CURLOPT_HTTPGET, true); // default
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("Λάθος κατά την κλήση του {$uri}. Curl error: " . curl_error($ch) . " Curl info: " . var_export(curl_getinfo($ch), true));
        }
        if (intval(($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) / 100) != 2) {
        // πραγματοποιήθηκε κλήση αλλά δεν ήταν "επιτυχής"
            return [
                'success' => false,
                'http_status' => $http_code,
                'response' => $result
            ];
            // throw new Exception("Αποτυχημένη κλήση. HTTP STATUS {$http_code}. Η απάντηση ήταν: {$result}", $http_code);
        }
        curl_close($ch);
        return [
            'success' => true,
            'http_status' => $http_code,
            'response' => $result
        ];
    }

    public function setDebug($debug = true)
    {
        $this->_debug = ($debug === true);
        return;
    }

}
