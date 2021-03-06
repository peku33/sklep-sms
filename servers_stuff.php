<?php

use App\Payment;

define('IN_SCRIPT', "1");
define("SCRIPT_NAME", "servers_stuff");

require_once "global.php";

function xml_output($return_value, $text, $positive, $extra_data = "")
{
    $output = "<return_value>{$return_value}</return_value>";
    $output .= "<text>{$text}</text>";
    $output .= "<positive>{$positive}</positive>";
    $output .= $extra_data;
    output_page($output, 1);
}

// Musi byc podany hash random_keya
if ($_GET['key'] != md5($settings['random_key'])) {
    exit;
}

$action = $_GET['action'];

if ($action == "purchase_service") {
    $output = '';

    if (($service_module = $heart->get_service_module($_GET['service'])) === null) {
        xml_output("bad_module", $lang->translate('bad_module'), 0);
    }

    if (!object_implements($service_module, "IService_PurchaseOutside")) {
        xml_output("bad_module", $lang->translate('bad_module'), 0);
    }

    // Sprawdzamy dane zakupu
    $purchase_data = new Entity_Purchase();
    $purchase_data->setService($service_module->service['id']);
    $purchase_data->user = $heart->get_user($_GET['uid']);
    $purchase_data->user->setPlatform($_GET['platform']);
    $purchase_data->user->setLastip($_GET['ip']);
    $purchase_data->setOrder([
        'server'    => $_GET['server'],
        'type'      => $_GET['type'],
        'auth_data' => $_GET['auth_data'],
        'password'  => $_GET['password'],
        'passwordr' => $_GET['password'],
    ]);
    $purchase_data->setPayment([
        'method'      => $_GET['method'],
        'sms_code'    => $_GET['sms_code'],
        'sms_service' => $_GET['transaction_service'],
    ]);

    // Ustawiamy taryfę z numerem
    $payment = new Payment($purchase_data->getPayment('sms_service'));
    $purchase_data->setTariff($payment->getPaymentModule()->getTariffById($_GET['tariff']));

    $return_validation = $service_module->purchase_data_validate($purchase_data);

    // Są jakieś błędy przy sprawdzaniu danych
    if ($return_validation['status'] != "ok") {
        $extra_data = '';
        if (!empty($return_validation['data']['warnings'])) {
            $warnings = '';
            foreach ($return_validation['data']['warnings'] as $what => $warning) {
                $warnings .= "<strong>{$what}</strong><br />" . implode("<br />", $warning) . "<br />";
            }

            if (strlen($warnings)) {
                $extra_data .= "<warnings>{$warnings}</warnings>";
            }
        }

        xml_output($return_validation['status'], $return_validation['text'], $return_validation['positive'],
            $extra_data);
    }

    /** @var Entity_Purchase $purchase_data */
    $purchase_data = $return_validation['purchase_data'];
    $purchase_data->setPayment([
        'method'      => $_GET['method'],
        'sms_code'    => $_GET['sms_code'],
        'sms_service' => $_GET['transaction_service'],
    ]);
    $return_payment = validate_payment($purchase_data);

    $extra_data = "";

    if (isset($return_payment['data']['bsid'])) {
        $extra_data .= "<bsid>{$return_payment['data']['bsid']}</bsid>";
    }

    if (isset($return_payment['data']['warnings'])) {
        $warnings = "";
        foreach ($return_payment['data']['warnings'] as $what => $text) {
            $warnings .= "<strong>{$what}</strong><br />{$text}<br />";
        }

        if (strlen($warnings)) {
            $extra_data .= "<warnings>{$warnings}</warnings>";
        }
    }

    xml_output($return_payment['status'], $return_payment['text'], $return_payment['positive'], $extra_data);
}

xml_output("script_error", "An error occured: no action.", false);