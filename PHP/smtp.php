<?
CLASS SMTPMAIL {
  var $FROM = "";
  var $TO = "";
  var $MSGFile = "";
  var $MSSHeader = "";

  // SMTPMAIL 建構子
  function SMTPMAIL($mssheader,$msgfile) {
    $this->MSSHeader=$mssheader;
    $this->MSGFile=$msgfile;
  }
  // 啟動 SENDMAIL PORT 
  function init() {
  }

  function mail() {
    global $DOCUMENT_ROOT,$SUB_ROOT;
    // For $BCCTag
    include("$DOCUMENT_ROOT/$SUB_ROOT/snmss.ini");

    if ( $this->FROM=="EX-MAILER-DAEMON" || $this->FROM=="MAILER-DAEMON" ) $this->FROM="<>";
    if ( !preg_match("/^</",$this->FROM) ) $this->FROM="<".$this->FROM;
    if ( !preg_match("/>$/",$this->FROM) ) $this->FROM=$this->FROM.">";
    // 支援多收件人用逗號隔開而且好處是一封 eml 而已
    $envto=explode(",",$this->TO);
    for($i=0;$i<sizeof($envto);$i++) {
      if ( !preg_match("/^</",$envto[$i]) ) $envto[$i]="<".$envto[$i];
      if ( !preg_match("/>$/",$envto[$i]) ) $envto[$i]=$envto[$i].">";
      $envto[$i]=escapeshellarg($envto[$i]);
    }

    include("$DOCUMENT_ROOT/$SUB_ROOT/execute.asp");
    // 用 -Am 不會做 sender domain check 了，但不加 -Am 還是會 check 哦
    // 發現有信 Header 裡會有造成 DSN: ... Unbalanced '<' 的連線層退信但加了 -v 就不會走那段 Header Parser，此經驗非常重要，但因為加了 -v 後或者 one sendmail 需等待整個連線執行完畢，在 maillog.asp 切記需使用 smtpwrapper.asp > /dev/null & 否則將會造成信中信 Lock 卡死
    $pp = popen($SENDMAIL." -v -Am -f".escapeshellarg($this->FROM)." ".join(" ",$envto),"w");

    $headernum=1;
    if( $this->MSSHeader!="" ) {
      // SPAM SQR 尚未改成 lib/getMyHostName.asp...
      //do { $fp=fopen("/var/tmp/MyHostName","r"); $MyHostName=chop(fgets($fp,1024)); fclose($fp); } while($MyHostName=="");
      include_once("$DOCUMENT_ROOT/$SUB_ROOT/lib/getMyHostName.asp");
      $MyHostName=getMyHostName();

      $fp=fopen($this->MSGFile,"r");
      while($fp!==false && !feof($fp)) {
	$line=fgets($fp,1024);
	// 只有第一行是 Return-Path: 時才拿掉
	if ( $headernum==1 && strtolower(substr($line,0,12))=="return-path:" ) continue;
	if ( $headernum && ( strtolower(substr($line,0,10))=="full-name:" || strtolower(substr($line,0,11))=="message-id:" ) ) continue;
	if ( $headernum && strlen($line)<=2 ) {
	  $line="X-MSS: ".$this->MSSHeader."@".$MyHostName."\n\n";
	  $headernum=0;
	}
	if ( $headernum && $this->MSSHeader=="AUTOBCC" && strtolower(substr($line,0,28))=="disposition-notification-to:" ) continue;
        if ( isset($BCCTag) && $headernum && $this->MSSHeader=="AUTOBCC" && substr(strtolower($line),0,8)=="subject:" ) {
          $line=substr($line,0,8)." ".$BCCTag." ".ltrim(substr($line,8));
          }
	if ( $line==".\n" || $line==".\r\n" ) $line=".".$line;
	fputs($pp,$line);
	if( $headernum ) $headernum++;
	}
      fclose($fp);
    }
    else {
      $fp=fopen($this->MSGFile,"r");
      while($fp!==false && !feof($fp)) {
	$line=fgets($fp,1024);
	// 只有第一行是 Return-Path: 時才拿掉
	if( $headernum==1 && strtolower(substr($line,0,12))=="return-path:" ) continue;
	if( $headernum && ( strtolower(substr($line,0,10))=="full-name:" || strtolower(substr($line,0,11))=="message-id:" ) ) continue;
	if( $headernum && strlen($line)<=2 ) $headernum=0;
	if( $line==".\n" || $line==".\r\n" ) $line=".".$line;
	fputs($pp,$line);
	if( $headernum ) $headernum++;
      }
      fclose($fp);
    }

    // 用 -Am 不會做 sender domain check 了，但不加 -Am 還是會 check 哦
    pclose($pp);
    // 這裡只好都先 return 成功
    return 1;

  }

  function close() {
  }
}

### Usage of SMTP class
/*
include("/home/www/htdocs/snmsqr/conf.asp");
$smtpmail=new SMTPMAIL("AUTOBCC","/tmp/zfewbody.eml");
$smtpmail->init();

$smtpmail->FROM="<haha@softnext.com.tw>";
$smtpmail->TO="<jason@softnext.com.tw>";
$smtpmail->mail();
$smtpmail->close();
*/
?>
