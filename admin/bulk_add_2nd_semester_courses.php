<?php
// Bulk add 2nd semester courses for all departments and 100 level
require_once __DIR__ . '/../includes/config.php';

// Add semester 2 courses for the main departments and GNS at level 100
$courses = [
    ['CHE 102', 'Introduction to Community Health II',    3, 1, 100, 2],
    ['CHE 104', 'Primary Health Care Practice II',         2, 1, 100, 2],
    ['CHE 106', 'Anatomy and Physiology II',              3, 1, 100, 2],
    ['CHE 108', 'Health Education Methods II',            2, 1, 100, 2],
    ['MLT 102', 'Clinical Biochemistry',                   3, 5, 100, 2],
    ['MLT 104', 'Haematology II',                         3, 5, 100, 2],
    ['MLT 106', 'Medical Microbiology I',                 2, 5, 100, 2],
    ['PHT 102', 'Pharmaceutical Chemistry II',            3, 6, 100, 2],
    ['PHT 104', 'Pharmacognosy II',                       2, 6, 100, 2],
    ['PHT 106', 'Pharmaceutics I',                        3, 6, 100, 2],
    ['GNS 102', 'Use of English II',                      2, 1, 100, 2],
    ['GNS 104', 'Nigerian Peoples & Culture',             2, 1, 100, 2],
    ['GNS 106', 'Entrepreneurship',                       2, 1, 100, 2],
];

$stmt = $conn->prepare("INSERT IGNORE INTO courses (code, title, credit_units, department_id, level, semester) VALUES (?,?,?,?,?,?)");
foreach ($courses as [$code, $title, $cu, $deptId, $level, $semester]) {
    $stmt->bind_param('ssiiii', $code, $title, $cu, $deptId, $level, $semester);
    $stmt->execute();
}
echo "Done adding 2nd semester courses for all departments.";
