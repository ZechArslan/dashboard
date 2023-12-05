<?php

include 'core.php';
// var_dump("Test");exit();
include 'db.php';

   $SkipTokenIds = $excludedToken;

   if(!isset($_POST['address']))
   exit('no data');
   $dateClause = "";
   if ($_POST['datestart'])
   {
      $dateClause = " and swaps.\"DateCreated\" between DATE('".$_POST['datestart']."') and DATE('".$_POST['dateend']."') ";
   }
   $adr = ($_POST['address']);

   $query = '
      SELECT swaps.*, 
      ft."USDPriceV3" * (swaps."FromAmount" * POWER(10,ftt."ExtraDigits")) as "FromUSDAmount", 
      tt."USDPriceV3" * (swaps."ToAmount" * POWER(10,ttt."ExtraDigits")) as "ToUSDAmount", 
      ftt."Name" as "FTName", fc."Address" as "FTAddress", ftt."Symbol" as "FTSymbol", 
      ttt."Name" as "TTName", tc."Address" as "TTAddress", ttt."Symbol" as "TTSymbol" 
      FROM swaps 
      LEFT JOIN tokenprice as ft ON ft."Id" = swaps."FromTokenId" 
      LEFT JOIN tokenprice as tt ON tt."Id" = swaps."ToTokenId" 
      LEFT JOIN token as ftt ON ftt."Id" = swaps."FromTokenId" 
      LEFT JOIN token as ttt ON ttt."Id" = swaps."ToTokenId" 
      LEFT JOIN contract as fc ON fc."Id" = swaps."FromTokenId" 
      LEFT JOIN contract as tc ON tc."Id" = swaps."ToTokenId" 
      LEFT JOIN address ON address."Id" = swaps."OriginAdrId" 
      WHERE (swaps."FromTokenId" NOT IN ('.$SkipTokenIds.') OR swaps."ToTokenId" NOT IN ('.$SkipTokenIds.')) 
      AND address."Hash" = \''.$adr.'\' AND address."Id" IS NOT NULL '.$dateClause.';
   
   ';
   // where address.Hash='$adr' and (swaps.FromTokenId = 623 or swaps.ToTokenId=)  AND address.Id is not null $dateClause

   // var_dump($query);exit();

   $addresId = "SELECT address.\"Id\" FROM address where address.\"Hash\" = '".$adr."';";
   $addresId = pg_query($connection, $addresId);
   $addresId = pg_fetch_all($addresId)[0]["Id"];


   // // $transfersQuery = 
   // // '
   // //       SELECT transfersonly."TokenId",
   // //       SUM((tokenprice."USDPrice" * transfersonly."Amount") - transfersonly."AmountUSD") as "PNL",
   // //       SUM(transfersonly."AmountUSD") as "RAmount",
   // //       transfersonly."Type",
   // //       SUM("Amount") as "AAmount",
   // //       SUM("Amount" * tokenprice."USDPrice")  as "USDAmount"
   // //    FROM transfersonly
   // //    LEFT JOIN tx ON tx."Id" = transfersonly."TransactionId"
   // //    LEFT JOIN tokenprice ON tokenprice."Id" = transfersonly."TokenId"
   // //    WHERE transfersonly."AddressId" = '.$addresId.' AND transfersonly."TokenId" NOT IN ('.$SkipTokenIds.') 
   // //    GROUP BY transfersonly."TokenId", transfersonly."Type"
   // //    ORDER BY transfersonly."TokenId" ASC;

   // // ';
   // var_dump($transfersQuery);exit();
   $result = pg_query($connection, $query);
   if (!$result) {
      // echo "An error occurred.\n";
      $error_message = pg_last_error($connection);
      echo "Error: " . $error_message;
      exit;
   }
   $empRecords = pg_fetch_all($result);
   // // $transfers = pg_query($connection, $transfersQuery);
   // // if (!$transfers) {
   // //    // echo "An error occurred.\n";
   // //    $error_message = pg_last_error($connection);
   // //    echo "Error: " . $error_message;
   // //    exit;
   // // }
   // // $transfers = pg_fetch_all($transfers);

   // // $totalTransfers = getDbData($conn,$totalTransfersQuery);
   // var_dump($transfers);exit();
   $transferData = [];
   $rAmountData = [];
   // // $totalTransferData = [];
   $incoming = [];
   $outgoing = [];
   // // foreach($transfers as $data)
   // // {
   // //    $tkk = $data['TokenId'];
   // //    if($data['Type'] == 0)
   // //       $incoming[$tkk] = [
   // //          'usd' => $data['RAmount'],
   // //          'orig' => $data['USDAmount'],
   // //          '_orig' => $data['AAmount']
   // //       ];
   // //    else
   // //       $outgoing[$tkk] = [
   // //          'usd' => $data['RAmount'] * -1,
   // //          'orig' => $data['USDAmount'] * -1,
   // //          '_orig' => $data['AAmount'] * -1
   // //       ];

   // //    if(!isset($transferData[$tkk]))
   // //       $transferData[$tkk] = $data['PNL'];
   // //    else
   // //       $transferData[$tkk] += $data['PNL'];

   // //       if(!isset($rAmountData[$tkk]))
   // //          $rAmountData[$tkk] = $data['RAmount'];
   // //       else
   // //          $rAmountData[$tkk] += $data['RAmount'];
   // // }



