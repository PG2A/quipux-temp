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
 * Classes representing HTML elements, used by $OUTPUT methods
 *
 * Please see http://docs.moodle.org/en/Developement:How_Moodle_outputs_HTML
 * for an overview.
 *
 * @package core
 * @category output
 * @copyright 2025 Casen Xu
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//defined('QUIPUX_INTERNAL') || die();

/**
 * Add quotes to HTML characters.
 *
 * Returns $var with HTML characters (like "<", ">", etc.) properly quoted.
 * Related function {@link p()} simply prints the output of this function.
 *
 * @param string $var the string potentially containing HTML characters
 * @return string
 */
function s($var) {
    if ($var === false) {
        return '0';
    }

    if ($var === null || $var === '') {
        return '';
    }

    return preg_replace(
        '/&amp;#(\d+|x[0-9a-f]+);/i', '&#$1;',
        htmlspecialchars($var, ENT_QUOTES | ENT_HTML401 | ENT_SUBSTITUTE)
    );
}

/**
 * Simple html output class
 *
 * @copyright 2009 Tim Hunt, 2010 Petr Skoda
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 * @package core
 * @category output
 */
class html_writer {

    /**
     * Outputs a tag with attributes and contents
     *
     * @param string $tagname The name of tag ('a', 'img', 'span' etc.)
     * @param string $contents What goes between the opening and closing tags
     * @param array $attributes The tag attributes (array('src' => $url, 'class' => 'class1') etc.)
     * @return string HTML fragment
     */
    public static function tag($tagname, $contents, array $attributes = null) {
        return self::start_tag($tagname, $attributes) . $contents . self::end_tag($tagname);
    }

    /**
     * Outputs an opening tag with attributes
     *
     * @param string $tagname The name of tag ('a', 'img', 'span' etc.)
     * @param array $attributes The tag attributes (array('src' => $url, 'class' => 'class1') etc.)
     * @return string HTML fragment
     */
    public static function start_tag($tagname, array $attributes = null) {
        return '<' . $tagname . self::attributes($attributes) . '>';
    }

    /**
     * Outputs a closing tag
     *
     * @param string $tagname The name of tag ('a', 'img', 'span' etc.)
     * @return string HTML fragment
     */
    public static function end_tag($tagname) {
        return '</' . $tagname . '>';
    }

    /**
     * Outputs an empty tag with attributes
     *
     * @param string $tagname The name of tag ('input', 'img', 'br' etc.)
     * @param array $attributes The tag attributes (array('src' => $url, 'class' => 'class1') etc.)
     * @return string HTML fragment
     */
    public static function empty_tag($tagname, array $attributes = null) {
        return '<' . $tagname . self::attributes($attributes) . ' />';
    }

    /**
     * Outputs a tag, but only if the contents are not empty
     *
     * @param string $tagname The name of tag ('a', 'img', 'span' etc.)
     * @param string $contents What goes between the opening and closing tags
     * @param array $attributes The tag attributes (array('src' => $url, 'class' => 'class1') etc.)
     * @return string HTML fragment
     */
    public static function nonempty_tag($tagname, $contents, array $attributes = null) {
        if ($contents === '' || is_null($contents)) {
            return '';
        }
        return self::tag($tagname, $contents, $attributes);
    }

    /**
     * Outputs a HTML attribute and value
     *
     * @param string $name The name of the attribute ('src', 'href', 'class' etc.)
     * @param string $value The value of the attribute. The value will be escaped with {@link s()}
     * @return string HTML fragment
     */
    public static function attribute($name, $value) {
        if ($value instanceof quipux_url) {
            return ' ' . $name . '="' . $value->out() . '"';
        }

        // special case, we do not want these in output
        if ($value === null) {
            return '';
        }

        // no sloppy trimming here!
        return ' ' . $name . '="' . s($value) . '"';
    }

    /**
     * Outputs a list of HTML attributes and values
     *
     * @param array $attributes The tag attributes (array('src' => $url, 'class' => 'class1') etc.)
     *       The values will be escaped with {@link s()}
     * @return string HTML fragment
     */
    public static function attributes(array $attributes = null) {
        $attributes = (array)$attributes;
        $output = '';
        foreach ($attributes as $name => $value) {
            $output .= self::attribute($name, $value);
        }
        return $output;
    }

    /**
     * Generates a simple image tag with attributes.
     *
     * @param string $src The source of image
     * @param string $alt The alternate text for image
     * @param array $attributes The tag attributes (array('height' => $max_height, 'class' => 'class1') etc.)
     * @return string HTML fragment
     */
    public static function img($src, $alt, array $attributes = null) {
        $attributes = (array)$attributes;
        $attributes['src'] = $src;
        // In case a null alt text is provided, set it to an empty string.
        $attributes['alt'] = $alt ?? '';
        if (array_key_exists('role', $attributes) && core_text::strtolower($attributes['role']) === 'presentation') {
            // A presentation role is not necessary for the img tag.
            // If a non-empty alt text is provided, the presentation role will conflict with the alt text.
            // An empty alt text denotes a decorative image. The presence of a presentation role is redundant.
            unset($attributes['role']);
            debugging('The presentation role is not necessary for an img tag.', DEBUG_DEVELOPER);
        }

        return self::empty_tag('img', $attributes);
    }

    /**
     * Generates random html element id.
     *
     * @staticvar int $counter
     * @staticvar type $uniq
     * @param string $base A string fragment that will be included in the random ID.
     * @return string A unique ID
     */
    public static function random_id($base='random') {
        static $counter = 0;
        static $uniq;

        if (!isset($uniq)) {
            $uniq = uniqid();
        }

        $counter++;
        return $base.$uniq.$counter;
    }

    /**
     * Generates a simple html link
     *
     * @param string|quipux_url $url The URL
     * @param string $text The text
     * @param array $attributes HTML attributes
     * @return string HTML fragment
     */
    public static function link($url, $text, array $attributes = null) {
        $attributes = (array)$attributes;
        $attributes['href']  = $url;
        return self::tag('a', $text, $attributes);
    }

