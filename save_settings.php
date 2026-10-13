<?php

include("../includes/auth_check.php");

requireRole('Admin');

include("../config/database.php");

/*
|--------------------------------------------------------------------------
| Only POST Request
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: settings.php");

    exit();

}

/*
|--------------------------------------------------------------------------
| Receive Form Data
|--------------------------------------------------------------------------
*/

$system_name = trim($_POST['system_name']);

$company_name = trim($_POST['company_name']);

$company_address = trim($_POST['company_address']);

$company_phone = trim($_POST['company_phone']);

$company_email = trim($_POST['company_email']);

$gps_radius = intval($_POST['gps_radius']);

$office_start = $_POST['office_start'];

$office_end = $_POST['office_end'];


/*
|--------------------------------------------------------------------------
| Company Logo Upload
|--------------------------------------------------------------------------
*/

$currentLogo = "default_logo.png";

$getLogo = mysqli_query(
    $conn,
    "SELECT company_logo FROM system_settings WHERE id = 1"
);

if ($row = mysqli_fetch_assoc($getLogo)) {
    $currentLogo = $row['company_logo'];
}

$company_logo = $currentLogo;

if (
    isset($_FILES['company_logo']) &&
    $_FILES['company_logo']['error'] == 0
) {

    $allowed = [
        "jpg",
        "jpeg",
        "png"
    ];

    $extension = strtolower(
        pathinfo(
            $_FILES['company_logo']['name'],
            PATHINFO_EXTENSION
        )
    );

    if (in_array($extension, $allowed)) {

        $newFileName =
            "logo_" .
            time() .
            "." .
            $extension;

        $destination =
            "../assets/images/logo/" .
            $newFileName;

        if (
            move_uploaded_file(
                $_FILES['company_logo']['tmp_name'],
                $destination
            )
        ) {

            $company_logo = $newFileName;

        }

    }

}

/*
|--------------------------------------------------------------------------
| Update Settings
|--------------------------------------------------------------------------
*/

$sql = "

UPDATE system_settings

SET

system_name=?,

company_name=?,

company_address=?,

company_phone=?,

company_email=?,

company_logo=?,

gps_radius=?,

office_start=?,

office_end=?

WHERE id=1

";

$stmt = mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(

$stmt,

"ssssssiss",

$system_name,

$company_name,

$company_address,

$company_phone,

$company_email,

$company_logo,

$gps_radius,

$office_start,

$office_end

);

if(mysqli_stmt_execute($stmt)){

header("Location: settings.php?success=1");

exit();

}else{

echo "Failed to update settings.";

}

?>