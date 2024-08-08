<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DNS Records Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.3/css/bulma.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle delete single record
            $('.delete-record').on('click', function(e) {
                e.preventDefault();
                var recordId = $(this).data('id');
                $.ajax({
                    type: 'POST',
                    url: 'delete_dns.php',
                    data: { record_id: recordId },
                    success: function(response) {
                        var result = JSON.parse(response);
                        if (result.success) {
                            location.reload();
                        } else {
                            alert('<BR>Failed to delete DNS record.<BR>');
                        }
                    },
                    error: function() {
                        alert('<BR>Failed to delete DNS record.<BR>');
                    }
                });
            });

            // Handle delete selected records
            $('#delete-selected').on('click', function(e) {
                e.preventDefault();
                var selectedRecords = [];
                $('input[name="record_ids[]"]:checked').each(function() {
                    selectedRecords.push($(this).val());
                });

                if (selectedRecords.length > 0 && confirm('Are you sure you want to delete the selected records?')) {
                    $.ajax({
                        type: 'POST',
                        url: 'delete_dns.php',
                        data: { record_ids: selectedRecords },
                        success: function(response) {
                            var result = JSON.parse(response);
                            if (result.success) {
                                location.reload();
                            } else {
                                alert('<BR>Failed to delete selected records.<BR>');
                            }
                        },
                        error: function() {
                            alert('<BR>Failed to delete selected records.<BR>');
                        }
                    });
                }
            });

            // Handle delete all records
            $('#delete-all').on('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete all records?')) {
                    $.ajax({
                        type: 'POST',
                        url: 'delete_dns.php',
                        data: { delete_all: true },
                        success: function(response) {
                            var result = JSON.parse(response);
                            if (result.success) {
                                location.reload();
                            } else {
                                alert('<BR>Failed to delete all DNS records.<BR>');
                            }
                        },
                        error: function() {
                            alert('<BR>Failed to delete all DNS records.<BR>');
                        }
                    });
                }
            });

            // Select/Deselect all checkboxes
            $('#select-all').on('change', function() {
                $('input[name="record_ids[]"]').prop('checked', $(this).is(':checked'));
            });
        });
    </script>
</head>
<body>
    <section class="section">
        <div class="container">
            <h1 class="title">DNS Records Management</h1>
            <button class="button is-danger" id="delete-all">Delete All</button>
            <button class="button is-warning" id="delete-selected">Delete Selected</button>
            <?php
            // Cloudflare API credentials
            $apiKey = 'API';
            $email = 'EMAIL';
            $zoneId = 'ID ZONE';

            // Cloudflare API endpoints
            $dnsRecordsUrl = "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records";

            // cURL function to send requests
            function sendRequest($url, $method = 'GET', $data = null) {
                global $apiKey, $email;
                
                $ch = curl_init();

                $headers = [
                    "X-Auth-Email: $email",
                    "X-Auth-Key: $apiKey",
                    "Content-Type: application/json"
                ];

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                if ($method == 'DELETE') {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                } elseif ($method == 'POST') {
                    curl_setopt($ch, CURLOPT_POST, 1);
                    if ($data) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    }
                }

                $result = curl_exec($ch);
                curl_close($ch);

                return json_decode($result, true);
            }

            // Get current page number from query parameter
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = 20; // Number of records per page
            $offset = ($page - 1) * $perPage;

            // Fetch all DNS records
            $response = sendRequest($dnsRecordsUrl);

            if ($response['success']) {
                $dnsRecords = array_slice($response['result'], $offset, $perPage);
                $totalRecords = count($response['result']);
                $totalPages = ceil($totalRecords / $perPage);
                
                echo '<table class="table is-striped is-fullwidth">';
                echo '<thead><tr><th><input type="checkbox" id="select-all"></th><th>Name</th><th>Type</th><th>Actions</th></tr></thead>';
                echo '<tbody>';

                // Display each DNS record with a delete button and checkbox
                foreach ($dnsRecords as $record) {
                    echo '<tr>';
                    echo '<td><input type="checkbox" name="record_ids[]" value="' . htmlspecialchars($record['id']) . '"></td>';
                    echo '<td>' . htmlspecialchars($record['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['type']) . '</td>';
                    echo '<td>';
                    echo '<button class="button is-danger delete-record" data-id="' . htmlspecialchars($record['id']) . '">Delete</button>';
                    echo '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';

                // Pagination controls
                echo '<nav class="pagination is-centered" role="navigation" aria-label="pagination">';
                if ($page > 1) {
                    echo '<a class="pagination-previous" href="?page=' . ($page - 1) . '">Previous</a>';
                }
                if ($page < $totalPages) {
                    echo '<a class="pagination-next" href="?page=' . ($page + 1) . '">Next</a>';
                }
                echo '<ul class="pagination-list">';
                for ($i = 1; $i <= $totalPages; $i++) {
                    echo '<li><a class="pagination-link ' . ($i == $page ? 'is-current' : '') . '" href="?page=' . $i . '">' . $i . '</a></li>';
                }
                echo '</ul>';
                echo '</nav>';
            } else {
                echo "<BR><BR>Failed to fetch DNS records: " . json_encode($response['errors']) . "\n<BR>";
            }
            ?>
        </div>
    </section>
</body>
</html>