    /**
     * Generates a simple checkbox with optional label
     *
     * @param string $name The name of the checkbox
     * @param string $value The value of the checkbox
     * @param bool $checked Whether the checkbox is checked
     * @param string $label The label for the checkbox
     * @param array $attributes Any attributes to apply to the checkbox
     * @param array $labelattributes Any attributes to apply to the label, if present
     * @return string html fragment
     */
    public static function checkbox($name, $value, $checked = true, $label = '',
                                    array $attributes = null, array $labelattributes = null) {
        $attributes = (array) $attributes;
        $output = '';

        if ($label !== '' and !is_null($label)) {
            if (empty($attributes['id'])) {
                $attributes['id'] = self::random_id('checkbox_');
            }
        }
        $attributes['type']    = 'checkbox';
        $attributes['value']   = $value;
        $attributes['name']    = $name;
        $attributes['checked'] = $checked ? 'checked' : null;

        $output .= self::empty_tag('input', $attributes);

        if ($label !== '' and !is_null($label)) {
            $labelattributes = (array) $labelattributes;
            $labelattributes['for'] = $attributes['id'];
            $output .= self::tag('label', $label, $labelattributes);
        }

        return $output;
    }

    /**
     * Generates a simple select yes/no form field
     *
     * @param string $name name of select element
     * @param bool $selected
     * @param array $attributes - html select element attributes
     * @return string HTML fragment
     */
    public static function select_yes_no($name, $selected=true, array $attributes = null) {
        $options = array('1'=>get_string('yes'), '0'=>get_string('no'));
        return self::select($options, $name, $selected, null, $attributes);
    }

    /**
     * Generates a simple select form field
     *
     * Note this function does HTML escaping on the optgroup labels, but not on the choice labels.
     *
     * @param array $options associative array value=>label ex.:
     *                array(1=>'One, 2=>Two)
     *              it is also possible to specify optgroup as complex label array ex.:
     *                array(array('Odd'=>array(1=>'One', 3=>'Three)), array('Even'=>array(2=>'Two')))
     *                array(1=>'One', '--1uniquekey'=>array('More'=>array(2=>'Two', 3=>'Three')))
     * @param string $name name of select element
     * @param string|array $selected value or array of values depending on multiple attribute
     * @param array|bool $nothing add nothing selected option, or false of not added
     * @param array $attributes html select element attributes
     * @return string HTML fragment
     */
    public static function select(array $options, $name, $selected = '', $nothing = array('' => 'choosedots'), array $attributes = null) {
        $attributes = (array)$attributes;
        if (is_array($nothing)) {
            foreach ($nothing as $k=>$v) {
                if ($v === 'choose' or $v === 'choosedots') {
                    $nothing[$k] = get_string('choosedots');
                }
            }
            $options = $nothing + $options; // keep keys, do not override

        } else if (is_string($nothing) and $nothing !== '') {
            // BC
            $options = array(''=>$nothing) + $options;
        }

        // we may accept more values if multiple attribute specified
        $selected = (array)$selected;
        foreach ($selected as $k=>$v) {
            $selected[$k] = (string)$v;
        }

        if (!isset($attributes['id'])) {
            $id = 'menu'.$name;
            // name may contaion [], which would make an invalid id. e.g. numeric question type editing form, assignment quickgrading
            $id = str_replace('[', '', $id);
            $id = str_replace(']', '', $id);
            $attributes['id'] = $id;
        }

        if (!isset($attributes['class'])) {
            $class = 'menu'.$name;
            // name may contaion [], which would make an invalid class. e.g. numeric question type editing form, assignment quickgrading
            $class = str_replace('[', '', $class);
            $class = str_replace(']', '', $class);
            $attributes['class'] = $class;
        }
        $attributes['class'] = 'select custom-select ' . $attributes['class']; // Add 'select' selector always.

        $attributes['name'] = $name;

        if (!empty($attributes['disabled'])) {
            $attributes['disabled'] = 'disabled';
        } else {
            unset($attributes['disabled']);
        }

        $output = '';
        foreach ($options as $value=>$label) {
            if (is_array($label)) {
                // ignore key, it just has to be unique
                $output .= self::select_optgroup(key($label), current($label), $selected);
            } else {
                $output .= self::select_option($label, $value, $selected);
            }
        }
        return self::tag('select', $output, $attributes);
    }

    /**
     * Returns HTML to display a select box option.
     *
     * @param string $label The label to display as the option.
     * @param string|int $value The value the option represents
     * @param array $selected An array of selected options
     * @return string HTML fragment
     */
    private static function select_option($label, $value, array $selected) {
        $attributes = array();
        $value = (string)$value;
        if (in_array($value, $selected, true)) {
            $attributes['selected'] = 'selected';
        }
        $attributes['value'] = $value;
        return self::tag('option', $label, $attributes);
    }

    /**
     * Returns HTML to display a select box option group.
     *
     * @param string $groupname The label to use for the group
     * @param array $options The options in the group
     * @param array $selected An array of selected values.
     * @return string HTML fragment.
     */
    private static function select_optgroup($groupname, $options, array $selected) {
        if (empty($options)) {
            return '';
        }
        $attributes = array('label'=>$groupname);
        $output = '';
        foreach ($options as $value=>$label) {
            $output .= self::select_option($label, $value, $selected);
        }
        return self::tag('optgroup', $output, $attributes);
    }

    /**
     * This is a shortcut for making an hour selector menu.
     *
     * @param string $type The type of selector (years, months, days, hours, minutes)
     * @param string $name fieldname
     * @param int $currenttime A default timestamp in GMT
     * @param int $step minute spacing
     * @param array $attributes - html select element attributes
     * @param float|int|string $timezone the timezone to use to calculate the time
     *        {@link https://moodledev.io/docs/apis/subsystems/time#timezone}
     * @return string HTML fragment
     */
    public static function select_time($type, $name, $currenttime = 0, $step = 5, array $attributes = null, $timezone = 99) {
        global $OUTPUT;

        if (!$currenttime) {
            $currenttime = time();
        }
        $calendartype = \core_calendar\type_factory::get_calendar_instance();
        $currentdate = $calendartype->timestamp_to_date_array($currenttime, $timezone);

        $userdatetype = $type;
        $timeunits = array();

        switch ($type) {
            case 'years':
                $timeunits = $calendartype->get_years();
                $userdatetype = 'year';
                break;
            case 'months':
                $timeunits = $calendartype->get_months();
                $userdatetype = 'month';
                $currentdate['month'] = (int)$currentdate['mon'];
                break;
            case 'days':
                $timeunits = $calendartype->get_days();
                $userdatetype = 'mday';
                break;
            case 'hours':
                for ($i=0; $i<=23; $i++) {
                    $timeunits[$i] = sprintf("%02d",$i);
                }
                break;
            case 'minutes':
                if ($step != 1) {
                    $currentdate['minutes'] = ceil($currentdate['minutes']/$step)*$step;
                }

                for ($i=0; $i<=59; $i+=$step) {
                    $timeunits[$i] = sprintf("%02d",$i);
                }
                break;
            default:
                throw new coding_exception("Time type $type is not supported by html_writer::select_time().");
        }

        $attributes = (array) $attributes;
        $data = (object) [
            'name' => $name,
            'id' => !empty($attributes['id']) ? $attributes['id'] : self::random_id('ts_'),
            'label' => get_string(substr($type, 0, -1), 'form'),
            'options' => array_map(function($value) use ($timeunits, $currentdate, $userdatetype) {
                return [
                    'name' => $timeunits[$value],
                    'value' => $value,
                    'selected' => $currentdate[$userdatetype] == $value
                ];
            }, array_keys($timeunits)),
        ];

        unset($attributes['id']);
        unset($attributes['name']);
        $data->attributes = array_map(function($name) use ($attributes) {
            return [
                'name' => $name,
                'value' => $attributes[$name]
            ];
        }, array_keys($attributes));

        return $OUTPUT->render_from_template('core/select_time', $data);
    }

