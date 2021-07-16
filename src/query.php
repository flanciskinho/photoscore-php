<?php

function print_header($title) {
    echo "<!doctype html>\n";
    echo "<html>";
    echo "<head>\n";
    echo "<meta charset=\"utf-8\">\n";
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, shrink-to-fit=no\">\n";
    echo "<link rel=\"stylesheet\" href=\"https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css\" integrity=\"sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk\" crossorigin=\"anonymous\">\n";
    echo "<link rel=\"stylesheet\" href=\"css/style.css\">\n";
    echo "<title>$title</title>\n";
    echo "</head><body>\n";
    echo "<div class=\"container-fluid\">";
}

function print_footer() {
  echo "</div>";

  echo "<script src=\"https://code.jquery.com/jquery-3.5.1.slim.min.js\" integrity=\"sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj\" crossorigin=\"anonymous\"></script>\n";
  echo "<script src=\"https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js\" integrity=\"sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo\" crossorigin=\"anonymous\"></script>\n";
  echo "<script src=\"https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js\" integrity=\"sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI\" crossorigin=\"anonymous\"></script>\n";

  echo "</body></html>";
}

function print_development_by() {
  echo "<div class=\"text-center my-5\"><small>";
  echo "Web server designed and implemented by ";
  echo "<a href=\"https://www.linkedin.com/in/flanciskinho/\">Fran Cedron</a> (2021)";
  echo "</small></div>";
}


function print_error($message) {
  print_header("Error");

  echo "<div class=\"text-center mt-5\">";
  echo "<h1>Oops!</h1>";
  echo "<h3>$message</h3>\n";

  echo "<a href=\"/\" class=\"btn btn-primary mt-1\">Take me home</a>";

  echo "</div>";

  print_footer();

  exit();
}

function print_score_header() {
  echo "<div class=\"row\">";
}

function print_score_footer() {
  echo "</div>"; // row
}

function print_score_rows($group, $scores) {
  for ($cnt = 0; $cnt < count($group); $cnt++) {
    echo "<div class=\"col-6 col-xs-6 col-sm-6 col-md-3 col-lg-3 col-xl-2 mx-0 my-1 p-0\">";

    echo "<div class=\"card m-2\">";

    echo "<div class=\"p-2 text-center\">";
    echo "<img class=\"img-fluid img-gallery\" src=\"$group[$cnt]\" loading=\"lazy\" />";
    echo "</div>";

    $per = 10 * $scores[$cnt];
    $rou = number_format($scores[$cnt], 2, '.', '');

    echo "<p class=\"my-0 py-0 mx-2 text-left\">$rou</p>";

    echo "<div class=\"progress rounded-0 py-0 mt-0 mb-2 mx-2\">";
    echo "<div class=\"progress-bar bg-primary\" role=\"progressbar\" style=\"width: $per%\" aria-valuenow=\"$rou\" aria-valuemin=\"0\" aria-valuemax=\"10\">";
    echo "</div>";
    echo "</div>";// progressbar

    echo "</div>"; // card

    echo "</div>"; // col
  }
}

function console_log( $data ){
  echo '<script>';
  echo 'console.log('. json_encode( $data ) .')';
  echo '</script>';
}

function authenticate($username, $password) {
  $endpoint = "https://api.photoilike.com/v2.0/authenticate";

  // create a new cURL resource
  $ch = curl_init($endpoint);

  // setup request to send json via post
  $data = array(
    'username' => $username,
    'password' => $password
  );
  $payload = json_encode($data);

  // attach encoded JSON string to the POST fields
  curl_setopt($ch,  CURLOPT_POSTFIELDS, $payload);

  // set the content type to application/json
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

  // return response instead of outputting
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  // execute the post request
  $response = curl_exec($ch);

  // close cURL resource
  curl_close($ch);

  return json_decode($response)->{'id_token'};
}

function get_score($session_token, $client_key, $group) {
  $endpoint = "https://api.photoilike.com/v2.0/score";

  $headers = array(
    'Content-Type:application/json',
    'Authorization: Bearer ' . $session_token
  );

  // create multiple cURL resource
  $mh = curl_multi_init();

  foreach ($group as $key => $value) {
    $ch[$key] = curl_init($endpoint);

    $data = array(
      'client-key' => $client_key,
      'image-url' => $value
    );
    $payload = json_encode($data);

    curl_setopt($ch[$key], CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch[$key], CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, true);

    // add the curl resources
    curl_multi_add_handle($mh, $ch[$key]);
  }

  // execute resources
  do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
  } while ($running > 0);

  // get responses
  foreach (array_keys($ch) as $key) {
    // var_dump(curl_getinfo($ch[$key], CURLINFO_HTTP_CODE));

    $tmp = curl_multi_getcontent($ch[$key]);
    $scores[$key] = json_decode($tmp)->{'score'};

    // remove cURL resource
    curl_multi_remove_handle($mh, $ch[$key]);
  }

  curl_multi_close($mh);

  return $scores;
}

function parse_urls($session_token, $client_key, $urls, $limit) {

  $cnt = 0;
  print_score_header();

  $group = array();
  foreach(preg_split("/((\r?\n)|(\r\n?))/", $urls) as $url){
    if (empty($url))
      continue;

    array_push($group, $url);
    if (sizeof($group) == $limit) {
      $scores = get_score($session_token, $client, $group);
      print_score_rows($group, $scores);
      $group = array();
    }
    $cnt++;
  }

  if (sizeof($group) != 0) {
    $scores = get_score($session_token, $client, $group);
    print_score_rows($group, $scores);
    $group = array();
  }

  print_score_footer();

  return $cnt;
}

print_header("Results");

$session_token = authenticate($_POST['user'], $_POST['pass']);

$start_time = microtime(true);
$amount = parse_urls($session_token, $_POST['client'], $_POST['images'], 5);
$end_time = microtime(true);
$execution_time = round($end_time - $start_time, 3);

echo "<div class=\"alert alert-secondary m-5\" role=\"alert\">";
echo "It takes $execution_time seconds to execute $amount images";
echo "</div>";


print_development_by();

print_footer();

?>
