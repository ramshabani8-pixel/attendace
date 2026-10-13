<?php

include("../includes/auth_check.php");

requireRole('Admin');

include("../config/database.php");

include("../includes/system_settings.php");

include("../includes/header.php");


/*
|--------------------------------------------------------------------------
| Today's Date
|--------------------------------------------------------------------------
*/

$today = date("Y-m-d");
$leaveTodaySql = "
    SELECT COUNT(DISTINCT u.id) AS total
    FROM leave_requests lr
    INNER JOIN users u
        ON lr.user_id = u.id
    WHERE u.status = 'Active'
      AND lr.status = 'Approved'
      AND ? BETWEEN lr.start_date AND lr.end_date
";

$leaveTodayStmt = mysqli_prepare($conn, $leaveTodaySql);
mysqli_stmt_bind_param($leaveTodayStmt, "s", $today);
mysqli_stmt_execute($leaveTodayStmt);
$leaveTodayResult = mysqli_stmt_get_result($leaveTodayStmt);
$leaveToday = (int) mysqli_fetch_assoc($leaveTodayResult)['total'];


/*
|--------------------------------------------------------------------------
| Total Active Users
|--------------------------------------------------------------------------
*/

$totalUsersQuery = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM users
    WHERE status = 'Active'
    "
);

$totalUsers = (int) mysqli_fetch_assoc(
    $totalUsersQuery
)['total'];


/*
|--------------------------------------------------------------------------
| Today's Attendance Summary
|--------------------------------------------------------------------------
*/

$summarySql = "
    SELECT

        SUM(
            CASE
                WHEN a.status = 'Present'
                THEN 1
                ELSE 0
            END
        ) AS present_count,

        SUM(
            CASE
                WHEN a.status = 'Late'
                THEN 1
                ELSE 0
            END
        ) AS late_count,

        SUM(
            CASE
                WHEN a.status = 'On Leave'
                THEN 1
                ELSE 0
            END
        ) AS leave_count,

        COALESCE(
            SUM(a.work_hours),
            0
        ) AS total_work_hours

    FROM attendance a

    WHERE a.attendance_date = ?
";


$summaryStmt = mysqli_prepare(
    $conn,
    $summarySql
);

mysqli_stmt_bind_param(
    $summaryStmt,
    "s",
    $today
);

mysqli_stmt_execute(
    $summaryStmt
);

$summaryResult = mysqli_stmt_get_result(
    $summaryStmt
);

$summary = mysqli_fetch_assoc(
    $summaryResult
);


$presentToday =
    (int) ($summary['present_count'] ?? 0);

$lateToday =
    (int) ($summary['late_count'] ?? 0);

$onLeaveToday =
    (int) ($summary['leave_count'] ?? 0);

$totalWorkHours =
    (float) ($summary['total_work_hours'] ?? 0);


/*
|--------------------------------------------------------------------------
| Automatic Absent Calculation
|--------------------------------------------------------------------------
*/

$absentToday =
    max(
        0,
        $totalUsers
        -
        $presentToday
        -
        $lateToday
        -
        $onLeaveToday
    );


/*
|--------------------------------------------------------------------------
| Attendance Rate
|--------------------------------------------------------------------------
|
| On Leave users are excluded from denominator.
|
*/

$eligibleUsers =
    $totalUsers
    -
    $onLeaveToday;


$attendanceRate = 0;


if ($eligibleUsers > 0) {

    $attendanceRate =
        (
            (
                $presentToday
                +
                $lateToday
            )
            /
            $eligibleUsers
        )
        * 100;

}


/*
|--------------------------------------------------------------------------
| Last 7 Days Attendance Trend
|--------------------------------------------------------------------------
*/

$sevenDaysAgo =
    date(
        "Y-m-d",
        strtotime("-6 days")
    );