    /**
     * Shortcut for quick making of lists
     *
     * Note: 'list' is a reserved keyword ;-)
     *
     * @param array $items
     * @param array $attributes
     * @param string $tag ul or ol
     * @return string
     */
    public static function alist(array $items, array $attributes = null, $tag = 'ul') {
        $output = html_writer::start_tag($tag, $attributes)."\n";
        foreach ($items as $item) {
            $output .= html_writer::tag('li', $item)."\n";
        }
        $output .= html_writer::end_tag($tag);
        return $output;
    }

    /**
     * Returns hidden input fields created from url parameters.
     *
     * @param quipux_url $url
     * @param array $exclude list of excluded parameters
     * @return string HTML fragment
     */
    public static function input_hidden_params(quipux_url $url, array $exclude = null) {
        $exclude = (array)$exclude;
        $params = $url->params();
        foreach ($exclude as $key) {
            unset($params[$key]);
        }

        $output = '';
        foreach ($params as $key => $value) {
            $attributes = array('type'=>'hidden', 'name'=>$key, 'value'=>$value);
            $output .= self::empty_tag('input', $attributes)."\n";
        }
        return $output;
    }

    /**
     * Generate a script tag containing the the specified code.
     *
     * @param string $jscode the JavaScript code
     * @param quipux_url|string $url optional url of the external script, $code ignored if specified
     * @return string HTML, the code wrapped in <script> tags.
     */
    public static function script($jscode, $url=null) {
        if ($jscode) {
            return self::tag('script', "\n//<![CDATA[\n$jscode\n//]]>\n") . "\n";

        } else if ($url) {
            return self::tag('script', '', ['src' => $url]) . "\n";

        } else {
            return '';
        }
    }

