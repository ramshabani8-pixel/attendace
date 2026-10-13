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

$status_filter = $_GET['status'] ?? '';

$search = trim($_GET['search'] ?? '');


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

    $supervisorResult = mysqli_stmt_get_result(
        $supervisorStmt
    );

    $supervisorData = mysqli_fetch_assoc(
        $supervisorResult
    );

    if (
        !$supervisorData ||
        empty($supervisorData['department_id'])
    ) {
        die("Your account is not assigned to a department.");
    }

    $supervisor_department_id = intval(
        $supervisorData['department_id']
    );
}


/*
|--------------------------------------------------------------------------
| Build Leave Request Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        lr.id,

        lr.user_id,

        lr.leave_type,

        lr.reason,

        lr.start_date,

        lr.end_date,

        lr.status,

        lr.reviewed_at,

        lr.reviewer_comment,

        lr.created_at,

        CONCAT(
            applicant.first_name,
            ' ',
            applicant.last_name
        ) AS applicant_name,

        applicant.user_code,

        d.department_name,

        CONCAT(
            reviewer.first_name,
            ' ',
            reviewer.last_name
        ) AS reviewer_name

    FROM leave_requests lr

    INNER JOIN users applicant
        ON lr.user_id = applicant.id

    LEFT JOIN departments d
        ON applicant.department_id = d.id

    LEFT JOIN users reviewer
        ON lr.reviewed_by = reviewer.id

    WHERE 1 = 1
";


$types = "";

$params = [];


/*
|--------------------------------------------------------------------------
| Supervisor Department Restriction
|--------------------------------------------------------------------------
*/

if ($role === 'Supervisor') {

    $sql .= "
        AND applicant.department_id = ?
    ";

    $types .= "i";

    $params[] = $supervisor_department_id;

}


/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    'Pending',
    'Approved',
    'Rejected'
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
        AND lr.status = ?
    ";

    $types .= "s";

    $params[] = $status_filter;

}


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if (!empty($search)) {

    $sql .= "
        AND (
            applicant.user_code LIKE ?
            OR applicant.first_name LIKE ?
            OR applicant.last_name LIKE ?
        )
    ";

    $types .= "sss";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;

    $params[] = $searchValue;

    $params[] = $searchValue;

}


/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY

        CASE

            WHEN lr.status = 'Pending'
            THEN 1

            WHEN lr.status = 'Approved'
            THEN 2

            ELSE 3

        END,

        lr.created_at DESC
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


/*
|--------------------------------------------------------------------------
| Dynamic Binding
|--------------------------------------------------------------------------
*/

if (!empty($params)) {

    $bindParams = [];

    $bindParams[] = $types;

    foreach (
        $params
        as $key => $value
    ) {

        $bindParams[] = &$params[$key];

    }

    call_user_func_array(
        [
            $stmt,
            'bind_param'
        ],
        $bindParams
    );

}


mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalRequests = 0;

$pendingCount = 0;

$approvedCount = 0;

$rejectedCount = 0;

$requests = [];