$tokens = [];
$tkInfo = [];
foreach($empRecords as $dt)
{
    if(!isset($tokens[$dt['FromTokenId']]))
    {
        $tkInfo[$dt['FromTokenId']] = [
            'name' => $dt['FTName'],
            'address' => $dt['FTAddress'],
            'symbol' => $dt['FTSymbol'],
        ];
        $tokens[$dt['FromTokenId']] = [
            'Sold' => 0,//isset($outgoing[$dt['FromTokenId']]) ? $outgoing[$dt['FromTokenId']]['orig'] : 0, // Main token sold
            'Bought' => 0,//isset($incoming[$dt['FromTokenId']]) ? $incoming[$dt['FromTokenId']]['orig'] : 0, // Main token bought
            'Received' => 0,//isset($outgoing[$dt['FromTokenId']]) ? $outgoing[$dt['FromTokenId']]['usd'] : 0, // Opposite currency received after Main Token sold
            'Spent' => 0,//isset($incoming[$dt['FromTokenId']]) ? $incoming[$dt['FromTokenId']]['usd'] : 0, // Opposite currency Spent after Main Token bought
            'BoughtOrig' => 0,//isset($incoming[$dt['FromTokenId']]) ? $incoming[$dt['FromTokenId']]['_orig'] : 0,
            'SoldOrig' => 0,//isset($outgoing[$dt['FromTokenId']]) ? $outgoing[$dt['FromTokenId']]['_orig'] : 0,
            'TradeCount' => 0,
            'StartDate' => 0,
            'EndDate' => 0
        ];
    }
    $tokens[$dt['FromTokenId']]['SoldOrig'] += $dt['FromAmount'];
    $tokens[$dt['FromTokenId']]['Sold'] += $dt['FromUSDAmount'];
    $tokens[$dt['FromTokenId']]['Received'] += $dt['ToUSDAmount'];
   // //  $tokens[$dt['FromTokenId']]['Received'] += $dt['FromUSDAmount'];
    $tokens[$dt['FromTokenId']]['TradeCount'] += 1;
    $datetime = strtotime($dt['DateCreated']);
   if ($tokens[$dt['FromTokenId']]['StartDate'] == 0 || strtotime($tokens[$dt['FromTokenId']]['StartDate']) > $datetime)
      $tokens[$dt['FromTokenId']]['StartDate'] = $dt['DateCreated'];
   if ($tokens[$dt['FromTokenId']]['EndDate'] == 0 || strtotime($tokens[$dt['FromTokenId']]['EndDate']) < $datetime)
      $tokens[$dt['FromTokenId']]['EndDate'] = $dt['DateCreated'];
    

    if(!isset($tokens[$dt['ToTokenId']]))
    {
        $tkInfo[$dt['ToTokenId']] = [
            'name' => $dt['TTName'],
            'address' => $dt['TTAddress'],
            'symbol' => $dt['TTSymbol'],
        ];
        $tokens[$dt['ToTokenId']] = [
            'Sold' => 0,//isset($outgoing[$dt['ToTokenId']]) ? $outgoing[$dt['ToTokenId']]['orig'] : 0, // Main token sold
            'Bought' => 0,//isset($incoming[$dt['ToTokenId']]) ? $incoming[$dt['ToTokenId']]['orig'] : 0, // Main token bought
            'Received' => 0,//isset($outgoing[$dt['ToTokenId']]) ? $outgoing[$dt['ToTokenId']]['usd'] : 0, // Opposite currency received after Main Token sold
            'Spent' => 0,//isset($incoming[$dt['ToTokenId']]) ? $incoming[$dt['ToTokenId']]['usd'] : 0, // Opposite currency Spent after Main Token bought
            'BoughtOrig' => 0,//isset($incoming[$dt['ToTokenId']]) ? $incoming[$dt['ToTokenId']]['_orig'] : 0,
            'SoldOrig' => 0,//isset($outgoing[$dt['ToTokenId']]) ? $outgoing[$dt['ToTokenId']]['_orig'] : 0,
            'TradeCount' => 0,
            'StartDate' => 0,
            'EndDate' => 0
        ];
    }
    $tokens[$dt['ToTokenId']]['BoughtOrig'] += $dt['ToAmount'];
    $tokens[$dt['ToTokenId']]['Bought'] += $dt['ToUSDAmount'];
    $tokens[$dt['ToTokenId']]['Spent'] += $dt['FromUSDAmount'];
   // //  $tokens[$dt['ToTokenId']]['Spent'] += $dt['ToUSDAmount'];
    $tokens[$dt['ToTokenId']]['TradeCount'] += 1;
   if ($tokens[$dt['ToTokenId']]['StartDate'] == 0 || strtotime($tokens[$dt['ToTokenId']]['StartDate']) > $datetime)
      $tokens[$dt['ToTokenId']]['StartDate'] = $dt['DateCreated'];
   if ($tokens[$dt['ToTokenId']]['EndDate'] == 0 || strtotime($tokens[$dt['ToTokenId']]['EndDate']) < $datetime)
      $tokens[$dt['ToTokenId']]['EndDate'] = $dt['DateCreated'];
}
foreach($tokens as $tk => $ts)
{
   $tokens[$tk]['EntryPrice'] = $ts['Spent'] && $ts['Bought']? $ts['Spent'] / $ts['Bought']:0;
   $tokens[$tk]['ExitPrice'] = $ts['Received'] && $ts['Sold'] ?$ts['Received'] / $ts['Sold']:0;

   if($ts['SoldOrig'] > 0 && $ts['BoughtOrig'] > 0)
   {
      // $tokens[$tk]['RealizedPNL'] = $ts['Received'] - ($ts['Sold'] * $ts['Spent']) / $ts['Bought'];
      // $tokens[$tk]['RealizedPNL'] = (($ts['Received']/$ts['SoldOrig'])*$ts['BoughtOrig'])-$ts['Spent'];
      $minOrig = min($ts['SoldOrig'], $ts['BoughtOrig']);
      $tokens[$tk]['RealizedPNL'] = (($ts['Received'] / $ts['SoldOrig']) * $minOrig) - ($ts['Spent']/$ts['BoughtOrig']) * $minOrig;
   }
   else
      $tokens[$tk]['RealizedPNL'] = 0;

      $tokens[$tk]['RealizedPNL1'] = ($ts['Received'] - $ts['Sold']);// - ($rAmountData[$tk] ?? 0);
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
   $tokens[$tk]['Name'] = $tkInfo[$tk]['name']. " (".$tkInfo[$tk]['symbol'].")";
   $tokens[$tk]['Address'] = $tkInfo[$tk]['address'];

}