    /**
     * Renders HTML table
     *
     * This method may modify the passed instance by adding some default properties if they are not set yet.
     * If this is not what you want, you should make a full clone of your data before passing them to this
     * method. In most cases this is not an issue at all so we do not clone by default for performance
     * and memory consumption reasons.
     *
     * @param html_table $table data to be rendered
     * @return string HTML code
     */
    public static function table(html_table $table) {
        // prepare table data and populate missing properties with reasonable defaults
        if (!empty($table->align)) {
            foreach ($table->align as $key => $aa) {
                if ($aa) {
                    $table->align[$key] = 'text-align:'. fix_align_rtl($aa) .';';  // Fix for RTL languages
                } else {
                    $table->align[$key] = null;
                }
            }
        }
        if (!empty($table->size)) {
            foreach ($table->size as $key => $ss) {
                if ($ss) {
                    $table->size[$key] = 'width:'. $ss .';';
                } else {
                    $table->size[$key] = null;
                }
            }
        }
        if (!empty($table->wrap)) {
            foreach ($table->wrap as $key => $ww) {
                if ($ww) {
                    $table->wrap[$key] = 'white-space:nowrap;';
                } else {
                    $table->wrap[$key] = '';
                }
            }
        }
        if (!empty($table->head)) {
            foreach ($table->head as $key => $val) {
                if (!isset($table->align[$key])) {
                    $table->align[$key] = null;
                }
                if (!isset($table->size[$key])) {
                    $table->size[$key] = null;
                }
                if (!isset($table->wrap[$key])) {
                    $table->wrap[$key] = null;
                }

            }
        }
        if (empty($table->attributes['class'])) {
            $table->attributes['class'] = 'generaltable';
        }
        if (!empty($table->tablealign)) {
            $table->attributes['class'] .= ' boxalign' . $table->tablealign;
        }

        // explicitly assigned properties override those defined via $table->attributes
        $table->attributes['class'] = trim($table->attributes['class']);
        $attributes = array_merge($table->attributes, array(
            'id'            => $table->id,
            'width'         => $table->width,
            'summary'       => $table->summary,
            'cellpadding'   => $table->cellpadding,
            'cellspacing'   => $table->cellspacing,
        ));
        $output = html_writer::start_tag('table', $attributes) . "\n";

        $countcols = 0;

        // Output a caption if present.
        if (!empty($table->caption)) {
            $captionattributes = array();
            if ($table->captionhide) {
                $captionattributes['class'] = 'accesshide';
            }
            $output .= html_writer::tag(
                'caption',
                $table->caption,
                $captionattributes
            );
        }

        if (!empty($table->head)) {
            $countcols = count($table->head);

            $output .= html_writer::start_tag('thead', array()) . "\n";
            $output .= html_writer::start_tag('tr', array()) . "\n";
            $keys = array_keys($table->head);
            $lastkey = end($keys);

            foreach ($table->head as $key => $heading) {
                // Convert plain string headings into html_table_cell objects
                if (!($heading instanceof html_table_cell)) {
                    $headingtext = $heading;
                    $heading = new html_table_cell();
                    $heading->text = $headingtext;
                    $heading->header = true;
                }

                if ($heading->header !== false) {
                    $heading->header = true;
                }

                $tagtype = 'td';
                if ($heading->header && (string)$heading->text != '') {
                    $tagtype = 'th';
                }

                $heading->attributes['class'] .= ' header c' . $key;
                if (isset($table->headspan[$key]) && $table->headspan[$key] > 1) {
                    $heading->colspan = $table->headspan[$key];
                    $countcols += $table->headspan[$key] - 1;
                }

                if ($key == $lastkey) {
                    $heading->attributes['class'] .= ' lastcol';
                }
                if (isset($table->colclasses[$key])) {
                    $heading->attributes['class'] .= ' ' . $table->colclasses[$key];
                }
                $heading->attributes['class'] = trim($heading->attributes['class']);
                $attributes = array_merge($heading->attributes, [
                    'style'     => $table->align[$key] . $table->size[$key] . $heading->style,
                    'colspan'   => $heading->colspan,
                ]);

                if ($tagtype == 'th') {
                    $attributes['scope'] = !empty($heading->scope) ? $heading->scope : 'col';
                }

                $output .= html_writer::tag($tagtype, $heading->text, $attributes) . "\n";
            }
            $output .= html_writer::end_tag('tr') . "\n";
            $output .= html_writer::end_tag('thead') . "\n";

            if (empty($table->data)) {
                // For valid XHTML strict every table must contain either a valid tr
                // or a valid tbody... both of which must contain a valid td
                $output .= html_writer::start_tag('tbody', array('class' => 'empty'));
                $output .= html_writer::tag('tr', html_writer::tag('td', '', array('colspan'=>count($table->head))));
                $output .= html_writer::end_tag('tbody');
            }
        }

        if (!empty($table->data)) {
            $keys       = array_keys($table->data);
            $lastrowkey = end($keys);
            $output .= html_writer::start_tag('tbody', array());

            foreach ($table->data as $key => $row) {
                if (($row === 'hr') && ($countcols)) {
                    $output .= html_writer::tag('td', html_writer::tag('div', '', array('class' => 'tabledivider')), array('colspan' => $countcols));
                } else {
                    // Convert array rows to html_table_rows and cell strings to html_table_cell objects
                    if (!($row instanceof html_table_row)) {
                        $newrow = new html_table_row();

                        foreach ($row as $cell) {
                            if (!($cell instanceof html_table_cell)) {
                                $cell = new html_table_cell($cell);
                            }
                            $newrow->cells[] = $cell;
                        }
                        $row = $newrow;
                    }

                    if (isset($table->rowclasses[$key])) {
                        $row->attributes['class'] .= ' ' . $table->rowclasses[$key];
                    }

                    if ($key == $lastrowkey) {
                        $row->attributes['class'] .= ' lastrow';
                    }

                    // Explicitly assigned properties should override those defined in the attributes.
                    $row->attributes['class'] = trim($row->attributes['class']);
                    $trattributes = array_merge($row->attributes, array(
                        'id'            => $row->id,
                        'style'         => $row->style,
                    ));
                    $output .= html_writer::start_tag('tr', $trattributes) . "\n";
                    $keys2 = array_keys($row->cells);
                    $lastkey = end($keys2);

                    $gotlastkey = false; //flag for sanity checking
                    foreach ($row->cells as $key => $cell) {
                        if ($gotlastkey) {
                            //This should never happen. Why do we have a cell after the last cell?
                            mtrace("A cell with key ($key) was found after the last key ($lastkey)");
                        }

                        if (!($cell instanceof html_table_cell)) {
                            $mycell = new html_table_cell();
                            $mycell->text = $cell;
                            $cell = $mycell;
                        }

                        if (($cell->header === true) && empty($cell->scope)) {
                            $cell->scope = 'row';
                        }

                        if (isset($table->colclasses[$key])) {
                            $cell->attributes['class'] .= ' ' . $table->colclasses[$key];
                        }

                        $cell->attributes['class'] .= ' cell c' . $key;
                        if ($key == $lastkey) {
                            $cell->attributes['class'] .= ' lastcol';
                            $gotlastkey = true;
                        }
                        $tdstyle = '';
                        $tdstyle .= isset($table->align[$key]) ? $table->align[$key] : '';
                        $tdstyle .= isset($table->size[$key]) ? $table->size[$key] : '';
                        $tdstyle .= isset($table->wrap[$key]) ? $table->wrap[$key] : '';
                        $cell->attributes['class'] = trim($cell->attributes['class']);
                        $tdattributes = array_merge($cell->attributes, array(
                            'style' => $tdstyle . $cell->style,
                            'colspan' => $cell->colspan,
                            'rowspan' => $cell->rowspan,
                            'id' => $cell->id,
                            'abbr' => $cell->abbr,
                            'scope' => $cell->scope,
                        ));
                        $tagtype = 'td';
                        if ($cell->header === true) {
                            $tagtype = 'th';
                        }
                        $output .= html_writer::tag($tagtype, $cell->text, $tdattributes) . "\n";
                    }
                }
                $output .= html_writer::end_tag('tr') . "\n";
            }
            $output .= html_writer::end_tag('tbody') . "\n";
        }
        $output .= html_writer::end_tag('table') . "\n";

        if ($table->responsive) {
            return self::div($output, 'table-responsive');
        }

        return $output;
    }

    /**
     * Renders form element label
     *
     * By default, the label is suffixed with a label separator defined in the
     * current language pack (colon by default in the English lang pack).
     * Adding the colon can be explicitly disabled if needed. Label separators
     * are put outside the label tag itself so they are not read by
     * screenreaders (accessibility).
     *
     * Parameter $for explicitly associates the label with a form control. When
     * set, the value of this attribute must be the same as the value of
     * the id attribute of the form control in the same document. When null,
     * the label being defined is associated with the control inside the label
     * element.
     *
     * @param string $text content of the label tag
     * @param string|null $for id of the element this label is associated with, null for no association
     * @param bool $colonize add label separator (colon) to the label text, if it is not there yet
     * @param array $attributes to be inserted in the tab, for example array('accesskey' => 'a')
     * @return string HTML of the label element
     */
    public static function label($text, $for, $colonize = true, array $attributes=array()) {
        if (!is_null($for)) {
            $attributes = array_merge($attributes, array('for' => $for));
        }
        $text = trim($text ?? '');
        $label = self::tag('label', $text, $attributes);

        // TODO MDL-12192 $colonize disabled for now yet
        // if (!empty($text) and $colonize) {
        //     // the $text may end with the colon already, though it is bad string definition style
        //     $colon = get_string('labelsep', 'langconfig');
        //     if (!empty($colon)) {
        //         $trimmed = trim($colon);
        //         if ((substr($text, -strlen($trimmed)) == $trimmed) or (substr($text, -1) == ':')) {
        //             //debugging('The label text should not end with colon or other label separator,
        //             //           please fix the string definition.', DEBUG_DEVELOPER);
        //         } else {
        //             $label .= $colon;
        //         }
        //     }
        // }

        return $label;
    }

