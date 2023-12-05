<?php
// ini_set('memory_limit', '20480MB');
include 'core.php';
include 'db.php';

   if ($_GET['address'])
   {
      $tokenAddress = $_GET['address'];
   }
   else
      echo "address not set";
   // $query = "SELECT contract.\"Address\",token.\"Id\",token.\"Name\",token.\"Symbol\" from contract LEFT JOIN token on token.\"Id\" = contract.\"Id\" where contract.\"Address\" = '$tokenAddress';";
   $query = "SELECT * from contract LEFT JOIN token on token.\"Id\" = contract.\"Id\" where LOWER(contract.\"Address\") = LOWER('$tokenAddress');";
   // var_dump($query);exit();
   // $stmt = $conn->prepare($query);
   // $stmt->execute();
   $addresId = pg_query($connection, $query);
   $empRecords = pg_fetch_all($addresId);
   // var_dump($empRecords);exit();
   $tokenId = $empRecords[0]['Id'];
   $tkInfo = [];
   $tkInfo['name'] = $empRecords[0]['Name'];
   $tkInfo['address'] = $empRecords[0]['Address'];
   $tkInfo['symbol'] = $empRecords[0]['Symbol'];
   // var_dump($tkInfo,$tokenId);exit();

   $query =
   '
         SELECT swaps.*, address."Hash", address."Id" AS "AcAddressId"
         FROM
      (
         SELECT
            SUM(CASE WHEN swaps."FromTokenId" = '.$tokenId.' THEN (swaps."FromAmount" * POWER(10,ftt."ExtraDigits")) ELSE 0 END) AS "FromAmount",
            SUM(CASE WHEN swaps."ToTokenId" = '.$tokenId.' THEN (swaps."ToAmount" * POWER(10,ttt."ExtraDigits")) ELSE 0 END) AS "ToAmount",
            SUM(CASE WHEN swaps."FromTokenId" = '.$tokenId.' THEN (ft."USDPriceV3" * (swaps."FromAmount" * POWER(10,ftt."ExtraDigits"))) ELSE 0 END) AS "FromUSDAmount",
            SUM(CASE WHEN swaps."ToTokenId" = '.$tokenId.' THEN (tt."USDPriceV3" * (swaps."ToAmount" * POWER(10,ttt."ExtraDigits"))) ELSE 0 END) AS "ToUSDAmount",
            SUM(CASE WHEN swaps."FromTokenId" = '.$tokenId.' THEN (tt."USDPriceV3" * (swaps."ToAmount" * POWER(10,ttt."ExtraDigits"))-2) ELSE 0 END) AS "_FromUSDAmount",
            SUM(CASE WHEN swaps."ToTokenId" = '.$tokenId.' THEN (ft."USDPriceV3" * (swaps."FromAmount" * POWER(10,ftt."ExtraDigits"))) ELSE 0 END) AS "_ToUSDAmount",
            MIN(swaps."DateCreated") AS "DateStart",
            MAX(swaps."DateCreated") AS "DateEnd",
            COUNT(1) AS "TradeCount",
            swaps."OriginAdrId"
         FROM swaps
            LEFT JOIN tokenprice AS ft ON ft."Id" = swaps."FromTokenId"
            LEFT JOIN tokenprice AS tt ON tt."Id" = swaps."ToTokenId"
            LEFT JOIN token as ftt ON ftt."Id" = swaps."FromTokenId" 
            LEFT JOIN token as ttt ON ttt."Id" = swaps."ToTokenId" 
         WHERE (swaps."FromTokenId" = '.$tokenId.' OR swaps."ToTokenId" = '.$tokenId.')
         GROUP BY swaps."OriginAdrId"
      ) as swaps
      LEFT JOIN address ON address."Id" = swaps."OriginAdrId"
   ';
   // var_dump($query);exit();


   $result = pg_query($connection, $query);
   if (!$result) {
      // echo "An error occurred.\n";
      $error_message = pg_last_error($connection);
      echo "Error: " . $error_message;
      exit;
   }
   $empRecords = pg_fetch_all($result);
   // var_dump($empRecords);exit();

   // // // $transfersQuery = "
   // // //    select transfersonly.\"AddressId\", 
   // // //    SUM((tokenprice.\"USDPriceV3\" * transfersonly.\"Amount\") - transfersonly.\"AmountUSD\") as \"PNL\",
   // // //    SUM(transfersonly.\"AmountUSD\" ) as \"RAmount\", 
   // // //    transfersonly.\"Type\", 
   // // //    SUM(\"Amount\") as \"AAmount\", 
   // // //    SUM(\"Amount\" * tokenprice.\"USDPriceV3\") as \"USDAmount\"

   // // //       from transfersonly
   // // //       LEFT JOIN tx on tx.\"Id\"=transfersonly.\"TransactionId\"
   // // //       LEFT JOIN tokenprice on tokenprice.\"Id\"=transfersonly.\"TokenId\"
   // // //       where  transfersonly.\"TokenId\"=$tokenId 
   // // //       GROUP by transfersonly.\"AddressId\", transfersonly.\"Type\"
   // // //       ORDER BY transfersonly.\"AddressId\" ASC;

   // // // ";

