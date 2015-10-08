<?
/*<!ionCube!>*/
include_once("/home/www/htdocs/BitEnforcer/conf.asp");
include_once("$DOCUMENT_ROOT/$SUB_ROOT/sql.asp");
include_once("$DOCUMENT_ROOT/$SUB_ROOT/FSLog.ini");

$MaxPartitionRecordCount        = 950000;
$CryptoID                       = array("VD0","AES","DES","3DS");
$UUID_RC4_KEY                   ="8b6cb4d7@223a#4a60,8367:43e58815cc92";

function fslDelRepository($dt) {
  global $conn,$FslBodyFolder,$MIRROR;
  if (!isset($MIRROR[this]) )
    $MIRROR[this]=0;
  $dir=$FslBodyFolder.fslDateToPath($dt);
  $mdir=substr($dir,0,(strlen($dir)-3));
  $fp=popen("/bin/find ".$dir." -type f","r");
  while (($fl=fgets($fp,1024)) !== false) {
    $fl=basename(chop($fl));
    fslDeleteFile($fl);
  }
  pclose($fp);
  exec("/bin/rm -rf ".$dir);
  @rmdir($mdir);
  $conn->Execute("Delete from RepositoryList where RepoDate=".$conn->qstr($dt)." And MirrorIdx=$MIRROR[this]");
}

function fslNewRepository($dt) {
  global $conn,$FslBodyFolder,$MIRROR;
  if (!isset($MIRROR[this]) )
    $MIRROR[this]=0;
  $rs=$conn->Execute("Select RepoDate from RepositoryList where RepoDate=".$conn->qstr($dt)." And MirrorIdx=$MIRROR[this]");
  if ( $rs->EOF ) {
    $fds=array("RepoDate","RepoPath","MirrorIdx");
    $val=array($dt,$FslBodyFolder,$MIRROR[this]);
    insert_sql("RepositoryList",$fds,$val);
  }
  $dir=explode("-",$dt);
  if ( !is_dir($FslBodyFolder."/".$dir[0]) ) {
    @mkdir($FslBodyFolder.$dir[0]);
    @chown($FslBodyFolder.$dir[0],"www");
    @chgrp($FslBodyFolder.$dir[0],"www");
	}
  if ( !is_dir($FslBodyFolder) ) {
    @mkdir($FslBodyFolder);
    @chown($FslBodyFolder,"www");
    @chgrp($FslBodyFolder,"www");
  }
  if ( !is_dir($FslBodyFolder."/".$dir[0]."/".$dir[1]) ) {
    @mkdir($FslBodyFolder.$dir[0]."/".$dir[1]);
    @chown($FslBodyFolder.$dir[0]."/".$dir[1],"www");
    @chgrp($FslBodyFolder.$dir[0]."/".$dir[1],"www");
  }
  if ( !is_dir($FslBodyFolder."/".$dir[0]."/".$dir[1]."/".$dir[2]) ) {
    @mkdir($FslBodyFolder.$dir[0]."/".$dir[1]."/".$dir[2]);
    @chown($FslBodyFolder.$dir[0]."/".$dir[1]."/".$dir[2],"www");
    @chgrp($FslBodyFolder.$dir[0]."/".$dir[1]."/".$dir[2],"www");
  }
}

function fslIsRepository($dt) {
  global $conn,$MIRROR;
  if (!isset($MIRROR[this]) )
    $MIRROR[this]=0;
  $rs=$conn->Execute("Select RepoDate from RepositoryList where RepoDate=".$conn->qstr($dt)." And MirrorIdx=$MIRROR[this]");
  if ( $rs->EOF ) {
    return false;
  } else {
    return true;
  }
}

function fslDateToPath($dt) {
  global $conn,$MIRROR;
  if (!isset($MIRROR[this]) )
    $MIRROR[this]=0;
  $rs=$conn->Execute("Selec RepoPath from RepositoryList where RepoDate=".$conn->qstr($dt)." And MirrorIdx=$MIRROR[this]");
  if ( $rs->EOF ) {
    return false;
  } else {
    return $rs->fields["RepoPath"].str_replace("-","/",$dt)."/";
  }
}

