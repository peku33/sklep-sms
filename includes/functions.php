<?php

/**
 * Pobranie szablonu.
 *
 * @param string $title Nazwa szablonu
 * @param boolean $install  Prawda, jeżeli pobieramy szablon instalacji.
 * @param boolean $eslashes  Prawda, jeżeli zawartość szablonu ma być "escaped".
 * @param boolean $htmlcomments Prawda, jeżeli chcemy dodać komentarze o szablonie.
 * @return string Szablon.
 */
function get_template($title, $install = false, $eslashes = true, $htmlcomments = true)
{
    global $settings;

    if (!$install) {
        $filename = SCRIPT_ROOT . "themes/{$settings['theme']}/{$title}.html";
        if (!file_exists($filename)) { // Jeżeli nie ma jakiegoś pliku, to pobierz go z szablonu domyślnego
            $filename = SCRIPT_ROOT . "themes/default/{$title}.html";
            if (!file_exists($filename))
                return "";
        }
        $template = file_get_contents($filename);
    } else
        $template = file_get_contents(SCRIPT_ROOT . "install/templates/{$title}.html");

    if ($htmlcomments) {
        $template = "<!-- start: " . htmlspecialchars($title) . " -->\n{$template}\n<!-- end: " . htmlspecialchars($title) . " -->";
    }

    if ($eslashes) {
        $template = str_replace("\\'", "'", addslashes($template));
    }

    $template = str_replace("{__VERSION__}", "\".VERSION.\"", $template);

    return $template;
}

/**
 * Pobranie szablonu
 * @param string $output Zwartość do wyświetlenia
 * @param string $header String do użycia w funkcji header()
 */
function output_page($output, $header = "Content-type: text/html; charset=\"UTF-8\"")
{
    header($header);
    echo $output;
    exit;
}

function get_row_limit($page, $row_limit=0)
{
    global $settings;
    $row_limit = $row_limit ? $row_limit : $settings['row_limit'];
    return ($page - 1) * $row_limit . "," . $row_limit;
}

function get_pagination($all, $current_page, $script, $get, $row_limit=0)
{
    global $settings;

    $row_limit = $row_limit ? $row_limit : $settings['row_limit'];

    // Wszystkich elementow jest mniej niz wymagana ilsoc na jednej stronie
    if ($all <= $row_limit)
        return;

    // Pobieramy ilosc stron
    $pages_amount = floor(max($all - 1, 0) / $row_limit) + 1;

    // Poprawiamy obecna strone, gdyby byla bledna
    if ($current_page > $pages_amount)
        $current_page = -1;

    // Usuwamy index "page"
    unset($get['page']);
    $get_string = "";

    // Tworzymy stringa z danych get
    foreach ($get as $key => $value) {
        if ($get_string != "") $get_string .= "&";
        $get_string .= urlencode($key) . "=" . urlencode($value);
    }
    if ($get_string != "")
        $get_string = "?{$get_string}";

    /*// Pierwsza strona
    $output = create_dom_element("a",1,array(
        'href'	=> $script.$get_string.($get_string != "" ? "&" : "?")."page=1",
        'class'	=> $current_page == 1 ? "current" : ""
    ))."&nbsp;";

    // 2 3 ...
    if( $current_page < 5 ) {
        // 2 3
        for($i = 2; $i <= 3; ++$i) {
            $output .= create_dom_element("a",$i,array(
                'href'	=> $script.$get_string.($get_string != "" ? "&" : "?")."page={$i}"
            ))."&nbsp;";
        }

        // Trzy kropki
        $output .= create_dom_element("a","...",array(
                'href'	=> $script.$get_string.($get_string != "" ? "&" : "?")."page=".round(($pages_amount-3)/2)
        ))."&nbsp;";
    }
    // ...
    else {

    }

    // Ostatnia strona
    $output .= create_dom_element("a",$pages_amount,array(
        'href'	=> $script.$get_string.($get_string != "" ? "&" : "?")."page=".$pages_amount,
        'class'	=> $current_page == $pages_amount ? "current" : ""
    ))."&nbsp;";*/

    $output = "";
    $lp = 2;
    for ($i = 1, $dots = false; $i <= $pages_amount; ++$i) {
        if ($i != 1 && $i != $pages_amount && ($i < $current_page - $lp || $i > $current_page + $lp)) {
            if (!$dots) {
                if ($i < $current_page - $lp)
                    $href = $script . $get_string . ($get_string != "" ? "&" : "?") .
                        "page=" . round(((1 + $current_page - $lp) / 2));
                else if ($i > $current_page + $lp)
                    $href = $script . $get_string . ($get_string != "" ? "&" : "?") .
                        "page=" . round((($current_page + $lp + $pages_amount) / 2));

                $output .= create_dom_element("a", "...", array(
                        'href' => $href
                    )) . "&nbsp;";
                $dots = true;
            }
            continue;
        }

        $output .= create_dom_element("a", $i, array(
                'href' => $href = $script . $get_string . ($get_string != "" ? "&" : "?") .
                    "page=" . $i,
                'class' => $current_page == $i ? "current" : ""
            )) . "&nbsp;";
        $dots = false;
    }

    return $output;
}