    /**
     * Combines a class parameter with other attributes. Aids in code reduction
     * because the class parameter is very frequently used.
     *
     * If the class attribute is specified both in the attributes and in the
     * class parameter, the two values are combined with a space between.
     *
     * @param string $class Optional CSS class (or classes as space-separated list)
     * @param array $attributes Optional other attributes as array
     * @return array Attributes (or null if still none)
     */
    private static function add_class($class = '', array $attributes = null) {
        if ($class !== '') {
            $classattribute = array('class' => $class);
            if ($attributes) {
                if (array_key_exists('class', $attributes)) {
                    $attributes['class'] = trim($attributes['class'] . ' ' . $class);
                } else {
                    $attributes = $classattribute + $attributes;
                }
            } else {
                $attributes = $classattribute;
            }
        }
        return $attributes;
    }

    /**
     * Creates a <div> tag. (Shortcut function.)
     *
     * @param string $content HTML content of tag
     * @param string $class Optional CSS class (or classes as space-separated list)
     * @param array $attributes Optional other attributes as array
     * @return string HTML code for div
     */
    public static function div($content, $class = '', array $attributes = null) {
        return self::tag('div', $content, self::add_class($class, $attributes));
    }

    /**
     * Starts a <div> tag. (Shortcut function.)
     *
     * @param string $class Optional CSS class (or classes as space-separated list)
     * @param array $attributes Optional other attributes as array
     * @return string HTML code for open div tag
     */
    public static function start_div($class = '', array $attributes = null) {
        return self::start_tag('div', self::add_class($class, $attributes));
    }

    /**
     * Ends a <div> tag. (Shortcut function.)
     *
     * @return string HTML code for close div tag
     */
    public static function end_div() {
        return self::end_tag('div');
    }

    /**
     * Creates a <span> tag. (Shortcut function.)
     *
     * @param string $content HTML content of tag
     * @param string $class Optional CSS class (or classes as space-separated list)
     * @param array $attributes Optional other attributes as array
     * @return string HTML code for span
     */
    public static function span($content, $class = '', array $attributes = null) {
        return self::tag('span', $content, self::add_class($class, $attributes));
    }

    /**
     * Starts a <span> tag. (Shortcut function.)
     *
     * @param string $class Optional CSS class (or classes as space-separated list)
     * @param array $attributes Optional other attributes as array
     * @return string HTML code for open span tag
     */
    public static function start_span($class = '', array $attributes = null) {
        return self::start_tag('span', self::add_class($class, $attributes));
    }

    /**
     * Ends a <span> tag. (Shortcut function.)
     *
     * @return string HTML code for close span tag
     */
    public static function end_span() {
        return self::end_tag('span');
    }
}

/**
 * Class for creating and manipulating urls.
 *
 * It can be used in moodle pages where config.php has been included without any further includes.
 *
 * It is useful for manipulating urls with long lists of params.
 * One situation where it will be useful is a page which links to itself to perform various actions
 * and / or to process form data. A quipux_url object :
 * can be created for a page to refer to itself with all the proper get params being passed from page call to
 * page call and methods can be used to output a url including all the params, optionally adding and overriding
 * params and can also be used to
 *     - output the url without any get params
 *     - and output the params as hidden fields to be output within a form
 *
 * @copyright 2007 jamiesensei
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core
 */
class quipux_url {

    /**
     * Scheme, ex.: http, https
     * @var string
     */
    protected $scheme = '';

    /**
     * Hostname.
     * @var string
     */
    protected $host = '';

    /**
     * Port number, empty means default 80 or 443 in case of http.
     * @var int
     */
    protected $port = '';

    /**
     * Username for http auth.
     * @var string
     */
    protected $user = '';

    /**
     * Password for http auth.
     * @var string
     */
    protected $pass = '';

    /**
     * Script path.
     * @var string
     */
    protected $path = '';

    /**
     * Optional slash argument value.
     * @var string
     */
    protected $slashargument = '';

    /**
     * Anchor, may be also empty, null means none.
     * @var string
     */
    protected $anchor = null;

    /**
     * Url parameters as associative array.
     * @var array
     */
    protected $params = array();

    /**
     * Create new instance of quipux_url.
     *
     * @param quipux_url|string $url - quipux_url means make a copy of another
     *      quipux_url and change parameters, string means full url or shortened
     *      form (ex.: '/course/view.php'). It is strongly encouraged to not include
     *      query string because it may result in double encoded values. Use the
     *      $params instead. For admin URLs, just use /admin/script.php, this
     *      class takes care of the $CFG->admin issue.
     * @param array $params these params override current params or add new
     * @param string $anchor The anchor to use as part of the URL if there is one.
     * @throws moodle_exception
     */
    public function __construct($url, array $params = null, $anchor = null) {
        global $CFG;

        if ($url instanceof quipux_url) {
            $this->scheme = $url->scheme;
            $this->host = $url->host;
            $this->port = $url->port;
            $this->user = $url->user;
            $this->pass = $url->pass;
            $this->path = $url->path;
            $this->slashargument = $url->slashargument;
            $this->params = $url->params;
            $this->anchor = $url->anchor;

        } else {
            $url = $url ?? '';
            // Detect if anchor used.
            $apos = strpos($url, '#');
            if ($apos !== false) {
                $anchor = substr($url, $apos);
                $anchor = ltrim($anchor, '#');
                $this->set_anchor($anchor);
                $url = substr($url, 0, $apos);
            }

            // Normalise shortened form of our url ex.: '/course/view.php'.
            if (strpos($url, '/') === 0) {
                $url = $CFG->wwwroot.$url;
            }

            if ($CFG->admin !== 'admin') {
                if (strpos($url, "$CFG->wwwroot/admin/") === 0) {
                    $url = str_replace("$CFG->wwwroot/admin/", "$CFG->wwwroot/$CFG->admin/", $url);
                }
            }

            // Parse the $url.
            $parts = parse_url($url);
            if ($parts === false) {
                throw new moodle_exception('invalidurl');
            }
            if (isset($parts['query'])) {
                // Note: the values may not be correctly decoded, url parameters should be always passed as array.
                parse_str(str_replace('&amp;', '&', $parts['query']), $this->params);
            }
            unset($parts['query']);
            foreach ($parts as $key => $value) {
                $this->$key = $value;
            }

            // Detect slashargument value from path - we do not support directory names ending with .php.
            $pos = strpos($this->path, '.php/');
            if ($pos !== false) {
                $this->slashargument = substr($this->path, $pos + 4);
                $this->path = substr($this->path, 0, $pos + 4);
            }
        }

        $this->params($params);
        if ($anchor !== null) {
            $this->anchor = (string)$anchor;
        }
    }