// // // $result = pg_query($connection, $transfersQuery);
// // // if (!$result) {
// // //    // echo "An error occurred.\n";
// // //    $error_message = pg_last_error($connection);
// // //    echo "Error: " . $error_message;
// // //    exit;
// // // }
$transfers = pg_fetch_all($result);
   // $transfers = getDbData($conn,$transfersQuery);
   // // $totalTransfers = getDbData($conn,$totalTransfersQuery);
   // var_dump($transfers);exit();
   $transferData = [];
   $rAmountData = [];
   $incoming = [];
   $outgoing = [];
   // // $totalTransferData = [];
   // // foreach($transfers as $data)
   // // {
   // //    $transferData[$data['AddressId']] = $data['PNL'];
   // //    $rAmountData[$data['AddressId']] = $data['RAmount'];
   // // }
   // // // foreach($transfers as $data)
   // // // {
   // // //    $tkk = $data['AddressId'];
   // // //    if($data['Type'] == 0)
   // // //       $incoming[$tkk] = [
   // // //          'usd' => $data['RAmount'],
   // // //          'orig' => $data['USDAmount'],
   // // //          '_orig' => $data['AAmount']
   // // //       ];
   // // //    else
   // // //       $outgoing[$tkk] = [
   // // //          'usd' => $data['RAmount'] * -1,
   // // //          'orig' => $data['USDAmount'] * -1,
   // // //          '_orig' => $data['AAmount'] * -1
   // // //       ];

   // // //    if(!isset($transferData[$tkk]))
   // // //       $transferData[$tkk] = $data['PNL'];
   // // //    else
   // // //       $transferData[$tkk] += $data['PNL'];

   // // //       if(!isset($rAmountData[$tkk]))
   // // //          $rAmountData[$tkk] = $data['RAmount'];
   // // //       else
   // // //          $rAmountData[$tkk] += $data['RAmount'];
   // // // }
   // // foreach($totalTransfers as $data)
   // // {
   // //    $totalTransferData[$data['AddressId']] = $data['PNL'];
   // // }
   // var_dump($empRecords);exit();
