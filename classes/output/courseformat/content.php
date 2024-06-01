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
 * Code to be executed after the plugin's database scheme has been installed is defined here.
 *
 * @package     format_btns
 * @category    upgrade
 * @copyright   2023 Jhon Rangel <jrangel@sanmateo.edu.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_btns\output\courseformat;

use context_system;
use core_courseformat\output\local\content as content_base;
use course_modinfo;
use moodle_url;

class content extends content_base
{
    var $currentsection;

    /**
     * @param \renderer_base $renderer
     * @return string
     * Nombre de la plantilla
     */
    public function get_template_name(\renderer_base $renderer): string
    {
        return 'format_btns/local/content';
    }

    /**
     * @param \renderer_base $output
     * @return \stdClass
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * Retornar la plantilla
     */
    public function export_for_template(\renderer_base $output): \stdClass
    {
        global $DB;
        $format = $this->format;
        $course = $format->get_course();

        $array_sections = array();
        $all_sections = $DB->get_records('course_sections', array('course' => $course->id));
        foreach ($all_sections as $section) {
            $info = new \stdClass();
            if ($section->section != 0) {
                $info->body = true;
            }
            $url = new moodle_url("/course/view.php", array(
                'id' => $course->id,
                'expandsection' => $section->section
            ));
            $url->set_anchor("section-$section->section");
            $info->url = $url->out();
            $info->namesection = $section->section;
            $info->disabled = $section->visible == 0 ? "disabled font-italic" : "";

            $array_sections[] = $info;
        }


        $sections = $this->export_sections($output);
        $sections[0]->section = 1;

        switch ($course->selectoption) {
            case "number";
                //Si es number se deja por defecto
                break;
            case 'leter_lowercase':
                $array_sections = $this->leter_lowercase($array_sections);
                break;
            case 'leter_uppercase':
                $array_sections = $this->leter_uppercase($array_sections);
                break;
            case 'roman_numbers':
                $array_sections = $this->roman_numbers($array_sections);
                break;
            default:
                //Si no hay opción se deja por defecto
                break;
        }

        $course->bgcolor = $course->bgcolor != "" ? $course->bgcolor : get_config('format_btns', 'bgcolor');
        $course->colorfont = $course->colorfont != "" ? $course->colorfont : get_config('format_btns', 'fontcolor');
        $course->bgcolor_selected = $course->bgcolor_selected != "" ? $course->bgcolor_selected : get_config('format_btns', 'bgcolor_selected');
        $course->fontcolor_selected = $course->fontcolor_selected != "" ? $course->fontcolor_selected : get_config('format_btns', 'fontcolor_selected');;


        $section_select = self::get_param_for_url(
            array('expandsection' => null,
                'section' => null,
            ));
        if ($section_select['expandsection'] == 0 or $section_select['expandsection'] == "")
            $section_select['expandsection'] = null;
        if ($section_select['expandsection'] != null) {
            $array_sections[$section_select['expandsection']]->selected = true;
        }

        $data = (object)[
            'title' => $format->page_title(),
            'sections' => $sections,
            'all_sections' => $array_sections,
            'format' => $format->get_format(),
            'sectionclasses' => '',
            'bgcolor' => $course->bgcolor,
            'colorfont' => $course->colorfont,
            'bgcolor_selected' => $course->bgcolor_selected,
            'fontcolor_selected' => $course->fontcolor_selected,
        ];

        if ($format->show_editor()) {
            $bulkedittools = new $this->bulkedittoolsclass($format);
            $data->bulkedittools = $bulkedittools->export_for_template($output);
        }


        $sectionnavigation = new $this->sectionnavigationclass($format, $this->currentsection);

        $data->sectionnavigation = $sectionnavigation->export_for_template($output);

        $sectionselector = new $this->sectionselectorclass($format, $sectionnavigation);
        $data->sectionselector = $sectionselector->export_for_template($output);


        $url = new moodle_url("/course/changenumsections.php",
            array('courseid' => $course->id, 'insertsection' => 0, 'sesskey' => sesskey()));
        $data->url_add_section = $url;

        $file_setting = get_config('format_btns', 'image_sections');
        if ($file_setting != "") {

            $url_1 = $this->get_content_file('format_btns_file', get_config('format_btns', 'image_sections'));

            $data->image_init_sectios = $url_1;
        }

        return $data;
    }

    /**
     * @param \renderer_base $output
     * @return array
     * @throws \coding_exception
     * @throws \moodle_exception
     * Exportar las secciones, de acuerdo a la necesidad
     */
    public function export_sections(\renderer_base $output): array
    {
        $format = $this->format;
        $course = $format->get_course();
        $modinfo = $this->format->get_modinfo();


        $realcoursedisplay = property_exists($course, 'realcoursedisplay') ? $course->realcoursedisplay : false;
        $firstsectionastab = ($realcoursedisplay == COURSE_DISPLAY_MULTIPAGE) ? 1 : 0;

        // Generate section list.
        $sections = [];
        $stealthsections = [];
        $numsections = $format->get_last_section_number();

        //Sección solicitada
        $section_select = self::get_param_for_url(
            array('expandsection' => null,
                'section' => null,
            ));
        if ($section_select['expandsection'] == 0 or $section_select['expandsection'] == 1
            or $section_select['expandsection'] == "")
            $section_select['expandsection'] = null;


        $this->currentsection = $section_select['expandsection'] != null ? $section_select['expandsection'] : 1;

        foreach ($this->get_sections_to_display($modinfo) as $thissection) {
            // The course/view.php check the section existence but the output can be called
            // from other parts so we need to check it.
            if (!$thissection) {
                throw new \moodle_exception('unknowncoursesection', 'error', course_get_url($course), s($course->fullname));
            }

            $section = new $this->sectionclass($format, $thissection);
            $sectionnum = $section->get_section_number();

            if (!$section_select['expandsection']) {
                if ($sectionnum > 1) {
                    continue;
                }
            } else {
                if ($sectionnum != 0 and $sectionnum != $section_select['expandsection']) {
                    continue;
                }
            }
            if ($sectionnum === 0 && $firstsectionastab) {
                continue;
            }

            if ($sectionnum > $numsections) {
                // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                if (!empty($modinfo->sections[$sectionnum])) {
                    $stealthsections[] = $section->export_for_template($output);
                }
                continue;
            }

            if (!$format->is_section_visible($thissection)) {
                continue;
            }


            $sections[] = $section->export_for_template($output);
        }

