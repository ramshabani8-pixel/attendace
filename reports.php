<?php

include("../includes/auth_check.php");

include("../config/database.php");

requireRole('Admin', 'Supervisor');

include("../includes/system_settings.php");


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

$current_user_id = intval($_SESSION['user_id']);

$role = $_SESSION['role'] ?? '';


/*
|--------------------------------------------------------------------------
| Default Date Range
|--------------------------------------------------------------------------
*/

$date_from = $_GET['date_from']
    ?? date("Y-m-01");

$date_to = $_GET['date_to']
    ?? date("Y-m-d");


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$department_filter =
    $_GET['department_id'] ?? '';

$shift_filter =
    $_GET['shift_id'] ?? '';

$status_filter =
    $_GET['status'] ?? '';

$search =
    trim($_GET['search'] ?? '');


/*
|--------------------------------------------------------------------------
| Supervisor Department
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

    $supervisorStmt = mysqli_prepare(
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
        empty($supervisorData['department_id'])
    ) {

        die(
            "Your account is not assigned to a department."
        );

    }

    $supervisor_department_id =
        intval(
            $supervisorData['department_id']
        );

}


/*
|--------------------------------------------------------------------------
| Departments
|--------------------------------------------------------------------------
*/

$departments = [];

if ($role === 'Admin') {

    $departmentResult = mysqli_query(
        $conn,
        "
        SELECT id, department_name
        FROM departments
        WHERE status = 'Active'
        ORDER BY department_name ASC
        "
    );

    while (
        $row =
        mysqli_fetch_assoc(
            $departmentResult
        )
    ) {

        $departments[] = $row;

    }

}


/*
|--------------------------------------------------------------------------
| Shifts
|--------------------------------------------------------------------------
*/

$shifts = [];

$shiftResult = mysqli_query(
    $conn,
    "
    SELECT id, shift_name
    FROM shifts
    WHERE status = 'Active'
    ORDER BY shift_name ASC
    "
);

while (
    $row =
    mysqli_fetch_assoc(
        $shiftResult
    )
) {

    $shifts[] = $row;

}


/*
|--------------------------------------------------------------------------
| Build Report Query
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
| Supervisor Department Restriction
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
| Admin Department Filter
|--------------------------------------------------------------------------
*/

if (
    $role === 'Admin' &&
    !empty($department_filter)
) {

    $department_id =
        intval(
            $department_filter
        );

    $sql .= "
        AND u.department_id = ?
    ";

    $types .= "i";

    $params[] =
        $department_id;

}


/*
|--------------------------------------------------------------------------
| Shift Filter
|--------------------------------------------------------------------------
*/

