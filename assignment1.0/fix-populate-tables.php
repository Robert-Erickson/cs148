<?php

/* This page grabs data from the UVM web site and redisplays it here.
 *
 * this page displays a list of courses from the registrars web site with the
 * status highlighted, going to be canceled, full or ok. the web interface is
 * lousy but i made the example short so it gives you something to play with :)
 *
 * Written By: Robert Erickson robert.erickson@uvm.edu
 * Last updated on: August 21, 2015
 *
 *
 * creates tables each time (dropping table if it exists)
 * Generates about nine warnings (which I have not traced):
 * Warning: PDOStatement::execute(): SQLSTATE[HY093]: Invalid parameter number: 
 * number of bound variables does not match number of tokens in myDatabase.php 
 * on line 139 
 */
// %^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%^%
//
//
//-----------------------------------------------------------------------------
//
// Initialize variables
//
// SQL to create tables, drop if they exist
// any other error checking may be good, parsing html entities etc
//choose which semester data you want to scrape
include "top.php";


$url = "http://giraffe.uvm.edu/~rgweb/batch/curr_enroll_fall.csv";
//$url="http://giraffe.uvm.edu/~rgweb/batch/curr_enroll_spring.csv";
//$url="http://giraffe.uvm.edu/~rgweb/batch/curr_enroll_summer.csv";

$outputBuffer[] = "";

$debug = false;
if (isset($_GET["debug"])) {
    $debug = true;
}

if ($debug)
    print "<p>DEBUG MODE IS ON</p>";
 
// Process file
$file = fopen($url, "r");
/* the variable $url will be empty or false if the file does not open */
if ($file) {
    if ($debug)
        print "<p>File Opened. Begin reading data into an array.</p>\n";
    /* This reads the first row which in our case is the column headers:
     * Subj # Title Comp Numb Sec Lec Lab Camp Code
     * Max Enrollment Current Enrollment Start Time End Time
     * Days Credits Bldg Room Instructor NetId Email
     */
    $headers = fgetcsv($file);
    /* the while loop keeps exectuing until we reach the end of the file at
     * which point it stops. the resulting variable $records is an array with
     * all our data.
     */
    while (!feof($file)) {
        $records[] = fgetcsv($file);
    }
//closes the file
    fclose($file);
    if ($debug) {
        print "<p>Finished reading. File closed.</p>\n";
        print "<p>Contents of my array<p><pre> ";
        print_r($records);
        print "</pre></p>";
    }
 
//Create tables if they dont exisit
// -- Table structure for table `tblTeachers`
    $query = "DROP TABLE IF EXISTS tblTeachers";
    
    $results = $thisDatabaseAdmin->delete($query);
    
    if($results) $outputBuffer[] =  "<p>Table Teachers dropped. </p>";
    
    $query = "CREATE TABLE IF NOT EXISTS tblTeachers ( ";
    $query .= "fldLastName varchar(100) NOT NULL, ";
    $query .= "fldFirstName varchar(100) NOT NULL, ";
    $query .= "pmkNetId varchar(12) NOT NULL, ";
    $query .= "fldSalary int(11) NOT NULL, ";
    $query .= "fldPhone varchar(7) NOT NULL, ";
    $query .= "PRIMARY KEY (pmkNetId)";
    $query .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8";

    $results = $thisDatabaseAdmin->insert($query, "", 0, 6, 0, 0, false, false);
    if($results) $outputBuffer[] = "<p>tblTeachers Created.</p>";
    
// -- Table structure for table `tblCourses`
    $query = "DROP TABLE IF EXISTS tblCourses";
    
    $results = $thisDatabaseAdmin->delete($query);
    if($results) $outputBuffer[] = "<p>Table Courses dropped. </p>";
    
    $query = "CREATE TABLE IF NOT EXISTS tblCourses ( ";
    $query .= "pmkCourseId int(11) NOT NULL AUTO_INCREMENT, ";
    $query .= "fldCourseNumber int(11) NOT NULL, ";
    $query .= "fldCourseName varchar(250) NOT NULL, ";
    $query .= "fldDepartment varchar(5) NOT NULL, ";
    $query .= "fldCredits tinyint(4) NOT NULL DEFAULT '3',";
    $query .= "PRIMARY KEY (pmkCourseId)";
    $query .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8";
    
    $results = $thisDatabaseAdmin->insert($query, "", 0, 6, 2, 0, false, false);
    
    if($results) $outputBuffer[] = "<p>tblCourses Created.</p>";
    
// -- Table structure for table `tblSections`
    $query = "DROP TABLE IF EXISTS tblSections";
    $results = $thisDatabaseAdmin->delete($query);
    if($results) $outputBuffer[] = "<p>tblSections dropped.</p>";
    
    $query = "CREATE TABLE IF NOT EXISTS tblSections ( ";
    $query .= "fnkCourseId int(11) NOT NULL, ";
    $query .= "fldCRN int(11) NOT NULL, ";
    $query .= "fnkTeacherNetId varchar(12) NOT NULL, ";
    $query .= "fldMaxStudents int(11) NOT NULL, ";
    $query .= "fldNumStudents int(11) NOT NULL, ";
    $query .= "fldSection varchar(3) NOT NULL, ";
    $query .= "fldType varchar(6) NOT NULL, ";
    $query .= "fldStart time, ";
    $query .= "fldStop time, ";
     $query .= "fldDays varchar(8) NOT NULL, ";
    $query .= "fldBuilding varchar(10) NOT NULL, ";
     $query .= "fldRoom varchar(5) NOT NULL, ";
    $query .= "PRIMARY KEY (`fnkCourseId`,`fldCRN`,`fnkTeacherNetId`)";
    $query .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8";

    $results = $thisDatabaseAdmin->insert($query, "", 0, 11, 0, 0, false, false);

    if($results) $outputBuffer[] = "<p>tblSections Created.</p>";
} else {
    if ($debug)
        print "<p>File Opened Failed.</p>\n";
}
//prepare the output
$outputBuffer[] = "<h1>Courses from: " . $url . "</h1>";
$outputBuffer[] = "<p>Showing all courses</p>";
$outputBuffer[] = "<table>";

