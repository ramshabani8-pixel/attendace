<?php

include("../includes/auth_check.php");

requireRole('Admin');

include("../config/database.php");


// Get all users with department name

$sql = "SELECT
            users.id,
            users.user_code,
            users.first_name,
            users.last_name,
            users.department_id,
            users.role,
            users.status,
            departments.department_name

        FROM users

        LEFT JOIN departments
            ON users.department_id = departments.id

        ORDER BY users.id DESC";


$usersResult = mysqli_query(
    $conn,
    $sql
);


if (!$usersResult) {

    die(
        "Users query failed: "
        . mysqli_error($conn)
    );

}


$usersCount = mysqli_num_rows(
    $usersResult
);


include("../includes/header.php");
include("../includes/sidebar.php");
include("../includes/topbar.php");

?>


<div class="container-fluid">


    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3>

                <i class="bi bi-people-fill"></i>

                Users Management

            </h3>

            <p class="text-muted mb-0">

                Manage system users and their information.

            </p>

        </div>


        <a
            href="add_user.php"
            class="btn btn-primary"
        >

            <i class="bi bi-plus-circle"></i>

            Add User

        </a>

    </div>



    <!-- Users Count -->

    <div class="alert alert-info">

        <i class="bi bi-people-fill"></i>

        <strong>
            Users found:
        </strong>

        <?php echo mysqli_num_rows($usersResult); ?>

    </div>



    <!-- Users Table -->

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <h5 class="mb-0">

                <i class="bi bi-people"></i>

                All Users

            </h5>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>ID</th>

                            <th>User Code</th>

                            <th>Name</th>

                            <th>Department</th>

                            <th>Role</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (mysqli_num_rows($usersResult) > 0): ?>


                        <?php while ($row = mysqli_fetch_assoc($usersResult)): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['id'] ?? ''
                                    );
                                    ?>

                                </td>



                                <!-- User Code -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['user_code'] ?? ''
                                    );
                                    ?>

                                </td>



                                <!-- Name -->

                                <td>

                                    <?php

                                    $fullName =
                                        ($row['first_name'] ?? '')
                                        . " "
                                        . ($row['last_name'] ?? '');

                                    echo htmlspecialchars(
                                        trim($fullName)
                                    );

                                    ?>

                                </td>



                                <!-- Department -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $row['department_name']
                                        )
                                    ) {

                                        echo htmlspecialchars(
                                            $row['department_name']
                                        );

                                    } else {

                                        echo '<span class="text-muted">
                                                Not Assigned
                                              </span>';

                                    }

                                    ?>

                                </td>



                                <!-- Role -->

                                <td>

                                    <span class="badge bg-primary">

                                        <?php

                                        echo htmlspecialchars(
                                            $row['role'] ?? ''
                                        );

                                        ?>

                                    </span>

                                </td>



                                <!-- Status -->

                                <td>

                                    <?php

                                    if (
                                        ($row['status'] ?? '')
                                        === 'Active'
                                    ):

                                    ?>

                                        <span class="badge bg-success">

                                            Active

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">

                                            Inactive

                                        </span>

                                    <?php endif; ?>

                                </td>



                                <!-- Actions -->

                                <td>


                                    <a
                                        href="view_user.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-info btn-sm"
                                        title="View User"
                                    >

                                        <i class="bi bi-eye"></i>

                                    </a>



                                    <a
                                        href="edit_user.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-warning btn-sm"
                                        title="Edit User"
                                    >

                                        <i class="bi bi-pencil"></i>

                                    </a>



                                    <?php

                                    if (
                                        ($row['status'] ?? '')
                                        === 'Active'
                                    ):

                                    ?>

                                        <a
                                            href="deactivate_user.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-danger btn-sm"
                                            title="Deactivate User"
                                            onclick="return confirm('Deactivate this user?');"
                                        >

                                            <i class="bi bi-person-x-fill"></i>

                                        </a>

                                    <?php endif; ?>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="7"
                                class="text-center text-muted py-5"
                            >

                                <i class="bi bi-people fs-1"></i>

                                <br>
                                <br>

                                <strong>
                                    No users found.
                                </strong>

                                <br>

                                There are currently no users in the database.

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