// }
$tokens = [];
// $tkInfo = [];
foreach($empRecords as $dt)
{
    if(!isset($tokens[$dt['Hash']]))
    {
        $tokens[$dt['Hash']] = [
            'Sold' => 0,//isset($outgoing[$dt['FromTokenId']]) ? $outgoing[$dt['FromTokenId']]['orig'] : 0, // Main token sold
            'Bought' => 0,//isset($incoming[$dt['FromTokenId']]) ? $incoming[$dt['FromTokenId']]['orig'] : 0, // Main token bought
            'Received' => 0,//isset($outgoing[$dt['FromTokenId']]) ? $outgoing[$dt['FromTokenId']]['usd'] : 0, // Opposite currency received after Main Token sold
            'Spent' => 0,//isset($incoming[$dt['FromTokenId']]) ? $incoming[$dt['FromTokenId']]['usd'] : 0, // Opposite currency Spent after Main Token bought
            'BoughtOrig' => 0,//isset($incoming[$dt['FromTokenId']]) ? $incoming[$dt['FromTokenId']]['_orig'] : 0,
            'SoldOrig' => 0,//isset($outgoing[$dt['FromTokenId']]) ? $outgoing[$dt['FromTokenId']]['_orig'] : 0,
            'TradeCount' => 0
        ];
    }

   // if($tokenId == $dt['FTAddress'])
   // {
      $tokens[$dt['Hash']]['SoldOrig'] += $dt['FromAmount'];
      $tokens[$dt['Hash']]['Sold'] += $dt['FromUSDAmount'];
      $tokens[$dt['Hash']]['Spent'] += $dt['_ToUSDAmount'];
      // // $tokens[$dt['Hash']]['Spent'] += $dt['_FromUSDAmount'];
   // }
   // else if($tokenId == $dt['TTAddress'])
   // {
      $tokens[$dt['Hash']]['BoughtOrig'] += $dt['ToAmount'];
      $tokens[$dt['Hash']]['Bought'] += $dt['ToUSDAmount'];
      $tokens[$dt['Hash']]['Received'] += $dt['_FromUSDAmount'];
      // // $tokens[$dt['Hash']]['Received'] += $dt['_ToUSDAmount'];
   // }
   // else
   // exit("Failed, Not match".$tokenAddress);

   $tokens[$dt['Hash']]['TradeCount'] = $dt['TradeCount'];
   $tokens[$dt['Hash']]['AddressId'] = $dt['AcAddressId'];


}
foreach($tokens as $tk => $ts)
{
   $tokens[$tk]['EntryPrice'] = $ts['Spent'] && $ts['Bought']? $ts['Spent'] / $ts['Bought']:0;
   $tokens[$tk]['ExitPrice'] = $ts['Received'] && $ts['Sold'] ?$ts['Received'] / $ts['Sold']:0;
   if($ts['Bought'] > 0 && $ts['SoldOrig'] > 0)
   {
      // $tokens[$tk]['RealizedPNL'] = $ts['Received'] - ($ts['Sold'] * $ts['Spent']) / $ts['Bought'];
      $minOrig = min($ts['SoldOrig'], $ts['BoughtOrig']);
      $tokens[$tk]['RealizedPNL'] = (($ts['Received'] / $ts['SoldOrig']) * $minOrig) - ($ts['Spent']/$ts['BoughtOrig']) * $minOrig;
   }
   else
      $tokens[$tk]['RealizedPNL'] = 0;
      $tokens[$tk]['RealizedPNL1'] = ($ts['Received'] - $ts['Spent']);// - ($rAmountData[$tk] ?? 0);
      $tokens[$tk]['RealizedPNL2'] = ($ts['Received'] - $ts['Spent']);// - ($transferData[$tk] ?? 0);
      // $tokens[$tk]['RealizedPNL'] = $ts['Received'] - $ts['Sold'];
   $tokens[$tk]['Remainder'] = $ts['Bought'] - $ts['Sold'];
   if($tokens[$tk]['Remainder'] > 0)
   {
      if($ts['Sold'] > 0)
         $tokens[$tk]['UnRealizedPNL'] = $tokens[$tk]['Remainder'] * ($ts['Received']/$ts['Sold']) - $tokens[$tk]['Remainder'] * ($ts['Spent']/$ts['Bought']);
      else
         $tokens[$tk]['UnRealizedPNL'] = $ts['Bought'] - $ts['Spent'];
   }
   else
   $tokens[$tk]['UnRealizedPNL'] = 0;
   $tokens[$tk]['Name'] = $tkInfo['name']. " (".$tkInfo['symbol'].")";
   $tokens[$tk]['Address'] = $tkInfo['address'];
   $tokens[$tk]['AccountAddress'] = $tk;
   // if($tk == "0x44ffcb49cf4f143267d5e3ee74532e1bb7b39236")
   // {
   //    echo json_encode($tokens[$tk]);
   //    exit();
   // }
}

