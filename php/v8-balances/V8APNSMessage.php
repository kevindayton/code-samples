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
 * V8APNSMessage.php
 * This file contains a class for building and sending an Apple Push Notification Service Message
 * 
 * @package     V8Engine
 * @subpackage  Messaging
 * @license     Private
 * @author      Kevin L. Dayton  
 * @copyright   2010 Volatile Eight Industries/Dayton Interactive/Kevin L. Dayton, All rights reserved.
 */
 
/**
 * V8APNSMessage
 * A class for building and sending an Apple Push Notification Service Message
 * 
 * @package     V8Engine
 * @subpackage  Messaging
 * @author      Kevin L. Dayton  
 *
 * @property  string      $message  The message you want to send
 * @property  ApnsDevice  $device   The device to which you want to send the message 
 * @property  bool        $sound    Do you want to play a sound with the message?
 * @property  int         $badge    Do you want to place a number badge on the icon?
 *
 * @method  void  __construct()
 * @method  void  send()
 */
class V8APNSMessage {
  public $message;
  public $device;
  public $sound;
  public $badge;
  
  
  /**
   * __construct()
   *
   * @author      Kevin L. Dayton  
   *
   * @param  string      $message  The message you want to send
   * @param  ApnsDevice  $device   The device to which you want to send the message 
   * @param  bool        $sound    Do you want to play a sound with the message?
   * @param  int         $badge    Do you want to place a number badge on the icon?
   * 
   * @return void
   */
  public function __construct($device, $message, $sound = null, $badge = null) {
    $deviceAlertTypes = $device->getAlertTypes();
    $this->deviceToken = $device->deviceToken;
    if ($deviceAlertTypes) {
      $this->message = (in_array('UIRemoteNotificationTypeAlert', $deviceAlertTypes)) ? $message : null;
      $this->sound = (in_array('UIRemoteNotificationTypeSound', $deviceAlertTypes)) ? $sound : false;
      $this->badge = (in_array('UIRemoteNotificationTypeBadge', $deviceAlertTypes)) ? (int)$badge : null;
    }
  }
  
  /**
   * send()
   * Compiles a message with its metadata and sends it
   * Requires a valid SSL certificate from the Apple Developer portal ({@link http://developer.apple.com})
   *
   * @todo  Read the server response and return it
   *
   * @author      Kevin L. Dayton  
   *
   * @return void
   */
  public function send() {
    if ($this->message) {
      $payload['aps']['alert'] = $this->message;
    }
    
    if ($this->sound) {
      $payload['aps']['sound'] = $this->sound;
    }
    
    if ($this->badge) {
      $payload['aps']['badge'] = $this->badge;
    }

    if($payload) {
      $payload = json_encode($payload);
      
      // Prod Settings
      $apnsHost = 'gateway.push.apple.com';
      $apnsCert = '/path/to/cert';
      // Dev Settings
      // $apnsHost = 'gateway.sandbox.push.apple.com';
      // $apnsCert = '/path/to/cert';
      $apnsPort = 2195;
      
      $apnsMessage = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $this->deviceToken)) . chr(0) . chr(strlen($payload)) . $payload;
      
      $streamContext = stream_context_create();
      stream_context_set_option($streamContext, 'ssl', 'local_cert', $apnsCert);
      
      $apns = stream_socket_client('ssl://' . $apnsHost . ':' . $apnsPort, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);
      
      fwrite($apns, $apnsMessage);
      fclose($apns);
    }
  }
}
