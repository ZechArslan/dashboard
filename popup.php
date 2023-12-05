<?php
include 'core.php';
include 'db.php';

// if($_GET['popup'] == 1)
// {
   // var_dump($_GET);exit();
   $dateClause = "";
   if ($_POST['datestart'])
   {
      $dateClause = " and  swaps.\"DateCreated\" between DATE('".$_POST['datestart']."') and DATE('".$_POST['dateend']."') ";
   }

   $query = '
      SELECT swaps.*,
      swaps."FromAmount" * tokenprice."USDPriceV3" as "FromAmountUsd",
      swaps."ToAmount" * tokenprice1."USDPriceV3" as "ToAmountUsd",
      tokenprice."USDPriceV3" as "fusdrate",
      tokenprice1."USDPriceV3" as "tusdrate",
      (swaps."FromAmount" * POWER(10,token."ExtraDigits")) as "FromAmount",
      (swaps."ToAmount" * POWER(10,totoken."ExtraDigits")) as "ToAmount",
      token."Symbol",
      token."Name",
      totoken."Symbol" as "totokenSymbol",
      totoken."Name" as "totokenName",
      tx."Hash" as "txhash",
      contract."Address" as "fcontractAddress",
      tocontract."Address" as "tocontractAddress",
      address."Hash",
      (((swaps."ToAmount" * POWER(10,totoken."ExtraDigits")) * tokenprice1."ETHPriceV3") - ((swaps."FromAmount" * POWER(10,token."ExtraDigits")) * tokenprice."ETHPriceV3")) as "PnlETHValue",
      (((swaps."ToAmount" * POWER(10,totoken."ExtraDigits")) * tokenprice1."USDPriceV3") - ((swaps."FromAmount" * POWER(10,token."ExtraDigits")) * tokenprice."USDPriceV3")) as "PnlUSDValue"
   FROM "swaps"
   LEFT JOIN "address" on "address"."Id" = swaps."OriginAdrId" 
   LEFT JOIN "token" on "token"."Id" = swaps."FromTokenId"
   LEFT JOIN "token" as "totoken" on "totoken"."Id" = swaps."ToTokenId"
   LEFT JOIN "contract" on "contract"."Id" = swaps."FromTokenId"
   LEFT JOIN "contract" as "tocontract" on "tocontract"."Id" = swaps."ToTokenId"
   LEFT JOIN "tokenprice" on "tokenprice"."Id" = swaps."FromTokenId"
   LEFT JOIN "tokenprice" as "tokenprice1" on "tokenprice1"."Id" = swaps."ToTokenId"
   LEFT JOIN "tx" on "tx"."Id" = swaps."TransactionId"
   WHERE address."Hash" = \''.$_POST['address'].'\' '.$dateClause.' order by swaps."Id" asc limit 15000;

   ';
   // $query = str_replace('`','"',$query);  
   // var_dump($query);exit();
   $result = pg_query($connection, $query);
   if (!$result) {
      // echo "An error occurred.\n";
      $error_message = pg_last_error($connection);
      echo "Error: " . $error_message;
      exit;
   }
   $empRecords = pg_fetch_all($result);

// } 
$result = [];
foreach ($empRecords as $data)
{
   $vals = [];
   $vals['TID'] = $data['TransactionId'];
   $vals['TID'] = '<a target="_blank" href="https://etherscan.io/tx/'.$data['txhash'].'"><span class="dthidden">'.$data['txhash'].'</span>'.substr($data['txhash'], 0, 5)."...</a>";
   $vals['FTOKEN'] = '<a target="_blank" href="https://etherscan.io/address/'.$data['fcontractAddress'].'">'.$data['Name'].'('.$data['Symbol'].')'."</a>";
   $vals['TOTOKEN'] = '<a target="_blank" href="https://etherscan.io/address/'.$data['tocontractAddress'].'">'.$data['totokenName'].'('.$data['totokenSymbol'].')'."</a>";
   $vals['FAMOUNT'] = $data['Symbol'] == 'USDT'? shortenUsd($data['FromAmount']):shortenNumber($data['FromAmount']);
//    $vals['FAMOUNT'] = '<span onClick="copyToClipboard(\''.$data['FromAmount'].'\')" title="'.$data['FromAmount'].'
// Click To Copy Token Amount">'.shortenUsd($data['FromAmountUsd'],$data['FromAmountUsd']).'</span>';
   $vals['TOAMOUNT'] = $data['totokenSymbol'] == 'USDT'?shortenUsd($data['ToAmount']):shortenNumber($data['ToAmount']);
//    $vals['TOAMOUNT'] = '<span onClick="copyToClipboard(\''.$data['ToAmount'].'\')" title="'.$data['ToAmount'].'
// Click To Copy Token Amount">'.shortenUsd($data['ToAmountUsd'],$data['ToAmountUsd']).'</span>';
   // $vals['FAMOUNTUsd'] = shortenUsd($data['FromAmountUsd'],$data['FromAmountUsd']).'-'.$data['fusdrate'];
   // $vals['TOAMOUNTUsd'] = shortenUsd($data['ToAmountUsd'],$data['ToAmountUsd']).'-'.$data['tusdrate'];   
   $vals['FAMOUNTUsd'] = shortenUsd($data['FromAmountUsd'],$data['FromAmountUsd']);
   $vals['TOAMOUNTUsd'] = shortenUsd($data['ToAmountUsd'],$data['ToAmountUsd']);   
   $vals['PNLUSD'] = shortenUsd($data['PnlUSDValue']);
   $vals['PNLETH'] = shortenNumber($data['PnlETHValue']);
   $vals['TDATEID'] = $data['DateCreated'];
   $vals['PRICE'] = shortenUsd($data['ToAmountUsd'] == 0?$data['FromAmountUsd']:$data['FromAmountUsd']/$data['ToAmountUsd']);
   // $vals['PRICE'] = shortenUsd($data['Price']);
   $result[] = $vals;
}
$responce['data'] = $result;
echo json_encode($responce);
?>