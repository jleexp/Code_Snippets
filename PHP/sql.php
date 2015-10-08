<?
// $DOCUMENT_ROOT & $SUB_ROOT in conf.asp
include("/home/www/htdocs/snmsqr/conf.asp");

include("$DOCUMENT_ROOT/$SUB_ROOT/sql/adodb.inc.php");

$conf["sql_server"]	="localhost";
$conf["sql_port"]	="";
$conf["sql_driver"]	="mysql";
$conf["sql_user"]	="snee";
$conf["sql_db"]		="snmsqr";
$conf["secs2cache"]	=300;
$conf["cache_dir"]	="/var/tmp";
include("$DOCUMENT_ROOT/$SUB_ROOT/pw.ini");
if( isset($sneepw) ) $conf["sql_passwd"]=$sneepw;
while( is_file("$DOCUMENT_ROOT/$SUB_ROOT/snrpc/snrpc.ini") && !isset($MIRROR) ) {
  include("$DOCUMENT_ROOT/$SUB_ROOT/snrpc/snrpc.ini");
  if( !isset($MIRROR) ) sleep(5);
}

$secs2cache		=$conf["secs2cache"];
$ADODB_CACHE_DIR	=$conf["cache_dir"];
$conn=&ADONewConnection($conf[sql_driver]);
$ret=$conn->PConnect($conf[sql_server],$conf[sql_user],$conf[sql_passwd],$conf[sql_db]);
if( $MIRROR[front] && !$ret && $MIRROR[this]!=$MIRROR[db] ) {

  $fplock = fopen("$DOCUMENT_ROOT/$SUB_ROOT/snrpc/snrpc.ini.tmp.go","w");
  flock($fplock,LOCK_EX);
  // �u���o���ɤ��� unlink �� WEB �|�@�ΡA�̦n�٬O chown
  chown("$DOCUMENT_ROOT/$SUB_ROOT/snrpc/snrpc.ini.tmp.go","www");

  $fp = fopen("$DOCUMENT_ROOT/$SUB_ROOT/snrpc/snrpc.ini.tmp","w");
  flock($fp,LOCK_EX);
  $fp2 = fopen("$DOCUMENT_ROOT/$SUB_ROOT/snrpc/snrpc.ini","r");
  while($fp2!==false && !feof($fp2)) {
    $line = fgets($fp2,1024);
    if( substr($line,0,19)=='$conf["sql_server"]' ) {
      $dbfail = 1;
      continue;
      }
    fputs($fp,$line);
    }
  fclose($fp2);
  flock($fp,LOCK_UN);
  fclose($fp);
  if( filesize("$DOCUMENT_ROOT/$SUB_ROOT/snrpc/snrpc.ini.tmp")>0 )
    copy("$DOCUMENT_ROOT/$SUB_ROOT/snrpc/snrpc.ini.tmp","$DOCUMENT_ROOT/$SUB_ROOT/snrpc/snrpc.ini");
  unlink("$DOCUMENT_ROOT/$SUB_ROOT/snrpc/snrpc.ini.tmp");

  flock($fplock,LOCK_UN);
  fclose($fplock);
  // ���� unlink �_�h���� Lock �|�����v�|����
  //unlink("$DOCUMENT_ROOT/$SUB_ROOT/snrpc/snrpc.ini.tmp.go");

  $conn->PConnect("localhost",$conf[sql_user],$conf[sql_passwd],$conf[sql_db]);
  // Alert (if ini is modified, only one process will alert)
  if( $dbfail && $MIRROR[alert]!="" ) {
    include_once("$DOCUMENT_ROOT/$SUB_ROOT/execute.asp");
    // Here can't include ini
    do { $fp=fopen("/var/tmp/MyHostName","r"); $MyHost[0]=chop(fgets($fp,1024)); fclose($fp); } while($MyHost[0]=="");
    $mp = popen("$SENDMAIL -Am -fsysadmin@localhost ".str_replace(","," ",$MIRROR[alert]),"w");
    fputs($mp,"From: sysadmin@localhost\n");
    fputs($mp,"To: $MIRROR[alert]\n");
    fputs($mp,"Subject: [Warning] ".$MIRRORS[$MIRROR[this]]." spare, DB disconnected.\n");
    fputs($mp,"X-MSS: INFO@$MyHost[0]\n\n\n");
    pclose($mp);
    }
  unset($fplock,$fp,$fp2,$line,$dbfail);
  }
unset($ret);