/* User functions */
function is_logged()
{
    return $_SESSION['uid'];
}

function get_privilages($which, $user = array())
{
    // Jeżeli nie podano użytkownika
    if (empty($user)) {
        global $user;
    }

    if (in_array($which, array("manage_settings", "view_groups", "manage_groups", "view_player_flags",
        "view_player_services", "manage_player_services", "view_income", "view_users", "manage_users",
        "view_sms_codes", "manage_sms_codes", "view_antispam_questions", "manage_antispam_questions",
        "view_services", "manage_services", "view_servers", "manage_servers", "view_logs", "manage_logs", "update")))
        return $user['privilages'][$which] && $user['privilages']['acp'];

    return $user['privilages'][$which];
}

function update_activity($uid)
{
    global $db;
    $db->query($db->prepare(
        "UPDATE " . TABLE_PREFIX . "users " .
        "SET `lastactiv` = NOW(), `lastip` = '%s' " .
        "WHERE `uid` = '%d'",
        array(get_ip(), $uid)
    ));
}

function charge_wallet($uid, $amount)
{
    global $db;
    $db->query($db->prepare(
        "UPDATE " . TABLE_PREFIX . "users " .
        "SET wallet=`wallet`+'%f' " .
        "WHERE `uid` = '%d'",
        array(number_format($amount, 2), $uid)
    ));
}

