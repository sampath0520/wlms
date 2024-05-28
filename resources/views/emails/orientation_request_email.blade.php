<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #007bff;
            color: #ffffff;
            padding: 10px 0;
            text-align: center;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }

        .content {
            padding: 20px;
        }

        .footer {
            text-align: center;
            padding: 10px 0;
            background-color: #f4f4f4;
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Orientation Request</h1>
        </div>
        <div class="content">
            <p>Hello {{ $emailData['name'] }},</p>
            <p>Thank you for your orientation request. Here are the details you provided:</p>
            <ul>
                <li><strong>Name:</strong> {{ $emailData['name'] }}</li>
                <li><strong>Email:</strong> {{ $emailData['email'] }}</li>
                <li><strong>Phone:</strong> {{ $emailData['phone'] }}</li>
            </ul>
            <p><strong>Message:</strong></p>
            <p>{{ $emailData['message'] }}</p>
            <p>We will review your request and get back to you as soon as possible.</p>
            <p>Thank you,</p>
            <p>Your Team</p>
        </div>
        <div class="footer">
            <p>This is an automated email. Please do not reply.</p>
        </div>
    </div>
</body>

</html>
