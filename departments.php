<?php

include("../includes/auth_check.php");

requireRole('Admin');

include("../config/database.php");


// --------------------------------------------------
// Get All Departments
// --------------------------------------------------

$departmentsSql = "SELECT
                        d.id,
                        d.department_name,
                        d.department_code,
                        d.description,
                        d.status,
                        d.created_at,
                        COUNT(u.id) AS user_count

                   FROM departments d

                   LEFT JOIN users u
                        ON u.department_id = d.id

                   GROUP BY
                        d.id,
                        d.department_name,
                        d.department_code,
                        d.description,
                        d.status,
                        d.created_at

                   ORDER BY d.id DESC";


$departmentsResult = mysqli_query(
    $conn,
    $departmentsSql
);


// --------------------------------------------------
// Check Query
// --------------------------------------------------

if (!$departmentsResult) {

    die(
        "Departments query failed: "
        . mysqli_error($conn)
    );

}


$departmentsCount = mysqli_num_rows(
    $departmentsResult
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
                <i class="bi bi-building"></i>
                Department Management
            </h3>

            <p class="text-muted mb-0">
                Manage organization departments and members.
            </p>
        </div>

        <a href="add_department.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i>
            Add Department
        </a>

    </div>


    <!-- Department Table -->
    <div class="card">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                All Departments
            </h5>

        </div>

        <div class="card-body">

        <div class="alert alert-info">

    <i class="bi bi-building"></i>

    <strong>Departments found:</strong>

    <?php echo $departmentsCount; ?>

</div>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>#</th>

                            <th>Department Name</th>

                            <th>Code</th>

                            <th>Description</th>

                            <th>Users</th>

                            <th>Status</th>

                            <th>Created</th>

                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                   <?php if ($departmentsCount > 0): ?>

                        <?php
                        $count = 1;

                        while ($row = mysqli_fetch_assoc($departmentsResult)):
                        ?>

                            <tr>

                                <td>
                                    <?php echo $count++; ?>
                                </td>

                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($row['department_name']); ?>
                                    </strong>
                                </td>

                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($row['department_code']); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    echo !empty($row['description'])
                                        ? htmlspecialchars($row['description'])
                                        : '<span class="text-muted">No description</span>';
                                    ?>
                                </td>

                                <td>

                                    <span class="badge bg-info text-dark">

                                        <i class="bi bi-people-fill"></i>

                                        <?php echo $row['user_count']; ?>

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

                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime($row['created_at'])
                                    );
                                    ?>

                                </td>

                                <td>

                                    <a
                                        href="edit_department.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-warning btn-sm"
                                        title="Edit Department"
                                    >

                                        <i class="bi bi-pencil"></i>

                                    </a>


                                    <?php if ($row['status'] === 'Active'): ?>

                                        <a
                                            href="deactivate_department.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-danger btn-sm"
                                            title="Deactivate Department"
                                            onclick="return confirm('Are you sure you want to deactivate this department?');"
                                        >

                                            <i class="bi bi-building-x"></i>

                                        </a>

                                    <?php else: ?>

                                        <a
                                            href="activate_department.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-success btn-sm"
                                            title="Activate Department"
                                            onclick="return confirm('Activate this department?');"
                                        >

                                            <i class="bi bi-check-circle"></i>

                                        </a>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="8" class="text-center text-muted py-4">

                                <i class="bi bi-building fs-2 d-block mb-2"></i>

                                No departments found.

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