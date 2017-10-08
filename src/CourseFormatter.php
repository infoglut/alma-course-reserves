<?php

/*
 * This file is part of the CSU Course Reserves App
 *
 * (c) California State University <library@calstate.edu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Reserves;

use Alma\Courses\Course;
use Alma\Courses\Metadata;
use Twig_Extension;
use Twig_SimpleFilter;

/**
 * Formatting filters for Twig
 * 
 * @author dwalker
 */
class CourseFormatter extends Twig_Extension
{
    /**
     * @var array
     */
    protected $config = array();
    
    /**
     * @var string
     */
    protected $campus;
    
    /**
     * New Course Formatter object
     */
    public function __construct($campus)
    {
        $this->config = parse_ini_file("campuses/$campus/config.ini", true);
        $this->campus = $campus;
    }
    
    /**
     * {@inheritDoc}
     * @see Twig_Extension::getFunctions()
     */
    public function getFilters()
    {
        return array(
            new Twig_SimpleFilter('display_course_name', array($this, 'displayCourseName')),
            new Twig_SimpleFilter('display_instructors', array($this, 'displayInstructors')),
            new Twig_SimpleFilter('display_title', array($this, 'displayTitle')),
            new Twig_SimpleFilter('display_publication_info', array($this, 'displayPublicationInfo')),
            new Twig_SimpleFilter('mmsid', array($this, 'getMmsId')),
        );
    }
    
    /**
     * Return course code and name
     *
     * @param Course $course
     * @return string
     */
    public function displayCourseName(Course $course)
    {
        $code = $course->getCode();
        $name = $course->getName();
        $notes = $course->getNotes();
    
        // if there is a code suffix it with colon
    
        if ( $code != "" ) {
            $code .= ': ';
        }
    
        return trim($code . $name);
    }
    
    /**
     * Return instructors in collapsed list
     *
     * @param Course $course
     * @return string
     */
    public function displayInstructors(Course $course)
    {
        // instructor assigned to course
    
    	$inst = array();
    
    	foreach ($course->getInstructors() as $instructor) {
    		$inst[] = $instructor->getLastName();
    	}
    
    	if (count($inst) > 0) {
    	    return trim(implode("; ", $inst));
    	}
    
    	// instructor brought over as note, during migration
    
    	foreach ($course->getNotes() as $note) {
    	    $content = $note->getContent();
    
    	    if (strstr($content, 'Instructor:')) {
    	        return trim(str_replace('Instructor:', '', $content));
    	    } elseif (strstr($content, 'PROF_TA:')) {
    	        return trim(str_replace('PROF_TA:', '', $content));
    	    }
    	}
    
    	// nada
    	return null;
    }
    
    /**
     * Title for display
     * 
     * Show title, remove statement of responsibility
     * 
     * @param Metadata $metadata
     * @return string
     */
    public function displayTitle(Metadata $metadata)
    {
        $title = $metadata->getTitle();
        $jtitle = $metadata->getJournalTitle();
        
        if ($title == ""  && $jtitle != "") {
            $title = $jtitle;
        }
        
        if (strstr($title, '/')) {
            $parts = explode('/', $title);
            array_pop($parts);
            $title = implode('/', $parts);
        }
    
        return $title;
    }
    
    /**
     * Publication information
     * 
     * Put together place, publisher, and date
     * 
     * @param Metadata $metadata
     * @return string
     */
    public function displayPublicationInfo(Metadata $metadata)
    {
        $final = "";
        $place = $metadata->getPlaceOfPublication();
        $publisher = $metadata->getPublisher();
        $date = $metadata->getPublicationDate();
    
        if ($publisher != "") {
            $final = $place . ' ' . $publisher;
    
            if ($date != "") {
                $final .= ', ' . $date;
            }
        } elseif ($date != "") {
            return $date;
        }
    
        return $final;
    }
    
    /**
     * Workaround to get MMS_ID
     * 
     * @param Metadata $metadata
     * @return string
     */
    public function getMmsId(Metadata $metadata)
    {
        return (string) $metadata->json()->mms_id;
    }
}