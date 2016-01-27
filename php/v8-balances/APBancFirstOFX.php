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
 * APBancFirstOFX.php
 * This file contains a class for fetching OFX data from the BancFirst Website
 * 
 * @package     V8 Balances
 * @subpackage  AutoPilot
 * @license     Private
 * @author      Kevin L. Dayton  
 * @copyright   2006 Volatile Eight Industries/Dayton Interactive/Kevin L. Dayton, All rights reserved.
 */
 
require_once ROOT_PATH . '/protected/modules/interfaces/IAutoPilot.php';
require_once ROOT_PATH . '/protected/modules/CBAutoPilot.php';
require_once ROOT_PATH . '/protected/modules/classes/ofx102.php';

/**
 * APBancFirstOFX
 * A class for fetching OFX from the BancFirst Website
 * 
 * @package     V8 Balances
 * @subpackage  AutoPilot
 * @author      Kevin L. Dayton  
 *
 * @property string     $token
 * @property bool       $challenge
 * @property string     $answer1
 * @property string     $answer2
 * @property string     $answer3
 * @property string     $acctName 
 * @property int        $acctNo
 * @property decimal    $acctCBalance 
 * @property decimal    $acctABalance
 * @property string     $acctDetailsLink
 * @property string     $acctURLRef
 * @property bool       $initialized
 * @property bool       $loggedIn
 * @property bool       $exportLoaded
 * @property string     $ssid
 * @property string     $processor
 * @property PDOObject  $db
 * @property string     $cookieJar
 * @property bool       $verbose 
 * @property bool       $mail
 * @property string     $acctSearchName
 * @property string     $task
 * @property array      $output
 * @property string     $password
 * @property string     $username
 * @property int        $acctId
 * @property mixed      $apAcctId
 * @property date       $start
 * @property date       $end
 * @property decimal    $ver
 * @property array      $trans
 * @property bool       $debug
 * @property decimal    $apVer
 * @property string     $apURL
 * @property string     $debugLog
 *
 * @method void     init($config)
 * @method void     run()
 * @method bool     getAccountInfo($acct)
 * @method mixed    initialize($onComplete)
 * @method void     login($onComplete)
 * @method void     processChallenge($result, $onComplete)
 * @method void     submitChallenge($onComplete)
 * @method void     getChallengeQuestions($onComplete,$token)
 * @method void     setChallengePreference($onComplete)
 * @method void     loadHomeBanking($onComplete)
 * @method string   getCSVFile($accountName)
 * @method void     logout()
 * @method void     getAnswer($question)
 * @method array    getPostedTransactions($acct,$start,$end)
 * @method array    getPendingTransactions($acct,$start,$end)
 * @method array    getTransactionOccurrences($trans, $hash)
 * @method void     loadAccountInfo($acctName,$onComplete)
 * @method void     loadExport($onComplete)
 * @method string   getOFXFile($accountName,$start,$end)
 * @method void     writeOutput($file,$data)
 */
class APBancFirstOFX extends CBAutoPilot implements IAutoPilot {
  private $token;
  private $challenge;
  private $answer1;
  private $answer2;
  private $answer3;
  private $acctName, 
  private $acctNo;
  private $acctCBalance; 
  private $acctABalance;
  private $acctDetailsLink;
  private $acctURLRef;
  private $initialized;
  private $loggedIn;
  private $exportLoaded;
  public $ssid;
  public $processor;
  public $db;
  public $cookieJar;
  public $verbose = false; 
  public $mail = false; 
  public $acctSearchName;
  public $task;
  public $output = array();
  public $password
  public $username;
  public $acctId;
  public $apAcctId;
  public $start;
  public $end;
  public $ver;
  public $trans;
  public $debug = false;
  public $apVer;
  public $apURL;
  public $debugLog;

