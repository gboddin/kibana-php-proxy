<?php

/* BEGIN CONFIG */

$config = array(
  'es-server' => '127.0.0.1',
  'es-port' => 9200,
  'groups' => array()
);

//give admin access to management + all dashboards :
$config['groups']['admin'] = array('admin');

// give user1 access to your_log_group1 ES index for user1
$config['groups']['your_log_group1'] = array('user1');
$config['groups']['your_log_group2'] = array('user3','user4');
$config['groups']['your_log_group3'] = array('user4','user1');

/* END CONFIG */

if(!isset($_SERVER['PATH_INFO'])) 
  http_die('no PATH_INFO from PHP',503);
if(!isset($_SERVER['REQUEST_METHOD']))
  http_die('no REQUEST_METHOD from PHP',503);
if(!isset($_SERVER['PHP_AUTH_USER']))
  http_die('Auth required',401);

// Some info from PHP
list($path,$method,$user) = array($_SERVER['PATH_INFO'],$_SERVER['REQUEST_METHOD'],$_SERVER['PHP_AUTH_USER']);
$queryParts = explode('/',$path);

//if admin, proxy the request :
if(in_array($user,$config['groups']['admin']))
  proxy_request();

//if we try to read from the kibana-int index, let it read :)
if($method == 'GET' && $queryParts[1] == 'kibana-int')
  proxy_request();

//Allow getting info from the ES instance
if($method == 'GET' && count($queryParts) < 3 && in_array($queryParts[1],array('_nodes')))
  proxy_request();

// action is the last part of the uri
$action = array_pop($queryParts);

if(
  (
    // allow GET AND POST on _search (if allowed index)
    ($method =='POST'||$method == 'GET') &&
    in_array($action,array('_search'))
  ) 
    || //or
  (
    // allow GET on _aliases AND _mapping (if allowed index)
    $method== 'GET' &&
    in_array($action,array('_aliases','_mapping'))
  )
) {
  //allow getting kibana config and dashboard list
  if($queryParts[1] == 'kibana-int') proxy_request();
  $indexes = explode(',',$queryParts[1]);
  //check access for all indexes
  foreach($indexes as $index) {
    preg_match('/([a-z-]+)\-([0-9]{4})\.([0-9]{2})\.([0-9]{2})/',$index,$matches);
    // if trying to access a non logstash index, drop it
    if(count($matches) != 5)
      http_die('Access denied !',401);
    list($full,$index,$year,$month,$day) = $matches;
    // if user is not allowed, drop it
    if(!in_array($user,$config['groups'][$index]))
      http_die('Access to '.$index.' forbidden',401);
  }
  proxy_request();
}

http_die('Error while granting access',401);

// END
// global functions :

function http_die($message,$code) {

  header($_SERVER['SERVER_PROTOCOL']." $code $message");
  // We're trhowing the authbox to the browser if anything is not permitted giving the chance to switch account :
  // WARNING , there's no password check whatsover in this script, we trust the Webserver for the authentication part (LDAP,htacces,mysql, it's up to you)

  if($code == 401) header('WWW-Authenticate: Basic realm="LDAP Access to your logging dashboard"');
  die($message);
}
function proxy_request() {
    global $config;
    list($path,$method,$user) = array($_SERVER['PATH_INFO'],$_SERVER['REQUEST_METHOD'],$_SERVER['PHP_AUTH_USER']);
    $body = file_get_contents("php://input");
    $url = 'http://'.$config['es-server'].':'.$config['es-port'].$path;
    if(!empty($_SERVER['QUERY_STRING'])) 
      $url = $url.'?'.$_SERVER['QUERY_STRING'];
    $url = str_replace(' ','+',$url);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    if(strlen($body)) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $body); 
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
        'Content-Type: application/json',                                                                                
        'Content-Length: ' . strlen($body))                                                                       
      );                                                                                                                   
    }
    $data = trim(curl_exec($ch));
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Length: '.strlen($data));
    die($data);
    
}
