<?php

include("../includes/auth_check.php");

requireRole('Admin');

include("../config/database.php");


// --------------------------------------------------
// Get All Working Shifts
// --------------------------------------------------

$shiftsSql = "SELECT
                    s.id,
                    s.shift_name,
                    s.check_in_time,
                    s.late_after,
                    s.check_out_time,
                    s.status,
                    s.created_at,
                    COUNT(u.id) AS user_count

              FROM shifts s

              LEFT JOIN users u
                    ON u.shift_id = s.id

              GROUP BY
                    s.id,
                    s.shift_name,
                    s.check_in_time,
                    s.late_after,
                    s.check_out_time,
                    s.status,
                    s.created_at

              ORDER BY s.id DESC";


$shiftsResult = mysqli_query(
    $conn,
    $shiftsSql
);


// --------------------------------------------------
// Check Query
// --------------------------------------------------

if (!$shiftsResult) {

    die(
        "Shifts query failed: "
        . mysqli_error($conn)
    );

}


$shiftsCount = mysqli_num_rows(
    $shiftsResult
);


// --------------------------------------------------
// Page Layout
// --------------------------------------------------

include("../includes/header.php");

include("../includes/sidebar.php");

include("../includes/topbar.php");

?>

<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3 class="mb-1">
                <i class="bi bi-clock-history"></i>
                Shift Management
            </h3>

            <p class="text-muted mb-0">
                Manage working shifts and attendance time rules.
            </p>

        </div>

        <a href="add_shift.php" class="btn btn-primary">

            <i class="bi bi-plus-circle"></i>
            Add Shift

        </a>

    </div>


    <!-- Shift Table -->
    <div class="card">

        <div class="card-header bg-white">

            <h5 class="mb-0">

                <i class="bi bi-clock"></i>
                All Working Shifts

            </h5>

        </div>


        <div class="card-body">

        <div class="alert alert-info">

    <i class="bi bi-clock-history"></i>

    <strong>Shifts found:</strong>

    <?php echo $shiftsCount; ?>

</div>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>#</th>

                            <th>Shift Name</th>

                            <th>Check-In</th>

                            <th>Late After</th>

                            <th>Check-Out</th>

                            <th>Assigned Users</th>

                            <th>Status</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if ($shiftsCount > 0): ?>

                        <?php

                        $count = 1;

                        while ($row = mysqli_fetch_assoc($shiftsResult)):
                        ?>

                            <tr>

                                <td>
                                    <?php echo $count++; ?>
                                </td>


                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $row['shift_name']
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <span class="badge bg-primary">

                                        <?php
                                        echo date(
                                            "h:i A",
                                            strtotime($row['check_in_time'])
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="badge bg-warning text-dark">

                                        <?php
                                        echo date(
                                            "h:i A",
                                            strtotime($row['late_after'])
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="badge bg-dark">

                                        <?php
                                        echo date(
                                            "h:i A",
                                            strtotime($row['check_out_time'])
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="badge bg-info text-dark">

                                        <i class="bi bi-people-fill"></i>

                                        <?php
                                        echo $row['user_count'];
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php if ($row['status'] === 'Active'): ?>

                                        <span class="badge bg-success">

                                            Active

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">

                                            Inactive

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <!-- Edit -->
                                    <a
                                        href="edit_shift.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-warning btn-sm"
                                        title="Edit Shift"
                                    >

                                        <i class="bi bi-pencil"></i>

                                    </a>


                                    <!-- Activate / Deactivate -->

                                    <?php if ($row['status'] === 'Active'): ?>

                                        <a
                                            href="deactivate_shift.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-danger btn-sm"
                                            title="Deactivate Shift"
                                            onclick="return confirm('Are you sure you want to deactivate this shift?');"
                                        >

                                            <i class="bi bi-pause-circle"></i>

                                        </a>

                                    <?php else: ?>

                                        <a
                                            href="activate_shift.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-success btn-sm"
                                            title="Activate Shift"
                                            onclick="return confirm('Activate this shift?');"
                                        >

                                            <i class="bi bi-play-circle"></i>

                                        </a>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="8"
                                class="text-center text-muted py-4"
                            >

                                <i class="bi bi-clock fs-2 d-block mb-2"></i>

                                No shifts found.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?php include("../includes/footer.php"); ?>