$tokens = array_filter($tokens, function($v) {
   return $v['RealizedPNL'] != 0 || $v['UnRealizedPNL'] != 0;
});
// var_dump($transferData);
// exit();
$tokens = array_map(function($token) {

   global $transferData,$rAmountData, $incoming, $outgoing;
   // // global $totalTransferData;
   $transferPNL =  isset($transferData[$token['AddressId']]) ? $transferData[$token['AddressId']] : floatval(0.0);
   // // $totalTransferPNL =  isset($totalTransferData[$token['AddressId']]) ? $totalTransferData[$token['AddressId']] : floatval(0.0);
   $ram =  isset($rAmountData[$token['AddressId']]) ? $rAmountData[$token['AddressId']] : 0;
   // var_dump($token['AddressId']);
   // var_dump(floatval($transferPNL));
   $ing = (isset($incoming[$token['AddressId']]) ? $incoming[$token['AddressId']]['usd'] : 0);
   $outg = (isset($outgoing[$token['AddressId']]) ? $outgoing[$token['AddressId']]['usd'] : 0);
   $bought = $token['Spent'] + $ing;
   $sold = $token['Received'] + $outg;
   return [
      // 'Token' => '<a href="https://etherscan.io/token/'.$token['Address'].'"><span class="dthidden">'.$token['Address'].'</span>'.$token['Name'].'</a>',
      // 'TokenId' => $token['AddressId'],
      'Token' => $token['Name'],
      // // 'Bought' => trimNum(round($token['Spent'],6)),
      // // 'Sold' => trimNum(round($token['Received'],6)),
      'Bought' => trimNum(round($bought,6)),
      'Sold' => trimNum(round($sold,6)),
      // // 'UnSold' => trimNum(round($token['Bought'] - $token['Sold'], 6)),
      'UnSold' => trimNum(round(
         ($token['Bought'] + (isset($incoming[$token['AddressId']]) ? $incoming[$token['AddressId']]['orig'] : 0)) - ($token['Sold'] + (isset($outgoing[$token['AddressId']]) ? $outgoing[$token['AddressId']]['orig'] : 0))

         , 6)),

      'RealizedPNL' => trimNum(round($token['RealizedPNL'], 2)),
      'UnRealizedPNL' => trimNum(round($token['UnRealizedPNL'], 2)),
      // // 'TransfersPNL' => trimNum(floatval( $totalTransferPNL)),
      'TransfersPNL' => trimNum(floatval( $ram)),
      // 'TotalPNL' => trimNum(floatval( $totalTransferPNL)),
      'TotalPNL' => trimNum(round($token['RealizedPNL2'],2) - round($ram,2) ),
      // 'TotalPNL' => trimNum(round($token['UnRealizedPNL'],2) +round($token['RealizedPNL'],2) + floatval( $transferPNL)),
      // 'Status' => $token['Remainder'] > 0 ? '<span style="color:#4caf50; font-weight:bold">Open</span>' : '<span style="color:#f44336; font-weight:bold">Closed</span>',
      // // 'Status' => $token['Remainder'] > 0 ? 'Open' : 'Closed',
      'Status' => ($token['Bought'] + (isset($incoming[$token['AddressId']]) ? $incoming[$token['AddressId']]['orig'] : 0)) - ($token['Sold'] + (isset($outgoing[$token['AddressId']]) ? $outgoing[$token['AddressId']]['orig'] : 0)) > 0 ? 'Open' : 'Closed',
      'TradeCount' => $token['TradeCount'],
      'EntryPrice' => $token['EntryPrice'],
      'ExitPrice' => $token['ExitPrice'],
      'AccountAddress' => $token['AccountAddress'],
   ];
}, array_values($tokens));
// exit();
// $jsonans = json_decode($jsondata, true);

$csv = 'TokenLeaderBorad.csv';
$delimiter = ',';
$resultstr = implode(',',['Token','Bought','Sold','UnSold','RealizedPNL','UnRealizedPNL','TransferValue','TotalPNL','Status','TradeCount','EntryPrice','ExitPrice','AccountAddress']);
$resultstr .= "\n";
foreach($tokens as  $data)
{
   $data = implode(',', array_values($data));
   $data = str_replace(',,',',0,',$data);
   // var_dump($data);
   // var_dump(implode(',', array_values($data)));
   // continue;
   // var_dump(implode(',', array_values($data)));
   // $data = array_push($data,$key);
   // $resultstr .= implode(',', array_values($data));
   $resultstr .= $data;
   $resultstr .= "\n";
   // var_dump($data);
   // echo "/n";
   // exit();
}
// exit();
// $f = fopen($csv, 'w');
// // loop over the input array
// foreach ($tokens as $line) {
//    // generate csv lines from the inner arrays
//    fputcsv($f, $line, $delimiter);
// }
// // reset the file pointer to the start of the file
// fseek($f, 0);
// tell the browser it's going to be a csv file
header('Content-Type: text/csv');
// tell the browser we want to save it instead of displaying it
header('Content-Disposition: attachment; filename="'.$csv.'";');
// make php send the generated csv lines to the browser
// fpassthru($f);
echo $resultstr;
exit();

// ?>