function validate_payment($data)
{
    global $heart, $settings, $lang;

    // Pobieramy dane użytkownika dokonującego zakupu
    $user = $heart->get_user($data['user']['uid']);
    $user['ip'] = $data['user']['ip'] ? $data['user']['ip'] : $user['ip'];
    $user['email'] = $data['user']['email'] ? $data['user']['email'] : $user['email'];
    $user['platform'] = $data['user']['platform'] ? $data['user']['platform'] : $user['platform'];

    // Tworzymy obiekt usługi którą kupujemy
    $service_module = $heart->get_service_module($data['service']);
    if (is_null($service_module)) {
        return array(
            'status' => "wrong_module",
            'text' => $lang['module_is_bad'],
            'positive' => false
        );
    }

    // Tworzymy obiekt, który będzie nam obsługiwał proces płatności
    if ($data['method'] == "sms") {
        $transaction_service = if_isset($data['transaction_service'], $settings['sms_service']);
        $payment = new Payment($transaction_service, $user['platform']);
    }
    else if ($data['method'] == "transfer") {
        $transaction_service = if_isset($data['transaction_service'], $settings['transfer_service']);
        $payment = new Payment($transaction_service, $user['platform']);
    }

    // Pobieramy ile kosztuje ta usługa dla przelewu / portfela
    if (!isset($data['cost_transfer']))
        $data['cost_transfer'] = number_format($heart->get_tariff_provision($data['tariff']), 2);

    // Metoda płatności
    if (!in_array($data['method'], array("sms", "transfer", "wallet"))) {
        return array(
            'status' => "wrong_method",
            'text' => "Wybrano błędny sposób zapłaty.",
            'positive' => false
        );
    } else if ($data['method'] == "wallet" && !is_logged()) {
        return array(
            'status' => "wallet_not_logged",
            'text' => "Nie można zapłacić portfelem, gdy nie jesteś zalogowany.",
            'positive' => false
        );
    } else if ($data['method'] == "transfer") {
        if ($data['cost_transfer'] <= 1)
            return array(
                'status' => "too_little_for_transfer",
                'text' => "Przelewem można płacić tylko za zakupy powyżej 1.00 {$settings['currency']}",
                'positive' => false
            );

        if (!$payment->transfer_available())
            return array(
                'status' => "transfer_unavailable",
                'text' => $lang['transfer_unavailable'],
                'positive' => false
            );
    } else if ($data['method'] == "sms" && !$payment->sms_available()) {
        return array(
            'status' => "sms_unavailable",
            'text' => $lang['sms_unavailable'],
            'positive' => false
        );
    } else if ($data['method'] == "sms" && $data['tariff'] && !isset($payment->payment_api->smses[$data['tariff']])) {
        return array(
            'status' => "no_sms_option",
            'text' => "Nie można zapłacić SMSem za tę ilość usługi. Wybierz inny sposób płatności.",
            'positive' => false
        );
    }

    // Kod SMS
    $data['sms_code'] = trim($data['sms_code']);
    if ($data['method'] == "sms" && $warning = check_for_warnings("sms_code", $data['sms_code']))
        $warnings['sms_code'] = $warning;

    // Błędy
    if (!empty($warnings)) {
        foreach ($warnings as $brick => $warning) {
            eval("\$warning = \"" . get_template("form_warning") . "\";");
            $warning_data['warnings'][$brick] = $warning;
        }
        return array(
            'status' => "warnings",
            'text' => $lang['form_wrong_filled'],
            'positive' => false,
            'data' => $warning_data
        );
    }

    if ($data['method'] == "sms") {
        // Sprawdzamy kod zwrotny
        $sms_return = $payment->pay_sms($data['sms_code'], $data['tariff']);
        $payment_id = $sms_return['payment_id'];

        if ($sms_return['status'] != "OK") {
            return array(
                'status' => $sms_return['status'],
                'text' => $sms_return['text'],
                'positive' => false
            );
        }
    } else if ($data['method'] == "wallet") {
        // Dodanie informacji o płatności z portfela
        $payment_id = pay_by_wallet($user, $data['cost_transfer']);

        // Funkcja pay_by_wallet zwróciła błąd.
        if (is_array($payment_id))
            return $payment_id;
    }

    if ($data['method'] == "wallet" || $data['method'] == "sms") {
        // Dokonujemy zakupu usługi
        $purchase_data = $data;
        $purchase_data['user'] = $user;
        $purchase_data['transaction'] = array(
            'method' => $data['method'],
            'payment_id' => $payment_id
        );
        $bought_service_id = $service_module->purchase($purchase_data);

        return array(
            'status' => "purchased",
            'text' => "Usługa została prawidłowo zakupiona.",
            'positive' => true,
            'data' => array('bsid' => $bought_service_id)
        );
    } else if ($data['method'] == "transfer") {
        // Przygotowujemy dane do przeslania ich do dalszej obróbki w celu stworzenia płatności przelewem
        $purchase_data = array(
            'service' => $service_module->service['id'],
            'email' => $user['email'],
            'cost' => $data['cost_transfer'],
            'desc' => newsprintf($lang['payment_for_service'], $service_module->service['name']),
            'order' => $data['order']
        );

        return $payment->pay_transfer($purchase_data);
    }
}

