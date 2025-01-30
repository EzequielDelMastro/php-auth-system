<?php
$client_id = "398422448371-tecdirisu3arsecb181vnc10lsg5081d.apps.googleusercontent.com";
$redirect_uri = "https://suchinmeli.com.ar/WS/src/google/login/auth/index.php";
$scope = "https://www.googleapis.com/auth/userinfo.email";
//$scope = "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email"; // Incluye el scope profile
$auth_url = "https://accounts.google.com/o/oauth2/auth?response_type=code"
    . "&client_id=" . urlencode($client_id)
    . "&redirect_uri=" . urlencode($redirect_uri)
    . "&scope=" . urlencode($scope)
    . "&access_type=online";

header("Location: $auth_url");
exit;
?>


