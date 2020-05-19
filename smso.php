<?php
/**
* 2007-2016 Daniel
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
*  @author    Daniel <contact@examsple.com>
*  @copyright 2007-2016 Daniel
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

include(_PS_MODULE_DIR_ . '/smso/includes/smso.php');

class Smso extends Module
{
    private $html = '';

    public function __construct()
    {
        $this->name = 'smso';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'SMSO';
        $this->ps_versions_compliancy = array('min' => '1.5.0', 'max' => _PS_VERSION_);

        $this->need_instance = 0;
        $this->bootstrap = true;

        $this->displayName = $this->l('SEND SMS VIA SMSO');
        $this->description = $this->l('SEND SMS VIA SMSO platform');
        $this->module_key = '18ea46330cff99ce3f5205d615e56bfc';
        $this->table_name = $this->name;
        parent::__construct();
        
    }

    public function install()
    {
        if (!parent::install() or
            !$this->registerHook('displayOrderConfirmation') or
            !$this->registerHook('actionOrderStatusUpdate') or
            !$this->installMyTables()
            ) {
            return false;
        }
            
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() or
            !Configuration::deleteByName('SMSO_STATUS') or
            !Configuration::deleteByName('SMSO_SENDER') or
            !Configuration::deleteByName('SMSO_TOKEN') or
            !$this->deleteMyTables()
            ) {
            return false;
        }
        return true;
    }

    private function installMyTables()
    {
        $log = '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.$this->table_name .'_log` (
                `id` INT(12) NOT NULL AUTO_INCREMENT,
                `phone` VARCHAR(255) NOT NULL,
                `message` VARCHAR(255) NOT NULL,
                `date_sent` DATETIME NOT NULL,
                `token` VARCHAR(255) NOT NULL,
                PRIMARY KEY ( `id` )
                ) ENGINE = ' ._MYSQL_ENGINE_;

        if (!Db::getInstance()->Execute($log)
        ) {
            return false;
        }
        return true;
    }

    private function deleteMyTables()
    {
        $sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.$this->table_name .'_log`';
        if (!Db::getInstance()->Execute($sql)
        ) {
            return false;
        }
        return true;
    }

    public function getContent()
    {
        $this->postProcess();
        
        $this->displayForm();
        
        return $this->html;
    }

    private function postProcess()
    {

        $fields = array();
        $fields['apikey'] = Tools::getValue('apikey');

        $errors = 0;

        $this->context->controller->getLanguages();

        foreach ($fields as $key => $value) {
            if (!Validate::isGenericName($value)) {
                $this->_errors[] = $this->l('Invalid Field'). ': ' . $key;
            }
        }

        if (Tools::isSubmit('submitUpdate')) {

            if (Tools::strlen(Tools::getValue('apikey'))<1) {
                $this->_errors[] = $this->l('Empty Field'). ': ' . $this->l('Token');
                $errors++;
            }

            if ($errors<1) {
                Configuration::updateValue(
                    'SMSO_STATUS',
                    Tools::getValue('theswitch')
                );
                
                Configuration::updateValue(
                    'SMSO_TOKEN',
                    Tools::getValue('apikey')
                );

                /*NEW UPDATE VALUES*/
                Configuration::updateValue('SMSO_NEW_ORDER', Tools::getValue('new_order_switch'));

                /*SHIPPED UPDATE VALUES*/
                Configuration::updateValue('SMSO_SHIPPED_ORDER', Tools::getValue('shipped_order_switch'));
                Configuration::updateValue('SMSO_SHIPPED_ORDER_STATUS', Tools::getValue('shipped_order_status'));

                /*PAID UPDATE VALUES*/
                Configuration::updateValue('SMSO_PAID_ORDER', Tools::getValue('paid_order_switch'));
                Configuration::updateValue('SMSO_PAID_ORDER_STATUS', Tools::getValue('paid_order_status'));

                /*CANCELED UPDATE VALUES*/
                Configuration::updateValue('SMSO_CANCELED_ORDER',Tools::getValue('canceled_order_switch'));
                Configuration::updateValue('SMSO_CANCELED_ORDER_STATUS', Tools::getValue('canceled_order_status'));

                /*FINISHED UPDATE VALUES*/
                Configuration::updateValue('SMSO_FINISHED_ORDER', Tools::getValue('finished_order_switch'));
                Configuration::updateValue('SMSO_FINISHED_ORDER_STATUS',  Tools::getValue('finished_order_status'));

                /*REFUNDED UPDATE VALUES*/
                Configuration::updateValue('SMSO_REFUNDED_ORDER', Tools::getValue('refunded_order_switch'));
                Configuration::updateValue('SMSO_REFUNDED_ORDER_STATUS',  Tools::getValue('refunded_order_status'));
 
                foreach ($this->context->controller->_languages as $language){
                    Configuration::updateValue("SMSO_NEW_ORDER_TEXT"."_".(int)$language['id_lang'], Tools::getValue('new_order_text' . '_'.(int)$language['id_lang']));
                    Configuration::updateValue("SMSO_SHIPPED_ORDER_TEXT"."_".(int)$language['id_lang'], Tools::getValue('shipped_order_text' . '_'.(int)$language['id_lang']));

                    Configuration::updateValue("SMSO_PAID_ORDER_TEXT"."_".(int)$language['id_lang'], Tools::getValue('paid_order_text' . '_'.(int)$language['id_lang']));
                    Configuration::updateValue("SMSO_CANCELED_ORDER_TEXT"."_".(int)$language['id_lang'], Tools::getValue('canceled_order_text' . '_'.(int)$language['id_lang']));
                    Configuration::updateValue("SMSO_FINISHED_ORDER_TEXT"."_".(int)$language['id_lang'], Tools::getValue('finished_order_text' . '_'.(int)$language['id_lang']));
                    Configuration::updateValue("SMSO_REFUNDED_ORDER_TEXT"."_".(int)$language['id_lang'], Tools::getValue('refunded_order_text' . '_'.(int)$language['id_lang']));
                }

                // $senders = $this->getValueSender();
                // $sender_values_arr = array();
                // foreach ($senders as $s) {
                //     if(Tools::getValue('sender_'.$s['id']) != '') {
                //         $sender_values_arr[] = Tools::getValue('sender_'.$s['id']);
                //     }
                // }
                // if(!empty($sender_values_arr)) {
                //    Configuration::updateValue('SMSO_SENDER', implode(",", $sender_values_arr));                    
                // }else{
                //     Configuration::updateValue('SMSO_SENDER','');
                // }

                $sender_radio_val = Tools::getValue('sender');
                $sender_radio_val = !empty($sender_radio_val) ? $sender_radio_val : '';
                Configuration::updateValue('SMSO_SENDER', $sender_radio_val);

            }
            if ($this->_errors) {
                $this->html .= $this->displayError(implode($this->_errors, '<br />'));
            } else {
                $this->html .= $this->displayConfirmation($this->l('Settings Updated'));
            }
        }
    }

    public function sendSMS($to, $body)
    {
        if ((int)Configuration::get('SMSO_STATUS') === 1) {
            
            $sms = new SmsoClass(Configuration::get('SMSO_TOKEN')); 
            $sender = $this->getRandomSender();   
            if(!$sender) return true;
            $to = $this->checkPhone($to);
            $response = $sms->sendMessage($to, $body, $sender);

            Db::getInstance()->insert(
                $this->table_name.'_log',
                array(
                    'phone' => pSQL($to),
                    'message' => pSQL($body),
                    'date_sent' => date("Y-m-d H:i:s"),
                    'token' => $response['response']['responseToken']
                )
            );
            return true;
        } 
        else {
            return true;
        }
    }

    public function checkPhone($to) {
        if(strlen($to) == 10){
            $to = "+4".$to;
        }
        return $to;
    }

    public function getRandomSender()
    {
        $values = array();
        $senders_arr = array(
            0 => Configuration::get('SMSO_SENDER')
        );
        // $sender = Configuration::get('SMSO_SENDER');
        // if($sender != '') {
        //     $senders_arr = explode(',', $sender);
        // }

        if(Configuration::get('SMSO_TOKEN') != '') {
            $sms = new SmsoClass(Configuration::get('SMSO_TOKEN')); 
            $senders = $sms->getSenders();
            foreach ($senders['response'] as $s) {
                $values[] = $s['id'];
            }

            $senderii = array_intersect($senders_arr, $values);
            $rnd = rand(0,(count($senderii)-1));
            return $senderii[$rnd];
        }
        else {
            return false;
        }
    }

    public function getValueSender(){
        $values = array();
        if(Configuration::get('SMSO_TOKEN') != '') {
            $sms = new SmsoClass(Configuration::get('SMSO_TOKEN')); 
            $senders = $sms->getSenders();
            foreach ($senders['response'] as $s) {
                $values[] = array(
                    'id' => $s['id'],
                    'label' => $s['name']."(cost:".$s['pricePerMessage'].")",
                    'value' => $s['id'],
                );
            }
        }
        return $values;
    }

    public function getOrderStatuses()
    {
        $values = array();
        $statuses = OrderState::getOrderStates($this->context->language->id);

        foreach ($statuses as $s) {
            $values[] = array(
                'id' => $s['id_order_state'],
                'name' => $s['name'],
                'val' => $s['id_order_state'],
            );
        }
        
        return $values;
    }

    public function getSmsoInfo()
    {
        return '<div class="panel">
            <div class="panel-heading">SMSO INFO - <a href="https://app.smso.ro/">https://app.smso.ro/</a></div>
            <div class="panel-info">
                '.$this->l('Steps:').'
                <ul>
                <li>1. '.$this->l('Create an account on : ').'<a href="https://app.smso.ro/register">SMSO</a></li>
                <li>2. '.$this->l('Add credit to your SMSO account').'</li>
                <li>3. '.$this->l('Get Token for access from here : ').'<a href="https://app.smso.ro/developers/api">Token</a></li>
                <li>4. '.$this->l('Configure settings below').'</li>
                </ul>
            </div>
        </div>';
    }

    public function displayForm()
    {
        $this->html .= $this->getSmsoInfo();
        $this->html .= $this->generateForm();
    }

    private function generateForm()
    {
        $this->getOrderStatuses();
        $inputs = array();
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Enable'),
            'name' => 'theswitch',
            'desc' => $this->l('Choose to enable/disable SMSO on your website'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                    ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                    )
                )
        );

        $inputs[] = array(
            'type' => 'text',
            'class' => 'fixed-width-lg',
            'label' => $this->l('SMSO TOKEN'),
            'desc' => $this->l('Get your SMSO Token from here: https://app.smso.ro/developers/api'),
            'name' => 'apikey'
        );

        $inputs[] = array(
            'type'   => 'radio',
            'label'  => $this->l('Sender list'),
            'name'   => 'sender',
            'desc'   => $this->l('Sender list from SMSO'),
            'values' => $this->getValueSender(),
            // 'values' => array(
                // 'query' => $this->getValueSender(),
                // 'id' => 'id',
                // 'name' => 'name'
            // )
        );

        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('New order'),
            'name' => 'new_order_switch',
            'desc' => $this->l('Send SMS to customer on new order'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                    ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                    )
                )
        );

        $inputs[] = array(
            'type' => 'text',
            'name' => 'new_order_text',
            'class' => '',
            'lang' => true,
            'label' => $this->l('SMS Message for new order'),
            'desc' => $this->l('You can use variables in message: %id_order% , %ref_order% , %client% , %total%, %date_order% .'),
        );

        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Shipped order'),
            'name' => 'shipped_order_switch',
            'desc' => $this->l('Send SMS to customer on shipped status change.'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                    ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                    )
                )
        );

        $inputs[] = array(
            'type' => 'select',
            'label' => $this->l('Shipped Order Status'),
            'name' => 'shipped_order_status',
            'desc' => $this->l('Select order status for shipped order.'),
            'options' => array(
                'query' => $this->getOrderStatuses(),
                'id' => 'id',
                'name' => 'name'
            )
        );

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->l('SMS Message for shipped order'),
            'name' => 'shipped_order_text',
            'lang' => true,
            'class' => '',
            'desc' => $this->l('You can use variables in message: %id_order% , %ref_order% , %client% , %total%, %date_order% .'),
        );

        /* PAID INPUTS */
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Paid order'),
            'name' => 'paid_order_switch',
            'desc' => $this->l('Send SMS to customer on paid status change.'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                    ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                    )
                )
        );

        $inputs[] = array(
            'type' => 'select',
            'label' => $this->l('Paid Order Status'),
            'name' => 'paid_order_status',
            'desc' => $this->l('Select order status for paid order.'),
            'options' => array(
                'query' => $this->getOrderStatuses(),
                'id' => 'id',
                'name' => 'name'
            )
        );

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->l('SMS Message for paid order'),
            'name' => 'paid_order_text',
            'lang' => true,
            'class' => '',
            'desc' => $this->l('You can use variables in message: %id_order% , %ref_order% , %client% , %total%, %date_order% .'),
        );

        /* CANCELED INPUTS */
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Canceled order'),
            'name' => 'canceled_order_switch',
            'desc' => $this->l('Send SMS to customer on cancel status change.'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                    ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                    )
                )
        );

        $inputs[] = array(
            'type' => 'select',
            'label' => $this->l('Canceled Order Status'),
            'name' => 'canceled_order_status',
            'desc' => $this->l('Select order status for cancel order.'),
            'options' => array(
                'query' => $this->getOrderStatuses(),
                'id' => 'id',
                'name' => 'name'
            )
        );

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->l('SMS Message for cancel order'),
            'name' => 'canceled_order_text',
            'lang' => true,
            'class' => '',
            'desc' => $this->l('You can use variables in message: %id_order% , %ref_order% , %client% , %total%, %date_order% .'),
        );

        /* FINISHED INPUTS */
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Finished order'),
            'name' => 'finished_order_switch',
            'desc' => $this->l('Send SMS to customer on finished status change.'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                    ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                    )
                )
        );

        $inputs[] = array(
            'type' => 'select',
            'label' => $this->l('Finished Order Status'),
            'name' => 'finished_order_status',
            'desc' => $this->l('Select order status for finished order.'),
            'options' => array(
                'query' => $this->getOrderStatuses(),
                'id' => 'id',
                'name' => 'name'
            )
        );

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->l('SMS Message for finished order'),
            'name' => 'finished_order_text',
            'lang' => true,
            'class' => '',
            'desc' => $this->l('You can use variables in message: %id_order% , %ref_order% , %client% , %total%, %date_order% .'),
        );

        /* REFUNDED INPUTS */
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Refunded order'),
            'name' => 'refunded_order_switch',
            'desc' => $this->l('Send SMS to customer on refunded status change.'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                    ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                    )
                )
        );

        $inputs[] = array(
            'type' => 'select',
            'label' => $this->l('Refunded Order Status'),
            'name' => 'refunded_order_status',
            'desc' => $this->l('Select order status for refunded order.'),
            'options' => array(
                'query' => $this->getOrderStatuses(),
                'id' => 'id',
                'name' => 'name'
            )
        );

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->l('SMS Message for refunded order'),
            'name' => 'refunded_order_text',
            'lang' => true,
            'class' => '',
            'desc' => $this->l('You can use variables in message: %id_order% , %ref_order% , %client% , %total%, %date_order% .'),
        );

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('SMSO Settings'),
                    'icon' => 'icon-cogs'
                    ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitUpdate'
                ),
                /*'buttons' => array(
                    array(
                        'href' => AdminController::$currentIndex.'&configure='.$this->name.
                        '&token='.Tools::getAdminTokenLite('AdminModules').'&sentHistory',
                        'title' => $this->l('SMS History'),
                        'name' => 'sentHistory'
                    )
                )*/
            )
        );

        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper = new HelperForm();
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        // $helper->submit_action = 'submitUpdate';
        $helper->currentIndex = $this->context->link->getAdminLink(
            'AdminModules',
            false
        ).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        );
        return $helper->generateForm(array($fields_form));
    }

    
    public function getConfigFieldsValues()
    {
        $values = array();
		$this->context->controller->getLanguages();
        
        $values['sender'] = Configuration::get('SMSO_SENDER');
        // $sender = Configuration::get('SMSO_SENDER');
        // if($sender != '') {
        //     $senders_arr = explode(',', $sender);
        //     foreach ($senders_arr as $s) {
        //         $values['sender_'.$s] = true;
        //     }
        // }        		

        foreach ($this->context->controller->_languages as $language){
        	$values['new_order_text'][(int)$language['id_lang']] = Configuration::get('SMSO_NEW_ORDER_TEXT'.'_'.(int)$language['id_lang']);
        	$values['shipped_order_text'][(int)$language['id_lang']] = Configuration::get('SMSO_SHIPPED_ORDER_TEXT'.'_'.(int)$language['id_lang']);

            $values['paid_order_text'][(int)$language['id_lang']] = Configuration::get('SMSO_PAID_ORDER_TEXT'.'_'.(int)$language['id_lang']);
            $values['canceled_order_text'][(int)$language['id_lang']] = Configuration::get('SMSO_CANCELED_ORDER_TEXT'.'_'.(int)$language['id_lang']);
            $values['finished_order_text'][(int)$language['id_lang']] = Configuration::get('SMSO_FINISHED_ORDER_TEXT'.'_'.(int)$language['id_lang']);
            $values['refunded_order_text'][(int)$language['id_lang']] = Configuration::get('SMSO_REFUNDED_ORDER_TEXT'.'_'.(int)$language['id_lang']);
        }

        return array(
            'theswitch' => Configuration::get('SMSO_STATUS'),
            'apikey' => Configuration::get('SMSO_TOKEN'),
            'new_order_switch' => Configuration::get('SMSO_NEW_ORDER'),
            'shipped_order_switch' => Configuration::get('SMSO_SHIPPED_ORDER'),
            'shipped_order_status' => Configuration::get('SMSO_SHIPPED_ORDER_STATUS'),

            'paid_order_switch'     => Configuration::get('SMSO_PAID_ORDER'),
            'paid_order_status'     => Configuration::get('SMSO_PAID_ORDER_STATUS'),
            'canceled_order_switch' => Configuration::get('SMSO_CANCELED_ORDER'),
            'canceled_order_status' => Configuration::get('SMSO_CANCELED_ORDER_STATUS'),
            'finished_order_switch' => Configuration::get('SMSO_FINISHED_ORDER'),
            'finished_order_status' => Configuration::get('SMSO_FINISHED_ORDER_STATUS'),
            'refunded_order_switch' => Configuration::get('SMSO_REFUNDED_ORDER'),
            'refunded_order_status' => Configuration::get('SMSO_REFUNDED_ORDER_STATUS'),

            ) + $values;
    }

    public function hookactionOrderStatusUpdate($params)
    {
        // echo ((int)Configuration::get('SMSO_FINISHED_ORDER_STATUS'))."===";
        // echo($params["newOrderStatus"]->id);
        // var_dump($params);

    	$id_lang = $this->context->language->id;

        if(Configuration::get('SMSO_SHIPPED_ORDER_TEXT'.'_'.(int)$id_lang) && 
        (int)Configuration::get('SMSO_SHIPPED_ORDER_STATUS') && 
        (int)Configuration::get('SMSO_SHIPPED_ORDER') && 
        (int)Configuration::get('SMSO_STATUS') && 
        (int)$params["newOrderStatus"]->id == (int)Configuration::get('SMSO_SHIPPED_ORDER_STATUS')){

            $id_address = $params["cart"]->id_address_delivery;
            $address_data = new Address($id_address);

            $mobile_phone = $address_data->phone_mobile;
            if($mobile_phone == '') $mobile_phone = $address_data->phone;
            if($mobile_phone != '') {
	            $order_data = new Order($params["id_order"]);
	            $name = $address_data->firstname." ".$address_data->lastname;
	            $id_comanda = $params["id_order"];
	            $total = Tools::displayPrice($order_data->total_paid, $this->context->currency, false);

	            $date = date('d/m/Y',strtotime($order_data->date_add));

	            $vars = array(
	            	'%id_order%' => $order_data->id,
	            	'%ref_order%' => $order_data->reference,
	            	'%client%' => $name,
	            	'%total%' => $total,
	            	'%date_order%' => $date,
	            );

	            $format = Configuration::get('SMSO_SHIPPED_ORDER_TEXT'.'_'.(int)$id_lang);

	            $message = str_replace(array_keys($vars), array_values($vars), $format);
	        
	            $this->sendSMS($mobile_phone, $message);
	        }
        }

        if(Configuration::get('SMSO_PAID_ORDER_TEXT'.'_'.(int)$id_lang) && 
        (int)Configuration::get('SMSO_PAID_ORDER_STATUS') && 
        (int)Configuration::get('SMSO_PAID_ORDER') && 
        (int)Configuration::get('SMSO_STATUS') && 
        (int)$params["newOrderStatus"]->id == (int)Configuration::get('SMSO_PAID_ORDER_STATUS')){

            $id_address = $params["cart"]->id_address_delivery;
            $address_data = new Address($id_address);

            $mobile_phone = $address_data->phone_mobile;
            if($mobile_phone == '') $mobile_phone = $address_data->phone;
            if($mobile_phone != '') {
                $order_data = new Order($params["id_order"]);
                $name = $address_data->firstname." ".$address_data->lastname;
                $id_comanda = $params["id_order"];
                $total = Tools::displayPrice($order_data->total_paid, $this->context->currency, false);

                $date = date('d/m/Y',strtotime($order_data->date_add));

                $vars = array(
                    '%id_order%' => $order_data->id,
                    '%ref_order%' => $order_data->reference,
                    '%client%' => $name,
                    '%total%' => $total,
                    '%date_order%' => $date,
                );

                $format = Configuration::get('SMSO_PAID_ORDER_TEXT'.'_'.(int)$id_lang);

                $message = str_replace(array_keys($vars), array_values($vars), $format);
            
                $this->sendSMS($mobile_phone, $message);
            }
        }

        if(Configuration::get('SMSO_CANCELED_ORDER_TEXT'.'_'.(int)$id_lang) && 
        (int)Configuration::get('SMSO_CANCELED_ORDER_STATUS') && 
        (int)Configuration::get('SMSO_CANCELED_ORDER') && 
        (int)Configuration::get('SMSO_STATUS') && 
        (int)$params["newOrderStatus"]->id == (int)Configuration::get('SMSO_CANCELED_ORDER_STATUS')){

            $id_address = $params["cart"]->id_address_delivery;
            $address_data = new Address($id_address);

            $mobile_phone = $address_data->phone_mobile;
            if($mobile_phone == '') $mobile_phone = $address_data->phone;
            if($mobile_phone != '') {
                $order_data = new Order($params["id_order"]);
                $name = $address_data->firstname." ".$address_data->lastname;
                $id_comanda = $params["id_order"];
                $total = Tools::displayPrice($order_data->total_paid, $this->context->currency, false);

                $date = date('d/m/Y',strtotime($order_data->date_add));

                $vars = array(
                    '%id_order%' => $order_data->id,
                    '%ref_order%' => $order_data->reference,
                    '%client%' => $name,
                    '%total%' => $total,
                    '%date_order%' => $date,
                );

                $format = Configuration::get('SMSO_CANCELED_ORDER_TEXT'.'_'.(int)$id_lang);

                $message = str_replace(array_keys($vars), array_values($vars), $format);
            
                $this->sendSMS($mobile_phone, $message);
            }
        }

        if(Configuration::get('SMSO_FINISHED_ORDER_TEXT'.'_'.(int)$id_lang) && 
        (int)Configuration::get('SMSO_FINISHED_ORDER_STATUS') && 
        (int)Configuration::get('SMSO_FINISHED_ORDER') && 
        (int)Configuration::get('SMSO_STATUS') && 
        (int)$params["newOrderStatus"]->id == (int)Configuration::get('SMSO_FINISHED_ORDER_STATUS')){

            $id_address = $params["cart"]->id_address_delivery;
            $address_data = new Address($id_address);
            
            $mobile_phone = $address_data->phone_mobile;
            if($mobile_phone == '') $mobile_phone = $address_data->phone;
            if($mobile_phone != '') {
                $order_data = new Order($params["id_order"]);
                $name = $address_data->firstname." ".$address_data->lastname;
                $id_comanda = $params["id_order"];
                $total = Tools::displayPrice($order_data->total_paid, $this->context->currency, false);

                $date = date('d/m/Y',strtotime($order_data->date_add));

                $vars = array(
                    '%id_order%' => $order_data->id,
                    '%ref_order%' => $order_data->reference,
                    '%client%' => $name,
                    '%total%' => $total,
                    '%date_order%' => $date,
                );

                $format = Configuration::get('SMSO_FINISHED_ORDER_TEXT'.'_'.(int)$id_lang);

                $message = str_replace(array_keys($vars), array_values($vars), $format);
                
                $this->sendSMS($mobile_phone, $message);
                
            }
        }

        if(Configuration::get('SMSO_REFUNDED_ORDER_TEXT'.'_'.(int)$id_lang) && 
        (int)Configuration::get('SMSO_REFUNDED_ORDER_STATUS') && 
        (int)Configuration::get('SMSO_REFUNDED_ORDER') && 
        (int)Configuration::get('SMSO_STATUS') && 
        (int)$params["newOrderStatus"]->id == (int)Configuration::get('SMSO_REFUNDED_ORDER_STATUS')){

            $id_address = $params["cart"]->id_address_delivery;
            $address_data = new Address($id_address);

            $mobile_phone = $address_data->phone_mobile;
            if($mobile_phone == '') $mobile_phone = $address_data->phone;
            if($mobile_phone != '') {
                $order_data = new Order($params["id_order"]);
                $name = $address_data->firstname." ".$address_data->lastname;
                $id_comanda = $params["id_order"];
                $total = Tools::displayPrice($order_data->total_paid, $this->context->currency, false);

                $date = date('d/m/Y',strtotime($order_data->date_add));

                $vars = array(
                    '%id_order%' => $order_data->id,
                    '%ref_order%' => $order_data->reference,
                    '%client%' => $name,
                    '%total%' => $total,
                    '%date_order%' => $date,
                );

                $format = Configuration::get('SMSO_REFUNDED_ORDER_TEXT'.'_'.(int)$id_lang);

                $message = str_replace(array_keys($vars), array_values($vars), $format);
            
                $this->sendSMS($mobile_phone, $message);
            }
        }
        

        //return true;
    }

    public function hookDisplayOrderConfirmation($params)
    {
    	$id_lang = $this->context->language->id;

    	if(Configuration::get('SMSO_NEW_ORDER_TEXT'.'_'.(int)$id_lang) && 
        (int)Configuration::get('SMSO_STATUS') &&
        (int)Configuration::get('SMSO_NEW_ORDER')) {
	        $cart_data = new Cart(Tools::getValue('id_cart'));
	        $order_data = new Order(Tools::getValue('id_order'));

	        $id_address = $cart_data->id_address_delivery;
	        $address_data = new Address($id_address);

	        $mobile_phone = $address_data->phone_mobile;
	        if($mobile_phone == '') $mobile_phone = $address_data->phone;
	        if($mobile_phone != '') {
		        $name = $address_data->firstname." ".$address_data->lastname;
		        $id_comanda = $params["id_order"];
		        $total = Tools::displayPrice($order_data->total_paid, $this->context->currency, false);

		        $date = date('d/m/Y',strtotime($order_data->date_add));

		        $vars = array(
		        	'%id_order%' => $order_data->id,
		        	'%ref_order%' => $order_data->reference,
		        	'%client%' => $name,
		        	'%total%' => $total,
		        	'%date_order%' => $date,
		        );

		        $format = Configuration::get('SMSO_NEW_ORDER_TEXT'.'_'.(int)$id_lang);

		        $message = str_replace(array_keys($vars), array_values($vars), $format);
		    
		        $this->sendSMS($mobile_phone, $message);
		    }
	    }
        
        //return true;
    }

    public function psversion()
    {
        $version = _PS_VERSION_;
        $ver = explode(".", $version);
        return $ver[1];
    }
}
