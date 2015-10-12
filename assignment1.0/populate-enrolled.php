<?php

// This page gets a list of students and assigns them a random set of courses
// to be enrolled in. Creates a sql file that you will need to import
//

include "top.php";

$dbUserName = get_current_user() . '_admin';
$whichPass = "a";
$thisDatabaseAdmin = new Database($dbUserName, $whichPass, $dbName);

$outputBuffer[] = "";

$debug = false;
if (isset($_GET["debug"])) {
    $debug = true;
}

if ($debug)
    print "<p>DEBUG MODE IS ON</p>";

// -- Table structure for table `tblSections`
$query = "DROP TABLE IF EXISTS tblEnrolls";
$results = $thisDatabaseAdmin->delete($query, 0, 0, 0, 0);
if ($results) {
    print "<p>tblEnrolls Deleted.</p>";
} else {
    print "<p>tblEnrolls NOT Deleted.</p>";
}

$query = "CREATE TABLE IF NOT EXISTS tblEnrolls ( ";
$query .= "fnkCourseId int(11) NOT NULL, ";
$query .= "fnkSectionId int(11) NOT NULL, ";
$query .= "fnkStudentId int(11) NOT NULL, ";
$query .= "fldGrade int(11) NOT NULL, ";
$query .= "PRIMARY KEY (fnkCourseId,fnkSectionId,fnkStudentId)";
$query .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8";
$results = $thisDatabaseAdmin->insert($query, "", 0, 5, 0, 0);
if ($results) {
    print "<p>tblEnrolls Created.</p>";
} else {
    print "<p>tblEnrolls NOT Created.</p>";
}


//get all the student id numbers
$query = "SELECT pmkStudentId, 0 as numClasses FROM tblStudents";
$allStudents = $thisDatabaseReader->select($query, "", 0, 0, 0, 0);
if (is_array($allStudents)) {
    print "<p>Student Count: " . count($allStudents) . "</p>";
} else {
    print "<p>No Students.</p>";
}


//get all the cs classes sections id numbers
$query = "SELECT fnkCourseId, fldCRN, fldNumStudents ";
$query .= "FROM tblSections, tblCourses ";
$query .= "WHERE pmkCourseId=fnkCourseId ";
$query .= "AND fldDepartment = 'CS'";

$allSections = $thisDatabaseReader->select($query, "", 1, 1, 2, 0);
if (is_array($allSections)) {
    print "<p>Section Count: " . count($allSections) . "</p>";
} else {
    print "<p>No Sections.</p>";
}


if ($debug) {
    print "<p>allSections:<pre>";
    print_r($allSections);
    print "</pre></p>";
}
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//
// SECTION: 2e Save Data
//
// This block saves the data to a CSV file.

$fileExt = ".sql";

$myFileName = "sql/enrollments";

$filename = $myFileName . $fileExt;

print "\n\n<p>Creating SQL file: " . $filename;

// now we just open the file for append
// $file = fopen($filename, 'a');

$a = 0;
$query = "INSERT IGNORE INTO tblEnrolls (fnkCourseId, fnkSectionId, fnkStudentId, fldGrade) VALUES \n";
$saveRecord = file_put_contents($filename, $query, LOCK_EX);

// put records into database tables
foreach ($allSections as $section) {
    /* select a random student until the course fills up
      make sure student is not enrolled in more than 5 courses
     */

    for ($i = 1; $i <= $section["fldNumStudents"]; $i++) {
        $studentAdded = false;

// grab random student
        do {
            $randomStudent = rand(0, count($allStudents) - 1);

            // tecehnically student can enroll twice but the the insert should fail
            if ($allStudents[$randomStudent]["numClasses"] <= 5) {
//update number of classes student has
                $allStudents[$randomStudent]["numClasses"] = $allStudents[$randomStudent]["numClasses"] + 1;

//put student into section

                $query = "(" . $section["fnkCourseId"] . ", " . $section["fldCRN"] . ", " . $allStudents[$randomStudent]["pmkStudentId"] . ", 0), \n";

                $saveRecord = file_put_contents($filename, $query, FILE_APPEND | LOCK_EX);
                if ($saveRecord > 0) {
                    $studentAdded = true;
                } else {
                    print "<p>Record not saved: ";
                    print $query . "</p>";
                }
                $a++;
            }
        } while ($studentAdded == false);
    }
}

//duplicate record should fail on insert but easy way to get ; at end.
$query = "(" . $section["fnkCourseId"] . ", " . $section["fldCRN"] . ", " . $allStudents[$randomStudent]["pmkStudentId"] . ", 0);\n";

$saveRecord = file_put_contents($filename, $query, FILE_APPEND | LOCK_EX);

// close the file
//fclose($file);
print "<p>" . $a . " sql statements created</p>";

print "<h1>EOJ</h1>";
?>