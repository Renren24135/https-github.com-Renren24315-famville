<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center text-white">
        <i class="bi bi-dice-5-fill fs-1"></i>
        <h1 class="display-3 fw-bold mt-3">{{ $code }}</h1>
        <p class="fs-5 text-white-50">{{ $message }}</p>
        <a href="{{ route('home') }}" class="btn btn-primary mt-3">Go to Homepage</a>
    </div>
</body>
</html>
