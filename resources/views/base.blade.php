<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>IPS | Checkflow</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.5.3/css/foundation.min.css" integrity="sha256-xpOKVlYXzQ3P03j397+jWFZLMBXLES3IiryeClgU5og=" crossorigin="anonymous" />

    <style>
        body {
            background-color: #AFB7C0;
            /*background-color: #D5D5D5;*/
            /*background-color: #b3d7ff;*/
        }
    </style>

    @yield('head')



</head>
<body>

{{--<div class="title-bar" data-responsive-toggle="realEstateMenu" data-hide-for="small">--}}
{{--    <button class="menu-icon" type="button" data-toggle></button>--}}
{{--    <div class="title-bar-title">Menu</div>--}}
{{--</div>--}}
<div class="top-bar" style="background-color: #4E6784" >
    <div class="top-bar-left" >
        <ul class="menu" data-responsive-menu="accordion" style="background-color: #4E6784">
            <li class="menu-text">IPS Checkflow</li>
{{--                        <li><a href="#">One</a></li>--}}
            {{--            <li><a href="#">Two</a></li>--}}
            {{--            <li><a href="#">Three</a></li>--}}
        </ul>
    </div>
    <div class="top-bar-right">
        <ul class="menu">
            {{--            <li><a href="#">My Account</a></li>--}}
            @if(Auth::id())
                {{--            <li><a class="button">Logout</a></li>--}}
                <li><a class="button" href="{{ route('logout') }}"
                       onclick="event.preventDefault();
            document.getElementById('logout-form').submit();">
                        {{ __('Logout') }}
                    </a></li>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            @endif
        </ul>
    </div>
</div>

<br>


@yield('content')

{{--foundation--}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>

{{--jquery--}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.5.3/js/foundation.js" integrity="sha256-7WVbN/J2vA6l4tJnRTx1Yh3RGQUcNRAYLo0OV9qsL+k=" crossorigin="anonymous"></script>

@yield('scripts')


</body>
</html>
