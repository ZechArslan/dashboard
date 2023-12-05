<?php
include 'core.php';
include 'db.php';

if(isset($_POST['blocks']))
{
   $resultt = getPtgrData('SELECT MIN("Height") as start, MAX("Height") as end FROM block;');
   // var_dump($resultt);exit();
   echo json_encode([$resultt[0]['start'],$resultt[0]['end']]);
   // echo [$resultt[0]['start'],$resultt[0]['end']];
   exit();
}
      //    include 'connection.php';
      $SkipTokenIds = "1,2,7,10,16332";
      // $SkipTokenIds = "ETH,WETH,Usd,USDC,LUNC";
      $exportCsv = isset($_POST['exportcsv']) || isset($_GET['exportcsv']);
      if($exportCsv)
      {
         $_POST = json_decode($_GET['data'], true);
      }

   $draw = $_POST['draw'];
   $row = (int)$_POST['start'];
   $rowperpage = (int)$_POST['length']; // Rows display per page
   if($exportCsv)
   {
      $row = 0;
      $rowperpage = 2147483647;
   }
   $columnIndex = $_POST['order'][0]['column']; // Column index
   $columnName = $_POST['columns'][$columnIndex]['data'] ?? "PnlUSDValue"; // Column name
   $columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
   $searchValue = $_POST['search']['value']; // Search value




   $searchArray = array();

   // Search
   $searchQuery = " ";
   if($searchValue != ''){
      $searchQuery = "
         AND (Address LIKE :Address OR
         --   AddressId LIKE :AddressId OR
         PnlUSDValue LIKE :PnlUSDValue OR
         PnlETHValue LIKE :PnlETHValue OR
         TradeCount LIKE :TradeCount OR
         AvragePnlPercentage LIKE :AvragePnlPercentage OR
         Balance LIKE :Balance OR
         TxCount LIKE :TxCount ) ";
      $searchArray = array(
         'Address'=>"$searchValue%",
         //   'AddressId'=>"$searchValue%",
         'PnlUSDValue'=>"$searchValue%",
         'PnlETHValue'=>"$searchValue%",
         'TxCount'=>"$searchValue%",
         'TradeCount'=>"$searchValue%",
         'AvragePnlPercentage'=>"$searchValue%",
         'Balance'=>"$searchValue%",

      );
   }
   foreach($_POST['columns'] as $data)
   {
      $data['search']['value'] = str_replace('((((','',$data['search']['value']);
      $data['search']['value'] = str_replace('))))','',$data['search']['value']);
      if ($data['search']['value'] && $data['search']['value'] != ",,")
      {
      //  if (sizeof(explode(",,",$data['search']['value'])) > 1  && explode(",,",$data['search']['value'])[0] && explode(",,",$data['search']['value'])[1])
         if (sizeof(explode(",,",$data['search']['value'])) > 1  )
         {
            $searches = explode(",,",$data['search']['value']);
            //  var_dump($searches);exit();
            $searches[0] = str_replace(',','',$searches[0]);
            $searches[1] = str_replace(',','',$searches[1]);
            if ($searches[0] && $searches[1])
               if ($searchQuery == " ")
               {
                  $searchQuery .= " AND (`".$data['data']."` BETWEEN '".$searches[0]."' and  '".$searches[1]."' ";
               }
               else
               {
                  $searchQuery .= " AND `".$data['data']."` BETWEEN '".$searches[0]."' and  '".$searches[1]."' ";

               }
            else if ($searches[0])
               if ($searchQuery == " ")
               {
                  $searchQuery .= " AND (`".$data['data']."` >= '".$searches[0]."' ";
               }
               else
               {
                  $searchQuery .= " AND `".$data['data']."` >= '".$searches[0]."' ";

               }
            else if ($searches[1])
               if ($searchQuery == " ")
               {
                  $searchQuery .= " AND (`".$data['data']."` <= '".$searches[1]."' ";
               }
               else
               {
                  $searchQuery .= " AND `".$data['data']."` <= '".$searches[1]."' ";

               }
         }
         else
         {
            if($data['data'] == "Address")
            {
               $data['search']['value'] =    strtolower($data['search']['value']);
            }
            $data['search']['value'] = str_replace(',,','',$data['search']['value']);
            $data['search']['value'] = str_replace(',','',$data['search']['value']);
            if ($searchQuery == " ")
            {
               $searchQuery .= " AND (`".$data['data']."` LIKE '".$data['search']['value']."%' ";
            }
            else
            {
               $searchQuery .= " AND `".$data['data']."` LIKE '".$data['search']['value']."%' ";

            }
         }
         $searchArray[$data['data']] = $data['search']['value'];
      }
   }
   if ($searchQuery != " ")
   {
      $searchQuery .= " ) ";

   }


   $records = getPtgrData("SELECT COUNT(*) AS allcount FROM pnlfast ;");
   // var_dump($records);exit();
   // $_POST['datestart'] = '2022-04-15';
   // $_POST['dateend'] = '2023-04-23';
   // var_dump($_POST['datestart']);exit();
   $totalRecords = $records[0]['allcount'];
   $empRecords = "";
   $totalRecordwithFilter = 0;

   if (!$_POST['datestart'])
   {
      $records = getPtgrData("SELECT COUNT(*) AS allcount FROM pnlfast WHERE true ".$searchQuery.";");
      $totalRecordwithFilter = $records[0]['allcount'];
      // var_dump("SELECT * FROM pnl WHERE true ".$searchQuery." ORDER BY \"".$columnName."\" ".$columnSortOrder." LIMIT  ".$rowperpage." OFFSET  ".$row.";");exit();
      $empRecords = getPtgrData("SELECT * FROM pnlfast WHERE true ".$searchQuery." ORDER BY \"".$columnName."\" ".$columnSortOrder." LIMIT  ".$rowperpage." OFFSET  ".$row.";");
   }
   else
   {
      if ($searchQuery)
         $searchQuery = " where true ".$searchQuery;

      $addressClause = '';
      if(isset($_POST['columns'][0]['search']['value']) && !empty($_POST['columns'][0]['search']['value']))
      {
         $result = pg_query($connection, "select \"Id\" from address where \"Hash\"='".$_POST['columns'][0]['search']['value']."'");
         if (!$result) {
            echo "An error occurred.\n";
            exit;
         }
         // $data = array();
         $empRecords = pg_fetch_all($result);
         // var_dump($empRecords[0]['Id']);exit();
         // $adrId = $conn->query("select Id from address where Hash='".$_POST['columns'][0]['search']['value']."'")->fetchObject()->Id;
         $addressClause = " AND swaps.\"OriginAdrId\" = ".$empRecords[0]['Id']." ";
         $transferAddressClause = " AND transfersonly.\"AddressId\" = ".$empRecords[0]['Id']." ";
      }


      /////////////////////////////////////////////////////////////

      $query = '
         SELECT  sl5.*
         FROM
         (
            SELECT  sl4.*
                     ,address."Hash" AS "Address",address."Id" AS "AddressId"
            FROM
            (
               SELECT  SUM(("RealizedPNL2" ) ) AS "TotalPNL"
                     ,0 AS "TransferAmount"
                     ,SUM("RealizedPNL" / 2)                AS "RealizedPNL"
                     ,SUM("UnRealizedPNL" / 2)              AS "UnRealizedPNL"
                     ,sl2."AddressId"                       AS "SubAddress"
                     ,(SUM(("TradeCount"/2) )  )                    AS "TxCount"
                     ,SUM("TradeCount")                        AS "TradeCount" /*
                     ,address."Hash"                        AS "Address"*/
               FROM
               (
                     SELECT  ROUND( CASE WHEN "Sold" > 0 AND "BoughtOrig" > 0 AND "SoldOrig" > 0 AND "Bought" > 0 THEN ( ( ("Received" / "SoldOrig") * LEAST("SoldOrig","BoughtOrig") ) - ( ("Spent" / "BoughtOrig") * LEAST("SoldOrig","BoughtOrig") ) ) ELSE 0 END,2 ) AS "RealizedPNL"
                        ,ROUND("Received" - "Spent",2) AS "RealizedPNL2"
                        ,ROUND( CASE WHEN ("Bought" - "Sold") > 0 THEN CASE WHEN "Sold" > 0 AND "Bought" > 0 THEN ( (("Bought" - "Sold") *("Received" / "Sold")) - (("Bought" - "Sold") *("Spent" / "Bought")) ) ELSE ("Bought" - "Spent") END ELSE 0 END,2 ) AS "UnRealizedPNL"
                        ,sl1."AddressId"               AS "AddressId"
                        ,"TradeCount"
                        ,"TokenId"
                     FROM
                     (
                        SELECT  ROUND(SUM("SoldOrig"),8)   AS "SoldOrig"
                                 ,ROUND(SUM("BoughtOrig"),8) AS "BoughtOrig"
                                 ,ROUND(SUM("Sold")::numeric,8)       AS "Sold"
                                 ,ROUND(SUM("Bought")::numeric,8)     AS "Bought"
                                 ,ROUND(SUM("Received")::numeric,8)   AS "Received"
                                 ,ROUND(SUM("Spent")::numeric,8)      AS "Spent"
                                 ,MIN("StartDate")           AS "StartDate"
                                 ,MAX("EndDate")             AS "EndDate"
                                 ,SUM("TradeCount")          AS "TradeCount"
                                 ,"TokenId"
                                 ,"AddressId"
                        FROM
                        (
                           SELECT  SUM(swaps."FromAmount")                 AS "SoldOrig"
                                    ,0                                       AS "BoughtOrig"
                                    ,SUM(ft."USDPriceV3" * (swaps."FromAmount" * POWER(10,fttoken."ExtraDigits"))) AS "Sold"
                                    ,0                                       AS "Spent"
                                    ,0                                       AS "Bought"
                                    ,SUM(tt."USDPriceV3" * (swaps."ToAmount" * POWER(10,tttoken."ExtraDigits")))   AS "Received"
                                    ,MIN(swaps."DateCreated")                AS "StartDate"
                                    ,MAX(swaps."DateCreated")                AS "EndDate"
                                    ,COUNT(DISTINCT swaps."TransactionId")   AS "TradeCount"
                                    ,swaps."FromTokenId"                     AS "TokenId"
                                    ,swaps."OriginAdrId"                     AS "AddressId"
                           FROM swaps
                           LEFT JOIN tokenprice AS "ft"
                           ON ft."Id" = swaps."FromTokenId"
                           LEFT JOIN token AS "fttoken"
                           ON fttoken."Id" = swaps."FromTokenId"
                           LEFT JOIN tokenprice AS "tt"
                           ON tt."Id" = swaps."ToTokenId"
                           LEFT JOIN token as "tttoken"
						         ON tttoken."Id" = swaps."ToTokenId"
                           WHERE (swaps."FromTokenId" not IN ('.$excludedToken.') OR swaps."ToTokenId" not IN ('.$excludedToken.'))'.
                           'AND swaps."DateCreated" BETWEEN DATE(\''.$_POST["datestart"].'\') and DATE(\''.$_POST["dateend"].'\') '.$addressClause.'
                           GROUP BY swaps."OriginAdrId", swaps."FromTokenId"
                           UNION ALL
                           SELECT  0                                       AS "SoldOrig"
                                    ,SUM(swaps."ToAmount")                   AS "BoughtOrig"
                                    ,0                                       AS "Sold"
                                    ,SUM(ft."USDPriceV3" * (swaps."FromAmount" * POWER(10,fttoken."ExtraDigits"))) AS "Spent"
                                    ,SUM(tt."USDPriceV3" * (swaps."ToAmount" * POWER(10,tttoken."ExtraDigits")))   AS "Bought"
                                    ,0                                       AS "Received"
                                    ,MIN(swaps."DateCreated")                AS "StartDate"
                                    ,MAX(swaps."DateCreated")                AS "EndDate"
                                    ,COUNT(DISTINCT swaps."TransactionId")   AS "TradeCount"
                                    ,swaps."ToTokenId"                       AS "TokenId"
                                    ,swaps."OriginAdrId"                     AS "AddressId"
                           FROM swaps
                           LEFT JOIN tokenprice AS "tt"
                           ON tt."Id" = swaps."ToTokenId"
                           LEFT JOIN token as "tttoken"
						         ON tttoken."Id" = swaps."ToTokenId"
                           LEFT JOIN tokenprice AS "ft"
                           ON ft."Id" = swaps."FromTokenId"
                           LEFT JOIN token AS "fttoken"
                           ON fttoken."Id" = swaps."FromTokenId"
                           WHERE (swaps."FromTokenId" not IN ('.$excludedToken.') OR swaps."ToTokenId" not IN ('.$excludedToken.'))'.
                           'AND swaps."DateCreated" BETWEEN DATE(\''.$_POST["datestart"].'\') and DATE(\''.$_POST["dateend"].'\') '.$addressClause.'
                           GROUP BY  swaps."OriginAdrId", swaps."ToTokenId"
                        ) AS sl0
                        GROUP BY  sl0."TokenId", sl0."AddressId"
                     ) AS sl1
               ) AS sl2
               GROUP BY  sl2."AddressId"
            ) AS sl4
            LEFT JOIN address
            ON address."Id" = sl4."SubAddress"
            /*where address."IsContract" = False*/
            ) AS sl5
            '.$searchQuery.' ORDER BY "'.$columnName.'" '.$columnSortOrder.'   LIMIT  '.$rowperpage.' OFFSET  '.$row.';
      ';
      // var_dump($query);exit();

      /////////////////////////////////////////////////////////////

      $query = str_replace('`','"',$query);
      $result = pg_query($connection, $query);
      if (!$result) {
         echo "An error occurred.\n";
         exit;
      }
      // $data = array();
      $empRecords = pg_fetch_all($result);


      // var_dump( $empRecords);
      // exit();
      $records = getPtgrData("SELECT COUNT(*) AS allcount FROM pnl  ".$searchQuery.";");
      //       var_dump( "SELECT COUNT(*) AS allcount FROM pnl ".$searchQuery.";");
      // exit();
      $totalRecordwithFilter = $records[0]['allcount'];

   }
   $data = array();

   foreach ($empRecords as $row) {
      $data[] = array(

         // "AddressId"=>$row['AddressId'],
         // "Address"=> $row['Address'],
         "Address"=> $exportCsv ? $row['Address'] : "<a style='color:#09c' class='txaddress' target='_blank' href='https://etherscan.io/address/".$row['Address']."'>".$row['Address']."</a>",
         "TotalPNL"=>shortenUsd($row['TotalPNL']),//number_format($row['PnlUSDValue'],2),
         "RealizedPNL"=> shortenNumber( $row['RealizedPNL']),
         "UnRealizedPNL"=> shortenNumber( $row['UnRealizedPNL']),
         "TxCount"=>round( $row['TxCount'],0),
         "TransferAmount"=>shortenNumber( $row['TransferAmount'])?shortenNumber( $row['TransferAmount']):0,
      );
   }

   // Response
   $response = array(
      "draw" => intval($draw),
      "iTotalRecords" => $totalRecords,
      "iTotalDisplayRecords" => $totalRecordwithFilter,
      "aaData" => $data
   );
   if($exportCsv)
   {
      // exit('dhdhdg');
      $csv = 'AddressPNL.csv';
      header('Content-Type: application/json; charset=utf-8');
      header('Content-Type: application/csv');
      header('Content-Disposition: attachment; filename="'.$csv.'";');
      $file_pointer = fopen($csv, 'w');
      $data = implode("\n", array_map(function($dt) {
         return implode(',', array_values($dt))."\n";
      }, $data));
      $data = implode(",",
         ['Address','Total P&L','Realized Pnl','UnRealized Pnl','TxCount','Transfer Value']//,'TxCount','TradeCount','TradeCount'
         )."\n".$data;
      echo $data;
      exit();
   }
   else
   echo json_encode($response);
?>