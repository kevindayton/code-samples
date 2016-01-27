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
 * apnsDevice.php
 * Object model for an Apple Push Notification-capable device
 * 
 * @package     V8 Balances
 * @license     Private
 * @author      Kevin L. Dayton  
 * @copyright   2010 Volatile Eight Industries/Dayton Interactive/Kevin L. Dayton, All rights reserved.
 */
 
require_once(V8E_PATH.'/V8Object.php');

/**
 * ApnsDevice
 * Object model for an Apple Push Notification-capable device
 * 
 * @package     V8 Balances
 * @author      Kevin L. Dayton  
 *
 * @property int      $id
 * @property string   $deviceName
 * @property string   $deviceToken
 * @property int      $deviceOwner
 * @property string   $model
 * @property decimal  $osVersion
 * @property decimal  $mobileBalancesVersion
 * @property int      $alertTypeMask
 * @property string   $uuid
 * @property int      $userID
 *
 * @const UIRemoteNotificationTypeNone;
 * @const UIRemoteNotificationTypeBadge;
 * @const UIRemoteNotificationTypeSound;
 * @const UIRemoteNotificationTypeAlert;
 * 
 * @method  void    __construct()
 * @method  void    initWithDeviceToken()
 * @method  bool    save()
 * @method  array   getAlertTypes()
 */
class ApnsDevice extends V8Object {
  public $id;
  public $deviceName;
  public $deviceToken;
  public $deviceOwner;
  public $model;
  public $osVersion;
  public $mobileBalancesVersion;
  public $alertTypeMask;
  public $uuid;
  public $userID;
  
  const UIRemoteNotificationTypeNone = 0;
  const UIRemoteNotificationTypeBadge = 1;
  const UIRemoteNotificationTypeSound = 2;
  const UIRemoteNotificationTypeAlert = 4;

  /**
   * __construct()
   *
   * @author  Kevin L. Dayton  
   *
   * @param   V8Application  $app  The V8Application object with which this class is to be instantiated.
   * 
   * @return  void
   */
  public function __construct(V8Application $app = null) {
    if ($app) {
      parent::__construct($app);
      $this->app = $app;
      $this->db = $app->db;
      $this->userID = $this->app->user->id;
    }
  }

  /**
   * initWithDeviceToken()
   * Initializes the values of an instantiated ApnsDevice class
   *
   * @author  Kevin L. Dayton  
   *
   * @param   string  $token  String representing a software token sent by the device
   * 
   * @return  void, throws exception on error
   */
  public function initWithDeviceToken($token) {
    $this->deviceToken = $token;
    $stmt = $this->db->prepare('SELECT device_id, uuid,device_name, device_token, device_owner, model, os_version, mobile_balances_version, alert_type_mask 
								FROM apns_devices WHERE device_token = :deviceToken AND device_owner = :user;');
    $stmt->bindParam(':deviceToken', $this->deviceToken);
    $stmt->bindParam(':user', $this->userID);
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row) {
      $this->id = $row['device_id'];
      $this->uuid = $row['uuid'];
      $this->deviceName = $row['device_name'];
      $this->deviceOwner = $row['device_owner'];
      $this->model = $row['model'];
      $this->osVersion = $row['os_version'];
      $this->mobileBalancesVersion = $row['mobile_balances_version'];
      $this->alertTypeMask = $row['alert_type_mask'];
    } else {
    	$e = $stmt->errorInfo();
    	throw new Exception($e[2]);
    	die();
    }
  }

  /**
   * save()
   * Saves changes to the instantiated ApnsDevice class
   *
   * @author  Kevin L. Dayton  
   * 
   * @return  bool, throws exception on error
   */
  public function save() {
    if (!$this->id) { //New mode
      $sql = 'INSERT INTO apns_devices (device_id,uuid,device_name, device_token, device_owner, model, os_version, mobile_balances_version, alert_type_mask) 
				VALUES (:device_id,uuid(),:device_name,:device_token,:device_owner,:model,:os_version,:mobile_balances_version,:alert_type_mask);';
      $id = $this->app->db->lastInsertId();
      $stmt = $this->db->prepare($sql);
      $stmt->bindParam(':device_id',$id);
      $this->id = $id;
    } else { //Edit mode
      $sql = 'UPDATE apns_devices 
				SET device_name = :device_name , device_token = :device_token, device_owner = :device_owner, model = :model, os_version = :os_version, 
					mobile_balances_version = :mobile_balances_version, alert_type_mask = :alert_type_mask 
				WHERE  device_id = :device_id;';
      $stmt = $this->db->prepare($sql);
      $stmt->bindParam(':device_id',$this->id);
    }
    $stmt->bindParam(':device_name',$this->deviceName);
    $stmt->bindParam(':device_token',$this->deviceToken); 
    $stmt->bindParam(':device_owner',$this->deviceOwner); 
    $stmt->bindParam(':model',$this->model); 
    $stmt->bindParam(':os_version',$this->osVersion); 
    $stmt->bindParam(':mobile_balances_version',$this->mobileBalancesVersion); 
    $stmt->bindParam(':alert_type_mask',$this->alertTypeMask); 
    
    $result = $stmt->execute();
      if ($result) {
        //
      } else {
      	$e = $stmt->errorInfo();
      	throw new Exception($e[2]);
      	die();
      }
      return $result;
  }

  /**
   * getAlertTypes()
   * Returns alert types based on a alert type mask
   *
   * @author  Kevin L. Dayton  
   * 
   * @return  array
   */    
  public function getAlertTypes() {
    $alertTypes = array();
    if ($this->alertTypeMask) {
      switch ($this->alertTypeMask) {
        case 1 :
          $alertTypes[] = 'UIRemoteNotificationTypeBadge';
          break;
        case 2 :
          $alertTypes[] = 'UIRemoteNotificationTypeSound';
          break;
        case 3 :
          $alertTypes[] = 'UIRemoteNotificationTypeBadge';
          $alertTypes[] = 'UIRemoteNotificationTypeSound';
          break;
        case 4 :
          $alertTypes[] = 'UIRemoteNotificationTypeAlert';
          break;
        case 5 :
          $alertTypes[] = 'UIRemoteNotificationTypeBadge';
          $alertTypes[] = 'UIRemoteNotificationTypeAlert';
          break;
        case 6 :
          $alertTypes[] = 'UIRemoteNotificationTypeSound';
          $alertTypes[] = 'UIRemoteNotificationTypeAlert';
          break;
        case 7 :
          $alertTypes[] = 'UIRemoteNotificationTypeBadge';
          $alertTypes[] = 'UIRemoteNotificationTypeSound';
          $alertTypes[] = 'UIRemoteNotificationTypeAlert';
          break;
        default :
          $alertTypes[] = 'UIRemoteNotificationTypeNone';
          break;
      }
    }
    return $alertTypes;
  }
}
