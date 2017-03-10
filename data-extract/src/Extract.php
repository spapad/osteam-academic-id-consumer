<?php

namespace Gr\Gov\Minedu\Osteam\App;

use Gr\Gov\Minedu\Osteam\Slim\Client;

/**
 * Description of app
 *
 * @author spapad
 */
class Extract 
{
    protected $client = null;
    protected $settings = [];

    public function __construct($client, $settings)
    {
        $this->client = $client;
        $this->settings = $settings; 
    }

    protected function generateAuth() 
    {
        $pass_md5 = md5($this->password);
        return "Basic " . base64_encode("{$this->username}:{$pass_md5}");
    }

    public function getAcademicID($amka, $auth_header_student) 
    {
        if (preg_match('/^[0-9]{11}$/', $amka) !== 1) {
            return 'ERROR:Service Call Parameters Error, student amka id must be 11 digit number';
        }

        $data = [
            'fields' => 'academicID'
        ];

        $headers = [
            "Authorization: {$auth_header_student}",
            'Content-Type: application/json',
            'Accept: */*',
            'User-Agent: osteam php command line client'
        ];

        try {
            $results = $this->client->get($this->settings['base_uri_student'] . "/{$amka}", $data, $headers);
        } catch (\Exception $e) {
            return "ERROR:" . $e->getMessage();
        }

        if ($results['success'] === false) {
            return "ERROR:{$results['response']}, {$results['http_status']}";
        }

        // echo var_export($results, true), PHP_EOL;
        return $results['response'];
    }

    public function getStudentInformation($academic_id, $auth_header_student) 
    {
        if (preg_match('/^[0-9]{12}$/', $academic_id) !== 1) {
            return 'ERROR:Service Call Parameters Error, student academic id must be 11 digit number';
        }

        $data = [
            'id' => $academic_id,
            'username' => $this->settings['username'],
            'password' => $this->settings['password'],
            'fields' => 'entryYear,studentshipType,departmentName,postGraduateProgram'
        ];

        $headers = [
            "Authorization: {$auth_header_student}",
            'Content-Type: application/json',
            'Accept: */*',
            'User-Agent: osteam php command line client'
        ];

        try {
            $results = $this->client->get($this->settings['base_uri_query'], $data, $headers);
        } catch (\Exception $e) {
            return "ERROR:" . $e->getMessage();
        }

        if ($results['success'] === false) {
            return "ERROR:{$results['response']}, {$results['http_status']}";
        }

        // echo var_export($results, true), PHP_EOL;
        return $results['response'];
    }

}
