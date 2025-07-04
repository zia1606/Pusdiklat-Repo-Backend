<!DOCTYPE html>
<html>
<head>
    <title>Admin Registration</title>
</head>
<body>
    <p>Halo {{ $name }},</p>
    <p>Anda telah terdaftar sebagai admin dengan detail sebagai berikut:</p>
    
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Password:</strong> {{ $password }}</p>
    
    <p>Silakan gunakan kredensial di atas untuk login ke sistem.</p>
    <p>Untuk keamanan, disarankan untuk mengubah password setelah login pertama.</p>
    
    <br>
    <p>Terima kasih,</p>
    <p>Tim Admin</p>
</body>
</html>