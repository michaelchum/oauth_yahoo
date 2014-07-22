<?php
require 'globals.php';
require 'oauth_helper.php';

// Fill in the next 3 variables.
$guid='UOM4UNZYNI3E2QK6PCGXJWOKMY';
$access_token='A=XTULSMrbiT47BY.oipJD04TrZFy.es8i3CyPcE5z7udjN5gCcAVmpbapbQfoqZh_3g.dmALgm.crVvZi.96NPKROcthNtbISPz6kRIqaLutzvl6qux3Yvn4O3TF0VqaTWcH3zHHB0DJwdTOSZXd3ngxFjWup18DdS7GMjOO4kNsAae8QGjuIPCMqCgns6Frn7nPE8qBUJRZ3nzpb3hwRA9KNKewSJ.C3tm81V9xAM9cvo3WI6C_D42b0LU0M66QhegbWrXb5yASsBOXWT3tnj5f8MHpDlSn0eaOIG4OuHBzifnRzj0sv1BoimjJWMdhAPE8.gY2qQufn1_9Nhpw8St7j9fcVGKjC2O7AyJme_psFQ1F8HQ5pnrwRazkqQSxPtIVDcNqYckBNEJ6E578qqmgmjZhoG0fonSMeekmzOMzbHtI0fW57Faa1Al5C6q5eRH7PBKY_DRqFm7U_yAS4DyK.KPtLmy5e3M.GiOz_kocuRh6K8S4nJelSZIIuLxmDLBteBhpFmddXHGVzmU2w4GpVhd5GSq4UsoYN6_dXsK4GAdC2jXJfeoGm1pFhw_Wm2K2sarnEtlDgEIWMVwE9gr5xVfOkIhG7A_noVjiJ1srPBnS5NWGXx79XUZsR5K.IOcA2M4femi29RZt59FnfSpJEQqRMX7.Mr1SIPnN1rfE94KOVSOSY7kYWg0NV2jYfCSNJLjmu7e1_Q0w3WbtoNa5iisL_78kSvaf1alL3i5.G._85W0dkqwrE9kkW0yZY4uq0jgx1ROy6sA927X_fjsdh4wYLZF3If0BzxuADzcgkoE0oM2q5VZp0vCQ-';
$access_token_secret='edefc7236c654690a2c1482ca471be815096306f';

// Call Contact API
$retarr = postcontact(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET,
                      $guid, $access_token, $access_token_secret,
                      true, true);

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
function postcontact($consumer_key, $consumer_secret, $guid, $access_token, $access_token_secret, $usePost=false, $passOAuthInHeader=true)
{
  $retarr = array();  // return value
  $response = array();

  $post_body='{"contact":{"fields":[{"type":"name","value":{"givenName":"John","middleName":"","familyName":"Doe","prefix":"","suffix":"","givenNameSound":"","familyNameSound":""}},{"type":"email","value":"johndoe@example.com"}]}}';
  $url = 'https://social.yahooapis.com/v1/user/' . $guid . '/contacts';
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
    $request_url = $url;
  } else {
    $query_parameter_string = oauth_http_build_query($params);
    $request_url = $url . '?' . $query_parameter_string;
  }

  // POST or GET the request
  if ($usePost) {
    logit("postcontact:INFO:request_url:$request_url");
    logit("postcontact:INFO:post_body:$post_body");
    $headers[] = 'Content-Type: application/json';
    $response = do_post($request_url, $post_body, 80, $headers);
  } else {
    logit("postcontact:INFO:request_url:$request_url");
    $response = do_get($request_url, 80, $headers);
  }

  // extract successful response
  if (! empty($response)) {
    list($info, $header, $body) = $response;
    if ($body) {
      logit("postcontact:INFO:response:");
      print(json_pretty_print($body));
    }
    $retarr = $response;
  }

  return $retarr;
}
?>
