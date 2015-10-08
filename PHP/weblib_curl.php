<?
/**
 * get_http() - Send HTTP GET Request with CURL
 */
function get_http($url, $timeout=15, $extra_request="") {
  global $ProxyIP, $ProxyPort, $ProxyUser, $ProxyPasswd, $HttpUser, $HttpPasswd;

  // 初始化一個 cURL 對象
  $curl = curl_init();
  if ($curl === FALSE) {
    return "curl init error: ".$url;
  }
  // CURLOPT_CONNECTTIMEOUT
  // The number of seconds to wait while trying to connect. Use 0 to wait
  // indefinitely.
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
  // CURLOPT_TIMEOUT
  // The maximum number of seconds to allow cURL functions to execute.
  curl_setopt($curl, CURLOPT_TIMEOUT, $timeout*2);
  // 設置你需要抓取的URL
  curl_setopt($curl, CURLOPT_URL, $url);
  // 設置 Proxy
  if ( isset($ProxyIP) && isset($ProxyPort) ) {
    curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1);
    curl_setopt($curl, CURLOPT_PROXY, $ProxyIP.":".$ProxyPort);
    // 設置 Proxy 認證資訊
    if ( isset($ProxyUser) && isset($ProxyPasswd) ) {
      curl_setopt($curl, CURLOPT_PROXYUSERPWD, $ProxyUser.":".$ProxyPasswd);
    }
  }
  // 設置認證資訊
  if ( isset($HttpUser) && isset($HttpPasswd) ) {
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, $HttpUser.":".$HttpPasswd);
  }
  // 不使用 cache
  curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
  // 不驗證 Certificate
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
  // 設置header
  //curl_setopt($curl, CURLOPT_HEADER, 1);
  // 設置cURL 參數，要求結果保存到字符串中還是輸出到屏幕上。
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  // 運行cURL，請求網頁
  $data = curl_exec($curl);

  if ($data === FALSE) {
    $data = curl_error($curl);
  }

  // 關閉URL請求
  curl_close($curl);

  $http["header"] = "";
  $http["body"] = $data;

  return $http;
}

/**
 * post_http() - Send HTTP POST Request with CURL
 */
function post_http($url, $data, $timeout=15) {
  global $ProxyIP, $ProxyPort, $ProxyUser, $ProxyPasswd, $HttpUser, $HttpPasswd;

  // 初始化一個 cURL 對象
  $curl = curl_init();
  if ($curl === FALSE) {
    return "curl init error: ".$url;
  }
  // CURLOPT_CONNECTTIMEOUT
  // The number of seconds to wait while trying to connect. Use 0 to wait
  // indefinitely.
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
  // CURLOPT_TIMEOUT
  // The maximum number of seconds to allow cURL functions to execute.
  curl_setopt($curl, CURLOPT_TIMEOUT, $timeout*2);
  // 設置你需要抓取的URL
  curl_setopt($curl, CURLOPT_URL, $url);
  // 設置 Proxy
  if ( isset($ProxyIP) && isset($ProxyPort) ) {
    curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1);
    curl_setopt($curl, CURLOPT_PROXY, $ProxyIP.":".$ProxyPort);
    // 設置 Proxy 認證資訊
    if ( isset($ProxyUser) && isset($ProxyPasswd) ) {
      curl_setopt($curl, CURLOPT_PROXYUSERPWD, $ProxyUser.":".$ProxyPasswd);
    }
  }
  // 設置認證資訊
  if ( isset($HttpUser) && isset($HttpPasswd) ) {
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, $HttpUser.":".$HttpPasswd);
  }
  // 不使用 cache
  curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
  // 不驗證 Certificate
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
  // 設置header
  //curl_setopt($curl, CURLOPT_HEADER, 1);
  // 設置cURL 參數，要求結果保存到字符串中還是輸出到屏幕上。
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  // 設置 POST
  curl_setopt($curl, CURLOPT_POST, TRUE);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
  // 運行cURL，請求網頁
  $data = curl_exec($curl);

  if ($data === FALSE) {
    $data = curl_error($curl);
  }

  // 關閉URL請求
  curl_close($curl);

  $http["header"] = "";
  $http["body"] = $data;

  return $http;
}
/*
$ProxyIP="172.16.1.238";
$ProxyPort="3128";
$ProxyUser="beadmin";
$ProxyPasswd="beos2u4u!tw";
$HttpUser="jason";
$HttpPasswd="ajis2u4u";

//$http=get_http("https://192.168.11.235/svn/BEAgent/");
//$http=post_http("https://ajissvc.ajistech.com/aaa.asp","post1=abcd&post2=efgh");
print_r($http);
*/
?>