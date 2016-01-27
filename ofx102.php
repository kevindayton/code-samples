<?php
/**
 * This was part of a personal finance web application project called 
 * "Checkbook" (later "Balances").  The code samples are pieces of the feature 
 * known as AutoPilot.  AutoPilot was operated as a command-line service 
 * written in PHP that would go out and download new transactions from a 
 * person's bank account and add them to the Checkbook application.  If the 
 * service found new transactions, it would send a message to the user by 
 * e-mail or by push notification on an Apple iOS device.
 */
 
/**
 * ofx102.php
 * This file contains a class for parsing OFX version 1.0.2 files
 * 
 * @package     V8 Balances
 * @subpackage  Data Import
 * @license     Private
 * @author      Kevin L. Dayton  
 * @copyright   2006 Volatile Eight Industries/Dayton Interactive/Kevin L. Dayton, All rights reserved.
 */

/**
 * OFX102Parser
 * A class for parsing OFX version 1.0.2 files
 * 
 * @package     V8 Balances
 * @subpackage  Data Import
 * @author      Kevin L. Dayton  
 *
 * @method  array  initWithFile()
 * @method  array  initWithString()
 */
class OFX102Parser {

  /**
   * initWithFile()
   * Takes a file path, creates a string from it, and runs it through initWithString()
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param string  $file A path to a file
   *
   * @return  array An array of parsed OFX 1.0.2 data
   */
  public function initWithFile($file) {
    $ofx = file_get_contents($file);
    return $this->initWithString($ofx);
  }