function pay_by_wallet($user, $cost)
{
    global $db;

    // Sprawdzanie, czy jest wystarczająca ilość kasy w portfelu
    if ($cost > $user['wallet']) {
        return array(
            'status' => "no_money",
            'text' => "Bida! Nie masz wystarczającej ilości kasy w portfelu. Doładuj portfel ;-)",
            'positive' => false
        );
    }

    // Ustawiamy miejsce, skad zostala wykonana platnosc
    $platform_name = get_platform($user['platform']);

    // Zabieramy kasę z portfela
    charge_wallet($user['uid'], -$cost);

    // Dodajemy informacje o płatności portfelem
    $db->query($db->prepare(
        "INSERT INTO `" . TABLE_PREFIX . "payment_wallet` " .
        "SET `cost` = '%.2f', `ip` = '%s', `platform` = '%s'",
        array($cost, $user['ip'], $platform_name)
    ));

    return $db->last_id();
}

function pay_by_admin($user)
{
    global $db;

    // Dodawanie informacji o płatności
    $db->query($db->prepare(
        "INSERT INTO `" . TABLE_PREFIX . "payment_admin` (aid, ip, platform) " .
        "VALUES ('%d','%s','%s')",
        array($user['uid'], $user['ip'], $user['platform'])
    ));

    return $db->last_id();
}

function add_bought_service_info($uid, $user_name, $ip, $method, $payment_id, $service, $server, $amount, $auth_data, $email, $extra_data, $forever=false)
{
    global $heart, $db, $lang;

    // Dodajemy informacje o kupionej usludze do bazy danych
    $db->query($db->prepare(
        "INSERT INTO `" . TABLE_PREFIX . "bought_services` " .
        "SET `uid`='%d', `payment`='%s', `payment_id`='%s', `service`='%s', " .
        "`server`='%d', `amount`='%s', `auth_data`='%s', `email`='%s', `extra_data`='%s'",
        array($uid, $method, $payment_id, $service, $server, $forever ? "Na zawsze" : $amount, $auth_data, $email, json_encode($extra_data))
    ));
    $bougt_service_id = $db->last_id();

    $ret = "brak";
    if ($email) {
        $message = purchase_info(array(
            'purchase_id' => $bougt_service_id,
            'action' => "email"
        ));
        if (strlen($message)) {
            $title = ($service == 'charge_wallet' ? $lang['charge_wallet'] : $lang['bought_service']);
            $ret = send_email($email, $auth_data, $title, $message);
        }

        if ($ret == "not_sent")
            $ret = "nie wysłano";
        else if ($ret == "sent")
            $ret = "wysłano";
    }

    $temp_server = $heart->get_server($server);
    log_info(newsprintf($lang['bought_service_info'], $service, $auth_data, $amount, $temp_server['name'], $payment_id, $ret, $user_name, $uid, $ip));
    unset($temp_server);

    return $bougt_service_id;
}

//
// $data:
// 	purchase_id - id zakupu
// 	payment - metoda płatności
// 	payment_id - id płatności
// 	action - jak sformatowac dane
//
function purchase_info($data)
{
    global $heart, $db, $settings;

    // Wyszukujemy po id zakupu
    if (isset($data['purchase_id'])) {
        $where = $db->prepare("t.id = '%d'", array($data['purchase_id']));
    } // Wyszukujemy po id płatności
    else if (isset($data['payment']) && isset($data['payment_id'])) {
        $where = $db->prepare(
            "t.payment = '%s' AND t.payment_id = '%s'",
            array($data['payment'], $data['payment_id'])
        );
    }
    else
        return "";

    $pbs = $db->fetch_array_assoc($db->query(
        "SELECT * FROM ({$settings['transactions_query']}) as t " .
        "WHERE {$where}"
    ));

    // Brak wynikow
    if (empty($pbs))
        return "Brak zakupu w bazie.";

    $service_module = $heart->get_service_module($pbs['service']);
    return $service_module !== NULL && class_has_interface($service_module, "IServicePurchaseWeb") ? $service_module->purchase_info($data['action'], $pbs) : "";
}

