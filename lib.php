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
use core\output\inplace_editable;

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     format_btns
 * @copyright   2023 Jhon Rangel <jrangelardila@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_btns extends core_courseformat\base
{
    protected function __construct($format, $courseid)
    {
        parent::__construct($format, $courseid);
        $this->set_section_number(false);
    }

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections()
    {
        return true;
    }


    /**
     * Retornar si se usa identación
     *
     * @return bool
     */
    public function uses_indentation(): bool
    {
        return true;
    }

    /**
     * Retornar si usa index
     *
     * @return true
     */
    public function uses_course_index()
    {
        return true;
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax()
    {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Retornar el uso de components
     *
     * @return true
     */
    public function supports_components()
    {
        return true;
    }

    /**
     * Whether this format allows to delete sections.
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section)
    {
        return true;
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news()
    {
        return true;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * This method is required for inplace section name editor.
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section)
    {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string(
                $section->name,
                true,
                ['context' => context_course::instance($this->courseid)]
            );
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Opciones personalizadas del curso
     *
     * @param $foreditform
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public function course_format_options($foreditform = false)
    {
        global $PAGE;

        $courseformatoptionsedit['colorfont'] = array(
            'label' => get_string('colorfont', 'format_btns'),
            'help' => 'colorfont',
            'help_component' => 'format_btns',
            'element_type' => 'text',
            'default' => get_config('format_btns', 'fontcolor')
        );

        $courseformatoptionsedit['bgcolor'] = array(
            'label' => get_string('bgcolor', 'format_btns'),
            'help' => 'bgcolor',
            'help_component' => 'format_btns',
            'element_type' => 'text',
            'default' => get_config('format_btns', 'bgcolor')
        );

        $courseformatoptionsedit['bgcolor_selected'] = array(
            'label' => get_string('bgcolor_selected', 'format_btns'),
            'help' => 'bgcolor',
            'help_component' => 'format_btns',
            'element_type' => 'text',
            'default' => get_config('format_btns', 'bgcolor_selected')
        );

        $courseformatoptionsedit['fontcolor_selected'] = array(
            'label' => get_string('fontcolor_selected', 'format_btns'),
            'help' => 'colorfont',
            'help_component' => 'format_btns',
            'element_type' => 'text',
            'default' => get_config('format_btns', 'fontcolor_selected')
        );

        $opt = get_config('format_btns', 'selectoption');
        $courseformatoptionsedit['selectoption'] = array(
            'label' => get_string('selectoption', 'format_btns'),
            'help' => 'selectoption',
            'help_component' => 'format_btns',
            'element_type' => 'select',
            'default' => $opt,
            'element_attributes' => array(
                array(
                    'number' => get_string('option1', 'format_btns'),
                    'leter_lowercase' => get_string('option2', 'format_btns'),
                    'leter_uppercase' => get_string('option3', 'format_btns'),
                    'roman_numbers' => get_string('option4', 'format_btns')
                )
            )
        );

        $courseformatoptionsedit['selectform'] = array(
            'label' => get_string('selectformbtn', 'format_btns'),
            'help' => 'selectform',
            'help_component' => 'format_btns',
            'element_type' => 'select',
            'default' => 'rounded',
            'element_attributes' => array(
                array(
                    'square' => get_string('square', 'format_btns'),
                    'rounded' => get_string('rounded', 'format_btns'),
                )
            )
        );

        $courseformatoptionsedit['title_section_view'] = array(
            'label' => get_string('title_section_view', 'format_btns'),
            'help' => 'title_section_view',
            'help_component' => 'format_btns',
            'element_type' => 'select',
            'default' => '0',
            'element_attributes' => array(
                array(
                    '0' => get_string('no'),
                    '1' => get_string('yes'),
                )
            )
        );


        return $courseformatoptionsedit;
    }

    /**
     * Eliminar sección
     *
     * @param $section
     * @param $forcedeleteifnotempty
     * @return bool
     */
    public function delete_section($section, $forcedeleteifnotempty = false)
    {
        return parent::delete_section($section, $forcedeleteifnotempty); // TODO: Change the autogenerated stub
    }

    /**
     * Bloques para ubicar
     *
     * @return array[]
     */
    public function get_default_blocks()
    {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => [],
        ];
    }

    /**
     * Permitir actividades sigilosas
     *
     * @param $cm
     * @param $section
     * @return true
     */
    public function allow_stealth_module_visibility($cm, $section)
    {
        return true;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * This method is required for inplace section name editor.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return inplace_editable
 */
function format_btns_inplace_editable($itemtype, $itemid, $newvalue)
{
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'pluginname'],
            MUST_EXIST
        );
        $format = core_courseformat\base::instance($section->course);
        return $format->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}