  /**
   * initWithString()
   * Takes a file path, creates a string from it, and runs it through initWithString()
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param string  $ofx A string representation of OFX 1.0.2 data
   *
   * @return  array An array of parsed OFX 1.0.2 data
   */  
  public function initWithString($ofx) {
    $metaPattern = '/([A-Za-z0-9:\r\n\t]+)*<OFX>/';
    preg_match_all($metaPattern, $ofx, $meta);
    $meta = trim($meta[1][0]);
    $meta = explode("\n", $meta);
    $fmtMeta = array();
    for($m=0;$m<count($meta);$m++) {
      $n = explode(':',$meta[$m]);
      $fmtMeta[$n[0]]=$n[1];
    }
    $result['META']= $fmtMeta;
    
    
    $ofx = str_replace(array("\s","\n","\t"),'',$ofx);
    // Get the Sign On Message Info
    // We are particularly interested in the status
    $signOnMsgPattern = '/<SIGNONMSGSRSV1>(.*?)<\/SIGNONMSGSRSV1>/i';
    preg_match_all($signOnMsgPattern, $ofx, $signOnMsg);
    $statusPattern = '/<STATUS>(.*?)<\/STATUS>/i';
    preg_match_all($statusPattern, $signOnMsg[1][0], $statusMatch);
    $statusDetailPattern = '/<[A-Z]+>/i';
    $status = (preg_split($statusDetailPattern, $statusMatch[1][0]));
    unset($status[0]);
    $result['OFX']['SIGNONMSGSRSV1']['SONRS']['STATUS'] = array('CODE'=>$status[1],'SEVERITY'=>$status[2]);
    
    // Get the transactions
    $msgRsvPattern = '/<(CREDITCARDMSGSRSV1|BANKMSGSRSV1)>[\s+]?<(CCSTMTTRNRS|STMTTRNRS)>[\s+]?<TRNUID>([0-9]+)[\s+]?<STATUS>[\s+]?<CODE>([0-9]+)[\s+]?<SEVERITY>([A-Z]+)[\s+]?<\/STATUS>[\s+]?<(CCSTMTRS|STMTRS)>[\s+]?<CURDEF>([A-Z]+)[\s+]?<([A-Z]*?ACCTFROM)>[\s+]?(<BANKID>([0-9]+))?[\s+]?<ACCTID>([A-Za-z0-9]+)[\s+]?(<ACCTTYPE>([0-9A-Za-z]+))?<\/[A-Z]+?ACCTFROM>[\s+]?<BANKTRANLIST>[\s+]?<DTSTART>([A-Za-z:\[\]0-9\.-]+)[\s+]?<DTEND>([A-Za-z:\[\]0-9\.-]+)[\s+]?<STMTTRN>.*?<\/STMTTRN>[\s+]?<\/BANKTRANLIST>[\s+]?<LEDGERBAL>[\s+]?<BALAMT>([-\.0-9]+)[\s+]?<DTASOF>([A-Za-z:\[\]0-9\.-]+)[\s+]?<\/LEDGERBAL>[\s+]?<AVAILBAL>[\s+]?<BALAMT>([-\.0-9]+)[\s+]?<DTASOF>([A-Za-z:\[\]0-9\.-]+)[\s+]?<\/AVAILBAL>[\s+]?<\/[A-Z]*?STMTRS>[\s+]?<\/[A-Z]*?STMTTRNRS>[\s+]?<\/[A-Z]*?MSGSRSV1>/mi';
    preg_match_all($msgRsvPattern, $ofx, $msgRsv);
    $transactionsMsgPattern = '/[\s+]?<STMTTRN>(.*?)<\/STMTTRN>[\s+]?/i';
    preg_match_all($transactionsMsgPattern, $ofx, $transactionsMsg);
    for($t=0; $t < count($transactionsMsg[1]); $t++) {
      $tTypePattern = '/<TRNTYPE>(.*?)<[A-Z]+>/';
      preg_match_all($tTypePattern,$transactionsMsg[1][$t], $tTypeMsg[$t]);
      $transactions[$t]['STMTTRN']['TRNTYPE'] = $tTypeMsg[$t][1][0];
      $dtPostedPattern = '/<DTPOSTED>(.*?)<[A-Z]+>/';
      preg_match_all($dtPostedPattern,$transactionsMsg[1][$t], $dtPosted[$t]);
      $transactions[$t]['STMTTRN']['DTPOSTED'] = $dtPosted[$t][1][0];
      $trnAmtPattern = '/<TRNAMT>(.*?)<[A-Z]+>/';
      preg_match_all($trnAmtPattern,$transactionsMsg[1][$t], $trnAmt[$t]);
      $transactions[$t]['STMTTRN']['TRNAMT'] = $trnAmt[$t][1][0];
      $fitIDPattern = '/<FITID>(.*?)<[A-Z]+>/';
      preg_match_all($fitIDPattern,$transactionsMsg[1][$t], $fitID[$t]);
      $transactions[$t]['STMTTRN']['FITID'] = $fitID[$t][1][0];
      $namePattern = '/<NAME>([0-9A-Za-z\/\s:\'\*\.#&\-]+)/';
      preg_match_all($namePattern,$transactionsMsg[1][$t], $name[$t]);
      $transactions[$t]['STMTTRN']['NAME'] = $name[$t][1][0];
      $memoPattern = '/<MEMO>([0-9A-Za-z\/\s:\'\*\.#&\-]+)/';
      preg_match_all($memoPattern,$transactionsMsg[1][$t], $memo[$t]);
      $transactions[$t]['STMTTRN']['MEMO'] = $memo[$t][1][0];
    }
    $result['OFX'][$msgRsv[1][0]][$msgRsv[2][0]]['TRNUID'] = $msgRsv[3][0];
    $result['OFX'][$msgRsv[1][0]][$msgRsv[2][0]]['STATUS']['CODE'] = $msgRsv[4][0];
    $result['OFX'][$msgRsv[1][0]][$msgRsv[2][0]]['STATUS']['SEVERITY'] = $msgRsv[5][0];
    $result['OFX'][$msgRsv[1][0]][$msgRsv[2][0]][$msgRsv[6][0]]['CURDEF']=$msgRsv[7][0];
    if($msgRsv[10][0] != '') {
      $result['OFX'][$msgRsv[1][0]][$msgRsv[2][0]][$msgRsv[6][0]][$msgRsv[8][0]]['BANKID']=$msgRsv[10][0];
    }
    $result['OFX'][$msgRsv[1][0]][$msgRsv[2][0]][$msgRsv[6][0]][$msgRsv[8][0]]['ACCTID']=$msgRsv[11][0];
    if($msgRsv[13][0] != '') {
      $result['OFX'][$msgRsv[1][0]][$msgRsv[2][0]][$msgRsv[6][0]][$msgRsv[8][0]]['ACCTTYPE']=$msgRsv[13][0];
    }
    $result['OFX'][$msgRsv[1][0]][$msgRsv[2][0]][$msgRsv[6][0]]['BANKTRANLIST']=$transactions;
    // Get ledger balance
    $ledgerMsgPattern = '/<LEDGERBAL>(.*?)<\/LEDGERBAL>/i';
    preg_match_all($ledgerMsgPattern, $ofx, $ledgerMsg);
    $ledgerDetailPattern = '/<[A-Z]+>/i';
    $ledger = preg_split($ledgerDetailPattern, $ledgerMsg[1][0]);
    unset($ledger[0]);
    $result['OFX'][$msgRsv[1][0]][$msgRsv[2][0]][$msgRsv[6][0]]['LEDGERBAL']['BALAMT']= $ledger[1];
    $result['OFX'][$msgRsv[1][0]][$msgRsv[2][0]][$msgRsv[6][0]]['LEDGERBAL']['DTASOF']= $ledger[2];
    // Get available balance
    $availMsgPattern = '/<AVAILBAL>(.*?)<\/AVAILBAL>/i';
    preg_match_all($availMsgPattern, $ofx, $availMsg);
    $availDetailPattern = '/<[A-Z]+>/i';
    $avail = preg_split($availDetailPattern, $availMsg[1][0]);
    unset($ledger[0]);
    $result['OFX'][$msgRsv[1][0]][$msgRsv[2][0]][$msgRsv[6][0]]['AVAILBAL']['BALAMT']= $avail[1];
    $result['OFX'][$msgRsv[1][0]][$msgRsv[2][0]][$msgRsv[6][0]]['AVAILBAL']['DTASOF']= $avail[2];
    return $result;
  }
}
