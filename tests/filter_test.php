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
 * Test filter lib.
 * @author    Guy Thomas <gthomas@moodlerooms.com>
 * @copyright Copyright (c) 2017 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

use tool_ally\local_file;

class filter_ally_testcase extends advanced_testcase {

    public $filter;

    public function setUp() {
        global $PAGE, $CFG;

        // We reset after every test because the filter modifies $CFG->additionalhtmlfooter.
        $this->resetAfterTest();

        require_once(__DIR__.'/../filter.php');

        $PAGE->set_url($CFG->wwwroot.'/course/view.php');
        $context = context_system::instance();
        $this->filter = new filter_ally($context, []);
        $this->filter->setup($PAGE, $context);
    }

    public function test_is_course_page() {
        global $PAGE, $CFG;

        $PAGE->set_url($CFG->wwwroot.'/course/view.php');
        $iscoursepage = phpunit_util::call_internal_method($this->filter, 'is_course_page', [], 'filter_ally');
        $this->assertTrue($iscoursepage);
        $PAGE->set_url($CFG->wwwroot.'/user/view.php');
        $iscoursepage = phpunit_util::call_internal_method($this->filter, 'is_course_page', [], 'filter_ally');
        $this->assertFalse($iscoursepage);
    }

    public function test_map_assignment_file_paths_to_pathhash() {
        global $PAGE, $CFG;

        $gen = $this->getDataGenerator();

        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_assignment_file_paths_to_pathhash', [], 'filter_ally'
        );
        $this->assertEmpty($map);

        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_assignment_file_paths_to_pathhash', [], 'filter_ally'
        );
        $this->assertEmpty($map);

        $course = $gen->create_course();
        $data = (object) [
            'course' => $course->id
        ];
        $assign = $gen->create_module('assign', $data);

        $fixturedir = $CFG->dirroot.'/filter/ally/tests/fixtures/';
        $files = scandir($fixturedir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $file = trim($file);
            $fixturepath = $fixturedir.$file;

            // Add actual file there.
            $filerecord = ['component' => 'mod_assign', 'filearea' => 'introattachment',
                'contextid' => context_module::instance($assign->cmid)->id, 'itemid' => 0,
                'filename' => $file, 'filepath' => '/'];
            $fs = get_file_storage();
            $fs->create_file_from_pathname($filerecord, $fixturepath);
        }

        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_assignment_file_paths_to_pathhash', [], 'filter_ally'
        );
        $this->assertEmpty($map);

        $PAGE->set_pagetype('mod-assign-view');
        $_GET['id'] = $assign->cmid;
        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_assignment_file_paths_to_pathhash', [], 'filter_ally'
        );
        $this->assertNotEmpty($map);
    }

    public function test_map_folder_file_paths_to_pathhash() {
        global $PAGE, $CFG;

        $this->setAdminUser();

        $gen = $this->getDataGenerator();

        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_folder_file_paths_to_pathhash', [], 'filter_ally'
        );
        $this->assertEmpty($map);

        $course = $gen->create_course();
        $data = (object) [
            'course' => $course->id
        ];
        $assign = $gen->create_module('folder', $data);

        $fixturedir = $CFG->dirroot.'/filter/ally/tests/fixtures/';
        $files = scandir($fixturedir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $file = trim($file);
            $fixturepath = $fixturedir.$file;

            // Add actual file there.
            $filerecord = [
                'component' => 'mod_folder',
                'filearea' => 'content',
                'contextid' => context_module::instance($assign->cmid)->id,
                'itemid' => 0,
                'filename' => $file,
                'filepath' => '/'
            ];
            $fs = get_file_storage();
            $fs->create_file_from_pathname($filerecord, $fixturepath);
        }

        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_folder_file_paths_to_pathhash', [], 'filter_ally'
        );
        $this->assertEmpty($map);

        $PAGE->set_pagetype('mod-folder-view');
        $_GET['id'] = $assign->cmid;
        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_folder_file_paths_to_pathhash', [], 'filter_ally'
        );
        $this->assertNotEmpty($map);
    }

    public function map_resource_file_paths_to_pathhash() {
        global $PAGE, $CFG;

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $student = $gen->create_user();
        $gen->enrol_user($student->id, $course->id, 'student');

        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_resource_file_paths_to_pathhash', [$course], 'filter_ally'
        );
        $this->assertEmpty($map);

        $fixturedir = $CFG->dirroot.'/filter/ally/tests/fixtures/';
        $files = scandir($fixturedir);

        $this->setAdminUser();

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $file = trim($file);
            $fixturepath = $fixturedir.$file;

            $data = (object) [
                'course'  => $course->id,
                'name'    => $file,
                'visible' => 0
            ];

            $resource = $gen->create_module('resource', $data);

            // Add actual file there.
            $filerecord = ['component' => 'mod_assign', 'filearea' => 'introattachment',
                'contextid' => context_module::instance($resource->cmid)->id, 'itemid' => 0,
                'filename' => $file, 'filepath' => '/'];
            $fs = get_file_storage();
            $fs->create_file_from_pathname($filerecord, $fixturepath);
        }

        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_resource_file_paths_to_pathhash', [$course], 'filter_ally'
        );
        $this->assertNotEmpty($map);

        // Check students don't get anything as all the resources were invisible.
        $this->setUser($student);
        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_resource_file_paths_to_pathhash', [$course], 'filter_ally'
        );
        $this->assertEmpty($map);

        // Check that admin user doesn't get anything when not on the appropriate page.
        $this->setAdminUser();
        $PAGE->set_url($CFG->wwwroot.'/user/view.php');
        $PAGE->set_pagetype('course-view-topics');
        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_resource_file_paths_to_pathhash', [$course], 'filter_ally'
        );

        $this->assertEmpty($map);
    }

    /**
     * @param bool $fileparam
     */
    public function test_process_url($fileparam = false) {
        $fileparam = $fileparam ? '?file=' : '';

        $urlformats = [
            'somecomponent' => 'http://test.com/pluginfile.php'.$fileparam.'/123/somecomponent/somearea/myfile.test',
            'label' => 'http://test.com/pluginfile.php'.$fileparam.'/123/label/somearea/0/myfile.test',
            'question' => 'http://test.com/pluginfile.php'.$fileparam.'/123/question/somearea/123/5/0/myfile.test'
        ];

        foreach ($urlformats as $expectedcomponent => $url) {
            list($contextid, $component, $filearea, $itemid, $filename) = phpunit_util::call_internal_method(
                $this->filter, 'process_url', [$url], 'filter_ally'
            );
            $this->assertEquals(123, $contextid);
            $this->assertEquals($expectedcomponent, $component);
            $this->assertEquals('somearea', $filearea);
            $this->assertEquals(0, $itemid);
            $this->assertEquals('myfile.test', $filename);
        }
    }

    public function test_process_url_fileparam() {
        $this->test_process_url(true);
    }

    /**
     * Get mock html for testing images.
     * @param string $url
     * @return string
     */
    protected function img_mock_html($url) {
        $text = <<<EOF
        <p>
            <span>text</span>
            写埋ルがンい未50要スぱ指6<img src="$url"/>more more text
        </p>
        <img src="$url">Here's that image again but void without closing tag.
EOF;
        return $text;
    }

    public function test_filter_img() {
        global $PAGE, $CFG;

        $PAGE->set_url($CFG->wwwroot.'/course/view.php');

        $gen = $this->getDataGenerator();

        $course = $gen->create_course();
        $student = $gen->create_user();
        $teacher = $gen->create_user();
        $gen->enrol_user($student->id, $course->id, 'student');
        $gen->enrol_user($teacher->id, $course->id, 'editingteacher');

        $this->setUser($teacher);

        $fs = get_file_storage();
        $filerecord = array(
            'contextid' => context_course::instance($course->id)->id,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'test.png'
        );
        $teststring = 'moodletest';
        $file = $fs->create_file_from_string($filerecord, $teststring);
        $url = local_file::url($file);

        $this->setUser($student);

        $text = $this->img_mock_html($url);
        $filteredtext = $this->filter->filter($text);
        // Make sure seizure guard image cover exists.
        $this->assertContains('<span class="ally-image-cover"', $filteredtext);
        // As we are not logged in as a teacher, we shouldn't get the feedback placeholder.
        $this->assertNotContains('<span class="ally-feedback"', $filteredtext);
        // Make sure both images were processed.
        $regex = '~<span class="filter-ally-wrapper ally-image-wrapper">'.
            '\\n'.'(?:\s*|)<img src="'.preg_quote($url, '~').'"~';
        preg_match_all($regex, $filteredtext, $matches);
        $count = count($matches[0]);
        $this->assertEquals(2, $count);
        $substr = '<span class="ally-image-cover"';
        $count = substr_count($filteredtext, $substr);
        $this->assertEquals(2, $count);

        $this->setUser($teacher);
        // Make sure teachers get seizure guard and feedback place holder.
        $filteredtext = $this->filter->filter($text);
        $this->assertContains('<span class="ally-image-cover"', $filteredtext);
        // As we are logged in as a teacher, we should get the feedback placeholder.
        $this->assertContains('<span class="ally-feedback"', $filteredtext);
        // Make sure both images were processed.
        preg_match_all($regex, $filteredtext, $matches);
        $count = count($matches[0]);
        $this->assertEquals(2, $count);
        $substr = '<span class="ally-image-cover"';
        $count = substr_count($filteredtext, $substr);
        $this->assertEquals(2, $count);
        $substr = '<span class="ally-feedback"';
        $count = substr_count($filteredtext, $substr);
        $this->assertEquals(2, $count);

        // Make sure that files created by students are not processed.
        $this->setUser($student);
        $fs = get_file_storage();
        $label = $gen->create_module('label', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($label->cmid);
        $filerecord = array(
            'contextid' => $cm->context->id,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'test-student-file.png',
            'userid' => $student->id
        );
        $teststring = 'moodletest';
        $file = $fs->create_file_from_string($filerecord, $teststring);
        $url = local_file::url($file);
        $text = $this->img_mock_html($url);
        // Make sure neither student created images were processed when logged in as a student.
        $filteredtext = $this->filter->filter($text);
        $this->assertNotContains('<span class="filter-ally-wrapper ally-image-wrapper">', $filteredtext);
        $this->assertNotContains('<span class="ally-image-cover"', $filteredtext);
        $this->assertNotContains('<span class="ally-feedback"', $filteredtext);

        // Make sure neither student created images were processed when logged in as a teacher.
        $this->setUser($teacher);
        $filteredtext = $this->filter->filter($text);
        $this->assertNotContains('<span class="filter-ally-wrapper ally-image-wrapper">', $filteredtext);
        $this->assertNotContains('<span class="ally-image-cover"', $filteredtext);
        $this->assertNotContains('<span class="ally-feedback"', $filteredtext);
    }

    public function test_filter_img_noslashargs() {
        global $CFG;
        $CFG->slasharguments = 0;
        $this->test_filter_img();
    }

    public function test_filter_img_blacklistedcontexts() {
        global $PAGE, $CFG, $USER;

        $this->setAdminUser();

        $PAGE->set_url($CFG->wwwroot.'/course/view.php');

        $gen = $this->getDataGenerator();

        $category = $gen->create_category();

        $blacklistedcontexts = [
            context_coursecat::instance($category->id),
            context_system::instance(),
            context_user::instance($USER->id)
        ];

        foreach ($blacklistedcontexts as $context) {
            $fs = get_file_storage();
            $filerecord = array(
                'contextid' => $context->id,
                'component' => 'mod_label',
                'filearea' => 'intro',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'test.png'
            );
            $teststring = 'moodletest';
            $fs->create_file_from_string($filerecord, $teststring);
            $path = str_replace('//', '', implode('/', $filerecord));

            $text = <<<EOF
            <p>
                <span>text</span>
                写埋ルがンい未50要スぱ指6<img src="$CFG->wwwroot/pluginfile.php/$path"/>more more text
            </p>
            <img src="$CFG->wwwroot/pluginfile.php/$path">Here's that image again but void without closing tag.
EOF;

            // We shouldn't get anything when the contexts are blacklisted.
            $filteredtext = $this->filter->filter($text);
            $this->assertNotContains('<span class="ally-image-cover"', $filteredtext);
            $this->assertNotContains('<span class="ally-feedback"', $filteredtext);
            $substr = '<span class="filter-ally-wrapper ally-image-wrapper">' .
                '<img src="' . $CFG->wwwroot . '/pluginfile.php/' . $path . '"';
            $this->assertNotContains($substr, $filteredtext);
            $substr = '<span class="ally-image-cover"';
            $this->assertNotContains($substr, $filteredtext);
            $substr = '<span class="ally-feedback"';
            $this->assertNotContains($substr, $filteredtext);
        }
    }

    public function test_filter_img_blacklistedcontexts_noslashargs() {
        global $CFG;
        $CFG->slasharguments = 0;
        $this->test_filter_img_blacklistedcontexts();
    }

    /**
     * Make sure that regex chars are handled correctly when present in img src file names.
     */
    public function test_filter_img_regexchars() {

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $teacher = $gen->create_user();
        $gen->enrol_user($teacher->id, $course->id, 'teacher');
        $this->setUser($teacher);
        $fs = get_file_storage();

        // Test regex chars in file name.
        $regextestfilenames = [
            'test (2).png',
            'test (3:?).png',
            'test (~4).png'
        ];
        $urls = [];
        $text = '';
        foreach ($regextestfilenames as $filename) {
            $filerecord = array(
                'contextid' => context_course::instance($course->id)->id,
                'component' => 'mod_label',
                'filearea' => 'intro',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $filename
            );
            $teststring = 'moodletest';
            $file = $fs->create_file_from_string($filerecord, $teststring);
            $url = local_file::url($file);
            $urls[] = $url;
            $text .= '<img src="'.$url.'"/>test';
        }
        $text = '<p>'.$text.'</p>';
        $filteredtext = $this->filter->filter($text);
        // Make sure all images were processed.
        $substr = '<span class="ally-image-cover"';
        $count = substr_count($filteredtext, $substr);
        $this->assertEquals(count($regextestfilenames), $count);
        $substr = '<span class="ally-feedback"';
        $count = substr_count($filteredtext, $substr);
        $this->assertEquals(count($regextestfilenames), $count);
        foreach ($urls as $url) {
            $regex = '~<span class="filter-ally-wrapper ally-image-wrapper">'.
                '\\n'.'(?:\s*|)<img src="'.preg_quote($url, '~').'"~';
            preg_match_all($regex, $filteredtext, $matches);
            $count = count($matches[0]);
            $this->assertEquals(1, $count);
        }
    }

    public function test_filter_img_regexchars_noslashargs() {
        global $CFG;
        $CFG->slasharguments = 0;
        $this->test_filter_img_regexchars();
    }

    /**
     * Create mock html for anchors.
     * @param string $url
     * @return string
     */
    protected function anchor_mock_html($url) {
        $text = <<<EOF
        <p>
            <span>text</span>
            写埋ルがンい未50要スぱ指6<a href="$url">HI THERE</a>more more text
        </p>
        <a href="$url">Here's that anchor again.</a>Boo!
EOF;
        return $text;
    }

    public function test_filter_anchor() {

        $gen = $this->getDataGenerator();

        $course = $gen->create_course();
        $student = $gen->create_user();
        $teacher = $gen->create_user();
        $gen->enrol_user($student->id, $course->id, 'student');
        $gen->enrol_user($teacher->id, $course->id, 'teacher');

        $fs = get_file_storage();
        $filerecord = array(
            'contextid' => context_course::instance($course->id)->id,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'test.txt'
        );
        $teststring = 'moodletest';
        $file = $fs->create_file_from_string($filerecord, $teststring);
        $url = local_file::url($file);

        $this->setUser($student);

        $text = $this->anchor_mock_html($url);
        $filteredtext = $this->filter->filter($text);
        // Make sure student gets download palceholder.
        $this->assertContains('<div class="ally-download"', $filteredtext);
        // As we are not logged in as a teacher, we shouldn't get the feedback placeholder.
        $this->assertNotContains('<div class="ally-feedback"', $filteredtext);
        // Make sure both anchors were processed.
        $regex = '~<div class="filter-ally-wrapper ally-anchor-wrapper clearfix">'.
            '\\n'.'(?:\s*|)<a href="'.preg_quote($url, '~').'"~';
        preg_match_all($regex, $filteredtext, $matches);
        $count = count($matches[0]);
        $this->assertEquals(2, $count);

        $this->setUser($teacher);
        // Make sure teachers get download and feedback place holder.
        $filteredtext = $this->filter->filter($text);
        $this->assertContains('<div class="ally-download"', $filteredtext);
        // As we are logged in as a teacher, we should get the feedback placeholder.
        $this->assertContains('<div class="ally-feedback"', $filteredtext);
        // Make sure both anchors were processed.
        preg_match_all($regex, $filteredtext, $matches);
        $count = count($matches[0]);
        $this->assertEquals(2, $count);

        // Make sure that files created by students are not processed.
        $this->setUser($student);
        $fs = get_file_storage();
        $label = $gen->create_module('label', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($label->cmid);
        $filerecord = array(
            'contextid' => $cm->context->id,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'test-student-file.txt',
            'userid' => $student->id
        );
        $teststring = 'moodletest';
        $file = $fs->create_file_from_string($filerecord, $teststring);
        $url = local_file::url($file);
        $text = $this->anchor_mock_html($url);
        // Make sure neither student created files were processed when logged in as a student.
        $filteredtext = $this->filter->filter($text);
        $this->assertNotContains('<span class="filter-ally-wrapper ally-image-wrapper">', $filteredtext);
        $this->assertNotContains('<span class="ally-download"', $filteredtext);
        $this->assertNotContains('<span class="ally-feedback"', $filteredtext);

        // Make sure neither student created files were processed when logged in as a teacher.
        $this->setUser($teacher);
        $filteredtext = $this->filter->filter($text);
        $this->assertNotContains('<span class="filter-ally-wrapper ally-image-wrapper">', $filteredtext);
        $this->assertNotContains('<span class="ally-download"', $filteredtext);
        $this->assertNotContains('<span class="ally-feedback"', $filteredtext);
    }

    public function test_filter_anchor_noslashargs() {
        global $CFG;
        $CFG->slasharguments = 0;
        $this->test_filter_anchor();
    }

    public function test_filter_anchor_blacklistedcontexts() {
        global $PAGE, $CFG, $USER;

        $this->setAdminUser();

        $PAGE->set_url($CFG->wwwroot.'/course/view.php');

        $gen = $this->getDataGenerator();

        $category = $gen->create_category();

        $blacklistedcontexts = [
            context_coursecat::instance($category->id),
            context_system::instance(),
            context_user::instance($USER->id)
        ];

        foreach ($blacklistedcontexts as $context) {
            $fs = get_file_storage();
            $filerecord = array(
                'contextid' => $context->id,
                'component' => 'mod_label',
                'filearea' => 'intro',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'test.txt'
            );
            $teststring = 'moodletest';
            $file = $fs->create_file_from_string($filerecord, $teststring);
            $url = local_file::url($file);

            $text = <<<EOF
            <p>
                <span>text</span>
                写埋ルがンい未50要スぱ指6<a href="$url">HI THERE</a>more more text
            </p>
            <a href="$url">Here's that anchor again.</a>Boo!
EOF;
            // We shouldn't get anything when contexts were blacklisted.
            $filteredtext = $this->filter->filter($text);
            $this->assertNotContains('<span class="ally-download"', $filteredtext);
            $this->assertNotContains('<span class="ally-feedback"', $filteredtext);
            // Make sure wrappers do not exist - i.e not processed.
            $regex = '~<div class="filter-ally-wrapper ally-anchor-wrapper clearfix">'.
                '\\n'.'(?:\s*|)<a href="'.preg_quote($url, '~').'"~';
            preg_match_all($regex, $filteredtext, $matches);
            $count = count($matches[0]);
            $this->assertEquals(0, $count);
        }
    }

    public function test_filter_anchor_blacklistedcontexts_noslashargs() {
        global $CFG;
        $CFG->slasharguments = 0;
        $this->test_filter_anchor_blacklistedcontexts();
    }

    /**
     * Make sure that regex chars are handled correctly when present in anchor href file names.
     */
    public function test_filter_anchor_regexchars() {

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $teacher = $gen->create_user();
        $gen->enrol_user($teacher->id, $course->id, 'teacher');
        $this->setUser($teacher);
        $fs = get_file_storage();

        // Test regex chars in file name.
        $regextestfilenames = [
            'test (2).txt',
            'test (3:?).txt',
            'test (~4).txt'
        ];
        $urls = [];
        $text = '';
        foreach ($regextestfilenames as $filename) {
            $filerecord = array(
                'contextid' => context_course::instance($course->id)->id,
                'component' => 'mod_label',
                'filearea' => 'intro',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $filename
            );
            $teststring = 'moodletest';
            $file = $fs->create_file_from_string($filerecord, $teststring);
            $url = local_file::url($file);
            $urls[] = $url;
            $text .= '<a href="'.$url.'">test</a>';
        }
        $text = '<p>'.$text.'</p>';
        $filteredtext = $this->filter->filter($text);
        // Make sure all anchors were processed.
        $substr = '<div class="ally-download"';
        $count = substr_count($filteredtext, $substr);
        $this->assertEquals(count($regextestfilenames), $count);
        $substr = '<div class="ally-feedback"';
        $count = substr_count($filteredtext, $substr);
        $this->assertEquals(count($regextestfilenames), $count);
        foreach ($urls as $url) {
            $regex = '~<div class="filter-ally-wrapper ally-anchor-wrapper clearfix">'.
                '\\n'.'(?:\s*|)<a href="'.preg_quote($url, '~').'"~';
            preg_match_all($regex, $filteredtext, $matches);
            $count = count($matches[0]);
            $this->assertEquals(1, $count);
        }
    }

    public function test_filter_anchor_regexchars_noslashargs() {
        global $CFG;
        $CFG->slasharguments = 0;
        $this->test_filter_anchor_regexchars();
    }

    public function test_map_forum_attachment_file_paths_to_pathhash() {
        global $PAGE, $CFG, $DB, $COURSE;

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $student = $gen->create_user();
        $teacher = $gen->create_user();
        $gen->enrol_user($student->id, $course->id, 'student');
        $gen->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);

        $PAGE->set_pagetype('mod-forum');
        $COURSE = $course;

        // Should be empty when nothing added.
        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_forum_attachment_file_paths_to_pathhash', [$course], 'filter_ally'
        );
        $this->assertEmpty($map);

        $record = new stdClass();
        $record->course = $course->id;
        $forum = self::getDataGenerator()->create_module('forum', $record);
        $_GET['id'] = $forum->cmid;
        $record = array();
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $teacher->id;
        $discussion = self::getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);
        $post = $DB->get_record('forum_posts', ['discussion' => $discussion->id, 'parent' => 0]);

        // Add a text file.
        $filerecord = ['component' => 'mod_forum', 'filearea' => 'attachment',
            'contextid' => context_module::instance($forum->cmid)->id, 'itemid' => $post->id,
            'filename' => 'test file.txt', 'filepath' => '/'];
        $fs = get_file_storage();
        $fs->create_file_from_string($filerecord, 'Test content');

        // Should still be empty when a non image file has been added (only image files are mapped).
        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_forum_attachment_file_paths_to_pathhash', [$course], 'filter_ally'
        );
        $this->assertEmpty($map);

        // Add an image file.
        $testfile = 'testpng_small.png';
        $filerecord = ['component' => 'mod_forum', 'filearea' => 'attachment',
            'contextid' => context_module::instance($forum->cmid)->id, 'itemid' => $post->id,
            'filename' => $testfile, 'filepath' => '/'];
        $fs = get_file_storage();
        $fixturedir = $CFG->dirroot.'/filter/ally/tests/fixtures/';
        $fixturepath = $fixturedir.'/'.$testfile;
        $fs->create_file_from_pathname($filerecord, $fixturepath);

        // Shouldn't be be empty when an image file has been added (only image files are mapped).
        $map = phpunit_util::call_internal_method(
            $this->filter, 'map_forum_attachment_file_paths_to_pathhash', [$course], 'filter_ally'
        );
        $this->assertNotEmpty($map);
    }
}
