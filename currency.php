<?php
define('API_KEY', 'fca_live_SbPt7RAHBpRm5nsHN1l1vKIMOEumEXAZD2O3rtH1');
define('API_URL', 'https://api.freecurrencyapi.com/v1/');

$validCurrencies = ['RUB', 'USD', 'GBP', 'EUR', 'JPY', 'PLN', 'TRY', 'CNY'];
function printHelp() {
    echo "Доступные действия:\n";
    echo "php currency.php  - возвращает список доступных действий с краткими пояснениями\n";
    echo "php currency.php help - аналогично предыдущему пункту\n";
    echo "php currency.php list - выводит столбиком список доступных для работы валют в формате {code} - {name}\n";
    echo "php currency.php {code1} {code2} (пример: USD RUB) - возвращает обменный курс для указанной пары валют\n";
    echo "php currency.php {code1} {code2} {amount} (пример USD RUB 100) - конвертирует {amount} в первой валюте во вторую валюту и возвращает получившуюся сумму\n";
    echo "php currency.php {code1} {code2} {yyyy-mm-dd} (дата в формате YYYY-MM-DD) - возвращает обменный курс для указанной пары на указанную дату\n";
    echo "php currency.php {code1} {code2} {amount} {yyyy-mm-dd} (дата в формате YYYY-MM-DD) - конвертирует {amount} в первой валюте во вторую валюту по курсу на указанную дату и возвращает получившуюся сумму\n";
}
function printCurrencyList($validCurrencies) {
    foreach ($validCurrencies as $currency) {
        echo "$currency\n";
    }
}
function fetchExchangeRate($from, $to, $date = null) {
    $endpoint = 'latest';
    $params = [
        'apikey' => API_KEY,
        'base_currency' => $from,
        'currencies' => $to
    ];

    if ($date) {
        $endpoint = 'historical';
        $params['date'] = $date;
    }

    $url = API_URL . $endpoint . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($date) {
        if (isset($data['data'][$date][$to])) {
            return $data['data'][$date][$to];
        }
    } else {
        if (isset($data['data'][$to])) {
            return $data['data'][$to];
        }
    }

    return null;
}

function handleExchangeRate($args) {
    global $validCurrencies;

    if (count($args) < 2) {
        echo "Недостаточно аргументов.\n";
        return;
    }

    $from = strtoupper($args[0]);
    $to = strtoupper($args[1]);

    if (!in_array($from, $validCurrencies) || !in_array($to, $validCurrencies)) {
        echo "Неподдерживаемая валюта.\n";
        return;
    }

    if (isset($args[2]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $args[2])) {
        $date = $args[2];
        $amount = 1;
    } else {
        $amount = isset($args[2]) ? (float)$args[2] : 1;
        $date = isset($args[3]) ? $args[3] : null;
    }

    $rate = fetchExchangeRate($from, $to, $date);

    if ($rate === null) {
        echo "Ошибка получения курса валют.\n";
        return;
    }

    $convertedAmount = $amount * $rate;
    echo "Курс: $rate\n";
    echo "Сумма: $convertedAmount $to\n";
}

if ($argc < 2) {
    printHelp();
    exit;
}
$command = $argv[1];
switch ($command) {
    case 'help':
        printHelp();
        break;

    case 'list':
        printCurrencyList($validCurrencies);
        break;

    default:
        handleExchangeRate(array_slice($argv, 1));
        break;
}