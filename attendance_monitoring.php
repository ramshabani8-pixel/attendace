<?php

include("../includes/auth_check.php");

requireRole('Admin', 'Supervisor');

include("../config/database.php");


/*
|--------------------------------------------------------------------------
| Current User Information
|--------------------------------------------------------------------------
*/

$current_user_id = intval($_SESSION['user_id']);

$role = $_SESSION['role'] ?? '';


/*
|--------------------------------------------------------------------------
| Default Date
|--------------------------------------------------------------------------
*/

$selected_date = $_GET['date'] ?? date("Y-m-d");


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$department_filter = $_GET['department_id'] ?? '';

$shift_filter = $_GET['shift_id'] ?? '';

$status_filter = $_GET['status'] ?? '';

$search = trim($_GET['search'] ?? '');


/*
|--------------------------------------------------------------------------
| Supervisor Department
|--------------------------------------------------------------------------
|
| Supervisor can only monitor their own department.
|
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
| Get Departments for Admin Filter
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
        $departmentRow =
        mysqli_fetch_assoc(
            $departmentResult
        )
    ) {

        $departments[] =
            $departmentRow;

    }

}


/*
|--------------------------------------------------------------------------
| Get Active Shifts for Filter
|--------------------------------------------------------------------------
*/

$shifts = [];

$shiftResult = mysqli_query(
    $conn,
    "
    SELECT
        id,
        shift_name
    FROM shifts
    WHERE status = 'Active'
    ORDER BY shift_name ASC
    "
);

while (
    $shiftRow =
    mysqli_fetch_assoc(
        $shiftResult
    )
) {

    $shifts[] =
        $shiftRow;

}


/*
|--------------------------------------------------------------------------
| Build Attendance Monitoring Query
|--------------------------------------------------------------------------
|
| Attendance is LEFT JOINED.
|
| If no attendance record exists:
| COALESCE(a.status, 'Absent')
|
*/

$sql = "
    SELECT

        u.id,

        u.user_code,

        CONCAT_WS(
            ' ',
            u.first_name,
            u.middle_name,
            u.last_name
        ) AS full_name,

        u.role,

        d.department_name,

        s.shift_name,

        s.check_in_time AS shift_check_in,

        s.late_after,

        s.check_out_time AS shift_check_out,

       CASE
        WHEN a.status IS NOT NULL THEN a.status
        WHEN lr.id IS NOT NULL THEN 'On Leave'
        ELSE 'Absent'
END AS attendance_status,

        a.check_in,

        a.check_in_distance,

        a.check_out,

        a.check_out_distance,

        a.work_hours

    FROM users u

    LEFT JOIN departments d
        ON u.department_id = d.id

    LEFT JOIN shifts s
        ON u.shift_id = s.id

    LEFT JOIN attendance a
    ON a.user_id = u.id
    AND a.attendance_date = ?

LEFT JOIN leave_requests lr
    ON lr.user_id = u.id
    AND lr.status = 'Approved'
    AND ? BETWEEN lr.start_date AND lr.end_date

    WHERE u.status = 'Active'
";


/*
|--------------------------------------------------------------------------
| Dynamic Query Parameters
|--------------------------------------------------------------------------
*/

