<?php


ini_set("max_execution_time", "-1");
ini_set("memory_limit", "-1");
ignore_user_abort(true);
set_time_limit(0);
header('Content-Type: application/json; charset=utf-8');
ini_set("display_errors", "1");



$file = 'excluded_token_data.json';
$data = file_get_contents($file);
$jsonfiledata = json_decode($data, true);
$excludedToken = [];
foreach($jsonfiledata as $subdata)
{
   $excludedToken[] = $subdata["TokenId"];
}
$ExtraSkipTokenIds = $excludedToken;
$excludedToken = implode(',',$excludedToken);

function getDbData($conn,$query)
{
   $stmt = $conn->prepare($query);
   $stmt->execute();
   $empRecords = $stmt->fetchAll();
   return $empRecords;
}

function shortenUsd($n)
{
   global $exportCsv;
   $n = floatval($n);
   $n_format = null;
   if ($exportCsv) {
      return $n > 0 ? '$'.$n : '-$'.abs($n);
   }
   $abs = abs($n);
   if($abs < 1000000)
   {
      return ($n < 0 ? '-$' : '$').number_format($abs, 2);
   }
   else if ($abs < 1000000000)
   {
      return ($n < 0 ? '-$' : '$').number_format($abs/1000000, 2).' <strong>M</strong>';
   }
   else if ($abs < 1000000000000)
   {
      return ($n < 0 ? '-$' : '$').number_format($abs/1000000000, 2).' <strong>B</strong>';
   }
   else
   {
      return ($n < 0 ? '-$' : '$').number_format($abs/1000000000000, 2).' <strong>T</strong>';
   }
}

function shortenNumber($n)
{
   global $exportCsv;
   if($exportCsv)
   return $n;
   $n = floatval($n);
   $n_format = null;
   if (($n < 1000000 && $n > -1000000)) {
      // Anything less than a million
      $n_format = number_format($n,2);
   } else if ($n < 1000000000 && $n > -1000000000) {
      // Anything less than a billion
      $n_format = number_format($n / 1000000, 2) . ' <strong>M</strong>';
   } else if ($n < 1000000000000 && $n > -1000000000000){
      // At least a trillion
      $n_format = number_format($n / 1000000000, 2) . ' <strong>B</strong>';
   }
   else {
      $n_format = number_format($n / 1000000000000, 2) . ' <strong>T</strong>';
   }
   return $n_format;
}

function trimNum($number)
{
	if(strpos($number, ".") === false)
    	return $number;
    else
	    return rtrim(rtrim($number, '0'), '.');
}