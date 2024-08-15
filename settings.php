<?php
/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     format_btns
 * @copyright   2023 Jhon Rangel <jrangelardila@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading(
        'format_btns/color_font',
        get_string('settings', 'format_btns'), null
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'format_btns/fontcolor',
        get_string('fontcolor', 'format_btns'),
        get_string('fontcolor_desc', 'format_btns'),
        '#FFFFFF' // Default value.
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'format_btns/bgcolor',
        get_string('bgcolor', 'format_btns'),
        get_string('bgcolor_desc', 'format_btns'),
        '#4d2433' // Default value.
    ));

    $options = array(
        'number' => get_string('option1', 'format_btns'),
        'leter_lowercase' => get_string('option2', 'format_btns'),
        'leter_uppercase' => get_string('option3', 'format_btns'),
        'roman_numbers' => get_string('option4', 'format_btns')
    );

    $settings->add(new admin_setting_configselect(
        'format_btns/selectoption',
        get_string('numeretion', 'format_btns'),
        get_string('numeretion_desc', 'format_btns'),
        'option1', // Default value.
        $options
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'format_btns/fontcolor_selected',
        get_string('fontcolor_selected', 'format_btns'),
        get_string('fontcolor_selected_desc', 'format_btns'),
        '#e7e7e7' // Default value.
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'format_btns/bgcolor_selected',
        get_string('bgcolor_selected', 'format_btns'),
        get_string('bgcolor_selected_desc', 'format_btns'),
        '#959494' // Default value.
    ));

    $settings->add(new admin_setting_configstoredfile(
        'format_btns/image_sections',
        get_string('selectd_file', 'format_btns'),
        get_string('selectd_file_desc', 'format_btns'),
        'format_btns_file',
        itemid: 0,
        options: array('accepted_types' => '.png', 'maxfiles' => 1)
    ));
}
