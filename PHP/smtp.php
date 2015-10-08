<?
CLASS SMTPMAIL {
  var $FROM = "";
  var $TO = "";
  var $MSGFile = "";
  var $MSSHeader = "";

  // SMTPMAIL �غc�l
  function SMTPMAIL($mssheader,$msgfile) {
    $this->MSSHeader=$mssheader;
    $this->MSGFile=$msgfile;
  }
  // �Ұ� SENDMAIL PORT 
  function init() {
  }

  function mail() {
    global $DOCUMENT_ROOT,$SUB_ROOT;
    // For $BCCTag
    include("$DOCUMENT_ROOT/$SUB_ROOT/snmss.ini");

    if ( $this->FROM=="EX-MAILER-DAEMON" || $this->FROM=="MAILER-DAEMON" ) $this->FROM="<>";
    if ( !preg_match("/^</",$this->FROM) ) $this->FROM="<".$this->FROM;
    if ( !preg_match("/>$/",$this->FROM) ) $this->FROM=$this->FROM.">";
    // �䴩�h����H�γr���j�}�ӥB�n�B�O�@�� eml �Ӥw
    $envto=explode(",",$this->TO);
    for($i=0;$i<sizeof($envto);$i++) {
      if ( !preg_match("/^</",$envto[$i]) ) $envto[$i]="<".$envto[$i];
      if ( !preg_match("/>$/",$envto[$i]) ) $envto[$i]=$envto[$i].">";
      $envto[$i]=escapeshellarg($envto[$i]);
    }

    include("$DOCUMENT_ROOT/$SUB_ROOT/execute.asp");
    // �� -Am ���|�� sender domain check �F�A�����[ -Am �٬O�| check �@
    // �o�{���H Header �̷|���y�� DSN: ... Unbalanced '<' ���s�u�h�h�H���[�F -v �N���|�����q Header Parser�A���g��D�`���n�A���]���[�F -v ��Ϊ� one sendmail �ݵ��ݾ�ӳs�u���槹���A�b maillog.asp ���O�ݨϥ� smtpwrapper.asp > /dev/null & �_�h�N�|�y���H���H Lock �d��
    $pp = popen($SENDMAIL." -v -Am -f".escapeshellarg($this->FROM)." ".join(" ",$envto),"w");

    $headernum=1;
    if( $this->MSSHeader!="" ) {
      // SPAM SQR �|���令 lib/getMyHostName.asp...
      //do { $fp=fopen("/var/tmp/MyHostName","r"); $MyHostName=chop(fgets($fp,1024)); fclose($fp); } while($MyHostName=="");
      include_once("$DOCUMENT_ROOT/$SUB_ROOT/lib/getMyHostName.asp");
      $MyHostName=getMyHostName();

      $fp=fopen($this->MSGFile,"r");
      while($fp!==false && !feof($fp)) {
	$line=fgets($fp,1024);
	// �u���Ĥ@��O Return-Path: �ɤ~����
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
	// �u���Ĥ@��O Return-Path: �ɤ~����
	if( $headernum==1 && strtolower(substr($line,0,12))=="return-path:" ) continue;
	if( $headernum && ( strtolower(substr($line,0,10))=="full-name:" || strtolower(substr($line,0,11))=="message-id:" ) ) continue;
	if( $headernum && strlen($line)<=2 ) $headernum=0;
	if( $line==".\n" || $line==".\r\n" ) $line=".".$line;
	fputs($pp,$line);
	if( $headernum ) $headernum++;
      }
      fclose($fp);
    }

    // �� -Am ���|�� sender domain check �F�A�����[ -Am �٬O�| check �@
    pclose($pp);
    // �o�̥u�n���� return ���\
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