$trendSql = "
    SELECT

        attendance_date,

        SUM(
            CASE
                WHEN status = 'Present'
                THEN 1
                ELSE 0
            END
        ) AS present_count,

        SUM(
            CASE
                WHEN status = 'Late'
                THEN 1
                ELSE 0
            END
        ) AS late_count,

        SUM(
            CASE
                WHEN status = 'On Leave'
                THEN 1
                ELSE 0
            END
        ) AS leave_count

    FROM attendance

    WHERE attendance_date >= ?
    AND attendance_date <= ?

    GROUP BY attendance_date

    ORDER BY attendance_date ASC
";


$trendStmt = mysqli_prepare(
    $conn,
    $trendSql
);

mysqli_stmt_bind_param(
    $trendStmt,
    "ss",
    $sevenDaysAgo,
    $today
);

mysqli_stmt_execute(
    $trendStmt
);

$trendResult =
    mysqli_stmt_get_result(
        $trendStmt
    );


$trendData = [];


while (
    $row =
    mysqli_fetch_assoc(
        $trendResult
    )
) {

    $trendData[
        $row['attendance_date']
    ] = $row;

}


/*
|--------------------------------------------------------------------------
| Prepare 7-Day Chart Data
|--------------------------------------------------------------------------
*/

$chartLabels = [];

$chartPresent = [];

$chartLate = [];

$chartAbsent = [];


for (
    $i = 6;
    $i >= 0;
    $i--
) {

    $date = date(
        "Y-m-d",
        strtotime("-$i days")
    );


    $chartLabels[] =
        date(
            "D d",
            strtotime($date)
        );


    $present =
        (int) (
            $trendData[$date]['present_count']
            ?? 0
        );


    $late =
        (int) (
            $trendData[$date]['late_count']
            ?? 0
        );


    $onLeave =
        (int) (
            $trendData[$date]['leave_count']
            ?? 0
        );


    $absent =
        max(
            0,
            $totalUsers
            -
            $present
            -
            $late
            -
            $onLeave
        );


    $chartPresent[] =
        $present;

    $chartLate[] =
        $late;

    $chartAbsent[] =
        $absent;

}


/*
|--------------------------------------------------------------------------
| Department Performance
|--------------------------------------------------------------------------
*/

$departmentSql = "
    SELECT

        d.department_name,

        COUNT(u.id) AS total_users,

        SUM(
            CASE
                WHEN a.status = 'Present'
                THEN 1
                ELSE 0
            END
        ) AS present_count,

        SUM(
            CASE
                WHEN a.status = 'Late'
                THEN 1
                ELSE 0
            END
        ) AS late_count,

        SUM(
            CASE
                WHEN a.status = 'On Leave'
                THEN 1
                ELSE 0
            END
        ) AS leave_count

    FROM departments d

    LEFT JOIN users u

        ON u.department_id = d.id

        AND u.status = 'Active'

    LEFT JOIN attendance a

        ON a.user_id = u.id

        AND a.attendance_date = ?

    WHERE d.status = 'Active'

    GROUP BY

        d.id,

        d.department_name

    ORDER BY

        d.department_name ASC
";


$departmentStmt =
    mysqli_prepare(
        $conn,
        $departmentSql
    );


mysqli_stmt_bind_param(
    $departmentStmt,
    "s",
    $today
);


mysqli_stmt_execute(
    $departmentStmt
);


$departmentResult =
    mysqli_stmt_get_result(
        $departmentStmt
    );


$departmentData = [];


while (
    $row =
    mysqli_fetch_assoc(
        $departmentResult
    )
) {

    $departmentData[] =
        $row;

}


/*
|--------------------------------------------------------------------------
| Recent Attendance Activities
|--------------------------------------------------------------------------
*/

$recentSql = "
    SELECT

        a.check_in,

        a.check_out,

        a.status,

        a.check_in_distance,

        u.user_code,

        CONCAT(
            u.first_name,
            ' ',
            u.last_name
        ) AS full_name,

        d.department_name

    FROM attendance a

    INNER JOIN users u

        ON a.user_id = u.id

    LEFT JOIN departments d

        ON u.department_id = d.id

    WHERE a.attendance_date = ?

    ORDER BY

        a.check_in DESC

    LIMIT 8
";


$recentStmt =
    mysqli_prepare(
        $conn,
        $recentSql
    );


