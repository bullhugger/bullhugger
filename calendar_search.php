<?php
require_once("login.class.php");
require_once("db.class.php");
require_once("config.class.php");
require_once("page.class.php");
require_once("user.class.php");

session_start();
$login = new Login();
if($login->status) {
  @$start = $_REQUEST["start"];
  @$end   = $_REQUEST["end"];
  $arr_result = array();
  $db = new Database();
  $db->open();
  $user = User::GetUser($_SESSION["user"], $db);
  $query = "SELECT * FROM ".TABLE_CALENDAR." WHERE dtstart<='$start' AND dtend>='$dtend'";
  $result = $db->query($query);
  if($result !== false) {
    while($row = $db->fetch($result)) {
      $arr_result[]["id"]          = $row["id"];
      $arr_result[]["title"]       = $row["title"];
      $arr_result[]["description"] = $row["description"];
      $arr_result[]["start"]       = $row["dtstart"];
      $arr_result[]["end"]         = $row["dtend"];
      $arr_result[]["calid"]       = $row["calid"];
      $arr_result[]["assign"]      = $row["assignid"];
    }
  }
  else {
    echo("Unable to retrieve calendar.");
  }
  $db->close();
  unset($db);
  echo(json_endcode($arr_result));
}
unset($login);
?>