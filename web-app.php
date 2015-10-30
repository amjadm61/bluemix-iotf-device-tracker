<?php
// include class
require('phpMQTT.php');

// set configuration values
$config = array(
  'org_id' => 'IOTF-ORG-ID',
  'port' => '1883',
  'app_id' => 'phpmqtt',
  'iotf_api_key' => 'IOTF-API-KEY',
  'iotf_api_secret' => 'IOTF-API-TOKEN',
  'maps_api_key' => 'GOOGLE-API-KEY',
  'device_id' => 'DEVICE-_ID'
);

$config['server'] = $config['org_id'] . '.messaging.internetofthings.ibmcloud.com';
$config['client_id'] = 'a:' . $config['org_id'] . ':' . $config['app_id'];
$location = array();

// initialize client
$mqtt = new phpMQTT($config['server'], $config['port'], $config['client_id']); 
$mqtt->debug = false;

// connect to broker
if(!$mqtt->connect(true, null, $config['iotf_api_key'], $config['iotf_api_secret'])){
  echo 'ERROR: Could not connect to IoT cloud';
	exit();
} 

// subscribe to topics
$topics['iot-2/type/+/id/' . $config['device_id'] . '/evt/accel/fmt/json'] = 
  array('qos' => 0, 'function' => 'getLocation');
$mqtt->subscribe($topics, 0);

// process messages
$elapsedSeconds = 0;
while ($mqtt->proc(true)) { 
  if (count($location) == 2) {
    $latitude = $location[0];
    $longitude = $location[1];
    $mapsApiUrl = 'https://maps.googleapis.com/maps/api/staticmap?key=' . $config['maps_api_key'] . '&size=640x480&maptype=roadmap&scale=2&markers=color:green|' . sprintf('%f,%f', $latitude, $longitude);
    break;
  } 
  
  if ($elapsedSeconds == 5) {
    break;  
  }
  
  sleep(1);
  $elapsedSeconds++;
}

// disconnect
$mqtt->close();

function getLocation($topic, $msg) {
  global $location;
  $json = json_decode($msg);
  $location = array($json->d->lat, $json->d->lon);
  return $location;
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Display Location</title>
    <style>
    html, #content, #map {
      height: 100%;
    }
    #footer {
      text-align: center;
    }
    </style>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap-theme.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>    
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <meta http-equiv="refresh" content="10">
  </head>
  <body>
    <div id="content">
    
      <div class="panel panel-default">
        <div class="panel-heading">Location of device <strong><?php echo $config['device_id']; ?></strong></div>
        <div class="panel-body">
          <?php if (isset($mapsApiUrl)): ?>
          <img class="img-responsive" id="mapImage" src="<?php echo $mapsApiUrl; ?>" />  
          <?php else: ?>
          No GPS data available.
          <?php endif; ?>
        </div>
      </div>
      
      <div id="footer">
        This page will automatically reload every 10 seconds. <br/>
        <img src="powered-by-google-on-white.png" /> <br />
        <a href="terms.html">Legal Notices</a>
      </div>
    </div>
    
  </body>
</html>
