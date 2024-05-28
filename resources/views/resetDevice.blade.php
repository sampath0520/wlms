<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Device</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@10/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        /* Custom CSS for outer border */
        .custom-border {
            border: 2px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card custom-border">
                    <div class="card-body">
                        <h5 class="card-title text-center">Reset Device</h5>
                        <form id="resetForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Enter your email:</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary" id="sendResetEmail">Send Reset
                                    Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (optional, if you need JavaScript functionality) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10/dist/sweetalert2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#resetForm').submit(function(e) {
                e.preventDefault(); // Prevent default form submission

                // Check if the form is valid
                if (this.checkValidity()) {
                    // Get the email value from the input field
                    var email = $('#email').val();

                    // Send AJAX request
                    $.ajax({
                        // Add email to the URL
                        url: "{{ url('/api/common/resetDevice/') }}" + "/" + encodeURIComponent(
                            email),
                        type: "GET",
                        success: function(response) {
                            // Check if the request was successful
                            if (response.status === true) {
                                // Handle successful response
                                console.log(response);
                                // Clear form fields
                                $('#resetForm')[0].reset();
                                // Display success message using SweetAlert
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: 'Device reset successful',
                                    compactMode: true // Small version of SweetAlert
                                });
                            } else {
                                // Handle error response
                                console.error(response.message);
                                // Display error message using SweetAlert
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message,
                                    compactMode: true // Small version of SweetAlert
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            // Parse the error response to get the error message
                            var errorMessage = "";
                            try {
                                errorMessage = JSON.parse(xhr.responseText).message;
                            } catch (e) {
                                errorMessage = xhr.responseText;
                            }
                            // Display error message using SweetAlert
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMessage,
                                compactMode: true // Small version of SweetAlert
                            });
                        }
                    });
                } else {
                    // Form is invalid, show validation feedback
                    e.stopPropagation();
                    $(this).addClass('was-validated');
                }
            });
        });
    </script>
</body>

</html>
