<?PHP
namespace App;
/**
 * Class Facepp - Face++ PHP SDK
 *
 * @author Tianye
 * @author Rick de Graaff <rick@lemon-internet.nl>
 * @since  2013-12-11
 * @version  1.1
 * @modified 16-01-2014
 * @copyright 2013 - 2015 Tianye
 **/
class Facepp
{
    ######################################################
    ### If you choose Amazon(US) server,please use the ###
    ### http://api-us.faceplusplus.com/facepp/v3               ###
    ### or                                             ###
    ### https://api-us.faceplusplus.com/facepp/v3              ###
    ######################################################
    public $server          = 'https://api-us.faceplusplus.com/facepp/v3';
    #public $server         = 'https://apicn.faceplusplus.com/facepp/v3';
    #public $server         = 'http://api-us.faceplusplus.com/facepp/v3';
    #public $server         = 'https://api-us.faceplusplus.com/facepp/v3';


    public $api_key         = '';        // set your API KEY or set the key static in the property
    public $api_secret      = '';        // set your API SECRET or set the secret static in the property

    private $useragent      = 'Faceplusplus PHP SDK/1.1';

    /**
     * @param $method - The Face++ API
     * @param array $params - Request Parameters
     * @return array - {'http_code':'Http Status Code', 'request_url':'Http Request URL','body':' JSON Response'}
     * @throws Exception
     */
    public function execute($method, array $params)
    {
        if( ! $this->apiPropertiesAreSet()) {
            throw new Exception('API properties are not set');
        }

        $params['api_key']      = $this->api_key;
        $params['api_secret']   = $this->api_secret;

        return $this->request("{$this->server}{$method}", $params);
    }

    private function request($request_url, $request_body)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $request_url,
          CURLOPT_POST => true,
          CURLOPT_POSTFIELDS => $request_body,
          CURLOPT_RETURNTRANSFER => true)
        );
        ini_set('max_execution_time', 300);

        $concurrencyErrorMsg = 'CONCURRENCY_LIMIT_EXCEEDED';
        $response = false;
        while($response === false || $concurrencyErrorMsg === 'CONCURRENCY_LIMIT_EXCEEDED' || $err) {
          $response = curl_exec($curl);
          $err = curl_error($curl);
          $jsonRes = json_decode( $response );
          if(isset($jsonRes->error_message)) {
            $concurrencyErrorMsg = $jsonRes->error_message;
          }
          else {
            if($response){
              $concurrencyErrorMsg = 'No Concurrency error';
            }
          }
        }

        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
          //return "cURL Error #:" . $err;
          return false;
        } else {
          return $response;
        }
    }

    private function apiPropertiesAreSet()
    {
        if( ! $this->api_key) {
            return false;
        }

        if( ! $this->api_secret) {
            return false;
        }
        
        return true;
    }
}
