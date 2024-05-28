<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Error</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .error-container {
            margin-top: 100px;
        }
    </style>
</head>

<body>
    <div class="container error-container">
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Security Error!</h4>
            <p>Unauthorized Access Detected. Cannot proceed with the request due to security reasons.</p>
            <hr>
            {{-- //normal message --}}
            <p class="mb-0">Please contact the system administrator for further assistance.</p>
        </div>
    </div>
</body>

</html>
