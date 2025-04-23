<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Scrap Parts Inventory - Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h4>Vehicle Scrap Parts Inventory</h4>
                        <p>Login to manage your inventory</p>
                    </div>
                    <div class="card-body">
                        <div id="loginAlert" class="alert d-none mt-3"></div>
                        <form id="loginForm" method="POST">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: 'auth/login.php',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        window.location.href = 'dashboard/';
                    } else {
                        $('#loginAlert').removeClass('d-none alert-success').addClass('alert-danger')
                            .html('Invalid email or password').show();
                    }
                },
                error: function() {
                    $('#loginAlert').removeClass('d-none alert-success').addClass('alert-danger')
                        .html('An error occurred. Please try again.').show();
                }
            });
        });
    });
    </script>
</body>
</html>
