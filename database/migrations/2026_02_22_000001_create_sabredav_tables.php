<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Calendar objects - individual events (iCalendar format)
        DB::statement("
            CREATE TABLE calendarobjects (
                id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                calendardata MEDIUMBLOB,
                uri VARBINARY(200),
                calendarid INTEGER UNSIGNED NOT NULL,
                lastmodified INT(11) UNSIGNED,
                etag VARBINARY(32),
                size INT(11) UNSIGNED NOT NULL,
                componenttype VARBINARY(8),
                firstoccurence INT(11) UNSIGNED,
                lastoccurence INT(11) UNSIGNED,
                uid VARBINARY(200),
                UNIQUE(calendarid, uri),
                INDEX calendarid_time (calendarid, firstoccurence)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Calendars - calendar containers
        DB::statement("
            CREATE TABLE calendars (
                id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                synctoken INTEGER UNSIGNED NOT NULL DEFAULT '1',
                components VARBINARY(21)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Calendar instances - links calendars to principals (users)
        DB::statement("
            CREATE TABLE calendarinstances (
                id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                calendarid INTEGER UNSIGNED NOT NULL,
                principaluri VARBINARY(100),
                access TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 = owner, 2 = read, 3 = readwrite',
                displayname VARCHAR(100),
                uri VARBINARY(200),
                description TEXT,
                calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
                calendarcolor VARBINARY(10),
                timezone TEXT,
                transparent TINYINT(1) NOT NULL DEFAULT '0',
                share_href VARBINARY(100),
                share_displayname VARCHAR(100),
                share_invitestatus TINYINT(1) NOT NULL DEFAULT '2' COMMENT '1 = noresponse, 2 = accepted, 3 = declined, 4 = invalid',
                UNIQUE(principaluri, uri),
                UNIQUE(calendarid, principaluri),
                UNIQUE(calendarid, share_href)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Calendar changes - sync tokens for CalDAV clients
        DB::statement("
            CREATE TABLE calendarchanges (
                id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                uri VARBINARY(200) NOT NULL,
                synctoken INT(11) UNSIGNED NOT NULL,
                calendarid INT(11) UNSIGNED NOT NULL,
                operation TINYINT(1) NOT NULL,
                INDEX calendarid_synctoken (calendarid, synctoken)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Calendar subscriptions
        DB::statement("
            CREATE TABLE calendarsubscriptions (
                id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                uri VARBINARY(200) NOT NULL,
                principaluri VARBINARY(100) NOT NULL,
                source TEXT,
                displayname VARCHAR(100),
                refreshrate VARCHAR(10),
                calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
                calendarcolor VARBINARY(10),
                striptodos TINYINT(1) NULL,
                stripalarms TINYINT(1) NULL,
                stripattachments TINYINT(1) NULL,
                lastmodified INT(11) UNSIGNED,
                UNIQUE(principaluri, uri)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Scheduling objects - free/busy scheduling
        DB::statement("
            CREATE TABLE schedulingobjects (
                id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                principaluri VARBINARY(255),
                calendardata MEDIUMBLOB,
                uri VARBINARY(200),
                lastmodified INT(11) UNSIGNED,
                etag VARBINARY(32),
                size INT(11) UNSIGNED NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Principals - CalDAV user principals
        DB::statement("
            CREATE TABLE principals (
                id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                uri VARBINARY(200) NOT NULL,
                email VARBINARY(80),
                displayname VARCHAR(80),
                UNIQUE(uri)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Group members
        DB::statement("
            CREATE TABLE groupmembers (
                id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                principal_id INTEGER UNSIGNED NOT NULL,
                member_id INTEGER UNSIGNED NOT NULL,
                UNIQUE(principal_id, member_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Locks - WebDAV locks
        DB::statement("
            CREATE TABLE locks (
                id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                owner VARCHAR(100),
                timeout INTEGER UNSIGNED,
                created INTEGER,
                token VARBINARY(100),
                scope TINYINT,
                depth TINYINT,
                uri VARBINARY(1000),
                INDEX(token),
                INDEX(uri(100))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Property storage - WebDAV properties
        DB::statement("
            CREATE TABLE propertystorage (
                id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                path VARBINARY(1024) NOT NULL,
                name VARBINARY(100) NOT NULL,
                valuetype INT UNSIGNED,
                value MEDIUMBLOB
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS propertystorage');
        DB::statement('DROP TABLE IF EXISTS locks');
        DB::statement('DROP TABLE IF EXISTS groupmembers');
        DB::statement('DROP TABLE IF EXISTS principals');
        DB::statement('DROP TABLE IF EXISTS schedulingobjects');
        DB::statement('DROP TABLE IF EXISTS calendarsubscriptions');
        DB::statement('DROP TABLE IF EXISTS calendarchanges');
        DB::statement('DROP TABLE IF EXISTS calendarinstances');
        DB::statement('DROP TABLE IF EXISTS calendars');
        DB::statement('DROP TABLE IF EXISTS calendarobjects');
    }
};