function fslPathToDate($fn) {
  $str=explode("/",$fn);
  return $str[3]."-".$str[4]."-".$str[5];
}

function fslGetHMd5($fl) {
  global $CryptoID,$DOCUMENT_ROOT,$SUB_ROOT,$UUID_RC4_KEY;
  $rv = false;
  if ( is_file($fl) ) {
    $fp=fopen($fl,"r");
    //Key MD5
    fseek($fp,85);
    $KMd5=fread($fp,32);
    //Key Checksum
    $KCks=fread($fp,4);
    //CryptoID
    $CRId=fread($fp,3);
    fclose($fp);
    if ( in_array($CRId,$CryptoID) && $KCks==substr($KMd5,1,4) ) {
      $fp=popen("$DOCUMENT_ROOT/$SUB_ROOT/lib/rtool '".escapeshellarg($UUID_RC4_KEY)."' 125 36 $fl","r");
      $rv=chop(fread($fp,36));
      pclose($fp);
    }
  }
  return $rv;
}

function fslSaveFile($HMd5,$fl){
  global $conn,$FslBodyFolder,$MIRROR;
  if (!isset($MIRROR[this]) )
    $MIRROR[this]=0;
  $rv=false;
  if ( fslIsFileExists($HMd5)===false ) {
    $dt=date("Y-m-d");
    if ( fslIsRepository($dt)===false ) {
      fslNewRepository($dt);
    }
    $dir=fslDateToPath($dt);
    $full_dir=$FslBodyFolder.$dir.substr($HMd5,0,1)."/";
    unset($dir);
    if ( !is_dir($full_dir) ) {
      @mkdir($full_dir);
	  @chown($full_dir,"www");
      @chgrp($full_dir,"www");
    }
    $fn=$HMd5;
    $full_path=$full_dir.$fn;
    @rename($fl,$full_path);
    if ( is_file($full_path) ) {
      @chown($full_path,"www");
      @chgrp($full_path,"www");
      $fds=array("PT","HMd5","MirrorIdx","FileSize","FilePath");
      $val=array(substr($HMd5,0,1),$HMd5,$MIRROR[this],filesize($full_path),$full_path);
      insert_sql("FileBody",$fds,$val);
      $rv=$full_path;
    }
  }
  return $rv;
}

function fslMoveFile($hmd5,$date) {
  global $conn,$FslBodyFolder,$MIRROR;
  if (!isset($MIRROR[this]) )
    $MIRROR[this]=0;
  $rv=false;
  $pt = substr($hmd5,0,1);
  $rs = $conn->Execute("Select * from FileBody where PT=".$conn->qstr($pt)." and HMd5=".$conn->qstr($hmd5)." And MirrorIdx=$MIRROR[this]");
  if ( !$rs->EOF ) {
    $srcPath = $rs->fields[FilePath];
    if ( fslIsRepository($date)===false ) {
      fslNewRepository($dt);
    }
    $dir=fslDateToPath($date);
    $full_dir=$FslBodyFolder.$dir.substr($hmd5,0,1)."/";
    unset($dir);
    if ( !is_dir($full_dir) ) {
      @mkdir($full_dir);
      @chown($full_dir,"www");
      @chgrp($full_dir,"www");
    }
    $dstPath=$full_dir.$hmd5;
    @rename($srcPath, $dstPath);
    if ( is_file($dstPath) ) {
	   $conn->Execute("Update FileBody set FilePath=".$conn->qstr($dstPath)." where PT=".$conn->qstr($pt)." and HMd5=".$conn->qstr($hmd5)." And MirrorIdx=$MIRROR[this]");
	   $rv=$dstPath;
    }
  }
  return $rv;
}

