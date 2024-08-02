<?php namespace Alpaca\Stream;

use Carbon\Carbon;
use Alpaca\Alpaca;

use function Amp\Websocket\Client\connect;

class WebSocket
{

    /**
     * TDAmeritrade
     *
     * @var Alpaca\Alpaca
     */
    private $alp;

    /**
     *  __construct 
     *
     */
    public function __construct(Alpaca $alp) {
        $this->alp = $alp;
    }

    /**
     * run()
     *
     */
    public function run($fn, $error) 
    {
        $data = $this->getPrincipals();
        $credentials = $this->setCredentials($data);

        $connection = connect('wss://'.$data['streamerInfo']['streamerSocketUrl'].'/ws');

        $connection->sendText('
                    {
                        "requests":[
                            {
                                "service" : "ADMIN",
                                "command" : "LOGIN", 
                                "requestid" : "0",
                                "account" : "'.$credentials['userid'].'",
                                "source" : "'.$credentials['appid'].'",
                                "parameters" : {
                                    "credential" : "'.rawurlencode(http_build_query($credentials)).'",
                                    "token" : "'.$credentials['token'].'",
                                    "version" : "1.0"
                                 }
                            }
                        ]
                    }');
        $connection->sendText('
                    {
                        "requests":[
                            {
                                "service" : "ADMIN",
                                "command" : "QOS", 
                                "requestid" : "1",
                                "account" : "'.$credentials['userid'].'",
                                "source" : "'.$credentials['appid'].'",
                                "parameters" : {
                                    "qoslevel": "0"
                                }
                            }
                        ]
                    }'); 
        $connection->sendText('
                    {
                        "requests":[
                            {
                                "service" : "ACCT_ACTIVITY",
                                "command" : "SUBS", 
                                "requestid" : "2",
                                "account" : "'.$credentials['userid'].'",
                                "source" : "'.$credentials['appid'].'",
                                "parameters" : {
                                    "keys": "'.$data['streamerSubscriptionKeys']['keys'][0]['key'].'", 
                                    "fields": "0,1,2,3"
                                }
                            }
                        ]
                    }');
        $i = 0;

        while ($message = $connection->receive()) 
        {
            $i++;
            $payload = $message->buffer();

            $r = $fn(json_decode($payload,1),$i);

            if ($r == false) {
                $connection->close();
                break;
            }
        }

    }

    /**
     * setCredentials()
     *
     */
    public function setCredentials($data) 
    {
        $timestamp = $this->convertTimeStamp($data['streamerInfo']['tokenTimestamp']);

        return [
            'userid' => $data['accounts'][0]['accountId'],
            'token' => $data['streamerInfo']['token'],
            'company' => $data['accounts'][0]['company'],
            'segment' => $data['accounts'][0]['segment'],
            'cddomain' => $data['accounts'][0]['accountCdDomainId'],
            'usergroup' => $data['streamerInfo']['userGroup'],
            'accesslevel' => $data['streamerInfo']['accessLevel'],
            'authorized' => 'Y',
            'timestamp' => $timestamp,
            'appid' => $data['streamerInfo']['appId'],
            'acl' => $data['streamerInfo']['acl']
        ];
    }

    /**
     * convertTimeStamp()
     *
     */
    public function convertTimeStamp($timestamp) {
        return ((Carbon::parse($timestamp)->valueOf()));
    }

    /**
     * getPrincipals()
     *
     */
    public function getPrincipals() 
    {
        return $this->alp->accounts()->userPrincipals([
            'fields' => 'streamerSubscriptionKeys,streamerConnectionInfo,preferences,surrogateIds'
        ]); 
    }
}
