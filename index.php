<?php
error_reporting(E_ALL);

$csv = array_map('str_getcsv', file('lib/csv/spots.csv'));

$current = 0;
$total = count($csv);

$returnArray = [];
$failArray = [];

$google_url = "https://maps.googleapis.com/maps/api/geocode/json?address=";
$api_key = "AIzaSyDgMV_9HUhk2J33O_IgCszzKOOVme6ysMU";



function grabItem($index, $total, $csv){
  if($index<$total){
    getData($index,$total,$csv,$csv[$index][0],$csv[$index][1]);
  }else{
    generateCSV();
  }
}



function getData($index,$total,$csv,$id,$name){

  global $returnArray;
  global $failArray;

  global $google_url;
  global $api_key;

  $url = $google_url.str_replace(" ","+",$name)."&key=".$api_key;
  
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $response = json_decode(curl_exec($ch), true);

  // If Status Code is ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
  if ($response['status'] == 'OK') {
    $geometry = $response['results'][0]['geometry'];
 
    $longitude = $geometry['location']['lat'];
    $latitude = $geometry['location']['lng'];

    $location = array($id,$name,$longitude,$latitude);
    $returnArray[] = $location;
  }else if($response['status'] == 'ZERO_RESULTS'){
    $failArray[] = array($id,$name);
  }else{
    echo $response['status'];
    break;
  }


  // Increment
  $index++;
  // wait so we dont overuse our API calls per second
  usleep(250000);
  // rinse, repeat
  grabItem($index,$total,$csv);

}

function generateCSV(){

  global $returnArray;
  global $failArray;

  // output results
  $out = fopen("lib/csv/output.csv","w");

  foreach ($returnArray as $location) {
    fputcsv($out, $location);
  }

  fclose($out);



  // output failures
  $fail = fopen("lib/csv/fail.csv","w");

  foreach ($failArray as $location) {
    fputcsv($fail, $location);
  }

  fclose($fail);



  echo "<strong>Location search complete:</strong> ".count($returnArray)." locations added successfully<br/>";
  echo "<strong>".count($failArray)." locations failed</strong>, listed below<br/>";
  print_r($failArray);

}

// let's kick this thing off
grabItem($current, $total, $csv);