    /**
     * Add an array of params to the params for this url.
     *
     * The added params override existing ones if they have the same name.
     *
     * @param array $params Defaults to null. If null then returns all params.
     * @return array Array of Params for url.
     * @throws coding_exception
     */
    public function params(array $params = null) {
        $params = (array)$params;

        foreach ($params as $key => $value) {
            if (is_int($key)) {
                throw new coding_exception('Url parameters can not have numeric keys!');
            }
            if (!is_string($value)) {
                if (is_array($value)) {
                    throw new coding_exception('Url parameters values can not be arrays!');
                }
                if (is_object($value) and !method_exists($value, '__toString')) {
                    throw new coding_exception('Url parameters values can not be objects, unless __toString() is defined!');
                }
            }
            $this->params[$key] = (string)$value;
        }
        return $this->params;
    }

    /**
     * Remove all params if no arguments passed.
     * Remove selected params if arguments are passed.
     *
     * Can be called as either remove_params('param1', 'param2')
     * or remove_params(array('param1', 'param2')).
     *
     * @param string[]|string $params,... either an array of param names, or 1..n string params to remove as args.
     * @return array url parameters
     */
    public function remove_params($params = null) {
        if (!is_array($params)) {
            $params = func_get_args();
        }
        foreach ($params as $param) {
            unset($this->params[$param]);
        }
        return $this->params;
    }

    /**
     * Remove all url parameters.
     *
     * @todo remove the unused param.
     * @param array $params Unused param
     * @return void
     */
    public function remove_all_params($params = null) {
        $this->params = array();
        $this->slashargument = '';
    }

    /**
     * Add a param to the params for this url.
     *
     * The added param overrides existing one if they have the same name.
     *
     * @param string $paramname name
     * @param string $newvalue Param value. If new value specified current value is overriden or parameter is added
     * @return mixed string parameter value, null if parameter does not exist
     */
    public function param($paramname, $newvalue = '') {
        if (func_num_args() > 1) {
            // Set new value.
            $this->params(array($paramname => $newvalue));
        }
        if (isset($this->params[$paramname])) {
            return $this->params[$paramname];
        } else {
            return null;
        }
    }

    /**
     * Merges parameters and validates them
     *
     * @param array $overrideparams
     * @return array merged parameters
     * @throws coding_exception
     */
    protected function merge_overrideparams(array $overrideparams = null) {
        $overrideparams = (array)$overrideparams;
        $params = $this->params;
        foreach ($overrideparams as $key => $value) {
            if (is_int($key)) {
                throw new coding_exception('Overridden parameters can not have numeric keys!');
            }
            if (is_array($value)) {
                throw new coding_exception('Overridden parameters values can not be arrays!');
            }
            if (is_object($value) and !method_exists($value, '__toString')) {
                throw new coding_exception('Overridden parameters values can not be objects, unless __toString() is defined!');
            }
            $params[$key] = (string)$value;
        }
        return $params;
    }

