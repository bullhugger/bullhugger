<?php
require_once("../page.class.php");
require_once("../login.class.php");
require_once("../user.class.php");
require_once("../util.meta.php");

$login = new Login();
if($login->status) {
  @$id     = $_REQUEST["id"];
  @$action = $_REQUEST["action"];
  $db = new Database();
  $db->open();
  $user = User::GetUser($_SESSION["user"], $db);
  Page::PrintHeader("<link rel=\"stylesheet\" href=\"".URL_HOME.DIR_HOME."vendor/jquery-ui/jquery-ui.min.css\">
	<link rel=\"stylesheet\" href=\"".URL_HOME.DIR_HOME."vendor/fullcalendar/lib/main.min.css\">
  <link href=\"".URL_HOME.DIR_HOME."vendor/bootstrap-timepicker/css/bootstrap-timepicker.min.css\" rel=\"stylesheet\">
  <script src=\"".URL_HOME.DIR_HOME."vendor/jquery-ui/jquery-ui.min.js\"></script>
  <script src=\"".URL_HOME.DIR_HOME."vendor/fullcalendar/moment.min.js\"></script>
  <script src=\"".URL_HOME.DIR_HOME."vendor/fullcalendar/lib/main.min.js\"></script>
  <script src=\"".URL_HOME.DIR_HOME."vendor/bootstrap-timepicker/js/bootstrap-timepicker.min.js\"></script>
  <style>
    .fc-event, .fc-event:hover { cursor:pointer; }
  </style>\n");
  Page::PrintMenu($user);
  Page::PrintMessage();
  $module = basename(__FILE__, ".php");
  @$search = isset($_REQUEST["s"]) ? $_REQUEST["s"] : $user["pref"][$module]["search"];
  if($action == "filter") {
    $filter = $_REQUEST["filter"];
  }
  else {
    $filter = $user["pref"][$module]["filter"];
  }
  // Save user search/filter preference
  if(isset($_REQUEST["s"])) {
    $user["pref"][$module]["search"] = $search;
  }
  $user["pref"][$module]["filter"] = $filter;
  $objUser = new User();
  $objUser->id = $user["loginid"];
  $objUser->pref = $user["pref"];
  $objUser->savePreference();
  unset($objUser);
  // Filter options
  $select_company = GetCompany($db, $user["pref"][$module]["filter"]["company"]);
  $query_search = "";
  $label_search = "";
  if(!empty($search)) {
    $query_search = " WHERE (`name` LIKE '%$search%' OR `code` LIKE '%$search%' OR regno LIKE '%$search%' OR regno2 LIKE '%$search%' OR address LIKE '%$search%')";
    $label_search = "Search result for \"$search\".";
  }
  foreach($filter as $filter_key => $filter_value) {
    switch($filter_key) {
      case "date_start" :
        if(!empty($filter_value)) {
          $query_search = empty($query_search) ? " WHERE id={$filter["company"]}" : "$query_search AND id={$filter["company"]}";
        }
        break;
      case "type" :
        $query_search_temp = "";
        if(count($filter[$filter_key]) > 0) {
          $query_search_temp .= " `type` IN ("; 
          foreach($filter[$filter_key] as $type) {
            $query_search_temp .= "'$type',";
          }
          $query_search_temp = rtrim($query_search_temp, ",").")";
        }
        $query_search = empty($query_search) ? " WHERE $query_search_temp" : "$query_search AND $query_search_temp";
        break;
    }
  }
  $check_type = array("mine" => "", "public" => "");
  if(isset($filter["type"]) && (count($filter["type"]) > 0)) {
    foreach($filter["type"] as $type) {
      $check_type[$type] = " checked";
    }
  }
  $event = "";
  $query = "SELECT cal.*, u.name FROM ".TABLE_CALENDAR." cal LEFT JOIN ".TABLE_USER." u ON cal.assignid=u.loginid WHERE MONTH(cal.dtstart)=MONTH(CURDATE())".$query_search;
  $result = $db->query($query);
  if($result !== false) {
    while($row = $db->fetch($result)) {
      $dtstart     = str_replace(" ", "T", $row["dtstart"]);
      $dtend       = str_replace(" ", "T", $row["dtend"]);
      $description = addslashes($row["description"]);
      $creator     = ($row["creatorid"] == $user["loginid"]) ? "true" : "false";
      $color       = "#BBBBBB";
      if(preg_match("\b{$user["loginid"]}\b", $row["assignid"]) == 1) {
        $color = "#CC0000";
      }
      else if($row["coid"] == 0) {
        $color = "#555299";
      }
      $event .= "{title: '{$row["title"]}', start: '$dtstart', end: '$dtend', description: '$description', id: '{$row["id"]}', calid: '{$row["calid"]}', cr: $creator, assign: '{$row["name"]}', color: '$color'}, ";
    }
  }
  else {
    echo($query);
  }
  if(!empty($event)) {
    $event = rtrim($event, ", ");
    $event = ", events: [ $event";
    $event .= "]";
  }
  $db->close();
  unset($db);
  echo("<main>
  <div class=\"container\">
    <h1><i class=\"fas fa-check-square\"></i> Dashboard</h1>
    <div id=\"calendar\">
    </div>
  </div>");

  $btn = "";
  if($user["allow_calendar_manage"]) {
    $btn = "<button type=\"button\" class=\"btn btn-primary px-4\" data-toggle=\"modal\" data-target=\"#dlgCreate\">Create Event</button> ";
    echo("<form id=\"frmEdit\" action=\"master.action.php\" method=\"post\">
      <div class=\"modal fade in\" id=\"dlgEdit\">
        <div class=\"modal-dialog modal-lg\">
          <div class=\"modal-content\">
            <div class=\"modal-header\">
              <h5 class=\"modal-title\">Edit Task</h5>
              <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                <span aria-hidden=\"true\">&times;</span>
              </button>
            </div>            
            <div class=\"modal-body\">
              <input type=\"hidden\" name=\"id\" value=\"\">
              <div class=\"form-group\"><label>Title</label><input type=\"text\" class=\"form-control\" name=\"title\" required></div>
              <div class=\"form-group\"><label>Description</label><textarea class=\"form-control\" name=\"description\"></textarea></div>
              <div class=\"form-group row\">
                <div class=\"col-lg-6\"><label>Start Date</label><input type=\"text\" class=\"form-control\" name=\"dtstart\" required></div>
                <div class=\"col-lg-6\"><label>End Date</label><input type=\"text\" class=\"form-control\" name=\"dtend\"></div>
              </div>
              <div class=\"form-group\"><label>Assign To</label><select class=\"form-control\" name=\"assign\"><option value=\"\"></option></select></div>
              <div class=\"form-check\"><input type=\"checkbox\" class=\"form-check-input\" name=\"type\"><label class=\"form-check-label\">Company event</label></div>
            </div>
            <div class=\"modal-footer\">
              <button type=\"submit\" class=\"btn btn-success\" name=\"action\" value=\"Update\" onClick=\"return Validate('#frmEdit');\">Update</button>
              <button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </form>");
  }
  echo("<div class=\"modal fade in\" id=\"dlgView\">
    <div class=\"modal-dialog\">
      <div class=\"modal-content\">
        <div class=\"modal-header\">
          <h3 class=\"modal-title\" id=\"viewTitle\">View Task</h3>
          <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
            <span aria-hidden=\"true\">&times;</span>
          </button>
        </div>            
        <div class=\"modal-body\">
          <input type=\"hidden\" name=\"id\" value=\"\">
          <input type=\"hidden\" name=\"calid\" value=\"\">
          <h6 id=\"viewDesc\"></h6>
          <div class=\"row\">
            <div class=\"col-sm-6 d-flex\"><label>Start</label><div class=\"font-weight-bold ml-1\" id=\"viewStart\"></div></div>
            <div class=\"col-sm-6 d-flex\"><label>End</label><div class=\"font-weight-bold ml-1\" id=\"viewEnd\"></div></div>
          </div>
          <div class=\"row\"><div class=\"col-12\"><label>Assigned To</label> <span class=\"badge badge-secondary\" id=\"viewAssign\"></span></div>
        </div>
        <div class=\"modal-footer\">
          <button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Close</button>
        </div>
      </div>
    </div>
  </div>
  <form id=\"frmCreate\" action=\"calendar.action.php\" method=\"post\">
    <div class=\"modal fade in\" id=\"dlgCreate\">
      <div class=\"modal-dialog modal-lg\">
        <div class=\"modal-content\">
          <div class=\"modal-header\">
            <h5 class=\"modal-title\">New Event</h5>
            <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
              <span aria-hidden=\"true\">&times;</span>
            </button>
          </div>            
          <div class=\"modal-body\">
            <div class=\"form-group\"><label>Title</label><input type=\"text\" class=\"form-control\" name=\"title\" required></div>
            <div class=\"form-group\"><label>Description</label><textarea class=\"form-control\" name=\"description\"></textarea>
            <div class=\"form-group row\">
              <div class=\"col-lg-6\"><label>Start Date</label><input type=\"text\" class=\"form-control\" name=\"dtstart\" required></div>
              <div class=\"col-lg-6\"><label>End Date</label><input type=\"text\" class=\"form-control\" name=\"dtend\"></div>
            </div>
            <div class=\"form-group\"><label>Assign To</label><select class=\"form-control\" name=\"assign\"><option value=\"\"></option></select></div>
            <div class=\"form-check\"><input type=\"checkbox\" class=\"form-check-input\" name=\"type\"><label class=\"form-check-label\">Company event</label></div>
          </div>
          <div class=\"modal-footer\">
            <button type=\"submit\" class=\"btn btn-primary\" name=\"action\" value=\"Save\" onClick=\"return Validate('#frmCreate');\">Save</button>
            <button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Cancel</button>
          </div>
        </div>
      </div>
    </div>
    </form>
    <div id=\"frmMyFilter\" class=\"container-fluid dropdown-menu\" role=\"dialog\">
      <strong>Advanced Filter</strong>
      <div class=\"row\">
        <div class=\"col-lg-4\"><label>From Date</label></div>
        <div class=\"col-lg-8\"><input type=\"text\" class=\"form-control\" id=\"filter_ds\" name=\"filter[date_start]\" placeholder=\"dd/mm/yyyy\" value=\"{$filter["date_start"]}\"></div>
      </div>
      <div class=\"row\">
        <div class=\"col-lg-4\"><label>To Date</label></div>
        <div class=\"col-lg-8\"><input type=\"text\" class=\"form-control\" id=\"filter_de\" name=\"filter[date_end]\" placeholder=\"dd/mm/yyyy\" value=\"{$filter["date_end"]}\"></div>
      </div>
      <div class=\"row\">
        <div class=\"col-lg-4 form-group\"><label>Type</label></div>
        <div class=\"col-lg-4 form-check\"><label class=\"form-check-label\"><input type=\"checkbox\" class=\"form-check-input\" name=\"filter[type][]\" value=\"public\"{$check_type["mine"]}> My events</label></div>
        <div class=\"col-lg-4 form-check\"><label class=\"form-check-label\"><input type=\"checkbox\" class=\"form-check-input\" name=\"filter[type][]\" value=\"private\"{$check_type["public"]}> Company events</label></div>
      </div>
      <div class=\"text-center mt-2\"><button type=\"submit\" class=\"btn btn-primary\">Apply Filter</button><input type=\"hidden\" name=\"action\" value=\"filter\"></div>
    </div>
  </main>\n");
  Page::PrintFooter($btn,
  "<script>
    function showNotification(msg) {
      if(!(\"Notification\" in window)) {
        alert('This browser does not support desktop notification');
      }
      else if(Notification.permission === \"granted\") {
        var notification = new Notification(msg, {icon: 'images/logo.png'});
      }
      else {
        Notification.requestPermission().then(function (permission) {
          if(permission === \"granted\") {
            var notification = new Notification(msg, {icon: 'images/due.png'});
          }
        });
      }
    }
    $(function() {
      var calendarDiv = document.getElementById('calendar');
      var calendar = new FullCalendar.Calendar(calendarDiv, {
        initialView: 'dayGridMonth',
        headerToolbar: {
          left  : 'title',
          center: 'dayGridMonth,timeGridWeek,timeGridDay',
          right : 'prev,next'
        },
        eventClick : function(info) {
          $('#dlgView').modal('show');
          $('#dlgView input[name=\"id\"]').val(info.event.id);
          $('#dlgView input[name=\"calid\"]').val(info.event.calid);
          $('#viewTitle').html(info.event.title);
          $('#viewDesc').html(info.event.extendedProps.description);
          $('#viewAssign').html(info.event.extendedProps.assign);
          var dtstart = moment(info.event.start).format(\"DD/MM/YYYY\");
          $('#viewStart').html(dtstart);
          if(info.event.end) {
            var dtend = moment(info.event.end).format(\"DD/MM/YYYY\");
            $('#viewEnd').html(dtend);
          }
          showNotification('This task is due!' + String.fromCharCode(10) + info.event.title + ' : ' + info.event.extendedProps.description);
        }
        $event
      });
      calendar.render();
      $('input[name=\"dtstart\"]').datepicker({
        dateFormat: \"dd/mm/yy\"
      });
      $('input[name=\"dtend\"]').datepicker({
        dateFormat: \"dd/mm/yy\"
      });
      $(\".timepicker\").timepicker({
        timeFormat: \"hh:mm p\"
      });
    });
    function Validate(frm) {
      if($(frm + ' input[name=\"regno\"]').val().indexOf('-') > 0) {
        alert('Please enter Registration No. without dash(-).');
        $(frm + ' input[name=\"regno\"]').focus();
        return false;
      }
    }
    $('#frmCreate').on('shown.bs.modal', function(e) {
      $('#frmCreate input[name=\"dtstart\"]').datepicker('setDate', new Date());
    });
    $('#frmEdit').on('shown.bs.modal', function(e) {
      $.ajax({
        url: \"".URL_HOME.DIR_HOME."master_search\",
        dataType: \"json\",
        data: { a: \"master\", id: $(e.relatedTarget).data(\"id\") }
      }).done(function(data) {
        $('#frmEdit input[name=\"id\"]').val(data.id);
        $('#frmEdit input[name=\"company\"]').val(data.name);
        $('#frmEdit input[name=\"code\"]').val(data.code);
        $('#frmEdit input[name=\"regno\"]').val(data.regno);
        $('#frmEdit input[name=\"regno2\"]').val(data.regno2);
        $('#frmEdit textarea[name=\"address\"]').val(data.address);
        $('#frmEdit select[name=\"type\"]').val(data.type);
        $('#lstEditBod').empty();
        for(var d of data.bod) {
          AddBod('#lstEditBod', d);
        }
      });
    });
  </script>");
}
else {
  Page::PrintLogin();
}
?>