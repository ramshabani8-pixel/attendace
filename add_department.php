<?php
include("../includes/header.php");
include("../config/database.php");

include("../includes/sidebar.php");
include("../includes/topbar.php");
?>

<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="mb-1">
                <i class="bi bi-building-add"></i>
                Add New Department
            </h3>

            <p class="text-muted mb-0">
                Create a new department in the organization.
            </p>
        </div>

        <a href="departments.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i>
            Back to Departments
        </a>

    </div>


    <!-- Add Department Form -->
    <div class="card">

        <div class="card-header bg-primary text-white">

            <h5 class="mb-0">
                Department Information
            </h5>

        </div>

        <div class="card-body">

            <form action="save_department.php" method="POST">

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
                            placeholder="Example: Information Technology"
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
                            placeholder="Example: IT"
                            maxlength="20"
                            required
                        >

                        <small class="text-muted">
                            Use a short unique code such as IT, HR, FIN or ADM.
                        </small>

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
                        placeholder="Enter department description..."
                    ></textarea>

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

                        <option value="Active">
                            Active
                        </option>

                        <option value="Inactive">
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
                    Save Department

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