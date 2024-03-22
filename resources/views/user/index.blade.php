<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/app.css') }}">
    <title>User</title>
</head>

<body>
<div id="app"></div>
<!-- Your code here -->
</body>

<script type="application/javascript" src="{{ asset('js/UserService.js') }}"></script>
</html>