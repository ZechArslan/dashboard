<?php
$host        = "localhost";
$port        = "5432";
$dbname      = "tgbot1";
$user = "postgres";
$password = "Oa2kY";
$connection = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
if (!$connection) {
    echo "An error occurred.\n";
    exit;
}
function getPtgrData($query)
{
   global $connection;
   $query = str_replace('`','"',$query);
   // var_dump($query);exit();
   $result = pg_query($connection, $query);
   if (!$result) {
      echo "An error occurred.\n";
      exit;
   }

   $empRecords = pg_fetch_all($result);
   return $empRecords;
}

