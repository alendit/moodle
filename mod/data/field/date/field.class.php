<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999-onwards Moodle Pty Ltd  http://moodle.com          //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

//2/19/07:  Advanced search of the date field is currently disabled because it does not track
// pre 1970 dates and does not handle blank entrys.  Advanced search functionality for this field
// type can be enabled once these issues are addressed in the core API.

class data_field_date extends data_field_base {

    var $type = 'date';

    var $day   = 0;
    var $month = 0;
    var $year  = 0;

    function display_add_field($recordid = 0, $formdata = null) {
        global $DB, $OUTPUT;

        if ($formdata) {
            $fieldname = 'field_' . $this->field->id . '_day';
            $day   = $formdata->$fieldname;
            $fieldname = 'field_' . $this->field->id . '_month';
            $month   = $formdata->$fieldname;
            $fieldname = 'field_' . $this->field->id . '_year';
            $year   = $formdata->$fieldname;

            $calendartype = \core_calendar\type_factory::get_calendar_instance();
            $gregoriandate = $calendartype->convert_to_gregorian($year, $month, $day);
            $content = make_timestamp(
                $gregoriandate['year'],
                $gregoriandate['month'],
                $gregoriandate['day'],
                $gregoriandate['hour'],
                $gregoriandate['minute'],
                0,
                0,
                false);
        } else if ($recordid) {
            $content = (int)$DB->get_field('data_content', 'content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid));
        } else {
            $content = time();
        }

        $str = '<div title="'.s($this->field->description).'" class="mod-data-input">';
        $dayselector = html_writer::select_time('days', 'field_'.$this->field->id.'_day', $content);
        $monthselector = html_writer::select_time('months', 'field_'.$this->field->id.'_month', $content);
        $yearselector = html_writer::select_time('years', 'field_'.$this->field->id.'_year', $content);
        $str .= $dayselector . $monthselector . $yearselector;
        $str .= '</div>';

        return $str;
    }

    //Enable the following three functions once core API issues have been addressed.
    function display_search_field($value=0) {
        $selectors = html_writer::select_time('days', 'f_'.$this->field->id.'_d', $value['timestamp'])
                        . html_writer::select_time('months', 'f_'.$this->field->id.'_m', $value['timestamp'])
                        . html_writer::select_time('years', 'f_'.$this->field->id.'_y', $value['timestamp']);
        $selectors_from = html_writer::select_time('days', 'f_'.$this->field->id.'_d_from', $value['timestamp_from'])
           . html_writer::select_time('months', 'f_'.$this->field->id.'_m_from', $value['timestamp_from'])
           . html_writer::select_time('years', 'f_'.$this->field->id.'_y_from', $value['timestamp_from']);
        $selectors_to = html_writer::select_time('days', 'f_'.$this->field->id.'_d_to', $value['timestamp_to'])
                        . html_writer::select_time('months', 'f_'.$this->field->id.'_m_to', $value['timestamp_to'])
                        . html_writer::select_time('years', 'f_'.$this->field->id.'_y_to', $value['timestamp_to']);
        $range_mode = html_writer::checkbox('f_'.$this->field->id.'_range',
                                            1,
                                            $value['range_select'],
                                            get_string('rangeselect', 'data'),
                                            array('class' => 'range_select_checkbox'));
        $datecheck = html_writer::checkbox('f_'.$this->field->id.'_z', 1, $value['usedate'], get_string('usedate', 'data'));
        $str = $range_mode . ' ';
        $str .= '<div class="exact_date_sel">' . $selectors . '</div>';
        $str .= '<div class="range_date_sel">' . 'From: <br />' . $selectors_from . '<br />';
        $str .= 'To: <br />' . $selectors_to . '</div>';
        $str .= $datecheck;

        return $str;
    }

    function generate_sql($tablealias, $value) {
        global $DB;
        static $i=0;
        $i++;
        $varcharcontent = $DB->sql_compare_text("{$tablealias}.content");

        if ($value['range_select']) {
            $params = array();
            $query = " ({$tablealias}.fieldid = {$this->field->id}";

            if (array_key_exists('timestamp_from', $value)) {
                $from = "df_date_from_$i";
                $query .= " AND $varcharcontent >= :$from";
                $params[$from] = $value['timestamp_from'];
            }
            if (array_key_exists('timestamp_to', $value)) {
                $to = "df_date_to_$i";
                $query .= " AND $varcharcontent <= :$to";
                $params[$to] = $value['timestamp_to'];
            }
            $query .= ") ";
            if (!empty($params)) {
                return array($query, $params);
            }
            return array('', array());
        } else {
            $name = "df_date_$i";
            return array(" ({$tablealias}.fieldid = {$this->field->id} AND $varcharcontent = :$name) ", array($name => $value['timestamp']));
        }
    }

    function parse_search_field() {
        $range_select = optional_param('f_'.$this->field->id.'_range', 0, PARAM_INT);
        $usedate = optional_param('f_'.$this->field->id.'_z', 0, PARAM_INT);
        if (!$usedate) return 0;

        $data = array();
        $day_from = optional_param('f_'.$this->field->id.'_d_from', 0, PARAM_INT);
        $month_from = optional_param('f_'.$this->field->id.'_m_from', 0, PARAM_INT);
        $year_from = optional_param('f_'.$this->field->id.'_y_from', 0, PARAM_INT);
        $day_to = optional_param('f_'.$this->field->id.'_d_to', 0, PARAM_INT);
        $month_to = optional_param('f_'.$this->field->id.'_m_to', 0, PARAM_INT);
        $year_to = optional_param('f_'.$this->field->id.'_y_to', 0, PARAM_INT);

        $calendartype = \core_calendar\type_factory::get_calendar_instance();
        if (!empty($day_from) && !empty($month_from) && !empty($year_from)) {

            $gregoriandate_from = $calendartype->convert_to_gregorian($year_from, $month_from, $day_from);


            $data['timestamp_from'] = make_timestamp(
                $gregoriandate_from['year'],
                $gregoriandate_from['month'],
                $gregoriandate_from['day'],
                $gregoriandate_from['hour'],
                $gregoriandate_from['minute'],
                0,
                0,
                false);
        }
        if (!empty($day_to) && !empty($month_to) && !empty($year_to)) {
            $gregoriandate_to = $calendartype->convert_to_gregorian($year_to, $month_to, $day_to);
            $data['timestamp_to'] = make_timestamp(
                $gregoriandate_to['year'],
                $gregoriandate_to['month'],
                $gregoriandate_to['day'],
                $gregoriandate_to['hour'],
                $gregoriandate_to['minute'],
                0,
                0,
                false);
        }

        $day   = optional_param('f_'.$this->field->id.'_d', 0, PARAM_INT);
        $month = optional_param('f_'.$this->field->id.'_m', 0, PARAM_INT);
        $year  = optional_param('f_'.$this->field->id.'_y', 0, PARAM_INT);
        if (!empty($day) && !empty($month) && !empty($year) && $usedate == 1) {
            $calendartype = \core_calendar\type_factory::get_calendar_instance();
            $gregoriandate = $calendartype->convert_to_gregorian($year, $month, $day);

            $data['timestamp'] = make_timestamp(
                $gregoriandate['year'],
                $gregoriandate['month'],
                $gregoriandate['day'],
                $gregoriandate['hour'],
                $gregoriandate['minute'],
                0,
                0,
                false);
            $data['usedate'] = 1;
        }
        $data['usedate'] = 1;
        $data['range_select'] = $range_select;

        return $data;
    }

    function update_content($recordid, $value, $name='') {
        global $DB;

        $names = explode('_',$name);
        $name = $names[2];          // day month or year

        $this->$name = $value;

        if ($this->day and $this->month and $this->year) {  // All of them have been collected now

            $content = new stdClass();
            $content->fieldid = $this->field->id;
            $content->recordid = $recordid;

            $calendartype = \core_calendar\type_factory::get_calendar_instance();
            $gregoriandate = $calendartype->convert_to_gregorian($this->year, $this->month, $this->day);
            $content->content = make_timestamp(
                $gregoriandate['year'],
                $gregoriandate['month'],
                $gregoriandate['day'],
                $gregoriandate['hour'],
                $gregoriandate['minute'],
                0,
                0,
                false);

            if ($oldcontent = $DB->get_record('data_content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid))) {
                $content->id = $oldcontent->id;
                return $DB->update_record('data_content', $content);
            } else {
                return $DB->insert_record('data_content', $content);
            }
        }
    }

    function display_browse_field($recordid, $template) {
        global $CFG, $DB;

        if ($content = $DB->get_field('data_content', 'content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid))) {
            return userdate($content, get_string('strftimedate'), 0);
        }
    }

    function get_sort_sql($fieldname) {
        global $DB;
        return $DB->sql_cast_char2int($fieldname, true);
    }


}