  /**
   * init()
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param   array  $config  An array of configuration values
   *
   * @return  void
   */ 
  public function init($config) {
    parent::init($config);
    $this->challenge = true;
    $this->debug = false;
    $this->processor = $processor;
    $this->debugLog = ROOT_PATH . '/protected/modules/resources/autopilot/logs/' . date('YmdHis') . '-bancfirstdata.txt';
  }

  /**
   * run()
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @return  void
   */ 
  public function run() {
  	$this->init(null);
    if ($this->apAcctId) {
      $this->cookieJar = ROOT_PATH . '/protected/modules/resources/autopilot/cookies/' . md5($this->acctId);
      eval('$this->' . $this->task . '(\'' . $this->apAcctId . '\',\''.$this->start.'\',\''.$this->end.'\');');
    } else {
      die('No account given');
    }
  }
  
  /**
   * getAccountInfo()
   * Shell function to satisfy interface.  More functionality to come.
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       mixed $acct An string or int that identifies an account 
   * @return      void
   */ 
  public function getAccountInfo($acct) {
    //Shell function to satisfy interface.  More functionality to come.
    return;
  }

  /**
   * initialize()
   * Initializes the Web request process
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       string $onComplete Name of function to call on completion
   * 
   * @return      mixed On success void, on failure string 
   */ 
  private function initialize($onComplete) {
    $ch = curl_init("https://www.bancfirstonline.com/onlineserv/HB/Signon.cgi");
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; tr-TR; rv:1.7.6) Gecko/20050321 Firefox/1.0.2');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    if ($this->debug) {
      $myFile = $this->debugLog;
      $fh = fopen($myFile, 'a') or die("can't open file");
      fwrite($fh, "initialize\n" . $output . "\n=====\n");  
      fclose($fh);
    }
    $pattern = '/(?<=input type="hidden" name="DISESSIONID" value=")(.*?)(?=" \/\>)/';
    preg_match($pattern, $output, $matches);
    if ($matches[0]) {
      $this->ssid = $matches[0];
      $this->login($onComplete);
    } else {
      $this->logout();
      return "Failed to initialize";
    }
  }

  /**
   * login()
   * cURLs the login page
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       string $onComplete Name of function to call on completion 
   * @return      void
   */ 
  private function login($onComplete) {
    $ch = curl_init("https://www.bancfirstonline.com/onlineserv/HB/Login.cgi");
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; tr-TR; rv:1.7.6) Gecko/20050321 Firefox/1.0.2');
    curl_setopt($ch, CURLOPT_POST, 1);
    $postfields = 'DISESSIONID=' . $this->ssid . '&runmode=SIGN_IN&userNumber=' . $this->username . '&password=' . $this->password . '&x=13&y=8&loginStartPage=';
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    if ($output) {
      $settingsPattern = "/var SETTINGS = ({
          activation_url : \'([0-9A-Za-z\.]+)\',
          form_id: \'passcodeForm\',
          token_id : \"([0-9A-Za-z\.]+)\",
          error_messages : {
              \'invalid_code\' : \'Invalid code - please try again.\',
              \'expired_code\' : \'Expired code. The code is only valid for 15 minutes.\'
          },
          challenge_questions: {
              \'select_question\' : \'--Select Validation Question--\',
              \'question\' : \'Question\',
              \'answer\' : \'Answer\'
          },
          channels: {
              \'sms\' : \'Text message\', 
              \'voice\' : \'Voice call\', 
              \'email\' : \'Email\', 
              \'cq\' : \'\'
          }	
      });/";
	  $breaks   = array("\r\n", "\n", "\r");
	  $settingsPattern = str_replace($breaks,'',$settingsPattern);
	  $output = str_replace($breaks,'',$output);
	  
	  preg_match($settingsPattern, $output, $settingsMatches);
    $challengePattern = '/Answer these questions to confirm your identity/';
    preg_match($challengePattern, $output, $matches);
      
      if($settingsMatches[3]) {
        $this->token = $settingsMatches[3];
        $this->processChallenge($this->getChallengeQuestions(null,$this->token), $onComplete);
      } else {
        $this->logout();
        $result = 'Login: unknown result';
        $this->output[] = $result . "\n";
      }
    }
    if ($this->debug) {
      $myFile = $this->debugLog;
      $fh = fopen($myFile, 'a') or die("can't open file");
      fwrite($fh, "login\n" . print_r($settingsMatches,1)."\n".$output . "\n=====\n");  
      fclose($fh);
    }
  }

  /**
   * processChallenge()
   * Process the challenge questions the login presents
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       string $result     String of HTML returned from the getChallengeQuestions method 
   * @param       string $onComplete Name of function to call on completion 
   * @return      void
   */ 
  private function processChallenge($result, $onComplete) {
    $questionPattern = '/(<td><span id="Question[0-9]" name="Question[0-9]">(.*?)<\/span><\/td>)/';
    preg_match_all($questionPattern, $result, $matches);
    if ($matches[2][0]) {
      $question1 = $matches[2][0];
    } else {
      $question1 = 'Cannot determine challenge question 1.';
    }
    if ($matches[2][1]) {
      $question2 = $matches[2][1];
    } else {
      $question2 = 'Cannot determine challenge question 2.';
    }
    if ($matches[2][2]) {
      $question3 = $matches[2][2];
    } else {
      $question3 = 'Cannot determine challenge question 3.';
    }
    if ($question1) {
      $this->answer1 = $this->getChallengeResponse($question1);
    }
    if ($question2) {
      $this->answer2 = $this->getChallengeResponse($question2);
    }
    if ($question3) {
      $this->answer3 = $this->getChallengeResponse($question3);
    }
    if ($this->debug) {
      $myFile = $this->debugLog;
      $fh = fopen($myFile, 'a') or die("can't open file");
      fwrite($fh, "processChallenge\n" . print_r($matches, 1) . "\n=====\n");  
      fclose($fh);
    }
    if ($this->answer1 . $this->answer2 . $this->answer3) {
      $this->submitChallenge($onComplete);
    } else {
      $this->logout();
      die(date('Y-m-d h:i:s') . ':' . "\t" . 'Could not establish answers to all the challenge questions.');
    }
  }

  /**
   * submitChallenge()
   * Submits the challenge answers
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       string $onComplete Name of function to call on completion 
   * @return      void
   */ 
  private function submitChallenge($onComplete) {
    $ch = curl_init("https://www.bancfirstonline.com/onlineserv/HB/Login.cgi");
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.bancfirstonline.com/onlineserv/HB/Login.cgi');
    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
    curl_setopt($ch, CURLOPT_COOKIE, 'SUBMITTED=ANNA');
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; tr-TR; rv:1.7.6) Gecko/20050321 Firefox/1.0.2');
    curl_setopt($ch, CURLOPT_POST, 1);
    $postfields = 'token='.$this->token.'&action=validateInfo&Answer1=' . $this->answer1 . '&Answer2=' . $this->answer2 . '&Answer3=' . $this->answer3;
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    if ($this->debug) {
      $myFile = $this->debugLog;
      $fh = fopen($myFile, 'a') or die("can't open file");
      fwrite($fh, "submitChallenge\n" . $postfields."\n".$output . "\n=====\n");  
      fclose($fh);
    }
    if (strpos($output, '<html>')) {
      $this->loadHomeBanking($onComplete);
    } elseif (strpos($output, 'success')) {
      $this->setChallengePreference($onComplete);
    }
  }

  /**
   * getChallengeQuestions()
   * Parses the challenge question from the login method
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       string $onComplete Name of function to call on completion 
   * @param       string token       A string used by the site to track requests for security purposes 
   *
   * @return      void
   */ 
  private function getChallengeQuestions($onComplete,$token) {
    $ch = curl_init("https://www.bancfirstonline.com/onlineserv/HB/Login.cgi");
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.bancfirstonline.com/onlineserv/HB/Login.cgi');
    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
    curl_setopt($ch, CURLOPT_COOKIE, 'SUBMITTED=ANNA');
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; tr-TR; rv:1.7.6) Gecko/20050321 Firefox/1.0.2');
    curl_setopt($ch, CURLOPT_POST, 1);
    $postfields = 'token='.$token.'&ChallengeChoice=CQ';
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $output = curl_exec($ch);
    if ($this->debug) {
      $myFile = $this->debugLog;
      $fh = fopen($myFile, 'a') or die("can't open file");
      fwrite($fh, "getChallengeQuestions\n" . $postfields."\n".$output . "\n=====\n");  
      fclose($fh);
    }
    curl_close($ch);
    return $output;
  }
  
  /**
   * setChallengePreference()
   * Sets the challenge preference to no because with this script the questions will be answered every time
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       string $onComplete Name of function to call on completion 
   *
   * @return      void
   */ 
  private function setChallengePreference($onComplete) {
    $ch = curl_init("https://www.bancfirstonline.com/onlineserv/HB/Login.cgi");
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.bancfirstonline.com/onlineserv/HB/Login.cgi');
    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
    curl_setopt($ch, CURLOPT_COOKIE, 'SUBMITTED=ANNA');
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; tr-TR; rv:1.7.6) Gecko/20050321 Firefox/1.0.2');
    curl_setopt($ch, CURLOPT_POST, 1);
    $postfields = 'Answer1=' . $this->answer1 . '&Answer2=' . $this->answer2 . '&action=Continue&mfa_enroll=no';
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $output = curl_exec($ch);
    if ($this->debug) {
      $myFile = $this->debugLog;
      $fh = fopen($myFile, 'a') or die("can't open file");
      fwrite($fh, "setChallengePreference\n" . $output . "\n=====\n");
      fclose($fh);
    }
    curl_close($ch);
    $this->loadHomeBanking($onComplete);
  }
  
  /**
   * loadHomeBanking()
   * Loads the home banking home page
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       string $onComplete Name of function to call on completion 
   *
   * @return      void
   */ 
  private function loadHomeBanking($onComplete) {
    $ch = curl_init("https://www.bancfirstonline.com/onlineserv/HB/HomeBanking.cgi");
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.bancfirstonline.com/onlineserv/HB/Login.cgi');
    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; tr-TR; rv:1.7.6) Gecko/20050321 Firefox/1.0.2');
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    if ($this->debug) {
      $myFile = $this->debugLog;
      $fh = fopen($myFile, 'a') or die("can't open file");
      fwrite($fh, "loadHomeBanking\n" . $output . "\n=====\n");
      fclose($fh);
    }
    $this->loadAccountInfo($this->acctName, $onComplete);
  }
  
  /**
   * getCSVFile()
   * Gets a CSV version of the transactions for an account
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       string $accountName Name of account 
   *
   * @return      string
   */ 
  public function getCSVFile($accountName) {
    $this->acctName = $accountName;
    if ($this->initialized) {
      $ch = curl_init("https://www.bancfirstonline.com/onlineserv/HB/Export.csv");
      curl_setopt($ch, CURLOPT_HEADER, 1);
      curl_setopt($ch, CURLOPT_REFERER, 'https://www.bancfirstonline.com/onlineserv/HB/Login.cgi');
      curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
      curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; tr-TR; rv:1.7.6) Gecko/20050321 Firefox/1.0.2');
      curl_setopt($ch, CURLOPT_POST, 1);
      $postfields = 'ref=' . $this->acctURLRef . '&nextStartMonth=04&nextStartDay=01&nextStartYear=2008&nextEndMonth=04&nextEndDay=30&nextEndYear=2008&startDate=04%2F01%2F2008+00%3A00%3A00&endDate=04%2F30%2F2008+00%3A00%3A00&state=export&source=export&foreignDates=FALSE&type=CSV&flaggedExport=FALSE&typeList=CSV';
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      $output = curl_exec($ch);
      curl_close($ch);
      $contentTypePatter = '/Content-Type:\s[0-9a-zA-Z\/;\s=-]*\n/';
      preg_match($contentTypePatter, $output, $contentType);
      if ($contentType[0]) {
        if (stripos($contentType[0], 'application/csv')) {
          $transFile = 'transactions.csv';
          //Strip the headers
          $content = substr($output, stripos($output, 'Transaction Number'));
          $x = explode("\n", $content);
          for($y = 0; $y <= count($x); $y++) {
            if ($x[$y]) {
              $fmt[] = explode(',', $x[$y]);
            }
          }
          $postedTrans = array_shift($fmt);
          $fh = fopen($transFile, 'w');
          if ($fh) {
            if (fwrite($fh, $content)) {
              $this->output[] = $transFile . ' written.' . "\n";
            } else {
              $this->output[] = 'Could not write to tansaction file.';
            }
          } else {
            $this->output[] = 'Could not create tansaction file.';
          }
        }
      } else {
        $this->output[] = 'No content type available.  Cannot download transaction file.';
      }
    } else {
      $this->initialize('getTransactionFile');
    }
  }

  /**
   * logout()
   * Destroys all cookies
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @return      string
   */ 
  public function logout() {
    if($this->debug) {
      copy($this->cookieJar,$this->cookieJar.date('YmdHis'));
    }
    @unlink($this->cookieJar);
  }

  /**
   * getAnswer()
   *
   * @todo        This is a shell function right now.
   * 
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       string  $question The question
   *
   * @return      string
   */ 
  public function getAnswer($question) {
    return null;
  }

  /**
   * getPostedTransactions()
   * Get all posted transactions in an OFX file
   *
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       mixed  $acct  The account indentifier as a string or int
   * @param       date   $start Earliest transaction date to fetch
   * @param       date   $end   Lastest transaction date to fetch
   *
   * @return      array
   */ 
  public function getPostedTransactions($acct, $start = null, $end = null) {
  $this->acctName = $acct;
    if (! $this->initialized) {
    $this->initialize('getPostedTransactions(\'' . $acct . '\',\''.$start.'\',\''.$end.'\');');
  } else {
    $ofx = $this->getOFXFile($acct, $start, $end);
    $parser = new OFX102Parser();
    $ofxArray = $parser->initWithString($ofx);
    $transactions = $ofxArray['OFX']['BANKMSGSRSV1']['STMTTRNRS']['STMTRS']['BANKTRANLIST'];
    for($x = 0; $x < count($transactions); $x++) {
    	if (floatval($transactions[$x]['STMTTRN']['TRNAMT']) != 0.00) {
        $trans[$x]['id'] = $x + 1;
        $trans[$x]['account_id'] = $this->acctId;
        $trans[$x]['raw_subject'] = trim($transactions[$x]['STMTTRN']['MEMO']).' / '.trim($transactions[$x]['STMTTRN']['NAME']);
        $a = trim(str_ireplace(array('/','POS','Pre-auth','PURCHASE'), '', trim($transactions[$x]['STMTTRN']['MEMO']).' / '.trim($transactions[$x]['STMTTRN']['NAME'])));
        $b = ucwords(strtolower($a));
        $c = str_ireplace(array('Eft Trans 1'), '', $b);
        $trans[$x]['key_hash'] = md5($transactions[$x]['STMTTRN']['FITID'] . @$transactions[$x]['MEMO']);
        $trans[$x]['subject'] = $c;
        $trans[$x]['amount'] = $transactions[$x]['STMTTRN']['TRNAMT'];
        $trans[$x]['trans_date'] = date('Y-m-d H:i:s', strtotime(substr($transactions[$x]['STMTTRN']['DTPOSTED'], 0, - 14)));
        $trans[$x]['dl_date'] = time();
        $trans[$x]['cleared'] = 1;
        $trans[$x]['count'] = 1;
      }
    }
    $this->trans = $trans;
    
    $this->logout();
    return false;
    }
  }

  /**
   * getPostedTransactions()
   * Get all pending transactions in an OFX file
   *
   * @todo        This is a shell function right now.
   *
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       mixed  $acct  The account indentifier as a string or int
   * @param       date   $start Earliest transaction date to fetch
   * @param       date   $end   Lastest transaction date to fetch
   *
   * @return      array
   */ 
  public function getPendingTransactions($acct, $start = null, $end = null) {
    //Shell function to satisfy interface.  More functionality to come.
    return null;
  }

  /**
   * getTransactionOccurrences()
   * Gets the number of occurences of a discovered transaction
   *
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       array  $trans  Array of transactions
   * @param       date   $hash   A hashed representation of a transaction
   *
   * @return      int
   */ 
  private function getTransactionOccurrences($trans, $hash) {
  	$count = 0;
    for($x = 0; $x < count($trans); $x++) {
      if ($trans[$x]['key_hash'] == $hash) {
        $count++;
      }
    }
    return $count;
  }

  /**
   * loadAccountInfo()
   * Loads the overall account details for a given account
   *
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       string  $acctName  Name of account
   * @param       string  $onComplete Name of function to call on completion 
   *
   * @return      void
   */ 
  private function loadAccountInfo($acctName, $onComplete = null) {

    $ch1 = curl_init("https://www.bancfirstonline.com/onlineserv/HB/Summary.cgi?primaryButton=ACCOUNT_ACCESS&secondaryButton=ACCOUNT_SUMMARY");
    curl_setopt($ch1, CURLOPT_HEADER, 1);
    curl_setopt($ch1, CURLOPT_REFERER, 'https://www.bancfirstonline.com/onlineserv/HB/Summary.cgi?primaryButton=ACCOUNT_ACCESS&secondaryButton=ACCOUNT_SUMMARY');
    curl_setopt($ch1, CURLOPT_COOKIEJAR, $this->cookieJar);
    curl_setopt($ch1, CURLOPT_COOKIE, 'AIBOnlineSurvey=TRUE');
    curl_setopt($ch1, CURLOPT_COOKIEFILE, $this->cookieJar);
    curl_setopt($ch1, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; tr-TR; rv:1.7.6) Gecko/20050321 Firefox/1.0.2');
    curl_setopt($ch1, CURLOPT_GET, 1);
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
    if ($this->verbose) {
      curl_setopt($ch1, CURLOPT_VERBOSE, 1);
    }
    curl_setopt($ch1, CURLOPT_FOLLOWLOCATION, 1);
    $output1 = curl_exec($ch1);
    curl_close($ch1);
    $needle = array("\r\n","\n");
    $x = str_replace($needle, '', $output1);
    $pattern = str_replace($needle, '', '/[\s]*<tr>[\s]*<td class="acct_nickname">[\s]*<A href="(SingleSignon\.cgi\?tpvRef=TPV_SDP&AUTO_LOAD=TRUE&accountRef=([a-z0-9]*)&nhp=TRUE)" onclick="parent\.updatenav\(\'ACCOUNT_ACCESS\',\'ACCOUNT_HISTORY\',\'ACCOUNT_ACCESS\',\'ACCOUNT_SUMMARY\'\); return openNHP\(\'[a-z0-9]*\'\);" onMouseOver=\'return displayAccountStatus\("[a-z0-9]*"\);\' onMouseOut="return clearStatus\(\);" class="text-link">([\s&;a-z0-9]*)<\/a>[\s]*<\/td>[\s]*<td align="right"><span class="text">([a-z0-9]*)<\/span>&nbsp;<\/td>[\s]*<td align="right"><span class="text">[a-z0-9]*<\/span>&nbsp;<\/td>[\s]*<td align="right"><span class="text">([0-9\.,]*)<\/span>&nbsp;<\/td>[\s]*<td align="right"><span class="text">([\.0-9,]*)<\/span>&nbsp;<\/td>[\s]*<td align="right">&nbsp;&nbsp;<A href="SingleSignon.cgi\?tpvRef=TPV_SDP&AUTO_LOAD=TRUE&accountRef=[a-z0-9]*&nhp=TRUE" onclick="parent\.updatenav\(\'ACCOUNT_ACCESS\',\'ACCOUNT_HISTORY\',\'ACCOUNT_ACCESS\',\'ACCOUNT_SUMMARY\'\); return openNHP\(\'[a-z0-9]*\'\);" onMouseOver=\'return displayAccountStatus\("[a-z0-9]*"\);\' onMouseOut="return clearStatus\(\);" class="text-link-small">View Recent Transactions<\/a><\/td>[\s]*<\/tr>/i');
    
    try {
        preg_match_all($pattern, $x, $content);
    } catch(Exception $e) {
        echo $e->getMessage();
        echo preg_last_error();
    }
    for($x = 0; $x < count($content[3]); $x++) {
      $acct[str_replace('&nbsp;', ' ', $content[3][$x])]['no'] = $content[4][$x];
    }
    for($x = 0; $x < count($content[6]); $x++) {
      $acct[str_replace('&nbsp;', ' ', $content[3][$x])]['cbalance'] = $content[5][$x];
    }
    for($x = 0; $x < count($content[7]); $x++) {
      $acct[str_replace('&nbsp;', ' ', $content[3][$x])]['abalance'] = $content[6][$x];
    }
    for($x = 0; $x < count($content[1]); $x++) {
      $acct[str_replace('&nbsp;', ' ', $content[3][$x])]['detailslink'] = $content[1][$x];
    }
    for($x = 0; $x < count($content[2]); $x++) {
      $acct[str_replace('&nbsp;', ' ', $content[3][$x])]['urlref'] = $content[2][$x];
    }
    if ($this->debug) {
      $myFile = $this->debugLog;
      $fh = fopen($myFile, 'a') or die("can't open file");
      fwrite($fh, "loadAccountInfo\n" .$output1."\n". print_r($content,1) . "\n=====\n");  
      fclose($fh);
    }
    $this->acctURLRef = $acct[$this->acctName]['urlref'];
    $this->acctDetailsLink = $acct[$this->acctName]['detailslink'];
    $this->acctABalance = $acct[$this->acctName]['abalance'];
    $this->acctCBalance = $acct[$this->acctName]['cbalance'];
    $this->acctNo = $acct[$this->acctName]['no'];
    $this->initialized = true;
    if ($onComplete) {
      eval('$this->' . $onComplete);
    }
  }

  /**
   * loadExport()
   * Loads the the export options page
   *
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       string  $onComplete Name of function to call on completion 
   *
   * @return      void
   */ 
  public function loadExport($onComplete = null) {
    if ($this->initialized) {
      $ch1 = curl_init("https://www.bancfirstonline.com/onlineserv/HB/Summary.cgi?state=export&primaryButton=ACCOUNT_ACCESS&secondaryButton=EXPORT");
      curl_setopt($ch1, CURLOPT_HEADER, 1);
      curl_setopt($ch1, CURLOPT_REFERER, 'https://www.bancfirstonline.com/onlineserv/HB/Summary.cgi?primaryButton=ACCOUNT_ACCESS');
      curl_setopt($ch1, CURLOPT_COOKIEJAR, $this->cookieJar);
      curl_setopt($ch1, CURLOPT_COOKIE, 'AIBOnlineSurvey=TRUE');
      curl_setopt($ch1, CURLOPT_COOKIEFILE, $this->cookieJar);
      curl_setopt($ch1, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; tr-TR; rv:1.7.6) Gecko/20050321 Firefox/1.0.2');
      curl_setopt($ch1, CURLOPT_POST, 1);
      curl_setopt($ch1, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch1, CURLOPT_FOLLOWLOCATION, 1);
      $output1 = curl_exec($ch1);
      curl_close($ch1);
      $this->exportLoaded = true;
      if ($onComplete) {
        eval('$this->' . $onComplete . '(\'' . $this->acctName . '\');');
      }
    } else {
      $this->initialize('loadExport(\'' . $onComplete . '\');');
    }
  }

  /**
   * getOFXFile()
   * Grabs the OFX file from the export page
   *
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       mixed  $acct  The account indentifier as a string or int
   * @param       date   $start Earliest transaction date to fetch
   * @param       date   $end   Lastest transaction date to fetch
   *
   * @return      string
   */ 
  private function getOFXFile($accountName, $start = null, $end = null) {
    $this->acctName = $accountName;
    if ($this->initialized) {
      $ch1 = curl_init("https://www.bancfirstonline.com/onlineserv/HB/Money.ofx");
      $data = array('startDate' => '03%2F01%2F2011+00%3A00%3A00','endDate' => '03%2F31%2F2011+00%3A00%3A00','state' => 'export','source' => 'export','foreignDates' => 'FALSE','flaggedExport' => 'FALSE','typeList' => 'x','ref' => '.','typeList' => 'OFX','ref' => $this->acctURLRef,'nextStartMonth' => '07','nextStartDay' => '26','nextStartYear' => '2008','nextEndMonth' => '08','nextEndDay' => '05','nextEndYear' => '2008');
      $data = 'ref='.$this->acctURLRef.'&nextStartMonth='.date('m',$start).'&nextStartDay='.date('d',$start).'&nextStartYear='.date('Y',$start).'&nextEndMonth='.date('m',$end).'&nextEndDay='.date('d',$end).'&nextEndYear='.date('Y',$end).'&startDate='.date('m',$start).'%2F'.date('d',$start).'%2F'.date('Y',$start).'+00%3A00%3A00&endDate='.date('m',$end).'%2F'.date('d',$end).'%2F'.date('Y',$end).'+00%3A00%3A00&state=export&source=export&foreignDates=FALSE&type=OFX&flaggedExport=FALSE&typeList=OFX';
      curl_setopt($ch1, CURLOPT_REFERER, 'https://www.bancfirstonline.com/onlineserv/HB/Summary.cgi?state=export&primaryButton=ACCOUNT_ACCESS&secondaryButton=EXPORT');
      curl_setopt($ch1, CURLOPT_COOKIEJAR, $this->cookieJar);
      curl_setopt($ch1, CURLOPT_COOKIE, 'AIBOnlineSurvey=TRUE');
      curl_setopt($ch1, CURLOPT_COOKIE, '2xClick=');
      curl_setopt($ch1, CURLOPT_COOKIEFILE, $this->cookieJar);
      curl_setopt($ch1, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; tr-TR; rv:1.7.6) Gecko/20050321 Firefox/1.0.2');
      curl_setopt($ch1, CURLOPT_POST, 1);
      curl_setopt($ch1, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
      $output1 = curl_exec($ch1);
      curl_close($ch1);
      return $output1;
    } else {
      $this->initialize('getOFXFile(\'' . $accountName . '\');');
    }
  }

  /**
   * writeOutput()
   * Writes output to a file.  Good for writing out debug info for review.
   *
   * @package     V8 Balances
   * @author      Kevin L. Dayton  
   *
   * @param       string  $file Name of the file
   * @param       string  $data Data to write to file
   *
   * @return      void
   */ 
  private function writeOutput($file, $data) {
    $fh = fopen($file, 'w') or die("can't open file");
    fwrite($fh, $data);
    fclose($fh);
  }
}