function delete_players_old_services()
{
    global $heart, $db, $settings;
    // Usunięcie przestarzałych usług gracza
    // Pierwsze pobieramy te, które usuniemy
    // Potem je usuwamy, a następnie wywołujemy akcje na module
    $result = $db->query(
        "SELECT `server`, `type`, `auth_data`, `service` " .
        "FROM `" . TABLE_PREFIX . "players_services` " .
        "WHERE `expire` < UNIX_TIMESTAMP() AND `expire` != '-1'"
    );
    $db->query(
        "DELETE FROM `" . TABLE_PREFIX . "players_services` " .
        "WHERE `expire` < UNIX_TIMESTAMP() AND `expire` != '-1'"
    );
    while ($row = $db->fetch_array_assoc($result)) {
        log_info("AUTOMAT: Usunięto wygasłą usługę gracza. Auth Data: {$row['auth_data']} " .
            "Serwer: {$row['server']} Usługa: {$row['service']} Typ: " . get_type_name($row['type']));
        if (is_null($service_module = $heart->get_service_module($row['service'])))
            continue;

        $service_module->delete_player_service($row);
    }

    // Usunięcie przestarzałych flag graczy
    // Tak jakby co
    $db->query(
        "DELETE FROM `" . TABLE_PREFIX . "players_flags` " .
        "WHERE (`a` < UNIX_TIMESTAMP() AND `a` != '-1') " .
        "AND (`b` < UNIX_TIMESTAMP() AND `b` != '-1') " .
        "AND (`c` < UNIX_TIMESTAMP() AND `c` != '-1') " .
        "AND (`d` < UNIX_TIMESTAMP() AND `d` != '-1') " .
        "AND (`e` < UNIX_TIMESTAMP() AND `e` != '-1') " .
        "AND (`f` < UNIX_TIMESTAMP() AND `f` != '-1') " .
        "AND (`g` < UNIX_TIMESTAMP() AND `g` != '-1') " .
        "AND (`h` < UNIX_TIMESTAMP() AND `h` != '-1') " .
        "AND (`i` < UNIX_TIMESTAMP() AND `i` != '-1') " .
        "AND (`j` < UNIX_TIMESTAMP() AND `j` != '-1') " .
        "AND (`k` < UNIX_TIMESTAMP() AND `k` != '-1') " .
        "AND (`l` < UNIX_TIMESTAMP() AND `l` != '-1') " .
        "AND (`m` < UNIX_TIMESTAMP() AND `m` != '-1') " .
        "AND (`n` < UNIX_TIMESTAMP() AND `n` != '-1') " .
        "AND (`o` < UNIX_TIMESTAMP() AND `o` != '-1') " .
        "AND (`p` < UNIX_TIMESTAMP() AND `p` != '-1') " .
        "AND (`q` < UNIX_TIMESTAMP() AND `q` != '-1') " .
        "AND (`r` < UNIX_TIMESTAMP() AND `r` != '-1') " .
        "AND (`s` < UNIX_TIMESTAMP() AND `s` != '-1') " .
        "AND (`t` < UNIX_TIMESTAMP() AND `t` != '-1') " .
        "AND (`u` < UNIX_TIMESTAMP() AND `u` != '-1') " .
        "AND (`v` < UNIX_TIMESTAMP() AND `v` != '-1') " .
        "AND (`w` < UNIX_TIMESTAMP() AND `w` != '-1') " .
        "AND (`x` < UNIX_TIMESTAMP() AND `x` != '-1') " .
        "AND (`y` < UNIX_TIMESTAMP() AND `y` != '-1') " .
        "AND (`z` < UNIX_TIMESTAMP() AND `z` != '-1')"
    );

    // Usuwamy przestarzałe logi
    if (intval($settings['delete_logs']) != 0) {
        $db->query($db->prepare(
            "DELETE FROM `" . TABLE_PREFIX . "logs` " .
            "WHERE `timestamp` < DATE_SUB(NOW(), INTERVAL '%d' DAY)",
            array($settings['delete_logs'])
        ));
    }
}

