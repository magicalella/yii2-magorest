<?php

namespace magicalella\magorest;

use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;

/**
 * Class Magorest
 * Magorest component
 * @package magicalella\magorest
 *
 * @author Mariusz Stróż <info@inwave.pl>
 */
class Magorest extends Component
{

    /**
     * @var string 
     */
    public $user;

    /**
     * @var string 
     */
    public $password;
    
    /**
     * @var string
     */
    public $endpoint;

    /**
     * @var retrive after login
     */
    public $apiKey;
    
    private $client;
    
    public $log = '';

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->user) {
            throw new InvalidConfigException('$user not set');
        }

        if (!$this->password) {
            throw new InvalidConfigException('$password not set');
        }
        
        if (!$this->endpoint) {
            throw new InvalidConfigException('$endpoint not set');
        }
        
        $this->client = new Client(
            [
                'baseUrl' => $this->$endpoint ,
                'responseConfig' => [
                    'format' => Client::FORMAT_JSON
                ],
            ]
        );
        
        if(!$this->access_token){
            $this->getApiKey();
        }
        parent::init();
    }
    
    /**
    Login recupera ApiKey
     */
    private function getApiKey(){
        $data = [];
        $data['username'] = $this->username;
        $data['password'] = $this->password;
        $json = json_encode($data);
        
        $request = $this->client->createRequest()
            ->setMethod('POST')
            ->setUrl('loginmanager/login')
            ->setFormat(Client::FORMAT_JSON)
            ->setHeaders([
                'Content-Type' => 'application/json;charset=UTF-8',
                'Accept' => 'application/json' ,
            ])
            ->setContent($json);
        $response = $request->send();
        
        if ($response->isOk) {
            if (!($this->apiKey = $response->data)) {
                Yii::$app->session->setFlash('error', 'ApiKey non ricevuta');
                Yii::error(sprintf('ERRORE CHIAMATA ApiKey MAGO :  ApiKey non ricevuta'), __METHOD__);
                $this->log .= ' ERRORE CHIAMATA ApiKey MAGO :  ApiKey non ricevuta ';
                return false;
            }
            return true;
        }else {
            //testare CODE del response checkStatusCode()
            Yii::$app->session->setFlash('error', 'Impossibile connettersi a MAGO');
            Yii::error(sprintf('ERRORE CHIAMATA ApiKey MAGO :  Impossibile connettersi a MAGO'), __METHOD__);
            $this->log .= ' ERRORE CHIAMATA ApiKey MAGO :  Impossibile connettersi a MAGO ';
            return false;
        }
    }
    
    private function checkLoginIsValid()
    {
        $url = 'loginmanager/loginisvalid/'.$this->apiKey;
        $request = $this->client->createRequest()
            ->setMethod('GET')
            ->setUrl($url)
            ->setFormat(Client::FORMAT_JSON)
            ->setHeaders([
                'Accept: application/json, application/json',
                'Content-Type: application/json;charset=UTF-8',
                'ApiKey: ' . $this->apiKey
            ])
            ->setContent($data);
        $response = $request->send();
        
        if (!$response->isOk) {
            //testare CODE del response checkStatusCode()
            Yii::error(sprintf('ERRORE CHIAMATA MAGO %s data : ',$url,print_r($data, true)), __METHOD__);
            $this->log .= ' ERRORE CHIAMATA MAGO : '.$url;
        }
        
        return $response;
    }
    
    private function checkLogOff()
    {
        $url = 'loginmanager/logoff/'.$this->apiKey;
        $request = $this->client->createRequest()
            ->setMethod('GET')
            ->setUrl($url)
            ->setFormat(Client::FORMAT_JSON)
            ->setHeaders([
                'Accept: application/json, application/json',
                'Content-Type: application/json;charset=UTF-8',
                'ApiKey: ' . $this->apiKey
            ])
            ->setContent($data);
        $response = $request->send();
        
        if (!$response->isOk) {
            //testare CODE del response checkStatusCode()
            Yii::error(sprintf('ERRORE CHIAMATA MAGO %s data : ',$url,print_r($data, true)), __METHOD__);
            $this->log .= ' ERRORE CHIAMATA MAGO : '.$url;
        }
        return $response;
    }
    
    
    
    private function checkStatusCode($response)
    {
        //con mappatura tutti errori
        
    }
    
    /**
     * Call MAGO function POST
     * @param string $call Name of API function to call
     * @param array $data
     * @return \stdClass Magorest response
     */
    public function post($call, $data)
    {
        $json = json_encode($data);
        $result = $this->curl($this->endpoint . $call, $json,'POST');
        return json_decode($result);
    }
    
    /**
     * Call MAGO function GET
     * @param string $call Name of API function to call
     * @param array $data
     * @return \stdClass Magorest response
     */
    public function get($call, $data)
    {
        $json = json_encode($data);
        $result = $this->curl($this->endpoint . $call, $json,'GET');
        return json_decode($result);
    }
    

    /**
     * Do request by CURL
     * @param $url
     * @param $data
     * @return mixed
     */
    private function curl($url, $data, $method = 'POST')
    {
        $response = false;
//         $ch = curl_init($url);
//         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type_request);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//                 'Accept: application/json, application/json',
//                 'Content-Type: application/json;charset=UTF-8',
//                 'ApiKey: ' . $this->apiKey
//             )
//         );
// 
//         return curl_exec($ch);
        
        $request = $this->client->createRequest()
            ->setMethod($method)
            ->setUrl($url)
            ->setFormat(Client::FORMAT_JSON)
            ->setHeaders([
                'Accept: application/json, application/json',
                'Content-Type: application/json;charset=UTF-8',
                'ApiKey: ' . $this->apiKey
            ])
            ->setContent($data);
        $response = $request->send();
        
        if (!$response->isOk) {
            //testare CODE del response checkStatusCode()
            Yii::error(sprintf('ERRORE CHIAMATA MAGO %s data : ',$url,print_r($data, true)), __METHOD__);
            $this->log .= ' ERRORE CHIAMATA MAGO : '.$url;
        }
        //dopo ogni chiamata chiudo sessione
        $this->checkLogOff();
        return $response;
    }


}
