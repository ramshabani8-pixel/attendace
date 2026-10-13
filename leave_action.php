<?php

include("../includes/auth_check.php");

requireRole('Admin', 'Supervisor');

include("../config/database.php");

include("../includes/notification_helper.php");


/*
|--------------------------------------------------------------------------
| Only POST Requests
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: leave_requests.php"
    );

    exit();

}


/*
|--------------------------------------------------------------------------
| Current Reviewer
|--------------------------------------------------------------------------
*/

$reviewer_id = intval(
    $_SESSION['user_id']
);

$role = $_SESSION['role'] ?? '';


/*
|--------------------------------------------------------------------------
| Form Data
|--------------------------------------------------------------------------
*/

$request_id = intval(
    $_POST['request_id'] ?? 0
);

$action = $_POST['action'] ?? '';

$reviewer_comment = trim(
    $_POST['reviewer_comment'] ?? ''
);


/*
|--------------------------------------------------------------------------
| Validate Action
|--------------------------------------------------------------------------
*/

if (
    $request_id <= 0
) {

    die(
        "Invalid leave request."
    );

}


if (
    !in_array(
        $action,
        [
            'approve',
            'reject'
        ],
        true
    )
) {

    die(
        "Invalid action."
    );

}


/*
|--------------------------------------------------------------------------
| Convert Action to Status
|--------------------------------------------------------------------------
*/

$newStatus =
    $action === 'approve'
    ? 'Approved'
    : 'Rejected';


/*
|--------------------------------------------------------------------------
| Get Leave Request
|--------------------------------------------------------------------------
*/

$requestSql = "

    SELECT

        lr.id,

        lr.user_id,

        lr.status,

        applicant.department_id

    FROM leave_requests lr

    INNER JOIN users applicant

        ON lr.user_id = applicant.id

    WHERE lr.id = ?

    LIMIT 1

";


$requestStmt = mysqli_prepare(
    $conn,
    $requestSql
);


mysqli_stmt_bind_param(
    $requestStmt,
    "i",
    $request_id
);


mysqli_stmt_execute(
    $requestStmt
);


$requestResult =
    mysqli_stmt_get_result(
        $requestStmt
    );


$request =
    mysqli_fetch_assoc(
        $requestResult
    );


if (!$request) {

    die(
        "Leave request was not found."
    );

}


/*
|--------------------------------------------------------------------------
| Check Pending Status
|--------------------------------------------------------------------------
*/

if (
    $request['status']
    !==
    'Pending'
) {

    die(
        "This leave request has already been reviewed."
    );

}


/*
|--------------------------------------------------------------------------
| Prevent Self Approval
|--------------------------------------------------------------------------
*/

if (
    intval(
        $request['user_id']
    )
    ===
    $reviewer_id
) {

    die(
        "You cannot approve or reject your own leave request."
    );

}


/*
|--------------------------------------------------------------------------
| Supervisor Department Restriction
|--------------------------------------------------------------------------
*/

if (
    $role === 'Supervisor'
) {

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
        $reviewer_id
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
        !$supervisorData
        ||
        intval(
            $supervisorData[
                'department_id'
            ]
        )
        !==
        intval(
            $request[
                'department_id'
            ]
        )
    ) {

        http_response_code(403);

        die(
            "You are not authorized to review this leave request."
        );

    }

}


/*
|--------------------------------------------------------------------------
| Update Request
|--------------------------------------------------------------------------
*/

$updateSql = "

    UPDATE leave_requests

    SET

        status = ?,

        reviewed_by = ?,

        reviewed_at = NOW(),

        reviewer_comment = ?

    WHERE id = ?

    AND status = 'Pending'

";


$updateStmt =
    mysqli_prepare(
        $conn,
        $updateSql
    );


mysqli_stmt_bind_param(
    $updateStmt,
    "sisi",
    $newStatus,
    $reviewer_id,
    $reviewer_comment,
    $request_id
);


if (mysqli_stmt_execute($updateStmt)) {

    if ($newStatus === 'Approved') {

        createNotification(
            $conn,
            $request['user_id'],
            "Leave Approved",
            "Your leave request has been approved.",
            "Success"
        );

    } else {

        createNotification(
            $conn,
            $request['user_id'],
            "Leave Rejected",
            "Your leave request has been rejected.",
            "Warning"
        );

    }

    header("Location: leave_requests.php?updated=1");

    exit();

}


echo "Unable to update leave request.";

?>