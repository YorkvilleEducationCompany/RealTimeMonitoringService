<?php

// RTMS LOGS
// ---------
?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.bundle.js"></script>
<?php

require_once("../../config.php");

// Security.
$context = context_system::instance();
require_login();
require_capability('moodle/site:config', $context);

// Page boilerplate stuff.
$url = new moodle_url('/local/rtms/logs.php');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$title = "RTMS Logs";
$PAGE->set_title($title);
$PAGE->set_heading($title);




echo $OUTPUT->header();

?>

<canvas id="myChart" width="400" height="100"></canvas>
<script>
var ctx = document.getElementById('myChart');
var myChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [
       		<?php 
	    		$logs = $DB->get_records_sql('SELECT * FROM {rtms_logs} ORDER BY id DESC LIMIT 100 ', array(1));
				foreach($logs as $log){
				  echo "'".gmdate("Y-m-d h:i:s", $log->time)."',";
				}
	    	?>
        ],
        datasets: [{
            label: 'Time to Complete',
            data: [
            	<?php 
            		$logs = $DB->get_records_sql('SELECT * FROM {rtms_logs} ORDER BY id DESC LIMIT 100 ', array(1));
					foreach($logs as $log){
					  echo $log->runtime.",";
					}
            	?>
            ],
            backgroundColor: [
                'rgba(63, 163, 36, 0.2)',
            ],
            borderColor: [
                'rgba(63, 163, 36, 1)',
            ],
            borderWidth: 1
        },{
            label: 'Courses Processed',
            data: [
            	<?php 
            		$logs = $DB->get_records_sql('SELECT * FROM {rtms_logs} ORDER BY id DESC LIMIT 100 ', array(1));
					foreach($logs as $log){
					  echo $log->amount.",";
					}
            	?>
            ],
            backgroundColor: [
                'rgba(0, 90, 255, 0.08)',
            ],
            borderColor: [
                'rgba(0, 90, 255, 0.5)',
            ],
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true
                }
            }]
        }
    }
});
</script>

<?php

$logs = $DB->get_records_sql('SELECT * FROM {rtms_logs} ORDER BY id DESC LIMIT 100 ', array(1));
foreach($logs as $log){
  //echo "<p>".$log->runtime."</p>";
}


echo $OUTPUT->footer();