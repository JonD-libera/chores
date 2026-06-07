<?php
    // Set the correct content-type for JSON response
    header('Content-Type: application/json');

    // Prepare the response array
    $response = [
        'status' => 'error',
        'message' => 'No data found',
    ];

    if (isset($_REQUEST['userid']) && isset($_REQUEST['assignment'])) {
        // Prepare SQL statement to fetch chore details
        $statement = $mysqli->prepare("SELECT c.name, c.description, c.pay, a.id, a.assigned_user 
                                       FROM chores c 
                                       JOIN assignments a ON a.chore_id = c.id 
                                       JOIN users u ON a.assigned_user = u.id 
                                       WHERE a.assigned_user = ? AND a.id = ?");
        $statement->bind_param('ii', $_REQUEST['userid'], $_REQUEST['assignment']);

        if ($statement->execute()) {
            // Store the result
            $statement->store_result();
            $statement->bind_result($chore, $description, $pay, $assignment, $user);

            if ($statement->num_rows > 0) {
                // Fetch the data
                $statement->fetch();

                // Build the response array
                $response = [
                    'status' => 'success',
                    'chore' => [
                        'name' => $chore,
                        'description' => $description,
                        'pay' => $pay,
                        'assignment_id' => $assignment,
                        'assigned_user' => $user,
                    ],
                ];
            }
        } else {
            $response['message'] = 'Failed to execute query';
        }
    } else {
        $response['message'] = 'Invalid parameters';
    }

    // Output the response as JSON
    echo json_encode($response);