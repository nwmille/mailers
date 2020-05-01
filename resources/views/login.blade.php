<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>IPS | Checkflow</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.5.3/css/foundation.min.css" integrity="sha256-xpOKVlYXzQ3P03j397+jWFZLMBXLES3IiryeClgU5og=" crossorigin="anonymous" />

    <style>
        body {
            background-color: #b3d7ff;
        }

        .floated-label-wrapper label {
            background: #fefefe;
            color: #1779ba;
            font-size: 0.75rem;
            font-weight: 600;
            left: 0.75rem;
            opacity: 1;
            padding: 0 0.25rem;
            position: absolute;
            top: -0.85rem;
            transition: all 0.15s ease-in;
            z-index: 1;
        }

        .floated-label-wrapper label input[type=text],
        .floated-label-wrapper label input[type=email],
        .floated-label-wrapper label input[type=password] {
            border-radius: 4px;
            font-size: 1.75em;
            padding: 0.5em;
        }

    </style>



    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css"/>

    <style>
        .title {
            font-size: 96px;
            margin-bottom: 20px;
        }
        .quote {
            font-size: 24px;
        }
    </style>
</head>
<body>
<br>

<form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="grid-container">
            <div class="grid-x grid-margin-x">

                <div class="large-10 large-offset-1 small-12">
                    <div class="lockscreen-logo text-center ">
                        <div class="title"><b>Checkflow</b></div>
                    </div>
                </div>

                <div class="large-4 large-offset-4 small-10 small-offset-1 callout">

                    <label for="email">Email</label>
                    <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required autofocus>
                    @if ($errors->has('email'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif

                    <label for="password">Password</label>
                    <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
                    @if ($errors->has('password'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif

{{--                    <label class="form-check-label" for="remember">--}}
{{--                        {{ __('Remember Me') }}--}}
{{--                    </label>--}}
{{--                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>--}}

                    <input type="submit" class="button expanded" value="Submit">
{{--                    @if (Route::has('password.request'))--}}
{{--                        <a class="btn btn-link" href="{{ route('password.request') }}">--}}
{{--                            {{ __('Forgot Your Password?') }}--}}
{{--                        </a>--}}
{{--                    @endif--}}
                </div>
                <div class="large-10 large-offset-1 small-12">
                    <br>
                    <div class="lockscreen-logo text-center ">
                        <div class="quote">{{ Illuminate\Foundation\Inspiring::quote() }}</div>
                    </div>
                    <br>
                </div>
            </div>
        </div>
    </form>


    {{--foundation--}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>

    {{--jquery--}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.5.3/js/foundation.js" integrity="sha256-7WVbN/J2vA6l4tJnRTx1Yh3RGQUcNRAYLo0OV9qsL+k=" crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>

    <script>
        $(function () {

            // init foundation
            $(document).foundation();

        });


    </script>
</body>
</html>
