<?php
// Connects to our user api for user management
// @author Wikitude
class UserAPI {

    // The access key
    private $apiKey = null;
    // The URL to the userAPI service
    private $url = null;

    function __construct($apiKey, $url){
        //initialize the values
        $this->apiKey = $apiKey;
        $this->url = $url;
    }

    public function createUser($email, $givenname, $familyname, $password) {
        $payload = array(
            "email" => $email,
            "givenname" => $givenname,
            "familyname" => $familyname,
            "password" => $password
        );

        return $this->sendRequest('POST', $this->url, $payload);
    }

    public function getUser($email) {
        $path = $this->url . "/?email=" . urlencode($email);
        return $this->sendRequest('GET', $path);
    }

    public function modifyUser($email, $givenname, $familyname, $password) {
        $path = $this->url . "/?email=" . urlencode($email);
        $payload = array();

        if (isset($givenname)) {
            $payload['givenname'] = $givenname;
        }

        if (isset($familyname)) {
            $payload['familyname'] = $familyname;
        }

        if (isset($password)) {
            $payload['password'] = $password;
        }

        return $this->sendRequest('PUT', $path, $payload);
    }

    // public function deleteUser($email) {
    //     $path = $this->url . "/?email=" . urlencode($email);
    //     return $this->sendRequest('DELETE', $path);
    // }

    private function sendRequest($method, $url, $payload = null) {
        $headers = array(
            "x-api-key: {$this->apiKey}"
        );
        $data = null;
        if ( $payload ) {
            $data = json_encode($payload);
        }
        $response = $this->simple_curl($url, $method, $headers, $data);

        //var_dump($response);
        //print '<BR>';

        $jsonResponse = null;
        if ( $response['content'] ) {
            $jsonResponse = $this->readJsonBody($response['content']);
        }

        if ($response['code'] != 200 && $response['code'] != 202) {
            if ($jsonResponse && $jsonResponse['message']) {
                throw new Exception('Error response with Code '.$response['code'].': '. $jsonResponse['message']);
            } else {
                throw new Exception('Error response with Code '.$response['code']);
            }            
        }

        return $jsonResponse;
    }

    private function readJsonBody($jsonstring) {
        return json_decode($jsonstring, true);
    }

    private function simple_curl($uri, $method='GET', $curl_headers=array(), $data=null, $curl_options=array()) {
        // defaults
        $default_curl_options = array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
        );
        $default_headers = array();
    
        // validate input
        $allowed_methods = array('GET', 'POST', 'PUT', 'DELETE');
    
        if(!in_array($method, $allowed_methods))
            throw new \Exception("'$method' is not valid cURL HTTP method.");
    
        if(!empty($data) && !is_string($data))
            throw new \Exception("Invalid data for cURL request '$method $uri'");
    
        // init
        $curl = curl_init($uri);
    
        // apply default options
        curl_setopt_array($curl, $default_curl_options);
    
        // apply method specific options
        switch($method) {
            case 'GET':
                break;
            case 'POST':
                if(!is_string($data))
                    throw new \Exception("Invalid data for cURL request '$method $uri'");
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case 'PUT':
                if(!is_string($data))
                    throw new \Exception("Invalid data for cURL request '$method $uri'");
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }
    
        // apply user options
        curl_setopt_array($curl, $curl_options);
    
        // add headers
        curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge($default_headers, $curl_headers));
    
        // parse result
        $raw = rtrim(curl_exec($curl));
        $lines = explode("\r\n", $raw);
        $headers = array();
        $content = '';
        $write_content = false;
        if(count($lines) > 3) {
            foreach($lines as $h) {
                if($h == '')
                    $write_content = true;
                else {
                    if($write_content)
                        $content .= $h."\n";
                    else
                        $headers[] = $h;
                }
            }
        }
        $error = curl_error($curl);
        $info = curl_getinfo($curl);
        $code = $info["http_code"];

        curl_close($curl);
    
        // return
        return array(
            'raw' => $raw,
            'headers' => $headers,
            'content' => $content,
            'error' => $error,
            'code' => $code
        );
    }
}

?>