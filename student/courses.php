<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 *
 * @package    local_intelliboard
 * @copyright  2017 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

require('../../../config.php');
require_once($CFG->dirroot .'/local/intelliboard/locallib.php');
require_once($CFG->dirroot .'/local/intelliboard/student/lib.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$search = optional_param('search', '', PARAM_ALPHANUMEXT);

require_login();
require_capability('local/intelliboard:students', context_system::instance());

if(!get_config('local_intelliboard', 't1') or !get_config('local_intelliboard', 't3')){
	throw new moodle_exception('invalidaccess', 'error');
}
$email = get_config('local_intelliboard', 'te1');
$params = array(
	'do'=>'learner',
	'mode'=> 1
);
$intelliboard = intelliboard($params);
if (isset($intelliboard->content)) {
    $factorInfo = json_decode($intelliboard->content);
} else {
	$factorInfo = '';
}

if($courseid and $action == 'details'){
	$progress = intelliboard_learner_course_progress($courseid, $USER->id);
	$json_data = array();
	foreach($progress[0] as $item){
		$l = 0;
		if(isset($progress[1][$item->timepoint])){
			$d = $progress[1][$item->timepoint];
			$l = round($d->grade,2);
		}
		$item->grade = round($item->grade,2);
		$tooltip = "<div class=\"chart-tooltip\">";
		$tooltip .= "<div class=\"chart-tooltip-header\">".date('D, M d Y', $item->timepoint)."</div>";
		$tooltip .= "<div class=\"chart-tooltip-body clearfix\">";
		$tooltip .= "<div class=\"chart-tooltip-left\"><span>". round($item->grade, 2)."%</span> ".get_string('current_grade','local_intelliboard')."</div>";
		$tooltip .= "<div class=\"chart-tooltip-right\"><span>". round($l, 2)."%</span> ".get_string('average_grade','local_intelliboard')."</div>";
		$tooltip .= "</div>";
		$tooltip .= "</div>";
		$item->timepoint = $item->timepoint*1000;
		$json_data[] = array($item->timepoint, $item->grade, $tooltip, $l, $tooltip);
	}
	echo json_encode($json_data);
	exit;
}

$PAGE->set_url(new moodle_url("/local/intelliboard/student/courses.php", array("search"=>s($search))));
$PAGE->set_pagetype('courses');
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->set_heading(get_string('intelliboardroot', 'local_intelliboard'));
$PAGE->requires->jquery();
$PAGE->requires->js('/local/intelliboard/assets/js/jquery.circlechart.js');
$PAGE->requires->css('/local/intelliboard/assets/css/style.css');

$courses = intelliboard_data('courses', $USER->id);
$totals = intelliboard_learner_totals($USER->id);

$t16 = get_config('local_intelliboard', 't16');
$t17 = get_config('local_intelliboard', 't17');
$t18 = get_config('local_intelliboard', 't18');
$t19 = get_config('local_intelliboard', 't19');
$t20 = get_config('local_intelliboard', 't20');
$t21 = get_config('local_intelliboard', 't21');
$t22 = get_config('local_intelliboard', 't22');
$t47 = get_config('local_intelliboard', 't47');

echo $OUTPUT->header();
?>
<?php if(!isset($intelliboard) || !$intelliboard->token): ?>
	<div class="alert alert-error alert-block fade in " role="alert"><?php echo get_string('intelliboardaccess', 'local_intelliboard'); ?></div>
<?php else: ?>
<div class="intelliboard-page intelliboard-student">
	<?php include("views/menu.php"); ?>

		<div class="intelliboard-search clearfix">
			<form action="<?php echo $PAGE->url; ?>" method="GET">
				<span class="pull-left"><input class="form-control" name="search" type="text" value="<?php echo format_string($search); ?>" placeholder="<?php echo get_string('type_here', 'local_intelliboard');?>" /></span>
				<button class="btn btn-default"><?php echo get_string('search');?></button>
				<span>
					<a class="active" value="grid" href=""><i class="ion-android-apps"></i></a>
					<a href="" value="list"><i class="ion-android-menu"></i></a>
				</span>

			</form>
		</div>
		<div class="intelliboard-overflow">
			<ul class="intelliboard-courses-grid clearfix">
			<?php $i=0; foreach($courses['data'] as $item): $i++; ?>
				<li class="f<?php echo $t47+1; ?> course-item">
					<div class="course-info clearfix">
						<div class="icon">
							<i class="ion-social-buffer"></i>
							<?php if($t22): ?>
							<span title="<?php echo get_string('enrolled_date','local_intelliboard');?>"><?php echo date("d F", $item->timemodified); ?></span>
							<?php endif; ?>
						</div>
						<div class="title">
							<strong><?php echo format_string($item->fullname); ?></strong>
							<?php if($t16): ?>
								<?php if($teachers = get_users_by_capability(context_course::instance($item->id), 'moodle/course:update', '', '', '', '', '', null, false)): ?>

									<?php $teachers = sort_by_roleassignment_authority($teachers, context_course::instance($item->id)); ?>
									<?php foreach($teachers as $teacher): ?>
										<p title="<?php echo get_string('teacher','local_intelliboard');?>"><?php echo $OUTPUT->user_picture($teacher, array('size'=>20)); ?> <?php echo fullname($teacher); ?></p>
									<?php break; endforeach; ?>
								<?php endif; ?>
							<?php endif; ?>
							<?php if($t17): ?>
								<span title="<?php echo get_string('category','local_intelliboard');?>"><i class="ion-ios-folder-outline"></i> <?php echo format_string($item->category); ?></span>
							<?php endif; ?>
						</div>
						<?php if($t19): ?>
						<div class="grade" title="<?php echo get_string('current_grade','local_intelliboard');?>">
							<div class="circle-progress"  data-percent="<?php echo (int)$item->grade; ?>"></div>
						</div>
						<?php endif; ?>
					</div>
					<div class="course-stats clearfix">
						<?php if($t18): ?>
						<div>
							<span><?php echo get_string('completion','local_intelliboard');?></span>
							<p><?php echo (int)$item->completedmodules; ?>/<?php echo (int)$item->modules; ?></p>
						</div>
						<?php endif; ?>

						<?php if($t20): ?>
						<div>
							<span><?php echo get_string('class_average','local_intelliboard');?></span>
							<p><?php echo (int)$item->average; ?>%</p>
						</div>
						<?php endif; ?>

						<?php if($t21): ?>
						<div>
							<span><?php echo get_string('time_spent','local_intelliboard');?></span>
							<p><?php echo ($item->duration)?seconds_to_time(intval($item->duration)):'-'; ?></p>
						</div>
						<?php endif; ?>
					</div>
					<div class="course-chart" id="course-chart<?php echo format_string($item->id); ?>"></div>
					<div class="course-more clearfix">
						<span>
							<?php if($item->timecompleted): ?><a title="<?php echo get_string('completed_on','local_intelliboard',date("m/d/Y", $item->timecompleted));?>" href="#completed"><i class="ion-android-done-all"></i></a><?php endif; ?>
							<?php //<a href=""><i class="ion-alert-circled"></i></a> ?>
							<?php if($item->certificates): ?><a title="<?php echo get_string('you_have_certificates','local_intelliboard',s($item->certificates));?>" href="#certificates"><i class="ion-ribbon-b"></i></a><?php endif; ?>
							<a class="course-details" href="" value="<?php echo $item->id; ?>"><i class="ion-podium"></i>
								<strong><?php echo get_string('close','local_intelliboard');?></strong>
							</a>
						</span>
						<a class="more" href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=<?php echo $item->id; ?>"><?php echo get_string('view_course_details','local_intelliboard');?></a>
					</div>
				</li>
			<?php endforeach; ?>
			</ul>
			<?php echo $courses['pagination']; ?>
		</div>
	<?php include("../views/footer.php"); ?>
</div>
<script type="text/javascript"
          src="https://www.google.com/jsapi?autoload={
            'modules':[{
              'name':'visualization',
              'version':'1',
              'packages':['corechart']
            }]
          }"></script>
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('.course-details').click(function(e){
			e.preventDefault();
			var id = jQuery(this).attr('value');
			var icon = jQuery(this).find('i');

			if(jQuery(this).hasClass('active')){
				jQuery('.intelliboard-courses-grid').removeClass('list cview');
				jQuery('.course-item').removeClass('active');
				jQuery(this).removeClass('active');
			}else{
				jQuery('.intelliboard-courses-grid').addClass('list cview');
				jQuery('.course-item').removeClass('active');
				jQuery(this).addClass('active')
				jQuery(this).parents('.course-item').addClass('active');

				jQuery.ajax({
					url: '<?php echo $PAGE->url; ?>&action=details&courseid='+id,
					dataType: "json",
					beforeSend: function(){
						jQuery(icon).attr('class','ion-ios-loop-strong ion-spin-animation');
					}
				}).done(function( data ) {
					jQuery(icon).attr('class','ion-podium ion-spin-animation');

					var json_data = [];
					for(var i = 0; i < data.length; i++){
						var item = data[i];
						json_data.push([new Date(item[0]), item[1], item[2], item[3], item[4]]);
					}

					var data = new google.visualization.DataTable();
					data.addColumn('date', 'Time');
					data.addColumn('number', 'My grade progress');
					data.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
					data.addColumn('number', 'Average grade');
					data.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
					data.addRows(json_data);

					var options = <?php echo format_string($factorInfo->CoursesCalculation); ?>;
					var chart = new google.visualization.LineChart(document.getElementById('course-chart'+id));
					chart.draw(data, options);
				});
			}
		});

		jQuery('.circle-progress').percentcircle(<?php echo format_string($factorInfo->GradesFCalculation); ?>);
		jQuery('.intelliboard-search span a').click(function(e){
			e.preventDefault();
			jQuery(this).parent().find('a').removeClass("active");
			jQuery(this).addClass("active");
			jQuery('.intelliboard-courses-grid').removeClass('list');
			jQuery('.intelliboard-courses-grid').addClass(jQuery(this).attr('value'));
			jQuery('.intelliboard-courses-grid').removeClass('cview');
			jQuery('.course-item').removeClass('active');
		});
	});
</script>
<?php endif; ?>
<?php echo $OUTPUT->footer();