    /**
     * Get the params as as a query string.
     *
     * This method should not be used outside of this method.
     *
     * @param bool $escaped Use &amp; as params separator instead of plain &
     * @param array $overrideparams params to add to the output params, these
     *      override existing ones with the same name.
     * @return string query string that can be added to a url.
     */
    public function get_query_string($escaped = true, array $overrideparams = null) {
        $arr = array();
        if ($overrideparams !== null) {
            $params = $this->merge_overrideparams($overrideparams);
        } else {
            $params = $this->params;
        }
        foreach ($params as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $index => $value) {
                    $arr[] = rawurlencode($key.'['.$index.']')."=".rawurlencode($value);
                }
            } else {
                if (isset($val) && $val !== '') {
                    $arr[] = rawurlencode($key)."=".rawurlencode($val);
                } else {
                    $arr[] = rawurlencode($key);
                }
            }
        }
        if ($escaped) {
            return implode('&amp;', $arr);
        } else {
            return implode('&', $arr);
        }
    }

    /**
     * Get the url params as an array of key => value pairs.
     *
     * This helps in handling cases where url params contain arrays.
     *
     * @return array params array for templates.
     */
    public function export_params_for_template(): array {
        $data = [];
        foreach ($this->params as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $index => $value) {
                    $data[] = ['name' => $key.'['.$index.']', 'value' => $value];
                }
            } else {
                $data[] = ['name' => $key, 'value' => $val];
            }
        }
        return $data;
    }

    /**
     * Shortcut for printing of encoded URL.
     *
     * @return string
     */
    public function __toString() {
        return $this->out(true);
    }

    /**
     * Output url.
     *
     * If you use the returned URL in HTML code, you want the escaped ampersands. If you use
     * the returned URL in HTTP headers, you want $escaped=false.
     *
     * @param bool $escaped Use &amp; as params separator instead of plain &
     * @param array $overrideparams params to add to the output url, these override existing ones with the same name.
     * @return string Resulting URL
     */
    public function out($escaped = true, array $overrideparams = null) {

        global $CFG;

        if (!is_bool($escaped)) {
            debugging('Escape parameter must be of type boolean, '.gettype($escaped).' given instead.');
        }

        $url = $this;

        // Allow url's to be rewritten by a plugin.
        if (isset($CFG->urlrewriteclass) && !isset($CFG->upgraderunning)) {
            $class = $CFG->urlrewriteclass;
            $pluginurl = $class::url_rewrite($url);
            if ($pluginurl instanceof quipux_url) {
                $url = $pluginurl;
            }
        }

        return $url->raw_out($escaped, $overrideparams);

    }

    /**
     * Output url without any rewrites
     *
     * This is identical in signature and use to out() but doesn't call the rewrite handler.
     *
     * @param bool $escaped Use &amp; as params separator instead of plain &
     * @param array $overrideparams params to add to the output url, these override existing ones with the same name.
     * @return string Resulting URL
     */
    public function raw_out($escaped = true, array $overrideparams = null) {
        if (!is_bool($escaped)) {
            debugging('Escape parameter must be of type boolean, '.gettype($escaped).' given instead.');
        }

        $uri = $this->out_omit_querystring().$this->slashargument;

        $querystring = $this->get_query_string($escaped, $overrideparams);
        if ($querystring !== '') {
            $uri .= '?' . $querystring;
        }

        $uri .= $this->get_encoded_anchor();

        return $uri;
    }

    /**
     * Encode the anchor according to RFC 3986.
     *
     * @return string The encoded anchor
     */
    public function get_encoded_anchor(): string {
        if (is_null($this->anchor)) {
            return '';
        }

        // RFC 3986 allows the following characters in a fragment without them being encoded:
        // pct-encoded: "%" HEXDIG HEXDIG
        // unreserved:  ALPHA / DIGIT / "-" / "." / "_" / "~" /
        // sub-delims:  "!" / "$" / "&" / "'" / "(" / ")" / "*" / "+" / "," / ";" / "=" / ":" / "@"
        // fragment:    "/" / "?"
        //
        // All other characters should be encoded.
        // These should not be encoded in the fragment unless they were already encoded.

        $allowed = 'a-zA-Z0-9\\-._~!$&\'()*+,;=:@\/?%';
        $anchor = '#';
        $anchor .= preg_replace_callback(
            '/[^' . $allowed . ']/',
            function ($matches) {
                return rawurlencode($matches[0]);
            },
            $this->anchor
        );

        return $anchor;
    }

    /**
     * Returns url without parameters, everything before '?'.
     *
     * @param bool $includeanchor if {@link self::anchor} is defined, should it be returned?
     * @return string
     */
    public function out_omit_querystring($includeanchor = false) {

        $uri = $this->scheme ? $this->scheme.':'.((strtolower($this->scheme) == 'mailto') ? '':'//'): '';
        $uri .= $this->user ? $this->user.($this->pass? ':'.$this->pass:'').'@':'';
        $uri .= $this->host ? $this->host : '';
        $uri .= $this->port ? ':'.$this->port : '';
        $uri .= $this->path ? $this->path : '';
        if ($includeanchor) {
            $uri .= $this->get_encoded_anchor();
        }

        return $uri;
    }

    /**
     * Compares this quipux_url with another.
     *
     * See documentation of constants for an explanation of the comparison flags.
     *
     * @param quipux_url $url The quipux_url object to compare
     * @param int $matchtype The type of comparison (URL_MATCH_BASE, URL_MATCH_PARAMS, URL_MATCH_EXACT)
     * @return bool
     */
    public function compare(quipux_url $url, $matchtype = URL_MATCH_EXACT) {

        $baseself = $this->out_omit_querystring();
        $baseother = $url->out_omit_querystring();

        // Append index.php if there is no specific file.
        if (substr($baseself, -1) == '/') {
            $baseself .= 'index.php';
        }
        if (substr($baseother, -1) == '/') {
            $baseother .= 'index.php';
        }

        // Compare the two base URLs.
        if ($baseself != $baseother) {
            return false;
        }

        if ($matchtype == URL_MATCH_BASE) {
            return true;
        }

        $urlparams = $url->params();
        foreach ($this->params() as $param => $value) {
            if ($param == 'sesskey') {
                continue;
            }
            if (!array_key_exists($param, $urlparams) || $urlparams[$param] != $value) {
                return false;
            }
        }

        if ($matchtype == URL_MATCH_PARAMS) {
            return true;
        }

        foreach ($urlparams as $param => $value) {
            if ($param == 'sesskey') {
                continue;
            }
            if (!array_key_exists($param, $this->params()) || $this->param($param) != $value) {
                return false;
            }
        }

        if ($url->anchor !== $this->anchor) {
            return false;
        }

        return true;
    }

    /**
     * Sets the anchor for the URI (the bit after the hash)
     *
     * @param string $anchor null means remove previous
     */
    public function set_anchor($anchor) {
        if (is_null($anchor)) {
            // Remove.
            $this->anchor = null;
        } else {
            $this->anchor = $anchor;
        }
    }

    /**
     * Sets the scheme for the URI (the bit before ://)
     *
     * @param string $scheme
     */
    public function set_scheme($scheme) {
        // See http://www.ietf.org/rfc/rfc3986.txt part 3.1.
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*$/', $scheme)) {
            $this->scheme = $scheme;
        } else {
            throw new coding_exception('Bad URL scheme.');
        }
    }

    /**
     * Sets the url slashargument value.
     *
     * @param string $path usually file path
     * @param string $parameter name of page parameter if slasharguments not supported
     * @param bool $supported usually null, then it depends on $CFG->slasharguments, use true or false for other servers
     * @return void
     */
    public function set_slashargument($path, $parameter = 'file', $supported = null) {
        global $CFG;
        if (is_null($supported)) {
            $supported = !empty($CFG->slasharguments);
        }

        if ($supported) {
            $parts = explode('/', $path);
            $parts = array_map('rawurlencode', $parts);
            $path  = implode('/', $parts);
            $this->slashargument = $path;
            unset($this->params[$parameter]);

        } else {
            $this->slashargument = '';
            $this->params[$parameter] = $path;
        }
    }

    // Static factory methods.

    /**
     * General moodle file url.
     *
     * @param string $urlbase the script serving the file
     * @param string $path
     * @param bool $forcedownload
     * @return quipux_url
     */
    public static function make_file_url($urlbase, $path, $forcedownload = false) {
        $params = array();
        if ($forcedownload) {
            $params['forcedownload'] = 1;
        }
        $url = new quipux_url($urlbase, $params);
        $url->set_slashargument($path);
        return $url;
    }

    /**
     * Factory method for creation of url pointing to plugin file.
     *
     * Please note this method can be used only from the plugins to
     * create urls of own files, it must not be used outside of plugins!
     *
     * @param int $contextid
     * @param string $component
     * @param string $area
     * @param int $itemid
     * @param string $pathname
     * @param string $filename
     * @param bool $forcedownload
     * @param mixed $includetoken Whether to use a user token when displaying this group image.
     *                True indicates to generate a token for current user, and integer value indicates to generate a token for the
     *                user whose id is the value indicated.
     *                If the group picture is included in an e-mail or some other location where the audience is a specific
     *                user who will not be logged in when viewing, then we use a token to authenticate the user.
     * @return quipux_url
     */
    public static function make_pluginfile_url($contextid, $component, $area, $itemid, $pathname, $filename,
                                               $forcedownload = false, $includetoken = false) {
        global $CFG, $USER;

        $path = [];

        if ($includetoken) {
            $urlbase = "$CFG->wwwroot/tokenpluginfile.php";
            $userid = $includetoken === true ? $USER->id : $includetoken;
            $token = get_user_key('core_files', $userid);
            if ($CFG->slasharguments) {
                $path[] = $token;
            }
        } else {
            $urlbase = "$CFG->wwwroot/pluginfile.php";
        }
        $path[] = $contextid;
        $path[] = $component;
        $path[] = $area;

        if ($itemid !== null) {
            $path[] = $itemid;
        }

        $path = "/" . implode('/', $path) . "{$pathname}{$filename}";

        $url = self::make_file_url($urlbase, $path, $forcedownload, $includetoken);
        if ($includetoken && empty($CFG->slasharguments)) {
            $url->param('token', $token);
        }
        return $url;
    }

    /**
     * Factory method for creation of url pointing to plugin file.
     * This method is the same that make_pluginfile_url but pointing to the webservice pluginfile.php script.
     * It should be used only in external functions.
     *
     * @since  2.8
     * @param int $contextid
     * @param string $component
     * @param string $area
     * @param int $itemid
     * @param string $pathname
     * @param string $filename
     * @param bool $forcedownload
     * @return quipux_url
     */
    public static function make_webservice_pluginfile_url($contextid, $component, $area, $itemid, $pathname, $filename,
                                                          $forcedownload = false) {
        global $CFG;
        $urlbase = "$CFG->wwwroot/webservice/pluginfile.php";
        if ($itemid === null) {
            return self::make_file_url($urlbase, "/$contextid/$component/$area".$pathname.$filename, $forcedownload);
        } else {
            return self::make_file_url($urlbase, "/$contextid/$component/$area/$itemid".$pathname.$filename, $forcedownload);
        }
    }

    /**
     * Factory method for creation of url pointing to draft file of current user.
     *
     * @param int $draftid draft item id
     * @param string $pathname
     * @param string $filename
     * @param bool $forcedownload
     * @return quipux_url
     */
    public static function make_draftfile_url($draftid, $pathname, $filename, $forcedownload = false) {
        global $CFG, $USER;
        $urlbase = "$CFG->wwwroot/draftfile.php";
        $context = context_user::instance($USER->id);

        return self::make_file_url($urlbase, "/$context->id/user/draft/$draftid".$pathname.$filename, $forcedownload);
    }

    /**
     * Factory method for creating of links to legacy course files.
     *
     * @param int $courseid
     * @param string $filepath
     * @param bool $forcedownload
     * @return quipux_url
     */
    public static function make_legacyfile_url($courseid, $filepath, $forcedownload = false) {
        global $CFG;

        $urlbase = "$CFG->wwwroot/file.php";
        return self::make_file_url($urlbase, '/'.$courseid.'/'.$filepath, $forcedownload);
    }

    /**
     * Checks if URL is relative to $CFG->wwwroot.
     *
     * @return bool True if URL is relative to $CFG->wwwroot; otherwise, false.
     */
    public function is_local_url() : bool {
        global $CFG;

        $url = $this->out();
        // Does URL start with wwwroot? Otherwise, URL isn't relative to wwwroot.
        return ( ($url === $CFG->wwwroot) || (strpos($url, $CFG->wwwroot.'/') === 0) );
    }

    /**
     * Returns URL as relative path from $CFG->wwwroot
     *
     * Can be used for passing around urls with the wwwroot stripped
     *
     * @param boolean $escaped Use &amp; as params separator instead of plain &
     * @param array $overrideparams params to add to the output url, these override existing ones with the same name.
     * @return string Resulting URL
     * @throws coding_exception if called on a non-local url
     */
    public function out_as_local_url($escaped = true, array $overrideparams = null) {
        global $CFG;

        // URL should be relative to wwwroot. If not then throw exception.
        if ($this->is_local_url()) {
            $url = $this->out($escaped, $overrideparams);
            $localurl = substr($url, strlen($CFG->wwwroot));
            return !empty($localurl) ? $localurl : '';
        } else {
            throw new coding_exception('out_as_local_url called on a non-local URL');
        }
    }

    /**
     * Returns the 'path' portion of a URL. For example, if the URL is
     * http://www.example.org:447/my/file/is/here.txt?really=1 then this will
     * return '/my/file/is/here.txt'.
     *
     * By default the path includes slash-arguments (for example,
     * '/myfile.php/extra/arguments') so it is what you would expect from a
     * URL path. If you don't want this behaviour, you can opt to exclude the
     * slash arguments. (Be careful: if the $CFG variable slasharguments is
     * disabled, these URLs will have a different format and you may need to
     * look at the 'file' parameter too.)
     *
     * @param bool $includeslashargument If true, includes slash arguments
     * @return string Path of URL
     */
    public function get_path($includeslashargument = true) {
        return $this->path . ($includeslashargument ? $this->slashargument : '');
    }

    /**
     * Returns a given parameter value from the URL.
     *
     * @param string $name Name of parameter
     * @return string Value of parameter or null if not set
     */
    public function get_param($name) {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        } else {
            return null;
        }
    }

    /**
     * Returns the 'scheme' portion of a URL. For example, if the URL is
     * http://www.example.org:447/my/file/is/here.txt?really=1 then this will
     * return 'http' (without the colon).
     *
     * @return string Scheme of the URL.
     */
    public function get_scheme() {
        return $this->scheme;
    }

    /**
     * Returns the 'host' portion of a URL. For example, if the URL is
     * http://www.example.org:447/my/file/is/here.txt?really=1 then this will
     * return 'www.example.org'.
     *
     * @return string Host of the URL.
     */
    public function get_host() {
        return $this->host;
    }

    /**
     * Returns the 'port' portion of a URL. For example, if the URL is
     * http://www.example.org:447/my/file/is/here.txt?really=1 then this will
     * return '447'.
     *
     * @return string Port of the URL.
     */
    public function get_port() {
        return $this->port;
    }
}
