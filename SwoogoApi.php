<?php
class SwoogoApi
{

    protected $consumerKey;
    protected $consumerSecret;
    protected $accessToken;

    public function __construct($consumerKey, $consumerSecret)
    {

        if (!in_array('curl', get_loaded_extensions())) {
            throw new \Exception('You need to install cURL, see: http://curl.haxx.se/docs/install.html');
        }

        if (empty($consumerKey) || empty($consumerSecret)) {
            throw new \Exception('Make sure you are passing in the correct parameters');
        }

        $this->consumerKey = urlencode($consumerKey);
        $this->consumerSecret = urlencode($consumerSecret);
        if (!$this->accessToken) {
            $this->authorize();
        }
    }


    public function request($url, $parameters = array(), $method = 'get')
    {

        $method = strtolower($method);

        $ch = curl_init();
        $paramString = http_build_query($parameters);
        curl_setopt($ch, CURLOPT_URL, $url . ($method == 'get' && !empty($paramString)?'?'.$paramString:''));
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paramString);
        } else if ($method == 'put') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($paramString)));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paramString);
        } else if ($method == 'delete') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->accessToken));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    /**
     * Send request to oauth server to get our access token
     */
    private function authorize()
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERPWD, $this->consumerKey.':'.$this->consumerSecret);
        curl_setopt($ch, CURLOPT_URL, 'https://www.swoogo.com/api/v1/oauth2/token.json');
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = json_decode(curl_exec($ch));
        if (empty($result->access_token)) {
            throw new \Exception(__CLASS__.': Unable to validate your consumer key and consumer secret. '.print_r($result, 1));
        }
        $this->accessToken = $result->access_token;

    }

}