while (
    $row = mysqli_fetch_assoc($result)
) {

    $requests[] = $row;

    $totalRequests++;


    if (
        $row['status'] === 'Pending'
    ) {

        $pendingCount++;

    }


    elseif (
        $row['status'] === 'Approved'
    ) {

        $approvedCount++;

    }


    elseif (
        $row['status'] === 'Rejected'
    ) {

        $rejectedCount++;

    }

}


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

                <i class="bi bi-calendar2-check"></i>

                Leave Requests

            </h3>

            <p class="text-muted mb-0">

                Review and manage leave applications.

            </p>

        </div>

    </div>


    <!-- Summary Cards -->

    <div class="row mb-4">


        <div class="col-md-3 mb-3">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Requests
                    </h6>

                    <h2>
                        <?php echo $totalRequests; ?>
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-3 mb-3">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">
                        Pending
                    </h6>

                    <h2 class="text-warning">
                        <?php echo $pendingCount; ?>
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-3 mb-3">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">
                        Approved
                    </h6>

                    <h2 class="text-success">
                        <?php echo $approvedCount; ?>
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-3 mb-3">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">
                        Rejected
                    </h6>

                    <h2 class="text-danger">
                        <?php echo $rejectedCount; ?>
                    </h2>

                </div>

            </div>

        </div>

    </div>


    <!-- Filters -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">

                <i class="bi bi-funnel"></i>

                Filter Requests

            </h5>

        </div>


        <div class="card-body">

            <form method="GET">

                <div class="row">


                    <div class="col-md-5 mb-3">

                        <label class="form-label">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="User Code or Name"
                            value="<?php
                            echo htmlspecialchars($search);
                            ?>"
                        >

                    </div>


                    <div class="col-md-4 mb-3">

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
                                value="Pending"
                                <?php
                                echo
                                $status_filter === 'Pending'
                                ? 'selected'
                                : '';
                                ?>
                            >
                                Pending
                            </option>

                            <option
                                value="Approved"
                                <?php
                                echo
                                $status_filter === 'Approved'
                                ? 'selected'
                                : '';
                                ?>
                            >
                                Approved
                            </option>

                            <option
                                value="Rejected"
                                <?php
                                echo
                                $status_filter === 'Rejected'
                                ? 'selected'
                                : '';
                                ?>
                            >
                                Rejected
                            </option>

                        </select>

                    </div>


                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            &nbsp;
                        </label>

                        <div>

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="bi bi-search"></i>

                                Filter

                            </button>


                            <a
                                href="leave_requests.php"
                                class="btn btn-secondary"
                            >

                                Reset

                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- Leave Requests Table -->

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white">

            <h5 class="mb-0">

                <i class="bi bi-table"></i>

                Leave Applications

            </h5>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>
                                Applicant
                            </th>

                            <th>
                                Department
                            </th>

                            <th>
                                Leave Type
                            </th>

                            <th>
                                Dates
                            </th>

                            <th>
                                Reason
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Review
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!empty($requests)): ?>


                        <?php foreach (
                            $requests
                            as $request
                        ): ?>


                            <tr>


                                <!-- Applicant -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $request[
                                                'applicant_name'
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
                                            $request[
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
                                        $request[
                                            'department_name'
                                        ]
                                        ?? 'N/A'
                                    );
                                    ?>

                                </td>


                                <!-- Leave Type -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $request[
                                            'leave_type'
                                        ]
                                    );
                                    ?>

                                </td>


                                <!-- Dates -->

                                <td>

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $request[
                                                'start_date'
                                            ]
                                        )
                                    );

                                    ?>

                                    <br>

                                    <small
                                        class="text-muted"
                                    >

                                        to

                                    </small>

                                    <br>

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $request[
                                                'end_date'
                                            ]
                                        )
                                    );

                                    ?>

                                </td>


                                <!-- Reason -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $request[
                                            'reason'
                                        ]
                                    );
                                    ?>

                                </td>


                                <!-- Status -->

                                <td>

                                    <?php

                                    if (
                                        $request['status']
                                        ===
                                        'Pending'
                                    ) {

                                        echo '
                                        <span class="
                                            badge
                                            bg-warning
                                            text-dark
                                        ">
                                            Pending
                                        </span>';

                                    }

                                    elseif (
                                        $request['status']
                                        ===
                                        'Approved'
                                    ) {

                                        echo '
                                        <span class="
                                            badge
                                            bg-success
                                        ">
                                            Approved
                                        </span>';

                                    }

                                    else {

                                        echo '
                                        <span class="
                                            badge
                                            bg-danger
                                        ">
                                            Rejected
                                        </span>';

                                    }

                                    ?>

                                </td>


                                <!-- Review -->

                                <td>

                                <?php

                                $isOwnRequest =
                                    (
                                        intval(
                                            $request[
                                                'user_id'
                                            ]
                                        )
                                        ===
                                        $current_user_id
                                    );


                                if (
                                    $request['status']
                                    ===
                                    'Pending'
                                    &&
                                    !$isOwnRequest
                                ):

                                ?>

                                    <form
                                        action="leave_action.php"
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?php
                                            echo $request['id'];
                                            ?>"
                                        >


                                        <div
                                            class="mb-2"
                                        >

                                            <textarea
                                                name="reviewer_comment"
                                                class="form-control
                                                       form-control-sm"
                                                rows="2"
                                                placeholder="Optional comment..."
                                            ></textarea>

                                        </div>


                                        <button
                                            type="submit"
                                            name="action"
                                            value="approve"
                                            class="btn btn-success
                                                   btn-sm"
                                        >

                                            <i class="bi bi-check-lg"></i>

                                            Approve

                                        </button>


                                        <button
                                            type="submit"
                                            name="action"
                                            value="reject"
                                            class="btn btn-danger
                                                   btn-sm"
                                        >

                                            <i class="bi bi-x-lg"></i>

                                            Reject

                                        </button>

                                    </form>


                                <?php elseif (
                                    $isOwnRequest
                                    &&
                                    $request['status']
                                    ===
                                    'Pending'
                                ): ?>

                                    <span
                                        class="badge bg-secondary"
                                    >

                                        Awaiting independent review

                                    </span>


                                <?php else: ?>


                                    <?php if (
                                        !empty(
                                            $request[
                                                'reviewer_name'
                                            ]
                                        )
                                    ): ?>

                                        <small>

                                            Reviewed by:

                                            <strong>

                                                <?php
                                                echo htmlspecialchars(
                                                    $request[
                                                        'reviewer_name'
                                                    ]
                                                );
                                                ?>

                                            </strong>

                                        </small>

                                        <br>

                                        <?php if (
                                            !empty(
                                                $request[
                                                    'reviewer_comment'
                                                ]
                                            )
                                        ): ?>

                                            <small
                                                class="text-muted"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $request[
                                                        'reviewer_comment'
                                                    ]
                                                );
                                                ?>

                                            </small>

                                        <?php endif; ?>

                                    <?php else: ?>

                                        --

                                    <?php endif; ?>

                                <?php endif; ?>

                                </td>

                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="7"
                                class="text-center
                                       text-muted
                                       py-5"
                            >

                                <i
                                    class="bi bi-calendar-x fs-1 d-block mb-3"
                                ></i>

                                No leave requests found.

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