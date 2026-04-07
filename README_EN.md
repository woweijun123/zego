# Installation & Usage

## Installation
Composer Autoload, which uses the PSR-4 autoloading mechanism provided by Composer.
1. Copy the package to a directory under the project root, for example, `/my_project/zego`, where `/my_project/` is the project root directory.
2. Add the `psr-4` autoloading configuration in `/my_project/composer.json`.
   ```json
   {
     ...
     "autoload": {
       "psr-4": {
         "ZEGO\\": "zego/src/ZEGO"
       }
     }
     ...
   }
   ```
3. Run the command `composer dump-autoload` or `composer dump-autoload -o` (for production) or `composer update` to generate the autoloader files.

## Usage
### General Token Generation Demo
- Use in the `/my_project/xxx.php` file.
- The general token is used for simple authorization of service interfaces, and the payload field can be empty.
  ```php
  require 'vendor/autoload.php';
  use ZEGO\ZegoServerAssistant;
  use ZEGO\ZegoErrorCodes;

  $appId = 1;
  $userId = 'demo';
  $secret = 'fa94dd0f974cf2e293728a526b028271';
  $payload = '';
  $token = ZegoServerAssistant::generateToken04($appId,$userId,$secret,3600,$payload);
  if( $token->code == ZegoErrorCodes::success ){
    print_r($token->token);
  }
  ```

### Strict Verification Token Generation Demo
- Use in the `/my_project/xxx.php` file.
- The strict verification token is used in scenarios where strong authentication is required for room login/streaming permissions, and the payload field must be generated according to the specifications.
  ```php
  require 'vendor/autoload.php';
  use ZEGO\ZegoServerAssistant;
  use ZEGO\ZegoErrorCodes;

  // Permission key definitions
  const PrivilegeKeyLogin   = 1; // Login
  const PrivilegeKeyPublish = 2; // Streaming

  // Permission switch definitions
  const PrivilegeEnable     = 1; // Enable
  const PrivilegeDisable    = 0; // Disable

  $appId = 1;
  $userId = 'demo';
  $roomId = "demo";
  $secret = 'fa94dd0f974cf2e293728a526b028271';
  $rtcRoomPayLoad = [
      'room_id' => $roomId, // Room ID; used for strong validation of the interface's room ID
      'privilege' => [     // List of permission switches; used for strong validation of the interface's operation permissions
          PrivilegeKeyLogin => PrivilegeEnable,
          PrivilegeKeyPublish => PrivilegeDisable,
      ],
      'stream_id_list' => [] // Stream list; used for strong validation of the interface's stream IDs; can be empty, if empty, no validation of stream IDs
  ];

  $payload = json_encode($rtcRoomPayLoad);

  $token = ZegoServerAssistant::generateToken04($appId, $userId, $secret, 3600, $payload);
  if( $token->code == ZegoErrorCodes::success ){
    print_r($token);
  }
  ```

### Error Code Explanation
  ```php
  namespace ZEGO;

  class ZegoErrorCodes{
      const success                       = 0;  // Successfully obtained the authentication token
      const appIDInvalid                  = 1;  // Incorrect appID parameter when calling the method
      const userIDInvalid                 = 3;  // Incorrect userID parameter when calling the method
      const secretInvalid                 = 5;  // Incorrect secret parameter when calling the method
      const effectiveTimeInSecondsInvalid = 6;  // Incorrect effectiveTimeInSeconds parameter when calling the method
  }
  ```

### Description of generateToken04 Parameters and Return Values
  ```php
      /**
       * Generate an authentication token for communication with the Zego server based on the provided parameter list.
       *
       * @param integer $appId The numeric ID issued by Zego, a unique identifier for each developer
       * @param string $userId User ID
       * @param string $secret The secret key corresponding to the appId provided by Zego, please keep it safe and do not disclose it
       * @param integer $effectiveTimeInSeconds The validity period of the token, in seconds
       * @param string $payload Business extension field, JSON string
       * @return ZegoAssistantToken Returns the token content, the value is a ZEGO\ZegoAssistantToken object. Before using, please check if the code property of the object is ZEGO\ZegoErrorCodes::success. The actual token content is stored in the token property.
       */
      public static function generateToken04(int $appId, string $userId, string $secret, int $effectiveTimeInSeconds, string $payload)
  ```

The return value is a `ZEGO\ZegoAssistantToken` object:
  ```php
  namespace ZEGO;

  class ZegoAssistantToken{
      public $code;
      public $message = '';
      public $token;
  }
  ```
