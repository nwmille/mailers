@extends('base')
@section('head')
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" />
@endsection

@section('content')
    <div class="grid-container">
        <H3 style="caption-side: top">{{$tableName}}</H3>
        <table id="table_id" class="display">
            <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{$header}}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach($displayThese as $key => $value)
                <tr>
                    @foreach($value as $data)
                    <td>{{$data}}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection


@section('scripts')
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#table_id').DataTable({
                caption: "foo",
            });
        } );
    </script>


@endsection