        if (!empty($stealthsections)) {
            $sections = array_merge($sections, $stealthsections);
        }

        return $sections;
    }

    /**
     * @param $params_need
     * @return mixed
     * @throws \coding_exception
     * @throws \moodle_exception
     * Retornar los parametros de la url
     */
    static function get_param_for_url($params_need)
    {
        global $CFG;

        $current_url = new moodle_url($CFG->wwwroot . $_SERVER['REQUEST_URI']);

        $params = $current_url->params();

        foreach ($params_need as $param => $value) {
            if (isset($_GET[$param])) {
                $params_need['expandsection'] = $_GET[$param];

                return $params_need;
            } else {
                $value = isset($params[$param]) ? $params[$param] : '';
                $params_need['expandsection'] = $value;
            }
        }
        return $params_need;
    }

    /**
     * @param course_modinfo $modinfo
     * @return array|\core_courseformat\output\local\section_info[]
     * Reescribiendo la función
     */
    private function get_sections_to_display(\course_modinfo $modinfo): array
    {
        $singlesection = $this->format->get_section_number();
        if ($singlesection) {
            return [
                $modinfo->get_section_info(0),
                $modinfo->get_section_info($singlesection),
            ];
        }

        return $modinfo->get_section_info_all();
    }

    /**
     * @param array $array_sections
     * @return array
     * Retornar las secciones, cuando se seleecione la opción de letras mayusculas
     */
    private function leter_lowercase(array $array_sections)
    {
        $abecedario = range('a', 'z');
        $count = 0;
        foreach ($array_sections as $array_section) {

            if ($array_section->namesection == 0) continue;

            $array_section->namesection = $abecedario[$count];
            $count++;
        }

        return $array_sections;
    }

    /**
     * @param array $array_sections
     * @return array
     * Retornar las secciones, cuando se seleecione la opción de letras mayusculas
     */
    private function leter_uppercase(array $array_sections)
    {
        $abecedario = range('A', 'Z');
        $count = 0;
        foreach ($array_sections as $array_section) {

            if ($array_section->namesection == 0) continue;

            $array_section->namesection = $abecedario[$count];
            $count++;
        }

        return $array_sections;
    }


    /**
     * @return string[]
     * Array con los números romanos
     */
    private function get_numbers_in_roman()
    {
        $romannumbers = array(
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
            13 => 'XIII',
            14 => 'XIV',
            15 => 'XV',
            16 => 'XVI',
            17 => 'XVII',
            18 => 'XVIII',
            19 => 'XIX',
            20 => 'XX',
            21 => 'XXI',
            22 => 'XXII',
            23 => 'XXIII',
            24 => 'XXIV',
            25 => 'XXV',
            26 => 'XXVI',
            27 => 'XXVII',
            28 => 'XXVIII',
            29 => 'XXIX',
            30 => 'XXX',
            31 => 'XXXI',
            32 => 'XXXII',
            33 => 'XXXIII',
            34 => 'XXXIV',
            35 => 'XXXV',
            36 => 'XXXVI',
            37 => 'XXXVII',
            38 => 'XXXVIII',
            39 => 'XXXIX',
            40 => 'XL',
            41 => 'XLI',
            42 => 'XLII',
            43 => 'XLIII',
            44 => 'XLIV',
            45 => 'XLV',
            46 => 'XLVI',
            47 => 'XLVII',
            48 => 'XLVIII',
            49 => 'XLIX',
            50 => 'L'
        );
        return $romannumbers;
    }

    /**
     * @param array $array_sections
     * @return array
     * Retornar cuando se indique la opción de números romanos
     */
    private function roman_numbers(array $array_sections)
    {
        $options = $this->get_numbers_in_roman();
        $count = 1;
        foreach ($array_sections as $array_section) {

            if ($array_section->namesection == 0) continue;

            $array_section->namesection = $options[$count];
            $count++;
        }

        return $array_sections;
    }

    /**
     * @param $filearea
     * @param $file_name
     * @return string
     * @throws \dml_exception
     * Retornar la imagen
     */
    public function get_content_file($filearea, $file_name)
    {
        global $DB;

        $file_name = substr($file_name, 1);

        $file_verified = $DB->get_record('files', array(
            'contextid' => 1,
            'component' => 'format_btns',
            'filearea' => $filearea,
            'filepath' => '/',
            'filename' => $file_name
        ));

        $fs = get_file_storage();

        $fileinfo = $file_verified;

        $file = $fs->get_file($fileinfo->contextid, $fileinfo->component, $fileinfo->filearea,
            $fileinfo->itemid, $fileinfo->filepath, $fileinfo->filename);


        if ($file) {
            $image_content = $file->get_content();

            $image_base64 = base64_encode($image_content);
            $mime_type = $file->get_mimetype();
            $image_src = 'data:' . $mime_type . ';base64,' . $image_base64;

            return $image_src;
        } else {
            return "";
        }
    }
}