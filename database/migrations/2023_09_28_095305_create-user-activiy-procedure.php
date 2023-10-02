<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::unprepared("
            DROP PROCEDURE IF EXISTS GetUserStatusChange;
            CREATE PROCEDURE `GetUserStatusChange`(IN userId INT)
            BEGIN
                SELECT
                    CONCAT(users.name, ' status was changed to ',
                    CASE
                        WHEN user_status_audit_log.status = 1 THEN 'Online'
                        WHEN user_status_audit_log.status = 2 THEN 'Offline'
                        WHEN user_status_audit_log.status = 3 THEN 'Unavailable'
                        WHEN user_status_audit_log.status = 4 THEN 'Sick'
                        WHEN user_status_audit_log.status = 5 THEN 'On Leave'
                        ELSE ''
                    END, ' at ',
                    TIME_FORMAT(user_status_audit_log.status_changed_at, '%h:%i:%s %p')) AS status_change_description
                FROM
                    user_status_audit_log
                INNER JOIN
                    users ON user_status_audit_log.user_id = users.id
                WHERE
                    user_status_audit_log.user_id = userId
                    AND DATE(user_status_audit_log.status_changed_at) = DATE(CURDATE())
                    AND TIME(user_status_audit_log.status_changed_at) BETWEEN '00:00:01' AND '23:59:59'
                ORDER BY
                    user_status_audit_log.status_changed_at;
            END
        ");
    }

    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS GetUserStatusChange');
    }
};
