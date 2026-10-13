<?php

include("../includes/auth_check.php");

requireRole('Admin');

include("../config/database.php");

/*
|--------------------------------------------------------------------------
| Get System Settings
|--------------------------------------------------------------------------
*/

$sql = "
SELECT *
FROM system_settings
LIMIT 1
";

$result = mysqli_query($conn, $sql);

$settings = mysqli_fetch_assoc($result);

include("../includes/header.php");
include("../includes/sidebar.php");

include("../includes/topbar.php");

?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3 class="mb-1">
                <i class="bi bi-gear-fill"></i>
                System Settings
            </h3>

            <p class="text-muted mb-0">
                Manage company and system information.
            </p>

        </div>

    </div>

    <div class="card shadow-sm border-0">

    <div class="card-header bg-white">

        <h5 class="mb-0">

            Company Information

        </h5>

    </div>

    <div class="card-body">

        <form
              action="save_settings.php"
                   method="POST"
                       enctype="multipart/form-data">

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">

                        System Name

                    </label>

                    <input
                        type="text"
                        name="system_name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($settings['system_name']); ?>"
                    >

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">

                        Company Name

                    </label>

                    <input
                        type="text"
                        name="company_name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($settings['company_name']); ?>"
                    >

                </div>

                <div class="col-md-12 mb-3">

                    <label class="form-label">

                        Company Address

                    </label>

                    <textarea
                        name="company_address"
                        class="form-control"
                        rows="3"
                    ><?php echo htmlspecialchars($settings['company_address']); ?></textarea>

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">

                        Company Phone

                    </label>

                    <input
                        type="text"
                        name="company_phone"
                        class="form-control"
                        value="<?php echo htmlspecialchars($settings['company_phone']); ?>"
                    >

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">

                        Company Email

                    </label>

                    <input
                        type="email"
                        name="company_email"
                        class="form-control"
                        value="<?php echo htmlspecialchars($settings['company_email']); ?>"
                    >

                </div>

            </div>

<div class="row">

    <div class="col-md-6 mb-3">

        <label class="form-label">Company Logo</label>

        <input
            type="file"
            name="company_logo"
            class="form-control"
            accept=".png,.jpg,.jpeg"
        >

        <small class="text-muted">
            Leave empty if you don't want to change the logo.
        </small>

    </div>

    <div class="col-md-6 mb-3 text-center">

        <label class="form-label d-block">
            Current Logo
        </label>

        <img
            src="../assets/images/logo/<?php echo htmlspecialchars($settings['company_logo']); ?>"
            class="img-thumbnail"
            style="max-width:150px; max-height:150px;"
            alt="Company Logo"
        >

    </div>

</div>

                        <hr>

            <h5 class="mb-3">

                Office Configuration

            </h5>

            <div class="row">

                <div class="col-md-4 mb-3">

                    <label class="form-label">

                        GPS Radius (Meters)

                    </label>

                    <input
                        type="number"
                        name="gps_radius"
                        class="form-control"
                        value="<?php echo $settings['gps_radius']; ?>"
                    >

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">

                        Office Start

                    </label>

                    <input
                        type="time"
                        name="office_start"
                        class="form-control"
                        value="<?php echo $settings['office_start']; ?>"
                    >

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">

                        Office End

                    </label>

                    <input
                        type="time"
                        name="office_end"
                        class="form-control"
                        value="<?php echo $settings['office_end']; ?>"
                    >

                </div>

            </div>

                        <button
                type="submit"
                class="btn btn-primary"
            >

                <i class="bi bi-save"></i>

                Save Settings

            </button>

        </form>

    </div>

</div>

</div>

<?php

include("../includes/footer.php");

?>