function send_email($email, $name, $subject, $text)
{
    global $settings, $lang;

    ////////// USTAWIENIA //////////
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);    // Adres e-mail adresata
    $name = htmlspecialchars($name);
    $sender_email = $settings['sender_email'];
    $sender_name = $settings['sender_email_name'];

    if (!$email) {
        return "wrong_email";
    }

    $header = "MIME-Version: 1.0\r\n";
    $header .= "Content-Type: text/html; charset=UTF-8\n";
    $header .= "From: {$sender_name} < {$sender_email} >\n";
    $header .= "To: {$name} < {$email} >\n";
    $header .= "X-Sender: {$sender_name} < {$sender_email} >\n";
    $header .= 'X-Mailer: PHP/' . phpversion();
    $header .= "X-Priority: 1 (Highest)\n";
    $header .= "X-MSMail-Priority: High\n";
    $header .= "Importance: High\n";
    $header .= "Return-Path: {$sender_email}\n"; // Return path for errors

    if (mail($email, $subject, $text, $header)) {
        log_info(newsprintf($lang['email_was_sent'], $email, $text));
        return "sent";
    } else {
        return "not_sent";
    }
}

function log_info($string)
{
    global $db;
    $db->query($db->prepare(
        "INSERT INTO `" . TABLE_PREFIX . "logs` " .
        "SET `text` = '%s'",
        array($string)
    ));
}

/**
 * Sprawdza, czy dany obiekt implementuje odpowiedni interfejs
 *
 * @param $class
 * @param $interface
 * @return bool
 */
function class_has_interface($class, $interface)
{
    $interfaces = class_implements($class);
    return in_array($interface, $interfaces);
}

function myErrorHandler($errno, $string, $errfile, $errline)
{
    global $settings, $lang;

    switch ($errno) {
        case E_USER_ERROR:
            $array = json_decode($string, true);
            $array['message'] = $lang['mysqli'][$array['message_id']]; // Pobieramy odpowiednik z bilioteki jezykowej
            eval("\$header = \"" . get_template("header_error") . "\";");
            eval("\$message = \"" . get_template("error_handler") . "\";");

            if ($array['query']) {
                $text = date($settings['date_format']) . ": " . $array['query'];
                if (!file_exists(SQL_LOG) || file_get_contents(SQL_LOG) == "") file_put_contents(SQL_LOG, $text);
                else file_put_contents(SQL_LOG, file_get_contents(SQL_LOG) . "\n\n" . $text);
            }

            output_page($message);
            exit;
            break;

        default:
            break;
    }

    /* Don't execute PHP internal error handler */
    if (!(error_reporting() & $errno))
        return false;
    else
        return true;
}

function create_dom_element($name, $text = "", $data = array())
{
    $features = "";
    foreach ($data as $key => $value) {
        if (is_array($value) || $value == "")
            continue;

        $features .= ($features ? " " : "") . $key . '="' . str_replace('"', '\"', $value) . '"';
    }

    $style = "";
    foreach ($data['style'] as $key => $value) {
        if ($value == "")
            continue;

        $style .= ($style ? "; " : "") . "{$key}: {$value}";
    }
    if ($style)
        $features .= ($features ? " " : "") . "style=\"{$style}\"";

    $name_hsafe = htmlspecialchars($name);
    $output = "<{$name_hsafe} {$features}>";
    if ($text)
        $output .= $text;
    if (!in_array($name, array("input", "img")))
        $output .= "</{$name_hsafe}>";

    return $output;
}