function dollerSign($n,$decimalplaces)
{

   return $n < 0? "-$".number_format(abs($n),$decimalplaces):"$".number_format($n,$decimalplaces);
}
if(isset($_POST['test']))
{
   foreach($tokens as $tk =>$tks)
   {
      $tokens[$tk]['Transfer'] = isset($rAmountData[$tk]) ? $rAmountData[$tk] : 0;
      $tokens[$tk]['Incoming'] = isset($incoming[$tk]) ? $incoming[$tk] : 0;
      $tokens[$tk]['Outgoing'] = isset($outgoing[$tk]) ? $outgoing[$tk] : 0;
   }
   echo json_encode($tokens);
   exit();
}
$tokens = array_diff_key($tokens, array_flip($ExtraSkipTokenIds));

$tokens = array_map(function($keys,$token) {
   // var_dump($keys);
   // var_dump($token);
   global $transferData, $rAmountData, $incoming, $outgoing;
   // // global $totalTransferData;
   $transferPNL =  isset($transferData[$keys]) ? $transferData[$keys] : 0;
   $ram =  isset($rAmountData[$keys]) ? $rAmountData[$keys] : 0;
   // // $totalTransferPNL =  isset($totalTransferData[$keys]) ? $totalTransferData[$keys] : 0;
   // var_dump($transferPNL);
   // var_dump(floatval ($transferPNL));
   $ing = (isset($incoming[$keys]) ? $incoming[$keys]['usd'] : 0);
   $outg = (isset($outgoing[$keys]) ? $outgoing[$keys]['usd'] : 0);
   $bought = $token['Spent'] + $ing;
   $sold = $token['Received'] + $outg;
   return [
      'Token' => '<a href="https://etherscan.io/token/'.$token['Address'].'"><span class="dthidden">'.$token['Address'].'</span>'.$token['Name'].'</a>',
      // 'Bought' => trimNum(dollerSign($token['Spent'],2)),
      // 'Sold' => trimNum(dollerSign($token['Received'],2)),
      'Bought' => trimNum(dollerSign($bought,2)),
      'Sold' => trimNum(dollerSign($sold,2)),
      // 'UnSold' => trimNum(dollerSign((round($token['Bought'],8) - round($token['Sold'],8)), 2)),
      'UnSold' => trimNum(dollerSign(
         ($token['Bought'] + (isset($incoming[$keys]) ? $incoming[$keys]['orig'] : 0)) - ($token['Sold'] + (isset($outgoing[$keys]) ? $outgoing[$keys]['orig'] : 0))
         , 2)),
      // 'UnSold' => ($token['Bought'] - $token['Sold']),
      'RealizedPNL' => '<span title="Realized PNL Based on Trade activity only">'.trimNum(dollerSign($token['RealizedPNL'], 2)).'</span>',
      'UnRealizedPNL' => '<span title="UnRealized PNL Based on Trade activity only">'.trimNum(dollerSign($token['UnRealizedPNL'], 2)).'</span>',
      // // 'TransfersPNL' => trimNum(dollerSign( floatval( $totalTransferPNL), 2)),
      'TransfersPNL' => '<span title="Transfer PNL: '.trimNum(dollerSign($transferPNL,2)).'">'.trimNum(dollerSign($ram, 2)).'</span>',
      // // 'TotalPNL' => '<p title="'.trimNum(dollerSign(floatval( $transferPNL), 2)).'">'.trimNum(dollerSign(floatval($token['UnRealizedPNL']) +floatval($token['RealizedPNL']) +floatval( $transferPNL), 2)).'</p>',
      'TotalPNL' => '<p title="'.trimNum(dollerSign(floatval( $transferPNL), 2)).'">'.trimNum(dollerSign($token['RealizedPNL2'] - $ram, 2)).'</p>',
      // 'TotalPNL' => trimNum(number_format(floatval($token['RealizedPNL']) +floatval( $transferPNL), 2)),
      'Status' => (($token['Bought'] + (isset($incoming[$keys]) ? $incoming[$keys]['orig'] : 0)) - ($token['Sold'] + (isset($outgoing[$keys]) ? $outgoing[$keys]['orig'] : 0))) > 0 ? '<span style="color:#4caf50; font-weight:bold">Open</span>' : '<span style="color:#f44336; font-weight:bold">Closed</span>',
      'TradeCount' => $token['TradeCount'],
      'Entry' => dollerSign($token['EntryPrice'],8),
      'Exit' => dollerSign($token['ExitPrice'],8),
      'StartDate' => $token['StartDate'],
      'EndDate' => $token['EndDate']
   ];
},array_keys($tokens), array_values($tokens));
// exit();
echo json_encode([ 'data' => $tokens]);//, 'q' => $query]);
exit();
// // function trimNum($number)
// // {
// // 	if(strpos($number, ".") === false)
// //     	return $number;
// //     else
// // 	    return rtrim(rtrim($number, '0'), '.');
// // }

// ?>