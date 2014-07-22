<?php
require 'globals.php';
require 'oauth_helper.php';

// Fill in the next 3 variables.
$querynum = 2;
$access_token='A=mHTCzdrYvVl4x6SaVS8W2oJTF.YOwv.Ob6Zqi2wFUz3oljk1Pps3LClAqBNr0SC5vCCz.al7XSma3870cZJx27DtzPpa6z8kJfgrww.pPO8u4P28RzxVLoHY4NK16XFbIMEpnDPo9Sm5dA3AY0_S7gNswij4L50r78yImaQVDmSjav6xIiV51KMxEcCrv7YO40MSv7UDnKYLm67Oc9Dy8m.7pgLdW4JhPjomU28IsU7kPHhZ6dtPUj0I03yD2XHVl5XDpaXaA3cIXw1R0H4G.bNgfbA0m242i0QGUR5rzE8S4S2Qe9KpnMuBopCsFrWIem5bZl7fvJYGk_YmTk9gARoGOwNIDl_T_U.gL_eax3.TYChILMsvYwEx7wDCw3aIWoPyxv7FI407Ey84DsIbmWUazekBNkQ4S2oDqu64WxSdkhLFdST4krdT085g.g4wVUjE0KZoIwXVEbu3n4WcDc.qTY28ImupWa_c4MC6GUWFePbRsifBWbD6agE5BtQvTFLytFQPezGqFs8h6tFBAh6x1fV_gR.8Fdec.pHDxhNBo4F.WlKG6uk32Vq8KaGk9p8KypBaTmsjFymrwmmPsu43D6yOwlRwgrCnlc4IJ9Haj6FH_30zPUYZozQArUigvWWZbej2N5aJQBagAhgkU99kawRETjBMRJlJ0skn_NpiErGHp16GWB4lfDS_KnDD7jBjMX0C_Bzy9d5EA2YRucMV237yZ.7MnioeRFYsPGjXPQfDzvBwTHkNNN5NHlFpMz2EtWC0_OMY4OIAmPIldgrMf4_9tOAoG4yTOUPt7c4.80cJEU2SkPBJHnflz0_IXoAYlw--';
$access_token_secret='69e7cce25555f9588ad89ffa9a3de3be201923cc';

// Call YQL
$retarr = call_yql(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET,
                   $querynum, $access_token, $access_token_secret,
                   false, true);
exit(0);

/**
 * Call the Yahoo Contact API
 * @param string $consumer_key obtained when you registered your app
 * @param string $consumer_secret obtained when you registered your app
 * @param string $guid obtained from getacctok
 * @param string $access_token obtained from getacctok
 * @param string $access_token_secret obtained from getacctok
 * @param bool $usePost use HTTP POST instead of GET
 * @param bool $passOAuthInHeader pass the OAuth credentials in HTTP header
 * @return response string with token or empty array on error
 */
function call_yql($consumer_key, $consumer_secret, $querynum, $access_token, $access_token_secret, $usePost=false, $passOAuthInHeader=true)
{
  $retarr = array();  // return value
  $response = array();

  if ($querynum == 1) {
    $url = 'https://query.yahooapis.com/v1/yql';
    // Show my profile
    $params['q'] = 'select * from social.profile where guid=me';
  } elseif ($querynum == 2) {
    $url = 'https://query.yahooapis.com/v1/yql';
    // Find my friends
    $params['q'] = 'select * from social.connections where owner_guid=me';
  } else {
    // Since this information is public, use the non oauth endpoint 'public'
    $url = 'https://query.yahooapis.com/v1/public/yql';
    // Find all sushi restaurants in SF order by number of ratings desc
    $params['q'] = 'select Title,Address,Rating from local.search where query="sushi" and location="san francisco, ca"|sort(field="Rating.TotalRatings",descending="true")';
  }

  $params['format'] = 'json';
  $params['callback'] = 'cbfunc';
  $params['oauth_version'] = '1.0';
  $params['oauth_nonce'] = mt_rand();
  $params['oauth_timestamp'] = time();
  $params['oauth_consumer_key'] = $consumer_key;
  $params['oauth_token'] = $access_token;

  // compute hmac-sha1 signature and add it to the params list
  $params['oauth_signature_method'] = 'HMAC-SHA1';
  $params['oauth_signature'] =
      oauth_compute_hmac_sig($usePost? 'POST' : 'GET', $url, $params,
                             $consumer_secret, $access_token_secret);

  // Pass OAuth credentials in a separate header or in the query string
  if ($passOAuthInHeader) {
    $query_parameter_string = oauth_http_build_query($params, true);
    $header = build_oauth_header($params, "yahooapis.com");
    $headers[] = $header;
  } else {
    $query_parameter_string = oauth_http_build_query($params);
  }

  // POST or GET the request
  if ($usePost) {
    $request_url = $url;
    logit("call_yql:INFO:request_url:$request_url");
    logit("call_yql:INFO:post_body:$query_parameter_string");
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    $response = do_post($request_url, $query_parameter_string, 80, $headers);
  } else {
    $request_url = $url . ($query_parameter_string ?
                           ('?' . $query_parameter_string) : '' );
    logit("call_yql:INFO:request_url:$request_url");
    $response = do_get($request_url, 80, $headers);
  }

  // extract successful response
  if (! empty($response)) {
    list($info, $header, $body) = $response;
    if ($body) {
      logit("call_yql:INFO:response:");
      print(json_pretty_print($body));
    }
    $retarr = $response;
  }

  return $retarr;
}
?>
