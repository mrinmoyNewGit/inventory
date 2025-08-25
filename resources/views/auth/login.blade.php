<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">

    <title>Login | bong print hub</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/favicon.png') }}">

    <!-- Bootstrap Icons (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">

    <!-- Vite assets (replaces old mix() calls) -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/js/chart-config.js'])
</head>

<body class="c-app flex-row align-items-center" style="background: linear-gradient(90deg, #0756b7, #07175f);">
<div class="container">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-center">
            <img width="200" src="{{ asset('images/logo-dark.png') }}" alt="Logo">
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-5">
            @if(Session::has('account_deactivated'))
                <div class="alert alert-danger" role="alert">
                    {{ Session::get('account_deactivated') }}
                </div>
            @endif

            <div class="card p-4 border-0 shadow-sm">
                <div class="card-body">
                    <form id="login" method="post" action="{{ url('/login') }}">
                        @csrf
                        <h1>Login</h1>
                        <p class="text-muted">Sign In to your account</p>

                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                            </div>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                   name="email" value="{{ old('email') }}" placeholder="Email">
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            </div>
                            <input id="password" type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Password" name="password">
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-4">
                                <button id="submit" class="btn btn-primary px-4 d-flex align-items-center" type="submit">
                                    Login
                                    <div id="spinner" class="spinner-border text-info" role="status"
                                         style="height: 20px;width: 20px;margin-left: 5px;display: none;">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </button>
                            </div>
                            <div class="col-8 text-right">
                                <a class="btn btn-link px-0" href="{{ route('password.request') }}">
                                    Forgot password?
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <p class="text-center mt-5 lead">
                Developed By
                <a href="#" class="font-weight-bold text-primary">Mrinmoy kolay</a>
            </p>
        </div>
    </div>
</div>

<script>
    const login = document.getElementById('login');
    const submit = document.getElementById('submit');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const spinner = document.getElementById('spinner');

    login.addEventListener('submit', () => {
        submit.disabled = true;
        email.readOnly = true;     // fixed: readOnly (not readonly)
        password.readOnly = true;  // fixed: readOnly (not readonly)
        spinner.style.display = 'block';
    });

    setTimeout(() => {
        submit.disabled = false;
        email.readOnly = false;
        password.readOnly = false;
        spinner.style.display = 'none';
    }, 3000);
</script>

</body>
</html>
