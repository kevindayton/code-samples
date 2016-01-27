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
 * apnsDeviceController.php
 * This file contains a class for controlling the fetching and writing the details of an
 * Apple Push Notification-capable device
 * 
 * @package     V8 Balances
 * @license     Private
 * @author      Kevin L. Dayton  
 * @copyright   2010 Volatile Eight Industries/Dayton Interactive/Kevin L. Dayton, All rights reserved.
 */
 
require_once ('framework/V8Response.php');
require_once ('framework/V8Controller.php');
require_once (COMMON_PATH.'/models/apnsDevice.class.php');

/**
 * ApnsDeviceController
 * A class for building and sending an Apple Push Notification Service Message
 * 
 * @package     V8 Balances
 * @author      Kevin L. Dayton  
 *
 * @property  ApnsDevice  $device   The device to which you want to send the message 
 *
 * @method  void        __construct()
 * @method  ApnsDevice  processJSONRequest()
 */
class ApnsDeviceController extends V8Controller {
  public $device;
  
  /**
   * __construct()
   *
   * @author      Kevin L. Dayton  
   *
   * @param  V8Application      $app The V8Application object with which this class is to be instantiated.
   * 
   * @return void
   */
  public function __construct($app) {
    parent::__construct($app);
    /** 
     * Comment the following 3 lines to disable OAuth Security
     */
    if (! $this->app->verifySignature()) {
      $r = new V8Response('Access to this resource requires a signed request.', '401', $this->app->requestType);
      $r->sendResponse();
    }

    if ($app->args[1]) {
      $this->device = new APNSDevice($app);
      $this->device->initWithDeviceToken($app->args[1]);
    }
    
    if ($this->app->putData) {
      if ($this->app->requestType == 'json') {
        $this->device = $this->processJSONRequest(urldecode($this->app->putData[0]));
        try {
          $this->device->save();
        } catch ( Exception $e ) {
          $r = new V8Response(array('Error' => $e->getMessage()), '500', $this->app->requestType);
          $r->sendResponse();
        }
      } else {
        throw new Exception('Only requests of type \'application/json\' are accepted.');
      }
    } else {
      throw new Exception('No request data to process.');
    }
    $this->device->clean();
    $r = new V8Response(array('APNSDevice' => $this->device), '200', $this->app->requestType);
    $r->sendResponse();
  }

  /**
   * processJSONRequest()
   * Takes a JSON request sent from a device and adds a new devices or
   * updates the details of an existing device.
   *
   * @author  Kevin L. Dayton  
   *
   * @param string $request JSON data from the request
   * 
   * @return ApnsDevice
   */
  private function processJSONRequest($request) {
    $device = null;
    $jdDevice = json_decode($request)->request->APNSDevice;
    if($jdDevice != false) {
      if (!$this->device) {
        $device = new APNSDevice($this->app);
      } else {
        $device = $this->device;
      }
      
      $device->deviceName = $jdDevice->name;
      $device->deviceToken = $jdDevice->token;
      $device->deviceOwner = $jdDevice->owner;
      $device->model = $jdDevice->model;
      $device->osVersion = $jdDevice->OSVersion;
      $device->mobileBalancesVersion = $jdDevice->MobileBalancesVersion;
      $device->alertTypeMask = $jdDevice->AlertTypeMask;
    }
    return $device;
  }

}