mysqli_stmt_bind_param(
    $recentStmt,
    "s",
    $today
);


mysqli_stmt_execute(
    $recentStmt
);


$recentResult =
    mysqli_stmt_get_result(
        $recentStmt
    );


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

                <i class="bi bi-speedometer2"></i>

                Admin Dashboard

            </h3>

            <p class="text-muted mb-0">

                Real-time attendance overview and
                organization performance.

            </p>

        </div>


        <div class="text-end">

            <h5 class="mb-0">

                <?php
                echo date(
                    "d M Y"
                );
                ?>

            </h5>

            <small class="text-muted">

                <?php
                echo date(
                    "l"
                );
                ?>

            </small>

        </div>

    </div>



    <!-- Summary Cards -->

    <div class="row mb-4">


        <!-- Total Users -->

        <div class="col-xl-3
                    col-md-6
                    mb-3">

            <div class="card
                        text-white
                        bg-primary
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between
                                align-items-center">

                        <div>

                            <h6>
                                Active Users
                            </h6>

                            <h2 class="mb-0">

                                <?php
                                echo $totalUsers;
                                ?>

                            </h2>

                        </div>

                        <i
                            class="bi bi-people-fill
                                   fs-1"
                        ></i>

                    </div>

                </div>

            </div>

        </div>



        <!-- Present -->

        <div class="col-xl-3
                    col-md-6
                    mb-3">

            <div class="card
                        text-white
                        bg-success
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between
                                align-items-center">

                        <div>

                            <h6>
                                Present Today
                            </h6>

                            <h2 class="mb-0">

                                <?php
                                echo $presentToday;
                                ?>

                            </h2>

                        </div>

                        <i
                            class="bi bi-check-circle-fill
                                   fs-1"
                        ></i>

                    </div>

                </div>

            </div>

        </div>



        <!-- Late -->

        <div class="col-xl-3
                    col-md-6
                    mb-3">

            <div class="card
                        text-dark
                        bg-warning
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between
                                align-items-center">

                        <div>

                            <h6>
                                Late Today
                            </h6>

                            <h2 class="mb-0">

                                <?php
                                echo $lateToday;
                                ?>

                            </h2>

                        </div>

                        <i
                            class="bi bi-clock-fill
                                   fs-1"
                        ></i>

                    </div>

                </div>

            </div>

        </div>



        <!-- Absent -->

        <div class="col-xl-3
                    col-md-6
                    mb-3">

            <div class="card
                        text-white
                        bg-danger
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between
                                align-items-center">

                        <div>

                            <h6>
                                Absent Today
                            </h6>

                            <h2 class="mb-0">

                                <?php
                                echo $absentToday;
                                ?>

                            </h2>

                        </div>

                        <i
                            class="bi bi-person-x-fill
                                   fs-1"
                        ></i>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- Second Stats Row -->

    <div class="row mb-4">


        <div class="col-md-6 mb-3">

            <div class="card
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between">

                        <div>

                            <h6 class="text-muted">

                                Attendance Rate

                            </h6>

                            <h2 class="mb-0">

                                <?php
                                echo number_format(
                                    $attendanceRate,
                                    1
                                );
                                ?>%

                            </h2>

                        </div>

                        <i
                            class="bi bi-graph-up-arrow
                                   fs-1
                                   text-success"
                        ></i>

                    </div>


                    <div class="progress mt-3"
                         style="height: 8px;">

                        <div
                            class="progress-bar bg-success"
                            role="progressbar"
                            style="width:
                            <?php
                            echo min(
                                100,
                                $attendanceRate
                            );
                            ?>%;"
                        ></div>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-6 mb-3">

            <div class="card
                        border-0
                        shadow-sm">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between">

                        <div>

                            <h6 class="text-muted">

                                Work Hours Today

                            </h6>

                            <h2 class="mb-0">

                                <?php
                                echo number_format(
                                    $totalWorkHours,
                                    2
                                );
                                ?>

                                <small>
                                    hrs
                                </small>

                            </h2>

                        </div>

                        <i
                            class="bi bi-hourglass-split
                                   fs-1
                                   text-primary"
                        ></i>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- Attendance Trend -->

    <div class="row mb-4">

        <div class="col-lg-8 mb-4">

            <div class="card
                        border-0
                        shadow-sm">

                <div class="card-header bg-white">

                    <h5 class="mb-0">

                        <i class="bi bi-bar-chart"></i>

                        7-Day Attendance Trend

                    </h5>

                </div>

                <div class="card-body">

                    <canvas
                        id="attendanceTrendChart"
                        height="120"
                    ></canvas>

                </div>

            </div>

        </div>


        <!-- Quick Info -->

        <div class="col-lg-4 mb-4">

            <div class="card
                        border-0
                        shadow-sm">

                <div class="card-header bg-white">

                    <h5 class="mb-0">

                        <i class="bi bi-info-circle"></i>

                        Today's Summary

                    </h5>

                </div>


                <div class="card-body">

                    <div class="d-flex
                                justify-content-between
                                mb-3">

                        <span>
                            Present
                        </span>

                        <strong
                            class="text-success"
                        >

                            <?php
                            echo $presentToday;
                            ?>

                        </strong>

                    </div>


                    <div class="d-flex
                                justify-content-between
                                mb-3">

                        <span>
                            Late
                        </span>

                        <strong
                            class="text-warning"
                        >

                            <?php
                            echo $lateToday;
                            ?>

                        </strong>

                    </div>


                    <div class="d-flex
                                justify-content-between
                                mb-3">

                        <span>
                            On Leave
                        </span>

                        <strong
                            class="text-info"
                        >

                            <?php
                            echo $onLeaveToday;
                            ?>

                        </strong>

                    </div>


                    <div class="d-flex
                                justify-content-between">

                        <span>
                            Absent
                        </span>

                        <strong
                            class="text-danger"
                        >

                            <?php
                            echo $absentToday;
                            ?>

                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- Department Performance -->

    <div class="card
                border-0
                shadow-sm
                mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">

                <i class="bi bi-building"></i>

                Department Performance

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

                            <th>
                                Department
                            </th>

                            <th>
                                Users
                            </th>

                            <th>
                                Present
                            </th>

                            <th>
                                Late
                            </th>

                            <th>
                                Absent
                            </th>

                            <th>
                                Attendance Rate
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    foreach (
                        $departmentData
                        as $department
                    ):

                        $departmentTotal =
                            (int)
                            $department[
                                'total_users'
                            ];

                        $departmentPresent =
                            (int)
                            $department[
                                'present_count'
                            ];

                        $departmentLate =
                            (int)
                            $department[
                                'late_count'
                            ];

                        $departmentLeave =
                            (int)
                            $department[
                                'leave_count'
                            ];

                        $departmentAbsent =
                            max(
                                0,

                                $departmentTotal
                                -
                                $departmentPresent
                                -
                                $departmentLate
                                -
                                $departmentLeave
                            );


                        $departmentEligible =
                            $departmentTotal
                            -
                            $departmentLeave;


                        $departmentRate = 0;


                        if (
                            $departmentEligible
                            > 0
                        ) {

                            $departmentRate =
                                (
                                    (
                                        $departmentPresent
                                        +
                                        $departmentLate
                                    )
                                    /
                                    $departmentEligible
                                )
                                *
                                100;

                        }

                    ?>

                        <tr>

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $department[
                                            'department_name'
                                        ]
                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php
                                echo $departmentTotal;
                                ?>

                            </td>


                            <td>

                                <span
                                    class="
                                    badge
                                    bg-success
                                    "
                                >

                                    <?php
                                    echo $departmentPresent;
                                    ?>

                                </span>

                            </td>


                            <td>

                                <span
                                    class="
                                    badge
                                    bg-warning
                                    text-dark
                                    "
                                >

                                    <?php
                                    echo $departmentLate;
                                    ?>

                                </span>

                            </td>


                            <td>

                                <span
                                    class="
                                    badge
                                    bg-danger
                                    "
                                >

                                    <?php
                                    echo $departmentAbsent;
                                    ?>

                                </span>

                            </td>


                            <td>

                                <div
                                    class="progress"
                                    style="height: 8px;"
                                >

                                    <div
                                        class="
                                        progress-bar
                                        bg-primary
                                        "
                                        style="
                                        width:
                                        <?php
                                        echo min(
                                            100,
                                            $departmentRate
                                        );
                                        ?>%;
                                        "
                                    ></div>

                                </div>

                                <small>

                                    <?php
                                    echo number_format(
                                        $departmentRate,
                                        1
                                    );
                                    ?>%

                                </small>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                    <?php if (
                        empty(
                            $departmentData
                        )
                    ): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="
                                text-center
                                text-muted
                                py-4
                                "
                            >

                                No department data available.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>



    <!-- Recent Attendance -->

    <div class="card
                border-0
                shadow-sm
                mb-4">

        <div class="card-header bg-white">

            <div class="d-flex
                        justify-content-between
                        align-items-center">

                <h5 class="mb-0">

                    <i class="bi bi-activity"></i>

                    Recent Attendance Activity

                </h5>


                <a
                    href="attendance_monitoring.php"
                    class="btn btn-sm btn-primary"
                >

                    View All

                </a>

            </div>

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

                            <th>
                                User
                            </th>

                            <th>
                                Department
                            </th>

                            <th>
                                Check-In
                            </th>

                            <th>
                                Distance
                            </th>

                            <th>
                                Check-Out
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php while (
                        $activity =
                        mysqli_fetch_assoc(
                            $recentResult
                        )
                    ): ?>

                        <tr>

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $activity[
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
                                        $activity[
                                            'user_code'
                                        ]
                                    );
                                    ?>

                                </small>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $activity[
                                        'department_name'
                                    ]
                                    ??
                                    'N/A'
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo $activity[
                                    'check_in'
                                ]
                                ? date(
                                    "h:i A",
                                    strtotime(
                                        $activity[
                                            'check_in'
                                        ]
                                    )
                                )
                                : '--';
                                ?>

                            </td>


                            <td>

                                <?php
                                echo $activity[
                                    'check_in_distance'
                                ]
                                !== null
                                ?

                                number_format(
                                    $activity[
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
                                echo $activity[
                                    'check_out'
                                ]
                                ? date(
                                    "h:i A",
                                    strtotime(
                                        $activity[
                                            'check_out'
                                        ]
                                    )
                                )
                                : '--';
                                ?>

                            </td>


                            <td>

                                <?php

                                $activityStatus =
                                    $activity[
                                        'status'
                                    ];


                                if (
                                    $activityStatus
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
                                    $activityStatus
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

                                ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<!-- Chart.js -->

<script
    src="https://cdn.jsdelivr.net/npm/chart.js"
></script>


<script>

const chartLabels =
    <?php
    echo json_encode(
        $chartLabels
    );
    ?>;


const chartPresent =
    <?php
    echo json_encode(
        $chartPresent
    );
    ?>;


const chartLate =
    <?php
    echo json_encode(
        $chartLate
    );
    ?>;


const chartAbsent =
    <?php
    echo json_encode(
        $chartAbsent
    );
    ?>;


const chartElement =
    document.getElementById(
        "attendanceTrendChart"
    );


if (chartElement) {

    new Chart(
        chartElement,
        {

            type: "line",

            data: {

                labels:
                    chartLabels,

                datasets: [

                    {
                        label:
                            "Present",

                        data:
                            chartPresent,

                        tension:
                            0.3
                    },


                    {
                        label:
                            "Late",

                        data:
                            chartLate,

                        tension:
                            0.3
                    },


                    {
                        label:
                            "Absent",

                        data:
                            chartAbsent,

                        tension:
                            0.3
                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                interaction: {

                    mode: "index",

                    intersect: false

                },

                plugins: {

                    legend: {

                        position:
                            "bottom"

                    }

                },

                scales: {

                    y: {

                        beginAtZero:
                            true,

                        ticks: {

                            precision: 0

                        }

                    }

                }

            }

        }

    );

}

</script>


<?php

include("../includes/footer.php");

?>