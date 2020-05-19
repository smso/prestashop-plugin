<?php
/**
* 2013-2019 SMSO
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    SMSO <dev@SMSO.ro>
*  @copyright 2013-2019 SMSO
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class SmsoClass
{

    public static $baseUrl = 'https://app.smso.ro/api/v1/';


    public function __construct($token)
    {
        $this->token = $token;
    }

    public function createContact($name, $number)
    {
        $data = array();
        $data['name'] = $name;
        $data['number'] = $number;
        return $this->makeRequest('/api/v3/contacts/create', 'POST', $data);
    }

    public function getSenders()
    {
        return $this->makeRequest('/senders', 'GET');
    }

    public function sendMessage($to, $body, $sender)
    {
        $data = array();
        $data['to'] = $to;
        $data['body'] = $body;
        $data['sender'] = $sender;
        return $this->makeRequest('/send', 'POST' , $data);
    }

    public function sendMessageSIM($to, $body)
    {
        $data = array();
        $data['to'] = $to;
        $data['body'] = $body;
        return $this->makeRequest('/send/sim', 'POST' , $data);
    }

    public function getStatusMessage($msg_token)
    {
        /*
        dispatched  No  The message is in the process of sending to the network. (rarely seen)
        sent    No  The message has been sent to the network.
        delivered   Yes The message has been delivered to the phone.
        undelivered Yes The message was undelivered.
        expired Yes The message is expired.
        error   Yes There was an error sending the message.
        */
        $data = array();
        $data['responseToken'] = $msg_token;
        return $this->makeRequest('/status', 'POST' , $data);
    }

    private function makeRequest($url, $method, $fields = array())
    {

        $token = $this->token;

        $url = SmsoClass::$baseUrl.$url;

        $fieldsString = http_build_query($fields);

        $headers = array();
        $headers[] = "X-Authorization: ".$token;

        $ch = curl_init();

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, count($fields));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);
        } else {
            $url .= '?'.$fieldsString;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        $return = array();
        $return['response'] = Tools::jsonDecode($result, true);

        if ($return['response'] == false) {
            $return['response'] = $result;
        }

        $return['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $return;
    }
}
