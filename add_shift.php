<?php
include("../includes/auth_check.php");

requireRole('Admin');
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

                <i class="bi bi-clock-history"></i>

                Add New Shift

            </h3>

            <p class="text-muted mb-0">

                Create a working shift for organization members.

            </p>

        </div>


        <a
            href="shifts.php"
            class="btn btn-secondary"
        >

            <i class="bi bi-arrow-left"></i>

            Back to Shifts

        </a>

    </div>


    <!-- Add Shift Form -->

    <div class="card">

        <div class="card-header bg-primary text-white">

            <h5 class="mb-0">

                <i class="bi bi-clock"></i>

                Shift Information

            </h5>

        </div>


        <div class="card-body">

            <form
                action="save_shift.php"
                method="POST"
            >

                <div class="row">

                    <!-- Shift Name -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">

                            Shift Name

                        </label>

                        <input
                            type="text"
                            name="shift_name"
                            class="form-control"
                            placeholder="Example: Morning Shift"
                            required
                        >

                    </div>


                    <!-- Status -->

                    <div class="col-md-6 mb-3">

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

                </div>


                <div class="row">

                    <!-- Check In -->

                    <div class="col-md-4 mb-3">

                        <label class="form-label">

                            Official Check-In Time

                        </label>

                        <input
                            type="time"
                            name="check_in_time"
                            class="form-control"
                            required
                        >

                    </div>


                    <!-- Late After -->

                    <div class="col-md-4 mb-3">

                        <label class="form-label">

                            Late After

                        </label>

                        <input
                            type="time"
                            name="late_after"
                            class="form-control"
                            required
                        >

                    </div>


                    <!-- Check Out -->

                    <div class="col-md-4 mb-3">

                        <label class="form-label">

                            Official Check-Out Time

                        </label>

                        <input
                            type="time"
                            name="check_out_time"
                            class="form-control"
                            required
                        >

                    </div>

                </div>


                <!-- Buttons -->

                <button
                    type="submit"
                    class="btn btn-success"
                >

                    <i class="bi bi-save"></i>

                    Save Shift

                </button>


                <a
                    href="shifts.php"
                    class="btn btn-secondary"
                >

                    Cancel

                </a>

            </form>

        </div>

    </div>

</div>

<?php include("../includes/footer.php"); ?>