if (!empty($shift_filter)) {

    $shift_id =
        intval(
            $shift_filter
        );

    $sql .= "
        AND u.shift_id = ?
    ";

    $types .= "i";

    $params[] =
        $shift_id;

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
| Search Filter
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
        "%" . $search . "%";

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
| Order
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        a.attendance_date DESC,
        u.first_name ASC
";


/*
|--------------------------------------------------------------------------
| Prepare Statement
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    $sql
);


/*
|--------------------------------------------------------------------------
| Dynamic Binding
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
| Store Records + Statistics
|--------------------------------------------------------------------------
*/

$records = [];

$totalRecords = 0;

$presentCount = 0;

$lateCount = 0;

$leaveCount = 0;

$totalWorkHours = 0;


while (
    $row =
    mysqli_fetch_assoc(
        $result
    )
) {

    $records[] =
        $row;

    $totalRecords++;


    if (
        $row['status']
        ===
        'Present'
    ) {

        $presentCount++;

    }


    elseif (
        $row['status']
        ===
        'Late'
    ) {

        $lateCount++;

    }


    elseif (
        $row['status']
        ===
        'On Leave'
    ) {

        $leaveCount++;

    }


    if (
        $row['work_hours']
        !== null
    ) {

        $totalWorkHours +=
            (float)
            $row['work_hours'];

    }

}


/*
|--------------------------------------------------------------------------
| Calculate Absent For Single-Day Report
|--------------------------------------------------------------------------
*/

$absentCount = null;

if (
    $date_from === $date_to
) {

    /*
    | Count Active Users
    | Based on current scope
    */

    $userSql = "
        SELECT COUNT(*)
        AS total_users
        FROM users
        WHERE status = 'Active'
    ";

    $userTypes = "";

    $userParams = [];


    if (
        $role === 'Supervisor'
    ) {

        $userSql .= "
            AND department_id = ?
        ";

        $userTypes .= "i";

        $userParams[] =
            $supervisor_department_id;

    }


    elseif (
        $role === 'Admin' &&
        !empty($department_filter)
    ) {

        $userSql .= "
            AND department_id = ?
        ";

        $userTypes .= "i";

        $userParams[] =
            intval(
                $department_filter
            );

    }


    if (!empty($shift_filter)) {

        $userSql .= "
            AND shift_id = ?
        ";

        $userTypes .= "i";

        $userParams[] =
            intval(
                $shift_filter
            );

    }


    $userStmt = mysqli_prepare(
        $conn,
        $userSql
    );


    if (
        !empty($userParams)
    ) {

        $userBind = [];

        $userBind[] =
            $userTypes;

        foreach (
            $userParams
            as $key => $value
        ) {

            $userBind[] =
                &$userParams[$key];

        }

        call_user_func_array(
            [
                $userStmt,
                'bind_param'
            ],
            $userBind
        );

    }


    mysqli_stmt_execute(
        $userStmt
    );


    $userResult =
        mysqli_stmt_get_result(
            $userStmt
        );


    $userData =
        mysqli_fetch_assoc(
            $userResult
        );


    $totalUsers =
        (int)
        $userData['total_users'];


    $eligibleUsers =
        $totalUsers
        -
        $leaveCount;


    $absentCount =
        max(
            0,
            $eligibleUsers
            -
            $presentCount
            -
            $lateCount
        );

}


/*
|--------------------------------------------------------------------------
| Page Layout
|--------------------------------------------------------------------------
*/
$leaveReportSql = "
    SELECT
        lr.leave_type,
        lr.reason,
        lr.start_date,
        lr.end_date,
        lr.status,
        CONCAT_WS(' ', u.first_name, u.last_name) AS full_name,
        u.user_code,
        d.department_name,
        CONCAT_WS(' ', reviewer.first_name, reviewer.last_name) AS reviewer_name,
        lr.reviewer_comment,
        lr.reviewed_at
    FROM leave_requests lr
    INNER JOIN users u
        ON lr.user_id = u.id
    LEFT JOIN departments d
        ON u.department_id = d.id
    LEFT JOIN users reviewer
        ON lr.reviewed_by = reviewer.id
    WHERE lr.status = 'Approved'
      AND lr.start_date <= ?
      AND lr.end_date >= ?
";

$leaveStmt = mysqli_prepare($conn, $leaveReportSql);
mysqli_stmt_bind_param($leaveStmt, "ss", $date_to, $date_from);
mysqli_stmt_execute($leaveStmt);
$leaveResult = mysqli_stmt_get_result($leaveStmt);


include("../includes/header.php");

include("../includes/sidebar.php");

include("../includes/topbar.php");

?>

<div class="container-fluid">


    <!-- Page Header -->

    <div class="d-flex
                justify-content-between
                align-items-center
                mb-4">

        <div>
<div class="text-center mb-4">

    <h2 class="fw-bold">

        <?php echo htmlspecialchars($systemSettings['company_name']); ?>

    </h2>

    <h4 class="text-primary">

        <?php echo htmlspecialchars($systemSettings['system_name']); ?>

    </h4>

    <p class="text-muted mb-0">

        <?php echo htmlspecialchars($systemSettings['company_address']); ?>

    </p>

    <small>

        Phone:
        <?php echo htmlspecialchars($systemSettings['company_phone']); ?>

        |

        Email:
        <?php echo htmlspecialchars($systemSettings['company_email']); ?>

    </small>

    <hr>

</div>
            <h3 class="mb-1">

                <i class="bi bi-file-earmark-bar-graph"></i>

                Attendance Reports

            </h3>

            <p class="text-muted mb-0">

                Analyze attendance records and working hours.

            </p>

        </div>


        <div>

            <div class="d-flex gap-2">

    <button
        onclick="window.print()"
        class="btn btn-outline-dark"
    >

        <i class="bi bi-printer"></i>

        Print

    </button>


    <button
        type="button"
        class="btn btn-success"
        onclick="exportReportCSV()"
    >

        <i class="bi bi-file-earmark-spreadsheet"></i>

        Export CSV

    </button>

</div>

        </div>

    </div>



    <!-- Summary -->

    <div class="row mb-4">


        <div class="col-xl-3
                    col-md-6
                    mb-3">

            <div class="card
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">

                        Total Records

                    </h6>

                    <h2>

                        <?php
                        echo $totalRecords;
                        ?>

                    </h2>

                </div>

            </div>

        </div>


        <div class="col-xl-2
                    col-md-6
                    mb-3">

            <div class="card
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">

                        Present

                    </h6>

                    <h2
                        class="text-success"
                    >

                        <?php
                        echo $presentCount;
                        ?>

                    </h2>

                </div>

            </div>

        </div>


        <div class="col-xl-2
                    col-md-6
                    mb-3">

            <div class="card
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">

                        Late

                    </h6>

                    <h2
                        class="text-warning"
                    >

                        <?php
                        echo $lateCount;
                        ?>

                    </h2>

                </div>

            </div>

        </div>


        <div class="col-xl-2
                    col-md-6
                    mb-3">

            <div class="card
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">

                        On Leave

                    </h6>

                    <h2
                        class="text-info"
                    >

                        <?php
                        echo $leaveCount;
                        ?>

                    </h2>

                </div>

            </div>

        </div>


        <div class="col-xl-3
                    col-md-6
                    mb-3">

            <div class="card
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">

                        Total Work Hours

                    </h6>

                    <h2>

                        <?php
                        echo number_format(
                            $totalWorkHours,
                            2
                        );
                        ?>

                        <small>hrs</small>

                    </h2>

                </div>

            </div>

        </div>

    </div>



    <!-- Single Day Absent -->

    <?php if (
        $absentCount !== null
    ): ?>

        <div class="alert alert-danger">

            <i class="bi bi-person-x-fill"></i>

            <strong>
                Absent Users:
            </strong>

            <?php
            echo $absentCount;
            ?>

            users have no attendance record
            for

            <strong>

                <?php
                echo date(
                    "d M Y",
                    strtotime(
                        $date_from
                    )
                );
                ?>

            </strong>.

        </div>

    <?php endif; ?>



    <!-- Filters -->

    <div class="card
                border-0
                shadow-sm
                mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">

                <i class="bi bi-funnel"></i>

                Report Filters

            </h5>

        </div>


        <div class="card-body">

            <form method="GET">

                <div class="row">


                    <!-- Date From -->

                    <div class="col-lg-2
                                col-md-6
                                mb-3">

                        <label class="form-label">

                            Date From

                        </label>

                        <input
                            type="date"
                            name="date_from"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $date_from
                            );
                            ?>"
                            required
                        >

                    </div>



                    <!-- Date To -->

                    <div class="col-lg-2
                                col-md-6
                                mb-3">

                        <label class="form-label">

                            Date To

                        </label>

                        <input
                            type="date"
                            name="date_to"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $date_to
                            );
                            ?>"
                            required
                        >

                    </div>



                    <!-- Department -->

                    <div class="col-lg-2
                                col-md-6
                                mb-3">

                        <label class="form-label">

                            Department

                        </label>

                        <?php if (
                            $role === 'Supervisor'
                        ): ?>

                            <select
                                class="form-select"
                                disabled
                            >

                                <option>
                                    My Department
                                </option>

                            </select>

                        <?php else: ?>

                            <select
                                name="department_id"
                                class="form-select"
                            >

                                <option value="">

                                    All Departments

                                </option>

                                <?php
                                foreach (
                                    $departments
                                    as $department
                                ):
                                ?>

                                    <option
                                        value="<?php
                                        echo $department['id'];
                                        ?>"
                                        <?php
                                        echo (
                                            $department_filter
                                            ==
                                            $department['id']
                                        )
                                        ? 'selected'
                                        : '';
                                        ?>
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $department[
                                                'department_name'
                                            ]
                                        );
                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        <?php endif; ?>

                    </div>



                    <!-- Shift -->

                    <div class="col-lg-2
                                col-md-6
                                mb-3">

                        <label class="form-label">

                            Shift

                        </label>

                        <select
                            name="shift_id"
                            class="form-select"
                        >

                            <option value="">

                                All Shifts

                            </option>

                            <?php
                            foreach (
                                $shifts
                                as $shift
                            ):
                            ?>

                                <option
                                    value="<?php
                                    echo $shift['id'];
                                    ?>"
                                    <?php
                                    echo (
                                        $shift_filter
                                        ==
                                        $shift['id']
                                    )
                                    ? 'selected'
                                    : '';
                                    ?>
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $shift[
                                            'shift_name'
                                        ]
                                    );
                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- Status -->

                    <div class="col-lg-2
                                col-md-6
                                mb-3">

                        <label class="form-label">

                            Status

                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option value="">

                                All Recorded Statuses

                            </option>

                            <option
                                value="Present"
                                <?php
                                echo (
                                    $status_filter
                                    === 'Present'
                                )
                                ? 'selected'
                                : '';
                                ?>
                            >

                                Present

                            </option>

                            <option
                                value="Late"
                                <?php
                                echo (
                                    $status_filter
                                    === 'Late'
                                )
                                ? 'selected'
                                : '';
                                ?>
                            >

                                Late

                            </option>

                            <option
                                value="On Leave"
                                <?php
                                echo (
                                    $status_filter
                                    === 'On Leave'
                                )
                                ? 'selected'
                                : '';
                                ?>
                            >

                                On Leave

                            </option>

                        </select>

                    </div>



                    <!-- Search -->

                    <div class="col-lg-2
                                col-md-12
                                mb-3">

                        <label class="form-label">

                            Search

                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Name or User Code"
                            value="<?php
                            echo htmlspecialchars(
                                $search
                            );
                            ?>"
                        >

                    </div>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="bi bi-search"></i>

                    Generate Report

                </button>


                <a
                    href="reports.php"
                    class="btn btn-secondary"
                >

                    <i class="bi bi-arrow-counterclockwise"></i>

                    Reset

                </a>

            </form>

        </div>

    </div>



    <!-- Report Table -->

    <div class="card
                border-0
                shadow-sm">

        <div class="card-header bg-white">

            <h5 class="mb-0">

                <i class="bi bi-table"></i>

                Attendance Report

            </h5>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table
                    class="table
                           table-hover
                           align-middle"
                >

                    <thead class="table-light">

                        <tr>

                            <th>Date</th>

                            <th>User</th>

                            <th>Department</th>

                            <th>Shift</th>

                            <th>Check-In</th>

                            <th>In Distance</th>

                            <th>Check-Out</th>

                            <th>Out Distance</th>

                            <th>Work Hours</th>

                            <th>Status</th>

                        </tr>

                    </thead>





                    <tbody>

                    <?php if (
                        !empty(
                            $records
                        )
                    ): ?>


                        <?php
                        foreach (
                            $records
                            as $record
                        ):
                        ?>

                            <tr>


                                <td>

                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $record[
                                                'attendance_date'
                                            ]
                                        )
                                    );
                                    ?>

                                </td>


                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $record[
                                                'full_name'
                                            ]
                                        );
                                        ?>

                                    </strong>

                                    <br>

                                    <small
                                        class="text-muted"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $record[
                                                'user_code'
                                            ]
                                        );
                                        ?>

                                    </small>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $record[
                                            'department_name'
                                        ]
                                        ??
                                        'N/A'
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $record[
                                            'shift_name'
                                        ]
                                        ??
                                        'N/A'
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo $record[
                                        'check_in'
                                    ]

                                    ? date(
                                        "h:i A",
                                        strtotime(
                                            $record[
                                                'check_in'
                                            ]
                                        )
                                    )

                                    : '--';
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo $record[
                                        'check_in_distance'
                                    ]
                                    !== null

                                    ?

                                    number_format(
                                        $record[
                                            'check_in_distance'
                                        ],
                                        2
                                    ) . " m"

                                    :

                                    '--';
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo $record[
                                        'check_out'
                                    ]

                                    ? date(
                                        "h:i A",
                                        strtotime(
                                            $record[
                                                'check_out'
                                            ]
                                        )
                                    )

                                    : '--';
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo $record[
                                        'check_out_distance'
                                    ]
                                    !== null

                                    ?

                                    number_format(
                                        $record[
                                            'check_out_distance'
                                        ],
                                        2
                                    ) . " m"

                                    :

                                    '--';
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo $record[
                                        'work_hours'
                                    ]
                                    !== null

                                    ?

                                    number_format(
                                        $record[
                                            'work_hours'
                                        ],
                                        2
                                    )
                                    . " hrs"

                                    :

                                    '--';
                                    ?>

                                </td>


                                <td>

                                    <?php

                                    $status =
                                        $record[
                                            'status'
                                        ];


                                    if (
                                        $status
                                        ===
                                        'Present'
                                    ) {

                                        echo '
                                        <span
                                            class="
                                            badge
                                            bg-success
                                            "
                                        >
                                            Present
                                        </span>';

                                    }

                                    elseif (
                                        $status
                                        ===
                                        'Late'
                                    ) {

                                        echo '
                                        <span
                                            class="
                                            badge
                                            bg-warning
                                            text-dark
                                            "
                                        >
                                            Late
                                        </span>';

                                    }

                                    elseif (
                                        $status
                                        ===
                                        'On Leave'
                                    ) {

                                        echo '
                                        <span
                                            class="
                                            badge
                                            bg-info
                                            text-dark
                                            "
                                        >
                                            On Leave
                                        </span>';

                                    }

                                    ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="10"
                                class="
                                text-center
                                text-muted
                                py-5
                                "
                            >

                                <i
                                    class="
                                    bi
                                    bi-file-earmark-x
                                    fs-1
                                    d-block
                                    mb-3
                                    "
                                ></i>

                                No attendance records found
                                for the selected filters.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="bi bi-calendar2-check"></i>
            Approved Leave Requests in Selected Range
        </h5>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Department</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Reason</th>
                        <th>Reviewer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($leaveResult) > 0): ?>
                        <?php while ($leave = mysqli_fetch_assoc($leaveResult)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($leave['full_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($leave['user_code']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($leave['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                <td><?php echo date("d M Y", strtotime($leave['start_date'])); ?></td>
                                <td><?php echo date("d M Y", strtotime($leave['end_date'])); ?></td>
                                <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($leave['reviewer_name'] ?? '--'); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No approved leave requests found in this range.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>

function exportReportCSV() {

    const params =
        new URLSearchParams(
            window.location.search
        );


    const exportUrl =
        "export_report.php?"
        +
        params.toString();


    window.location.href =
        exportUrl;

}

</script>


<?php

include("../includes/footer.php");

?>