$types = "ss";
$params = [
    $selected_date,
    $selected_date
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
| Admin Department Filter
|--------------------------------------------------------------------------
*/

if (
    $role === 'Admin' &&
    !empty($department_filter)
) {

    $departmentId =
        intval(
            $department_filter
        );

    $sql .= "
        AND u.department_id = ?
    ";

    $types .= "i";

    $params[] =
        $departmentId;

}


/*
|--------------------------------------------------------------------------
| Shift Filter
|--------------------------------------------------------------------------
*/

if (!empty($shift_filter)) {

    $shiftId =
        intval(
            $shift_filter
        );

    $sql .= "
        AND u.shift_id = ?
    ";

    $types .= "i";

    $params[] =
        $shiftId;

}


/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    'Present',
    'Late',
    'Absent',
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
        AND COALESCE(
            a.status,
            'Absent'
        ) = ?
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
        CASE
            WHEN COALESCE(
                a.status,
                'Absent'
            ) = 'Absent'
            THEN 1

            WHEN COALESCE(
                a.status,
                'Absent'
            ) = 'Late'
            THEN 2

            ELSE 3
        END,

        u.first_name ASC
";


/*
|--------------------------------------------------------------------------
| Prepare Query
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    $sql
);


/*
|--------------------------------------------------------------------------
| Bind Dynamic Parameters
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
| Statistics
|--------------------------------------------------------------------------
*/

$totalUsers = 0;

$presentCount = 0;

$lateCount = 0;

$absentCount = 0;

$leaveCount = 0;


/*
|--------------------------------------------------------------------------
| Store Monitoring Records
|--------------------------------------------------------------------------
*/

$attendanceRecords = [];


while (
    $row =
    mysqli_fetch_assoc(
        $result
    )
) {

    $attendanceRecords[] =
        $row;


    $totalUsers++;


    switch (
        $row['attendance_status']
    ) {

        case 'Present':

            $presentCount++;

            break;


        case 'Late':

            $lateCount++;

            break;


        case 'On Leave':

            $leaveCount++;

            break;


        default:

            $absentCount++;

            break;

    }

}


/*
|--------------------------------------------------------------------------
| Page Layout
|--------------------------------------------------------------------------
*/

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

            <h3 class="mb-1">

                <i class="bi bi-calendar-check-fill"></i>

                Attendance Monitoring

            </h3>

            <p class="text-muted mb-0">

                Monitor attendance, working hours,
                and office location verification.

            </p>

        </div>


        <div class="text-end">

            <h5 class="mb-0">

                <?php
                echo date(
                    "d M Y",
                    strtotime(
                        $selected_date
                    )
                );
                ?>

            </h5>

            <small class="text-muted">

                <?php
                echo date(
                    "l",
                    strtotime(
                        $selected_date
                    )
                );
                ?>

            </small>

        </div>

    </div>



    <!-- Summary Cards -->

    <div class="row mb-4">


        <!-- Total -->

        <div class="col-lg-3
                    col-md-6
                    mb-3">

            <div class="card
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between">

                        <div>

                            <h6 class="text-muted">

                                Total Users

                            </h6>

                            <h2 class="mb-0">

                                <?php
                                echo $totalUsers;
                                ?>

                            </h2>

                        </div>

                        <i
                            class="bi bi-people-fill
                                   fs-1
                                   text-primary"
                        ></i>

                    </div>

                </div>

            </div>

        </div>



        <!-- Present -->

        <div class="col-lg-3
                    col-md-6
                    mb-3">

            <div class="card
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between">

                        <div>

                            <h6 class="text-muted">

                                Present

                            </h6>

                            <h2
                                class="mb-0
                                       text-success"
                            >

                                <?php
                                echo $presentCount;
                                ?>

                            </h2>

                        </div>

                        <i
                            class="bi bi-check-circle-fill
                                   fs-1
                                   text-success"
                        ></i>

                    </div>

                </div>

            </div>

        </div>



        <!-- Late -->

        <div class="col-lg-3
                    col-md-6
                    mb-3">

            <div class="card
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between">

                        <div>

                            <h6 class="text-muted">

                                Late

                            </h6>

                            <h2
                                class="mb-0
                                       text-warning"
                            >

                                <?php
                                echo $lateCount;
                                ?>

                            </h2>

                        </div>

                        <i
                            class="bi bi-clock-fill
                                   fs-1
                                   text-warning"
                        ></i>

                    </div>

                </div>

            </div>

        </div>



        <!-- Absent -->

        <div class="col-lg-3
                    col-md-6
                    mb-3">

            <div class="card
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between">

                        <div>

                            <h6 class="text-muted">

                                Absent

                            </h6>

                            <h2
                                class="mb-0
                                       text-danger"
                            >

                                <?php
                                echo $absentCount;
                                ?>

                            </h2>

                        </div>

                        <i
                            class="bi bi-person-x-fill
                                   fs-1
                                   text-danger"
                        ></i>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- Filters -->

    <div class="card
                border-0
                shadow-sm
                mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">

                <i class="bi bi-funnel"></i>

                Attendance Filters

            </h5>

        </div>


        <div class="card-body">

            <form method="GET">

                <div class="row">


                    <!-- Date -->

                    <div class="col-lg-3
                                col-md-6
                                mb-3">

                        <label class="form-label">

                            Date

                        </label>

                        <input
                            type="date"
                            name="date"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $selected_date
                            );
                            ?>"
                        >

                    </div>



                    <!-- Department -->

                    <div class="col-lg-3
                                col-md-6
                                mb-3">

                        <label class="form-label">

                            Department

                        </label>

                        <?php
                        if (
                            $role === 'Supervisor'
                        ):
                        ?>

                            <select
                                class="form-select"
                                disabled
                            >

                                <option>

                                    My Department

                                </option>

                            </select>

                            <input
                                type="hidden"
                                name="department_id"
                                value="<?php
                                echo $supervisor_department_id;
                                ?>"
                            >

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

                                <?php
                                endforeach;
                                ?>

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

                            <?php
                            endforeach;
                            ?>

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

                                All Statuses

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
                                value="Absent"
                                <?php
                                echo (
                                    $status_filter
                                    === 'Absent'
                                )
                                ? 'selected'
                                : '';
                                ?>
                            >

                                Absent

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
                            placeholder="User Code or Name"
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

                    Filter

                </button>


                <a
                    href="attendance_monitoring.php"
                    class="btn btn-secondary"
                >

                    <i class="bi bi-arrow-counterclockwise"></i>

                    Reset

                </a>

            </form>

        </div>

    </div>



    <!-- Attendance Table -->

    <div class="card
                border-0
                shadow-sm">

        <div class="card-header bg-white">

            <h5 class="mb-0">

                <i class="bi bi-table"></i>

                Attendance Records

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

                            <th>User</th>

                            <th>Department</th>

                            <th>Shift</th>

                            <th>Check-In</th>

                            <th>In GPS</th>

                            <th>Check-Out</th>

                            <th>Out GPS</th>

                            <th>Work Hours</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    if (
                        !empty(
                            $attendanceRecords
                        )
                    ):

                    ?>


                        <?php

                        foreach (
                            $attendanceRecords
                            as $record
                        ):

                        ?>


                            <tr>


                                <!-- User -->

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



                                <!-- Department -->

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



                                <!-- Shift -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $record[
                                            'shift_name'
                                        ]
                                        ??
                                        'Not Assigned'
                                    );
                                    ?>

                                </td>



                                <!-- Check In -->

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



                                <!-- Check In GPS -->

                                <td>

                                    <?php

                                    if (
                                        $record[
                                            'check_in_distance'
                                        ]
                                        !== null
                                    ):

                                    ?>

                                        <span
                                            class="
                                                badge
                                                bg-info
                                                text-dark
                                            "
                                        >

                                            <?php
                                            echo number_format(
                                                $record[
                                                    'check_in_distance'
                                                ],
                                                2
                                            );
                                            ?>

                                            m

                                        </span>

                                    <?php else: ?>

                                        --

                                    <?php endif; ?>

                                </td>



                                <!-- Check Out -->

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



                                <!-- Check Out GPS -->

                                <td>

                                    <?php

                                    if (
                                        $record[
                                            'check_out_distance'
                                        ]
                                        !== null
                                    ):

                                    ?>

                                        <span
                                            class="
                                                badge
                                                bg-info
                                                text-dark
                                            "
                                        >

                                            <?php
                                            echo number_format(
                                                $record[
                                                    'check_out_distance'
                                                ],
                                                2
                                            );
                                            ?>

                                            m

                                        </span>

                                    <?php else: ?>

                                        --

                                    <?php endif; ?>

                                </td>



                                <!-- Work Hours -->

                                <td>

                                    <?php

                                    echo
                                    $record[
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
                                    .
                                    " hrs"

                                    :

                                    '--';

                                    ?>

                                </td>



                                <!-- Status -->

                                <td>

                                    <?php

                                    $status =
                                        $record[
                                            'attendance_status'
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

                                    else {

                                        echo '
                                        <span
                                            class="
                                                badge
                                                bg-danger
                                            "
                                        >
                                            Absent
                                        </span>';

                                    }

                                    ?>

                                </td>


                            </tr>


                        <?php

                        endforeach;

                        ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="9"
                                class="
                                    text-center
                                    text-muted
                                    py-5
                                "
                            >

                                <i
                                    class="
                                        bi
                                        bi-calendar-x
                                        fs-1
                                        d-block
                                        mb-3
                                    "
                                ></i>

                                No attendance records found.

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<?php

include("../includes/footer.php");

?>