<!DOCTYPE html>
<html>
<head>
    <title>Meta Insights</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        label {
            font-weight: bold;
        }
        input[type="text"] {
            width: 300px;
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        input[type="submit"] {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
<script>
        function prependAccountId() {
            var accountIdInput = document.getElementById('accountId');
            var accountIdValue = accountIdInput.value.trim();

            // Prepend 'act_' to the entered account ID if it's not empty
            if (accountIdValue !== '') {
                accountIdInput.value = 'act_' + accountIdValue;
            }
        }
    </script>
</head>
<body>
    <h1>Meta Insights Form</h1>
    <form action="fetch_campaigns_group.php" method="POST" onsubmit="prependAccountId()">
        <label for="accessToken">Access Token:</label><br>
        <input type="text" id="accessToken" name="accessToken" placeholder="Enter Access Token" required><br><br>

        <label for="accountId">Account ID:</label><br>
        <input type="text" id="accountId" name="accountId" placeholder="Enter Account ID" required><br><br>

        <input type="submit" value="Submit">
    </form>
</body>
</html>