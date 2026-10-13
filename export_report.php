<?php

include("../includes/auth_check.php");

requireRole('Admin', 'Supervisor');

include("../config/database.php");


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

$current_user_id = intval($_SESSION['user_id']);

$role = $_SESSION['role'] ?? '';


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$date_from =
    $_GET['date_from']
    ?? date("Y-m-01");

$date_to =
    $_GET['date_to']
    ?? date("Y-m-d");

$department_filter =
    $_GET['department_id']
    ?? '';

$shift_filter =
    $_GET['shift_id']
    ?? '';

$status_filter =
    $_GET['status']
    ?? '';

$search =
    trim(
        $_GET['search']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| Get Supervisor Department
|--------------------------------------------------------------------------
*/

$supervisor_department_id = null;

if ($role === 'Supervisor') {

    $supervisorSql = "
        SELECT department_id
        FROM users
        WHERE id = ?
        LIMIT 1
    ";

    $supervisorStmt =
        mysqli_prepare(
            $conn,
            $supervisorSql
        );

    mysqli_stmt_bind_param(
        $supervisorStmt,
        "i",
        $current_user_id
    );

    mysqli_stmt_execute(
        $supervisorStmt
    );

    $supervisorResult =
        mysqli_stmt_get_result(
            $supervisorStmt
        );

    $supervisorData =
        mysqli_fetch_assoc(
            $supervisorResult
        );

    if (
        !$supervisorData ||
        empty(
            $supervisorData[
                'department_id'
            ]
        )
    ) {

        die(
            "Your account is not assigned to a department."
        );

    }

    $supervisor_department_id =
        intval(
            $supervisorData[
                'department_id'
            ]
        );

}


/*
|--------------------------------------------------------------------------
| Build Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        a.attendance_date,

        u.user_code,

        CONCAT_WS(
            ' ',
            u.first_name,
            u.middle_name,
            u.last_name
        ) AS full_name,

        d.department_name,

        s.shift_name,

        a.check_in,

        a.check_in_distance,

        a.check_out,

        a.check_out_distance,

        a.work_hours,

        a.status,

        o.office_name

    FROM attendance a

    INNER JOIN users u
        ON a.user_id = u.id

    LEFT JOIN departments d
        ON u.department_id = d.id

    LEFT JOIN shifts s
        ON u.shift_id = s.id

    LEFT JOIN office_locations o
        ON a.office_id = o.id

    WHERE
        a.attendance_date
        BETWEEN ?
        AND ?
";


$types = "ss";

$params = [

    $date_from,

    $date_to

];


/*
|--------------------------------------------------------------------------
| Supervisor Restriction
|--------------------------------------------------------------------------
*/

if ($role === 'Supervisor') {

    $sql .= "
        AND u.department_id = ?
    ";

    $types .= "i";

    $params[] =
        $supervisor_department_id;

}


/*
|--------------------------------------------------------------------------
| Department Filter
|--------------------------------------------------------------------------
*/

if (
    $role === 'Admin' &&
    !empty(
        $department_filter
    )
) {

    $sql .= "
        AND u.department_id = ?
    ";

    $types .= "i";

    $params[] =
        intval(
            $department_filter
        );

}


/*
|--------------------------------------------------------------------------
| Shift Filter
|--------------------------------------------------------------------------
*/

if (!empty($shift_filter)) {

    $sql .= "
        AND u.shift_id = ?
    ";

    $types .= "i";

    $params[] =
        intval(
            $shift_filter
        );

}


/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

$allowedStatuses = [

    'Present',

    'Late',

    'On Leave'

];


if (
    !empty($status_filter) &&
    in_array(
        $status_filter,
        $allowedStatuses,
        true
    )
) {

    $sql .= "
        AND a.status = ?
    ";

    $types .= "s";

    $params[] =
        $status_filter;

}


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if (!empty($search)) {

    $sql .= "
        AND (
            u.user_code LIKE ?
            OR u.first_name LIKE ?
            OR u.middle_name LIKE ?
            OR u.last_name LIKE ?
        )
    ";

    $types .= "ssss";

    $searchValue =
        "%"
        . $search
        . "%";

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

}


/*
|--------------------------------------------------------------------------
| Sort
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        a.attendance_date DESC,
        u.first_name ASC
";


/*
|--------------------------------------------------------------------------
| Prepare
|--------------------------------------------------------------------------
*/

$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


/*
|--------------------------------------------------------------------------
| Dynamic Bind
|--------------------------------------------------------------------------
*/

$bindParams = [];

$bindParams[] =
    $types;

foreach (
    $params
    as $key => $value
) {

    $bindParams[] =
        &$params[$key];

}


call_user_func_array(
    [
        $stmt,
        'bind_param'
    ],
    $bindParams
);


/*
|--------------------------------------------------------------------------
| Execute
|--------------------------------------------------------------------------
*/

mysqli_stmt_execute(
    $stmt
);


$result =
    mysqli_stmt_get_result(
        $stmt
);


/*
|--------------------------------------------------------------------------
| CSV Download Headers
|--------------------------------------------------------------------------
*/

$filename =
    "attendance_report_"
    . date("Y-m-d_H-i-s")
    . ".csv";


header(
    "Content-Type: text/csv; charset=utf-8"
);

header(
    "Content-Disposition: attachment; filename=\"$filename\""
);

header(
    "Pragma: no-cache"
);

header(
    "Expires: 0"
);


/*
|--------------------------------------------------------------------------
| Open Output
|--------------------------------------------------------------------------
*/

$output =
    fopen(
        "php://output",
        "w"
    );


/*
|--------------------------------------------------------------------------
| UTF-8 BOM
|--------------------------------------------------------------------------
|
| Helps Microsoft Excel display
| UTF-8 characters correctly.
|
*/

fprintf(
    $output,
    chr(0xEF)
    . chr(0xBB)
    . chr(0xBF)
);


/*
|--------------------------------------------------------------------------
| CSV Header
|--------------------------------------------------------------------------
*/

fputcsv(
    $output,
    [

        "Date",

        "User Code",

        "Full Name",

        "Department",

        "Shift",

        "Office",

        "Check-In",

        "Check-In Distance (m)",

        "Check-Out",

        "Check-Out Distance (m)",

        "Work Hours",

        "Status"

    ]
);


/*
|--------------------------------------------------------------------------
| Data Rows
|--------------------------------------------------------------------------
*/

while (
    $row =
    mysqli_fetch_assoc(
        $result
    )
) {

    fputcsv(
        $output,
        [

            $row[
                'attendance_date'
            ],

            $row[
                'user_code'
            ],

            $row[
                'full_name'
            ],

            $row[
                'department_name'
            ] ?? '',

            $row[
                'shift_name'
            ] ?? '',

            $row[
                'office_name'
            ] ?? '',

            $row[
                'check_in'
            ] ?? '',

            $row[
                'check_in_distance'
            ] ?? '',

            $row[
                'check_out'
            ] ?? '',

            $row[
                'check_out_distance'
            ] ?? '',

            $row[
                'work_hours'
            ] ?? '',

            $row[
                'status'
            ] ?? ''

        ]
    );

}


fclose(
    $output
);

exit();

?>