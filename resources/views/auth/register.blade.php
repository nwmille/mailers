@extends('base')

@section('head')
    <style>
        .floated-label-wrapper {
            position: relative;
        }

        .floated-label-wrapper label {
            background: #fefefe;
            color: #1779ba;
            font-size: 0.75rem;
            font-weight: 600;
            left: 0.75rem;
            opacity: 0;
            padding: 0 0.25rem;
            position: absolute;
            top: 2rem;
            transition: all 0.15s ease-in;
            z-index: -1;
        }

        .floated-label-wrapper label input[type=text],
        .floated-label-wrapper label input[type=email],
        .floated-label-wrapper label input[type=password] {
            border-radius: 4px;
            font-size: 1.75em;
            padding: 0.5em;
        }

        .floated-label-wrapper label.show {
            opacity: 1;
            top: -0.85rem;
            z-index: 1;
            transition: all 0.15s ease-in;
        }
    </style>
@endsection

@section('content')

    <div class="grid-container">
        <div class="grid-x grid-margin-x">
            <div class="cell callout large-6 large-offset-3">
                <div class="grid-y">
                    <form class="text-center" method="POST" action="{{ route('register') }}">
{{--                    <form class="text-center" method="POST" action="{{ route('user/create') }}">--}}
                        @csrf
                        <h2>New User</h2>
                        <div class="floated-label-wrapper">
                            <label for="full-name" class="show">FULL NAME</label>
                            <input type="text" id="full-name" name="name" placeholder="">
                        </div>
                        <div class="floated-label-wrapper">
                            <label for="email" class="show">E-MAIL</label>
                            <input type="email" id="email" name="email" placeholder="" autocomplete="off">
                        </div>
                        <div class="floated-label-wrapper">
                            <label for="password" class="show">PASSWORD</label>
                            <input type="password" id="pass" name="password" placeholder="">
                        </div>
                        <div class="floated-label-wrapper">
                            <label for="role" class="show">ROLE</label>
                            <select type="roles" id="role" name="role">
                                @foreach($data as $role)
                                    <option value="{{$role}}">{{$role}}</option>
                                @endforeach
                            </select>
                        </div>
                        <input id="create_user" class="button expanded" type="submit" value="Submit">
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

