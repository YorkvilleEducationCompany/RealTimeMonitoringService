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
 * @package local_rtms
 * @author Andrew Normore<anormore@yorkvilleu.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2019 onwards Yorkville Education Company
 */



if (is_siteadmin()) {
    $settings = new admin_settingpage('local_rtms', get_string('pluginname', 'local_rtms'));
    $ADMIN->add('localplugins', $settings);
}

// ---------------------
$RtmsKey = get_config('local_rtms', 'key');

//var_dump("//".$_SERVER['SERVER_NAME']); die();

// RTMS Link
$settings->add( new admin_setting_configempty('local_rtms/local_rtms',
        "RTMS URL",
        "Test run:<hr /><a target='_blank' href='".$CFG->wwwroot."/local/rtms/?key=".$RtmsKey."'>".$CFG->wwwroot."/local/rtms/?KEY=".$RtmsKey."</a><hr /><a target='_blank' href='".$CFG->wwwroot."/local/rtms/?debugMessages=true&key=".$RtmsKey."'>".$CFG->wwwroot."/local/rtms/?KEY=".$RtmsKey."&debugMessages=true</a><hr />You must add a new cron job, separate to the regular Moodle cron, like this: <hr />*/1 * * * * wget -O - ".$CFG->wwwroot."/local/rtms/?key=".$RtmsKey." > /dev/null 2>&1"
    )
);


// KEY?
$settings->add(
    new admin_setting_configtext(
        'local_rtms/key',
        "URL Key",
        '',
        "",
        PARAM_TEXT
    )
);

// AMOUNT TO PROCESS
$settings->add(
    new admin_setting_configselect(
        'local_rtms/amountToProcess',
        "Amount of Courses to Process",
        '',
        10,
        array(
            1 => '1 (LOW CPU, SLOWER RESULTS)',
            5 => '5',
            10 => '10',
            25 => '25',
            50 => '50 (MID CPU, FAST RESULTS)',
            100 => '100',
            200 => '200 (HIGH CPU, VERY FAST RESULTS)',

        )
    )
);

// PLUGINS
$settings->add(
	new admin_setting_configcheckbox('local_rtms/plugin_NED_block',
		"NED_block.php",
    	"Enabled",
    	0
    )
);
$settings->add(
	new admin_setting_configcheckbox('local_rtms/plugin_YU_overdueAssignmentsToZero',
		"YU_overdueAssignmentsToZero.php",
    	"Enabled",
    	0
    )
);