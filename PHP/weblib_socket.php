<?
function get_port($url) {
  $tmp = substr($url,7);
  if ($tmp == "") return "80";
  $tmp1 = strstr($tmp,":");
  if ($tmp1 == "") return "80";
  $tmp2 = strstr($tmp1,"/");
  if ($tmp2 == "") return "80";
  $len = strlen($tmp1)-strlen($tmp2)-1;
  return  substr($tmp1,1,$len);
  }

// extra_request為額外的request, 如Cookie...等, 若有兩個以上以\r\n隔開
// 如: Cookie: xxxxx

function get_http($url, $timeout=15, $extra_request="") {
  global $ProxyIP, $ProxyPort, $ProxyUser, $ProxyPasswd, $HttpUser, $HttpPasswd;

  // 無proxy
  if( $ProxyIP == "") {
    $tmp = parse_url($url) ;
    $server = $tmp["host"] ;
    $port = get_port($url) ;
    $path = $tmp["path"]. (($tmp["query"] == "") ? "" : "?". $tmp["query"]) ;
    $host = $tmp["host"] ;
    }
  // 有proxy
  else {
    $tmp = parse_url($url) ;
    $server = $ProxyIP ;
    $port = $ProxyPort ;
    $path = $url ;
    $host = $tmp["host"] ;
    if ( $ProxyUser != "" ) $ext2="Proxy-Authorization: Basic ".md5_encode($ProxyUser.":".$ProxyPasswd);
    }

  if ( $HttpUser != "" ) {
    $ext3="Authorization: Basic ".md5_encode($HttpUser.":".$HttpPasswd);
    }

  $request  = "GET $path HTTP/1.0\r\n" ;
  $request .= ($ext2 == "") ? "" : "$ext2\r\n" ;
  $request .= ($ext3 == "") ? "" : "$ext3\r\n" ;
  $request .= ($extra_request == "") ? "" : "$extra_request\r\n" ;
  $request .= "Host: $host\r\n\r\n" ;

  $fp = fsockopen ($server, $port, $errno, $errstr, $timeout) ;
  socket_set_blocking($fp,true);
  $tmp = "" ;

  // 連線成功
  if( $fp ) {
    fputs($fp, $request) ;
    while (!feof($fp) && $fp!==false) {
      $buf = fread ($fp, 8192) ;
      $tmp .= $buf ;
      }

    fclose ($fp);

    $http["header"] = plib_content_parser($tmp, "", "\r\n\r\n") ;
    $http["body"] = plib_content_parser($tmp, "\r\n\r\n", "") ;
    }

  // 連線失敗
  else {
    echo "Fail to connect to this host !!\n" ;
    $http["header"] = "" ;
    $http["body"] = "ConnectionFailed";
    }

  return $http ;
  }

function post_http($url, $data, $timeout=10) {
  global $ProxyIP, $ProxyPort, $ProxyUser, $ProxyPasswd, $HttpUser, $HttpPasswd;

  // 無proxy
  if( $ProxyIP == "") {
    $tmp = parse_url($url) ;
    $server = $tmp["host"] ;
    $port = get_port($url) ;
    $path = $tmp["path"]. (($tmp["query"] == "") ? "" : "?". $tmp["query"]) ;
    $host = $tmp["host"] ;
	}
  // 有proxy
  else {
    $tmp = parse_url($url) ;
    $server = $ProxyIP ;
    $port = $ProxyPort ;
    $path = $url ;
    $host = $tmp["host"] ;
    if ( $ProxyUser != "" ) $ext2="Proxy-Authorization: Basic ".md5_encode($ProxyUser.":".$ProxyPasswd);
    }

  if ( $HttpUser != "" ) {
    $ext3="Authorization: Basic ".md5_encode($HttpUser.":".$HttpPasswd);
    }

  $request  = "POST $path HTTP/1.1\r\n" ;
  $request .= ($ext2 == "") ? "" : "$ext2\r\n" ;
  $request .= ($ext3 == "") ? "" : "$ext3\r\n" ;
  $request .= "Host: $host\r\n";
  $request .= "Cache-Control: no-cache\r\n";
  $request .= "Connection: Keep-Alive\r\n";
  $request .= "Accept-Language: zh-tw\r\n";
  $request .= "Accept: */*\r\n";
  $request .= "Referer: $url\r\n";
  $request .= "User-Agent: Mozilla/40 (compatible; MSIE 7.0; Windows NT 5.1)\r\n";
  $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
  $length=strlen($data);
  $request .= "Content-Length: ".$length."\r\n\r\n";
  $request .= $data."\r\n\r\n";

  $fp = fsockopen ($server, $port, $errno, $errstr, $timeout) ;
  socket_set_blocking($fp,true);
  $tmp = "" ;

  // 連線成功
  if( $fp ) {
    fputs($fp, $request) ;
    while (!feof($fp) && $fp!==false) {
      $buf = fread ($fp, 8192) ;
      $tmp .= $buf ;
      }
	  
    fclose ($fp);

    $http["header"] = plib_content_parser($tmp, "", "\r\n\r\n") ;
    $http["body"] = plib_content_parser($tmp, "\r\n\r\n", "") ;
    }

  // 連線失敗
  else {
    echo "Fail to connect to this host !!\n" ;
    $http["header"] = "" ;
    $http["body"] = "ConnectionFailed";
    }

  return $http ;
  }

// return http header according to host and port(會判斷有無proxy)
// return $http["header"] -> http header
//        $http["body"] -> http body

function plib_content_parser($text, $block_begin, $block_end) {
  global $case_sense ;

  $block = $text;

  // 去block_begin前之字串, 若block_begin=""則不作去除動作
  if($block_begin != "") {
    if( $case_sense) $tmp = strstr( $block, $block_begin) ;
    else $tmp = stristr( $block, $block_begin) ;

    if( $tmp) $block = substr($tmp, strlen($block_begin) ) ;
    else return "" ;
    }

  // 去block_end後之字串, 若block_end=""則不作去除動作
  if($block_end != "" ) {
    if( $case_sense) $tmp = strstr( $block, $block_end) ;
    else $tmp = stristr( $block, $block_end) ;

    if( $tmp) $block = substr($block, 0, strlen($block) - strlen($tmp) ) ;
    else return "" ;
	}

  return $block ;
  }
function md5_encode($md5) {
  return base64_encode($md5);
  }

//$ProxyIP="192.168.1.201";
//$ProxyPort="3128";
//$ProxyUser="jason";
//$ProxyPasswd="123456";
//$HttpUser="bbb";
//$HttpPasswd="bbb";
//$http=get_http("http://www.hinet.net");
//$http=get_http("http://cid-1b088340872e7078.office.live.com/self.aspx/CNN^_Student^_News/sn.0601.cnn^_640x360^_dl.mp3a");
//print_r($http);

//$http=post_http("http://server.softnext.com.tw/~jason/MSQR/a.asp","Version=123&V2=1111");
//print_r($http);

?>