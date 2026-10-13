<?php
include("../includes/auth_check.php");

requireRole('Admin');
include("../includes/header.php");
include("../config/database.php");

// Check if department ID exists
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: departments.php");
    exit();
}

$id = intval($_GET['id']);

// Get department details
$sql = "SELECT * FROM departments WHERE id = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$department = mysqli_fetch_assoc($result);

// Check if department exists
if (!$department) {
    header("Location: departments.php");
    exit();
}

include("../includes/sidebar.php");
include("../includes/topbar.php");
?>

<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="mb-1">
                <i class="bi bi-pencil-square"></i>
                Edit Department
            </h3>

            <p class="text-muted mb-0">
                Update department information.
            </p>
        </div>

        <a href="departments.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i>
            Back to Departments
        </a>

    </div>


    <!-- Edit Department Form -->
    <div class="card">

        <div class="card-header bg-warning">

            <h5 class="mb-0">
                Department Information
            </h5>

        </div>


        <div class="card-body">

            <form action="update_department.php" method="POST">

                <!-- Hidden ID -->
                <input
                    type="hidden"
                    name="id"
                    value="<?php echo $department['id']; ?>"
                >


                <div class="row">

                    <!-- Department Name -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Department Name
                        </label>

                        <input
                            type="text"
                            name="department_name"
                            class="form-control"
                            value="<?php echo htmlspecialchars($department['department_name']); ?>"
                            required
                        >

                    </div>


                    <!-- Department Code -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Department Code
                        </label>

                        <input
                            type="text"
                            name="department_code"
                            class="form-control"
                            value="<?php echo htmlspecialchars($department['department_code']); ?>"
                            maxlength="20"
                            required
                        >

                    </div>

                </div>


                <!-- Description -->
                <div class="mb-3">

                    <label class="form-label">
                        Description
                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        rows="4"
                    ><?php echo htmlspecialchars($department['description'] ?? ''); ?></textarea>

                </div>


                <!-- Status -->
                <div class="mb-4">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option
                            value="Active"
                            <?php
                            echo ($department['status'] === 'Active')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="Inactive"
                            <?php
                            echo ($department['status'] === 'Inactive')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <!-- Buttons -->
                <button
                    type="submit"
                    class="btn btn-success"
                >

                    <i class="bi bi-save"></i>
                    Update Department

                </button>


                <a
                    href="departments.php"
                    class="btn btn-secondary"
                >

                    Cancel

                </a>

            </form>

        </div>

    </div>

</div>

<?php include("../includes/footer.php"); ?>