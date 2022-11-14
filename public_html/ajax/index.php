<?php
include_once (__DIR__.'/../../vendor/autoload.php');

$targetUrl = "https://pub.fsa.gov.ru";
$connectParams = [
    'username' => 'anonymous',
    'password' => 'hrgesf7HDR67Bd',
];

$cmd = isset($_POST['cmd']) ? $_POST['cmd'] : "unknown";
$response = [
    'status' => 'error',
    'error' => 'unknown' === $cmd ? 'Unknown command' : ''
];

if('unknown' !== $cmd)
{
    //первая загрузка страницы - подконектимся и сохраним токен
    if(fsaparse()->Connect($targetUrl, $connectParams))
    {
        switch($cmd)
        {
            case 'getFiltersData':
                if($FiltersFields = fsaparse()->getFiltersFields())
                {
                    $response['status'] = 'OK';
                    $response['filtersData'] = $FiltersFields;
                }
                else
                    $response['error'] = "Can not read filters data. Terminating parsing.";
                break;
            case 'sendFiltersData':
                $columns = explode(',', $_POST['columns']);//вытащили отдельно на случай, если надо будет допроверить/ заэскейпить / экранироват и т.д.
                if($FilteredData = fsaparse()->RequestFilteredData($columns))
                {
                    if(isset($FilteredData['items']) && is_array($FilteredData['items']) && count($FilteredData['items']))
                    {
                        $response['status'] = 'OK';
                        $response['resultData'] = $FilteredData;
                    }
                    else
                        $response['error'] = "Result data is empty";
                }
                else
                    $response['error'] = "Can not read filters data. Terminating parsing.";
                break;
        }
    }

}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE );
exit(0);
//die("");

$urlLogin = 'https://pub.fsa.gov.ru/login';
$urlItems = 'https://pub.fsa.gov.ru/api/v1/rss/common/certificates/get';
$LoginBody = '{"username":"anonymous","password":"hrgesf7HDR67Bd"}';
$itemsBody = '{"size":10,"page":0,"filter":{"idTechReg":[11],"regDate":{"minDate":"","maxDate":""},"endDate":{"minDate":"","maxDate":"2019-09-01T00:00:00.000Z"},"columnsSearch":[]},"columnsSort":[{"column":"date","sort":"DESC"}]}';

$headers=get_headers($urlLogin,1);
$Cookie=explode(";", $headers['Set-Cookie']);
$ch = curl_init();

$ar = [
    CURLOPT_URL => $urlLogin,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_RETURNTRANSFER => true,
    CURLINFO_HEADER_OUT => true,
    CURLOPT_POSTFIELDS => $LoginBody,
    CURLOPT_SSL_VERIFYPEER => FALSE,
    CURLOPT_HEADER => 1,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
    ],
];
curl_setopt_array($ch, $ar);

$result = curl_exec($ch);
if($errno = curl_errno($ch)) {
    $error_message = curl_strerror($errno);
    echo "cURL error ({$errno}):\n {$error_message}";
}

$info = curl_getinfo($ch);
echo "getinfo: <pre>".print_r($info, true)."</pre>";

curl_close($ch);
echo "result:<pre>".print_r($result, true)."</result:pre>";

//die("OK");
$result2=json_decode($result,true);
$headersAll = array();
$header_text = substr($result, 0, strpos($result, "\r\n\r\n"));
foreach (explode("\r\n", $header_text) as $i => $line)
    if ($i === 0)
        $headersAll['http_code'] = $line;
    else
    {
        list ($key, $value) = explode(': ', $line);

        $headersAll[$key] = $value;
    }

$headers=get_headers($urlItems,1);
$Cookie=explode(";", $headers['Set-Cookie']);
$ch = curl_init($urlItems);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLINFO_HEADER_OUT, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $itemsBody);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Host: pub.fsa.gov.ru',
    'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:61.0) Gecko/20100101 Firefox/61.0',
    'Accept: application/json, text/plain, */*',
    'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
    'Accept-Encoding: gzip, deflate, br',
    'Referer: https://pub.fsa.gov.ru/rss/certificate',
    'Authorization: '.$headersAll['Authorization'],
    'Pragma: no-cache',
    'Cache-Control: no-cache',
    'Content-Type: application/json',
    'Content-Length: '.strlen($itemsBody),
    'Cookie: '.$Cookie[0],
    'Connection: keep-alive'
));
$result = curl_exec($ch);
if($errno = curl_errno($ch)) {
    $error_message = curl_strerror($errno);
    echo "cURL error ({$errno}):\n {$error_message}";
}
curl_close($ch);
print_r($result);


echo "<hr/>test";