function create_brick($text, $class = "", $alpha = 0.2)
{
    $brick_r = rand(0, 255);
    $brick_g = rand(0, 255);
    $brick_b = rand(0, 255);
    return create_dom_element("div", $text, array(
        'class' => "brick" . ($class ? " {$class}" : ""),
        'style' => array(
            'border-color' => "rgb({$brick_r},{$brick_g},{$brick_b})",
            'background-color' => "rgba({$brick_r},{$brick_g},{$brick_b},{$alpha})"
        )
    ));
}

function get_platform($platform)
{
    if ($platform == "cs16") return "Counter-Strike v1.6";
    else return $platform;
}

// Zwraca nazwę typu
function get_type_name($value)
{
    global $lang;

    if ($value == TYPE_NICK)
        return $lang['nickpass'];
    else if ($value == TYPE_IP)
        return $lang['ippass'];
    else if ($value == TYPE_SID)
        return $lang['sid'];

    return "";
}

function get_type_name2($value)
{
    global $lang;

    if ($value == TYPE_NICK)
        return $lang['nick'];
    else if ($value == TYPE_IP)
        return $lang['ip'];
    else if ($value == TYPE_SID)
        return $lang['sid'];

    return "";
}

function get_ip()
{
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
        return $_SERVER['HTTP_CF_CONNECTING_IP'];

    return $_SERVER['REMOTE_ADDR'];
}


function get_sms_cost($number)
{
    if (strlen($number) < 4) {
        return 0;
    } else if ($number[0] == "7") {
        return $number[1] == "0" ? 0.5 : intval($number[1]);
    } else if ($number[0] == "9") {
        return intval($number[1] . $number[2]);
    }

    return 0;
}

function hash_password($password, $salt)
{
    return md5(md5($password) . md5($salt));
}

function newsprintf($string)
{
    $arg_list = func_get_args();
    $num_args = count($arg_list);

    for ($i = 1; $i < $num_args; $i++) {
        $string = str_replace('{' . $i . '}', $arg_list[$i], $string);
    }

    return $string;
}

function escape_filename($filename)
{
    $filename = str_replace('/', '_', $filename);
    $filename = str_replace(' ', '_', $filename);
    $filename = str_replace('.', '_', $filename);
    return $filename;
}

function get_random_string($length)
{
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890"; //length:36
    $final_rand = "";
    for ($i = 0; $i < $length; $i++) $final_rand .= $chars[rand(0, strlen($chars) - 1)];
    return $final_rand;
}

function valid_steam($steamid)
{
    return preg_match('/\bSTEAM_([0-9]{1}):([0-9]{1}):([0-9])+$/', $steamid) ? '1' : '0';
}

function secondsToTime($seconds)
{
    global $lang;

    $dtF = new DateTime("@0");
    $dtT = new DateTime("@$seconds");
    return $dtF->diff($dtT)->format("%a {$lang['days']} {$lang['and']} %h {$lang['hours']}");
}

function if_isset(&$isset, $default)
{
    return isset($isset) ? $isset : $default;
}

function mb_str_split($string)
{
    return preg_split('/(?<!^)(?!$)/u', $string);
}

function searchWhere($search_ids, $search, &$where)
{
    global $db;

    $search_where = array();
    $search_like = $db->escape('%' . implode('%', mb_str_split($search)) . '%');

    foreach ($search_ids as $search_id) {
        $search_where[] = "{$search_id} LIKE '{$search_like}'";
    }

    if (!empty($search_where)) {
        $search_where = implode(" OR ", $search_where);
        if ($where != "") $where .= " AND ";

        $where .= "( {$search_where} )";
    }
}

/**
 * @param $url - adres url
 * @param int $timeout - po jakim czasie ma przerwać
 */
function curl_get_contents($url, $timeout=10) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_TIMEOUT => $timeout
    ));
    $resp = curl_exec($curl);
    curl_close($curl);

    return $resp;
}