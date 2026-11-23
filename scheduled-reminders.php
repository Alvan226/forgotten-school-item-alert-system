<?php

// This script must only run through the command line.
// Windows Task Scheduler will execute it automatically.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Access denied.");
}

require_once __DIR__ . '/includes/db.php';

// Generate reminders for today's outstanding school items.
$sql = "
    INSERT IGNORE INTO NOTIFICATION
        (
            student_id,
            link_id,
            reminder_date,
            message,
            sent_at,
            delivery_status
        )

    SELECT
        st.student_id,
        cil.link_id,
        CURDATE(),

        CONCAT(
            'Reminder: Pack ',
            s.item_name,
            ' for ',
            t.class_unit,
            ' today. Class starts at ',
            TIME_FORMAT(t.start_time, '%h:%i %p'),
            '.'
        ),

        NOW(),
        'sent'

    FROM STUDENT_TIMETABLE st

    JOIN TIMETABLE_ENTRY t
        ON st.timetable_id = t.timetable_id

    JOIN CLASS_ITEM_LINK cil
        ON t.timetable_id = cil.timetable_id

    JOIN SCHOOL_ITEM s
        ON cil.item_id = s.item_id

    LEFT JOIN CHECKLIST_STATUS cs
        ON cs.link_id = cil.link_id
        AND cs.student_id = st.student_id
        AND cs.checklist_date = CURDATE()

    WHERE t.day_of_week = DAYNAME(CURDATE())
        AND COALESCE(cs.is_packed, 0) = 0
";

if ($conn->query($sql)) {

    $created = $conn->affected_rows;

    echo "Automatic reminder process completed successfully.\n";
    echo "New notifications created: " . $created . "\n";

} else {

    echo "Reminder generation failed.\n";
    echo "Database error: " . $conn->error . "\n";
}

$conn->close();

?>