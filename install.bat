@echo off
setlocal EnableExtensions

cd /d "%~dp0"
title LASCOHET Installer

echo ==============================================
echo   LASCOHET Student Management - Installer
echo ==============================================
echo.

set "DUMP_FILE=database\lascohet_full_dump.sql"
if not exist "%DUMP_FILE%" (
    echo [ERROR] Database dump not found: %DUMP_FILE%
    echo Make sure this file exists, then run this installer again.
    exit /b 1
)

set "MYSQL_EXE="
if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe"
if not defined MYSQL_EXE (
    where mysql >nul 2>nul
    if %ERRORLEVEL%==0 set "MYSQL_EXE=mysql"
)

if not defined MYSQL_EXE (
    echo [ERROR] MySQL client not found.
    echo Install XAMPP or add mysql.exe to PATH, then retry.
    exit /b 1
)

echo MySQL client: %MYSQL_EXE%
echo.

set "DB_HOST=127.0.0.1"
set "DB_PORT=3306"
set "DB_USER=root"
set "DB_PASS="
set "DB_NAME=lascohet_results"

set /p DB_HOST=MySQL Host [127.0.0.1]: 
if "%DB_HOST%"=="" set "DB_HOST=127.0.0.1"

set /p DB_PORT=MySQL Port [3306]: 
if "%DB_PORT%"=="" set "DB_PORT=3306"

set /p DB_USER=MySQL Username [root]: 
if "%DB_USER%"=="" set "DB_USER=root"

set /p DB_PASS=MySQL Password [leave blank if none]: 

set /p DB_NAME=Database Name [lascohet_results]: 
if "%DB_NAME%"=="" set "DB_NAME=lascohet_results"

echo.
echo Creating database "%DB_NAME%"...

if "%DB_PASS%"=="" (
    "%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" -e "CREATE DATABASE IF NOT EXISTS `%DB_NAME%` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
) else (
    "%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" -p"%DB_PASS%" -e "CREATE DATABASE IF NOT EXISTS `%DB_NAME%` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
)

if not %ERRORLEVEL%==0 (
    echo [ERROR] Failed to create database. Check your MySQL credentials.
    exit /b 1
)

echo Importing full database data from "%DUMP_FILE%"...

if "%DB_PASS%"=="" (
    "%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" "%DB_NAME%" < "%DUMP_FILE%"
) else (
    "%MYSQL_EXE%" -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USER%" -p"%DB_PASS%" "%DB_NAME%" < "%DUMP_FILE%"
)

if not %ERRORLEVEL%==0 (
    echo [ERROR] SQL import failed. Review MySQL output above.
    exit /b 1
)

echo.
echo Database import completed successfully.
echo.

if exist "composer.json" (
    where composer >nul 2>nul
    if %ERRORLEVEL%==0 (
        if not exist "vendor\autoload.php" (
            echo Installing PHP dependencies with Composer...
            composer install --no-interaction --prefer-dist
        ) else (
            echo Composer dependencies already present. Skipping.
        )
    ) else (
        echo Composer not found in PATH. Skipping dependency install.
    )
)

echo.
echo ==============================================
echo   Installation completed successfully
echo ==============================================
echo Open: http://localhost/Student-Management-System/
echo.

pause
exit /b 0
