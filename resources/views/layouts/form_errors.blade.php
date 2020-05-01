@if ($errors->any())
    <div class="row">
        <div class="alert alert-error span4 offset4" style="text-align: center">
            @foreach ($errors->all() as $error)
                <strong>Error!</strong> {!! $error !!}
                <br>
            @endforeach
        </div>
    </div>
@elseif(session('message'))
    <div class="alert alert-success span4 offset4">
        {{ session('message') }}
    </div>
@endif


