<?
include("/home/www/htdocs/BitEnforcer/conf.asp");
if ( !function_exists("SMB_ReadCfg") ) {
$SMB_XML="$DOCUMENT_ROOT/$SUB_ROOT/XML/samba.xml";
$MountTo="/mnt";
function SMB_ReadCfg($cfg) {
  if ( file_exists($cfg) ) {
    $dom = domxml_open_file($cfg);
    $node = $dom->get_elements_by_tagname("Server");
    $smb["Server"] = $node[0]->get_content();
    $node = $dom->get_elements_by_tagname("Share");
    $smb["Share"] = $node[0]->get_content();
    $node = $dom->get_elements_by_tagname("Domain");
    $smb["Domain"] = $node[0]->get_content();
    $node = $dom->get_elements_by_tagname("User");
    $smb["User"] = $node[0]->get_content();
    $node = $dom->get_elements_by_tagname("Password");
    $smb["Password"] = $node[0]->get_content();
  } else {
    $smb["Server"] = "";
    $smb["Share"] = "";
    $smb["Domain"] = "";
    $smb["User"] = "";
    $smb["Password"] = "";
  }
  return $smb;
}
function SMB_SaveCfg($cfg,$smb,$sync) {
  global $DOCUMENT_ROOT,$SUB_ROOT;
  $doc = domxml_new_doc('1.0');
  $root = $doc->create_element('BEFS');
  $root = $doc->append_child($root);
  $pcc = $doc->create_element('Server');
  $pcc = $root->append_child($pcc);
  $pvalue = $doc->create_text_node($smb["Server"]);
  $pvalue = $pcc->append_child($pvalue);
  $pcc = $doc->create_element('Share');
  $pcc = $root->append_child($pcc);
  $pvalue = $doc->create_text_node($smb["Share"]);
  $pvalue = $pcc->append_child($pvalue);
  $pcc = $doc->create_element('Domain');
  $pcc = $root->append_child($pcc);
  $pvalue = $doc->create_text_node($smb["Domain"]);
  $pvalue = $pcc->append_child($pvalue);
  $pcc = $doc->create_element('User');
  $pcc = $root->append_child($pcc);
  $pvalue = $doc->create_text_node($smb["User"]);
  $pvalue = $pcc->append_child($pvalue);
  $pcc = $doc->create_element('Password');
  $pcc = $root->append_child($pcc);
  $pvalue = $doc->create_text_node($smb["Password"]);
  $pvalue = $pcc->append_child($pvalue);
  $xml_string = $doc->dump_mem(true);
  $fp=fopen($cfg,"w");
  fwrite($fp,$xml_string);
  fclose($fp);
  chown($cfg,"www");
  chgrp($cfg,"www");
  //Trigger Sync
  if ($sync==1) {
    exec("/usr/local/php/bin/php -q $DOCUMENT_ROOT/$SUB_ROOT/berpc/triggerput.asp 2 > /dev/null &");
  }
}
function SMB_Mount($cfg) {
  global $DOCUMENT_ROOT,$SUB_ROOT;
  if ( !file_exists($cfg) ) {
    return false;
  }
  exec("$DOCUMENT_ROOT/$SUB_ROOT/lib/pwrapper $DOCUMENT_ROOT/$SUB_ROOT/lib/smbmount.asp mount $cfg",$rarray);
  $ret=chop(implode("",$rarray));
  if ( strpos($ret,"MountDone")!== false )
    return true;
  else
    return false;
}
function SMB_UMount() {
  global $DOCUMENT_ROOT,$SUB_ROOT;
  exec("$DOCUMENT_ROOT/$SUB_ROOT/lib/pwrapper $DOCUMENT_ROOT/$SUB_ROOT/lib/smbmount.asp umount");
}
function SMB_TestWrite() {
  global $DOCUMENT_ROOT,$SUB_ROOT;
  exec("$DOCUMENT_ROOT/$SUB_ROOT/lib/pwrapper $DOCUMENT_ROOT/$SUB_ROOT/lib/smbmount.asp testwrite",$rarray);
  $ret=chop(implode("",$rarray));
  if ( strpos($ret,"WriteDone")!== false )
    return true;
  else
    return false;
}
} //End of function_exists
/*
$smb["Server"]="192.168.11.236";
$smb["Share"]="Enc";
$smb["User"]="administrator";
$smb["Password"]="abc123";
$smb["domain"]="";
SMB_SaveCfg($SMB_XML,$smb,1);
*/
//SMB_Mount("/var/tmp/smb.500244ba95bad");
//SMB_TestWrite();
?>