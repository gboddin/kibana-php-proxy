DEPRECATED
==========

Kibana 4 can now be used with Shield (https://www.elastic.co/products/shield) which is a more robust solution allowing LDAP integration for instance.

kibana-php-proxy
================

A PHP proxy working with path info to allow fine group filtering

  - Filter your authenticated users to only get their logstash index(es)
  - Works with Logstash [index_name-[YYYY.MM.DD]] indexes patterns
 
kibana-php-proxy assumes you're using one Elasticsearch index per log group (customer,website,application,...), eg :

  - loggroup1 *(so loggroup1-2014.10.23, loggroup1-2014.10.24, loggroup1-2014.10.25)*
  - loggroup2 *(so loggroup2-2014.10.23, loggroup2-2014.10.24, loggroup2-2014.10.25)*

and let you assign those index to users.

**WARNING**: You HAVE TO configure your webserver for authentication and receive $_SERVER['PHP_AUTH_USER'] from PHP

kibana-proxy.php config: 
--------------


```php
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
```
kibana/config.js config:
---------------------

```js
    elasticsearch: "//"+window.location.hostname+"/kibana-proxy.php",
```