// intialize variables
$pmkCourseId = 0;
$subj = "";
$num = 0;
$title = "";

// put records into database tables
foreach ($records as $oneClass) {
// course table
    $query = "INSERT INTO tblCourses(fldCourseNumber, fldCourseName, fldDepartment, fldCredits) ";
    $query .= "VALUES (?, ?, ?, ?)";
    $data = array($oneClass[1], $oneClass[2], $oneClass[0], $oneClass[12]);
    if ($debug) {
        print "<p>sql " . $query . "</p><p><pre> ";
        print_r($data);
        print "</pre></p>";
    }
    $style = "background-color: lightblue;";
    if (!($subj == $oneClass[0] and
            $num == $oneClass[1] and
            $title == $oneClass[2])) {
      
        $results = $thisDatabaseWriter->insert($query, $data);
        $pmkCourseId = $thisDatabaseWriter->lastInsert();
        if ($results) {
            $style = "background-color: lightgreen;";
        } else {
            $style = "background-color: lightred;";
        }
    }
    $outputBuffer[] = "\t<tr></th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[1] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[2] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[0] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[12] . "</th>";

//avoid duplicates
    $subj = $oneClass[0];
    $num = $oneClass[1];
    $title = $oneClass[2];

// teacher table:
    $query = "INSERT IGNORE INTO tblTeachers(fldLastName, fldFirstName, pmkNetId, fldSalary, fldPhone) ";
    $query .= "VALUES (?, ?, ?, ?, ?)";
    $data = explode(', ', $oneClass[15]); // name

    $data[] = $oneClass[16]; // net id
    $data[] = rand(24000, 250000); // salary
    $data[] = "656" . str_pad(rand(0,9999), 4, "0", STR_PAD_LEFT); // phone
    
    
    if ($debug) {
        print "<p>sql " . $query . "</p><p><pre> ";
        print_r($data);
        print "</pre></p>";
    }
    $debug=false;
    
    $results = $thisDatabaseWriter->insert($query, $data, 0, 0, 0, 0, false, false);
    if ($results) {
        $style = "background-color: green;";
    } else {
        $style = "background-color: red;";
    }
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[16] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $data[1] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $data[0] . "</th>";

// section table
    $query = "INSERT INTO tblSections(fnkCourseId, fldCRN, fnkTeacherNetId, fldMaxStudents, fldNumStudents, fldSection, fldType, fldStart, fldStop, fldDays, fldBuilding, fldRoom) ";
    
    $query .= "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $data = array($pmkCourseId, $oneClass[3], $oneClass[16], $oneClass[7], $oneClass[8], $oneClass[4], $oneClass[5], $oneClass[9], $oneClass[10], $oneClass[11], $oneClass[13], $oneClass[14]);
    

    if ($debug) {
        print "<p>sql " . $query . "</p><p><pre> ";
        print_r($data);
        print "</pre></p>";
    }

    
    $results = $thisDatabaseWriter->insert($query, $data);
    if ($results) {
        $style = "background-color: green;";
    } else {
        $style = "background-color: red;";
    }
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $pmkCourseId . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[3] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[16] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[7] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[8] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[4] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[5] . "</th>";
        $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[9] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[10] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[11] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[13] . "</th>";
    $outputBuffer[] = "\t\t<th style='" . $style . "'>" . $oneClass[14] . "</th>";
    $outputBuffer[] = "\n\n\t</tr>";
} // ends looping through all records

$outputBuffer[] = "</table>";
$outputBuffer = join("\n", $outputBuffer);
echo $outputBuffer;
?>