function fslDeleteFile($hmd5) {
  global $conn,$MIRROR;
  if (!isset($MIRROR[this]) )
    $MIRROR[this]=0;
  $pt = substr($hmd5,0,1);
  $rs = $conn->Execute("Select * from FileBody where PT=".$conn->qstr($pt)." and HMd5=".$conn->qstr($hmd5)." And MirrorIdx=$MIRROR[this]");
  if ( !$rs->EOF ) {
    @unlink($rs->fields[FilePath]);
    $conn->Execute("Delete from FileBody where PT=".$conn->qstr($pt)." and HMd5=".$conn->qstr($hmd5)." And MirrorIdx=$MIRROR[this]");
  }
}

function fslIsFileExists($hmd5) {
  global $conn;
  $pt = substr($hmd5,0,1);
  $rs = $conn->Execute("Select * from FileBody where PT=".$conn->qstr($pt)." and HMd5=".$conn->qstr($hmd5));
  if ( $rs->EOF ) {
    return false;
  } else {
    if ( is_file($rs->fields[FilePath]) ) {
      return $rs->fields[FilePath];
    } else {
      return true;
    }
  }
}

function fslGetLastPartitionRange($tbl,$fd) {
  global $conn;
  $rs = $conn->Execute("SELECT PARTITION_NAME, TABLE_ROWS, PARTITION_EXPRESSION, PARTITION_DESCRIPTION, PARTITION_ORDINAL_POSITION FROM INFORMATION_SCHEMA.PARTITIONS WHERE TABLE_NAME = ".$conn->qstr($tbl)." Order by PARTITION_ORDINAL_POSITION");
  if ($rs->RecordCount()==1) {
    $dt="2012-08-01";
  } else {
    $days="";
    while ( !$rs->EOF ) {
      if ($rs->fields["PARTITION_DESCRIPTION"]!="MAXVALUE") {
        $days=$rs->fields["PARTITION_DESCRIPTION"];
      }
      $rs->MoveNext();
    }
    $rs1=$conn->Execute("Select $fd from $tbl where TO_DAYS($fd)=$days");
    $dt=$rs1->fields[0];
  }
  return $dt;
}

function fslGetLastPartitionCount($tbl,$field) {
  global $conn;
  $dt=fslGetLastPartitionRange($tbl,$field);
  $rs1=$conn->Execute("Select TO_DAYS(".$conn->qstr($dt).")");
  $value=$rs1->fields[0];
  $rs = $conn->Execute("Select count(*) from $tbl where TO_DAYS($field)>=$value");
  return $rs->fields[0];
}

function fslPartitionRotate($tbl,$dt) {
  global $conn;
  $rs=$conn->Execute("Select PARTITION_ORDINAL_POSITION from INFORMATION_SCHEMA.PARTITIONS where TABLE_NAME=".$conn->qstr($tbl)." order by PARTITION_ORDINAL_POSITION desc Limit 0,1");
  $pcnt=$rs->fields[0];
  $pname="p".str_pad($pcnt,4,"0",STR_PAD_LEFT);
  $conn->Execute("Alter table $tbl REORGANIZE Partition pmax Into ( Partition $pname VALUES LESS THAN (TO_DAYS(".$conn->qstr($dt).")), Partition smax VALUES LESS THAN (MAXVALUE) )");
  $conn->Execute("Alter table $tbl REORGANIZE Partition smax Into ( Partition pmax VALUES LESS THAN (MAXVALUE) )");
}

//echo fslGetLastPartitionRange("FileLog","LogDate")."\n";
//echo fslGetLastPartitionCount("FileLog","LogDate")."\n";
//fslPartitionRotate("FileLog","2012-08-16");
//echo fslGetLastPartitionRange("FileLog","LogDate")."\n";
//echo fslGetLastPartitionCount("FileLog","LogDate")."\n";
/*
$ts1=mktime();
if (fslIsFileExists("ab6872863edc0e0424c3fc5a9a123752"))
  echo "found\n";
else
  echo "nothing\n";
$ts2=mktime();
$df=($ts2-$ts1)/1000;
echo $df."\n";
*/
//fslMoveFile("f4acf564-3486-4cc0-8dbb-69ebe4ac9270","2013-04-26");
//fslDeleteFile("f4acf564-3486-4cc0-8dbb-69ebe4ac9270");
?>