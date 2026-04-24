<?php
$mysqli = new mysqli('localhost','root','','lascohet_results');
if ($mysqli->connect_error) die('DB ERROR: ' . $mysqli->connect_error);

// Add 'unit' column if missing
$cols = $mysqli->query("SHOW COLUMNS FROM courses LIKE 'unit'")->fetch_assoc();
if (!$cols) {
  $mysqli->query("ALTER TABLE courses ADD COLUMN unit INT DEFAULT 3");
  // Backfill from credit_unit if it exists
  $has_credit_unit = $mysqli->query("SHOW COLUMNS FROM courses LIKE 'credit_unit'")->fetch_assoc();
  if ($has_credit_unit) {
    $mysqli->query("UPDATE courses SET unit=credit_unit");
  }
  echo "unit column added and backfilled!\n";
} else {
  echo "unit column already exists.\n";
}
