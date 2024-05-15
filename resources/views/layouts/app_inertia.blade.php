<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="{{ asset('image/favicon.ico') }}">
  <title inertia>{{ config('constants.APP_NAME', 'IMCRM') }}</title>
  @routes()
  @vite(['resources/js/inertia/inertia.js'])
  @inertiaHead
</head>

<body>
  @inertia
</body>

</html>