if ( !function_exists("auto_key") ) {
  function loadVar($table,$varname) {
    global $conn;

    $rs=$conn->Execute("Select varvalue from $table Where varname=".$conn->qstr($varname));
    if ( !$rs->EOF ) return $rs->fields[varvalue];
    else return false;
    }
  function jsValue($str) {
    $str=str_replace("'","\'",preg_replace("/([".chr(128)."-".chr(255)."]\\\\)\\\\/","\\1",str_replace("\\","\\\\",$str)));
    return $str;
    }

  function auto_key() {
    global $DOCUMENT_ROOT,$MIRROR;

    $fplock = fopen("$DOCUMENT_ROOT/auto_key.go","w");
    flock($fplock,LOCK_EX);
    // �u���o���ɤ��� unlink �� WEB �|�@�ΡA�̦n�٬O chown
    chown("$DOCUMENT_ROOT/auto_key.go","www");

    $fp = @fopen("$DOCUMENT_ROOT/auto_key","r");
    if( $fp!==false ) {
      $line = fgets($fp,1024);
      fclose($fp);
    // �Ĥ@���ҥήɥ� sleep 60 �קK�P��Ʈw�� auto_key �Ĩ�
    } else sleep(60);
    // ntpdate �� bios �ɶ����~�����Y�A�H�p�ɬ���줣�i���^�Y
    $chkdayhr = substr($line,0,10);
    $chktime = substr($line,10,4);
    $chkkey = (integer)substr($line,14,3);

    // ���i�� lock �ƫܤ[�w�g��U�@��ҥH��Ƥw�g�ܤF�A�]����l�ƭn�b lock ��
    $auto = date("YmdHis");
    $autodayhr = substr($auto,0,10);
    $autotime = substr($auto,10,4);
    $key = 1;
    do {
      if( isset($MIRROR[this]) && $MIRROR[count]>1 ) $autokey=$key++*$MIRROR[count]+$MIRROR[this];
      else $autokey=$key++;
      if( $autokey>999 ) { $auto = date("YmdHis"); $autodayhr = substr($auto,0,10); $autotime = substr($auto,10,4); $key = 1; continue; }
    } while( $autodayhr==$chkdayhr && ( $autotime<$chktime || ( $autotime==$chktime && $autokey<=$chkkey ) ) );
    $auto = $auto.str_repeat("0",3-strlen($autokey)).$autokey;

    $fp = fopen("$DOCUMENT_ROOT/auto_key","w");
    fputs($fp,$auto);
    fclose($fp);
    // �u���o���ɤ��� unlink �� WEB �|�@�ΡA�̦n�٬O chown
    chown("$DOCUMENT_ROOT/auto_key","www");

    flock($fplock,LOCK_UN);
    fclose($fplock);

    return $auto;
  }

/*##################(insert_sql�d��)

$fields=array("qryname","grpid","datetype");
$values=array($qryname,$grpid,$datetype);
�۰ʧP�_WEB����BSHELL��Ū�ɰ���
insert_sql($tbl[mailqry],$fields,$values);
���ɭ�WEB����o�S���ݭnStrip���\��pSession
insert_sql($tbl[mailqry],$fields,$values,0);

####################(update_sql�d��)

$fields=array("qryname","grpid","datetype");
$values=array($qryname,$grpid,$datetype);
�۰ʧP�_WEB����BSHELL��Ū�ɰ���
update_sql($tbl[mailqry],$fields,$values," qryid='$qryid'");
���ɭ�WEB����o�S���ݭnStrip���\��pSession
update_sql($tbl[mailqry],$fields,$values," qryid='$qryid'",0);

##################*/

// �۰ʧP�_ WEB ����P SHELL ����, log �������ȮɨS�g
   function insert_sql($table,$field,$value,$web=1,$log=0) {
     global $conn,$HTTP_HOST;
     if(!is_array($field)) return false;
     if(!is_array($value)) return false;
     $arrsize=count($field);
     if($arrsize!=count($value)) return false;
     $chtprocess="/([".chr(128)."-".chr(255)."]\\\\)\\\\/";
     $sqlcmd="Insert into $table (";
     for($i=0;$i<$arrsize;$i++) {
        if($field[$i]=="hsubject" || $field[$i]=="hfrom" ) {
          if($table!="filter_mailfilter") {
            if(strlen($value[$i])>512) $value[$i]=substr($value[$i],0,512);
            }
          }
	if($i>0) {
	  $sqlstr1.=",";
	  $sqlstr2.=",";
	}
	$sqlstr1.=$field[$i];
	if($HTTP_HOST && $web) {
	  $sqlstr2.=$conn->qstr(preg_replace($chtprocess,"\\1",$value[$i]),get_magic_quotes_gpc());
	} else $sqlstr2.=$conn->qstr(str_replace("'","\'",preg_replace($chtprocess,"\\1",str_replace("\\","\\\\",$value[$i]))),1);
     }
     $sqlcmd.=$sqlstr1.") values (".$sqlstr2.")";
     //$conn->Execute("Lock tables $table write");
     $conn->Execute($sqlcmd);
     $conn->Execute("Unlock tables");
   }
     
   function update_sql($table,$field,$value,$where,$web=1,$log=0) {
     global $conn,$HTTP_HOST;
     if(!is_array($field)) return false;
     if(!is_array($value)) return false;
     $arrsize=count($field);
     if($arrsize!=count($value)) return false;
     $chtprocess="/([".chr(128)."-".chr(255)."]\\\\)\\\\/";
     $sqlcmd="Update $table set ";
     for($i=0;$i<$arrsize;$i++) {
        if($field[$i]=="hsubject" || $field[$i]=="hfrom" ) {
          if($table!="filter_mailfilter") {
            if(strlen($value[$i])>512) $value[$i]=substr($value[$i],0,512);
            }
          }
	if($i>0) $sqlcmd.=",";
	if($HTTP_HOST && $web) {
	   $sqlcmd.=$field[$i]."=".$conn->qstr(preg_replace($chtprocess,"\\1",$value[$i]),get_magic_quotes_gpc());
	} else $sqlcmd.=$field[$i]."=".$conn->qstr(str_replace("'","\'",preg_replace($chtprocess,"\\1",str_replace("\\","\\\\",$value[$i]))),1);
     }
     if( $where ) $sqlcmd.=" where ".$where;
     //$conn->Execute("Lock tables $table write");
     $conn->Execute($sqlcmd);
     $conn->Execute("Unlock tables");
   }
    function snhtmlspecialchars($str) {
      return str_replace("  ","&nbsp;&nbsp;",preg_replace("/&lt;br&gt;/i","<br>",preg_replace("/&amp;(#\d{3,5};)/i","&\\1",htmlspecialchars($str))));
    }
  }
if ( !function_exists("optstr") ) {
function optstr($str,$ct) {
  if(strlen($str) > $ct) {
    for($i=0;$i<$ct;$i++) {
      $ch=substr($str,$i,1);
      if(ord($ch)>127) $i++;
      }
    $str= substr($str,0,$i)."...";
    }
  return $str;
  }
}
?>
