@extends('base')

@section('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.1.0/css/rowGroup.dataTables.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection



@section('content')



    <div class="cell medium-auto medium-cell-block-container">
        <div class="grid-x grid-padding-x">
            <div class="cell medium-3 large-2">
                <div class="grid-y">
{{--                    <div class="callout" style="background-color: #9099A2">--}}
                    <div class="callout" style="background-color: #4E6784">
                        <form id="check-query" autocomplete="off" method="post" action="/foo/bar">
                            @csrf

                        <div class="cell shrink small-auto">
                            <h5><b>Check</b></h5>
                            <hr>
                        </div>

                        <div class="cell shrink small-auto">
                            <div class="floated-label-wrapper">
                                <label for="check_number">Check Number</label>
                                <input type="text" id="check_number" name="check_number">
                            </div>
                        </div>

                        <div class="cell shrink small-auto">
                            <div class="floated-label-wrapper">
                                <label for="vendor_name">Vendor Name</label>
                                <input type="text" id="vendor_name" name="vendor_name">
                            </div>
                        </div>

                        <div class="cell shrink small-auto">
                            <div class="floated-label-wrapper">
                                {{--<label for="Order Date">Order Date</label>--}}
                                {{--<input type="text" name="order_date">--}}
                                <label for="vendor_name">Date Range</label>
                                <div class="input-group">
                                    <input class="input-group-field" placeholder="Select date(s)" type="text" name="check_date">
{{--                                    <span class="input-group-label">To</span>--}}
{{--                                    <input class="input-group-field" type="text" name="check_date_end">--}}
                                </div>
                            </div>
                        </div>

                        <div class="cell shrink small-auto">
                            <button type="submit" class="button small expanded">Search</button>
                        </div>

                        </form>

                        <form id="invoice-query" autocomplete="off" method="post" action="/foo/baz">
                            @csrf


                            <div class="cell shrink small-auto">
                            <h5><b>Invoice</b></h5>
                            <hr>
                        </div>

                        <div class="cell shrink small-auto">
                            <div class="floated-label-wrapper">
                                <label for="invoice_number">Invoice Number</label>
                                <input type="text" id="invoice_number" name="invoice_number">
                            </div>
                        </div>

                        <div class="cell shrink small-auto">
                            <div class="floated-label-wrapper">
                                <label for="vendor_name">Vendor Name</label>
                                <input type="text" id="vendor_name" name="vendor_name">
                            </div>
                        </div>

                        <div class="cell shrink small-auto">
                            <div class="floated-label-wrapper">
                                <label for="vendor_name">Date Range</label>
                                <div class="input-group">
                                    <input class="input-group-field" autocomplete="off" type="text" name="invoice_date">
                                </div>
                            </div>
                        </div>

                        <div class="cell shrink small-auto">
                            <button type="submit" class="button small expanded">Search</button>
                        </div>

                        </form>

                    </div>
                </div>
            </div>
            <div id="results-container" class="cell medium-9 medium-cell-block-y large-10" style="visibility: hidden">
                <h5> Search Results</h5>
                <table id="results" class="display cell-border">
                    <thead>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>




@endsection



@section('scripts')

{{--    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>--}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>

{{--<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>--}}
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/rowgroup/1.1.0/js/dataTables.rowGroup.min.js"></script>

    <script>

        $(function () {

            // init foundation
            $(document).foundation();


            $.fn.reverse_invoice = function () {
                var invoiceForm = $("#invoice-query");
                invoiceForm.trigger("reset");
                $("input[name='invoice_number']").val(this.attr('id'))
                $("#invoice-query").submit();
            };

            $.fn.reverse_check = function () {
                var invoiceForm = $("#check-query");
                invoiceForm.trigger("reset");
                $("input[name='check_number']").val(this.attr('id'))
                $("#check-query").submit();
            };

            // date picker(s)
            $("input[name='check_date'], input[name='invoice_date']").daterangepicker({
                locale: {
                    // "format": "MM/DD/YYYY",
                    "format": "YYYY-MM-DD",
                    "separator": " to ",
                    "customRangeLabel": "Custom"
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                "alwaysShowCalendars": true,
                "startDate": moment().subtract(1, 'years').format('YYYY-01-01'),
                "endDate": moment().format('YYYY-MM-DD'),
                autoUpdateInput: true,
                autoApply: true,
            }, function(start, end, label) {
            }, function(chosen_date) {

                // console.log(chosen_date);
                // $("input[name='check_date']").val(chosen_date.format('YYYY-MM-DD'));
                // console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')');
            });



        // form submit
            $("#check-query, #invoice-query").submit(function (e) {

                e.preventDefault();

                var inputFields;
                var url;
                var columns;
                var inputForm;
                var dataSrc;

                if (this.id === 'check-query') {
                    inputForm = 'check';
                    inputFields = $("form#check-query :input").not(':button,:hidden');
                    url = 'foo/bar';
                    columns = [
                        {title: "Check Number", data: null, defaultContent: "", orderable: false, target: 0},
                        {
                            title: "Invoice Number",
                            data: 'invoice_number',
                            // render: function (data, type, row) { return data + " <img style=\"floatright\" src=\"https://img.icons8.com/plasticine/35/000000/pdf.png\">" + " <img style=\"floatright\" src=\"https://img.icons8.com/offices/30/000000/search.png\">" }
                            // render: function (data, type, row) { return data + "<img class=\"invoiceToCheck\" id=\"" + data + "\" style=\"floatright\" src=\"https://img.icons8.com/offices/30/000000/search.png\">"
                            render: function (data, type, row, checkDate) {
                                if (data[2] === true)
                                {
                                    return data[0] + " <img id=\""+data[0]+"\" src=\"https://img.icons8.com/offices/30/000000/search.png\" onclick=\"$(this).reverse_invoice();\"/>  <a href=\"/viewPDF/"+data[0]+"/"+data[1]+"\" target='_blank'><img id=\""+data+"\" src=\"https://img.icons8.com/doodle/30/000000/pdf-2.png\" target=\"_blank\"/></a> "
                                }
                                else
                                {
                                    return data[0] + " <img id=\""+data[0]+"\" src=\"https://img.icons8.com/offices/30/000000/search.png\" onclick=\"$(this).reverse_invoice();\"/>"
                                }
                            }
                        },
                        {title: "Vendor", data: 'vendor_name'},
                        {title: "Amount", data: 'invoice_total'},
                        {title: "Date", data: 'check_date'}
                    ];
                    dataSrc = 'check_number';
                } else if (this.id === 'invoice-query') {
                    inputForm = 'invoice';
                    inputFields = $("form#invoice-query :input").not(':button,:hidden');
                    url = 'foo/baz';
                    columns =
                        [
                            {title: "Invoice Number", data: null, defaultContent: "", orderable: false, target: 0},
                            {
                                title: "Check Number",
                                data: 'check_number',
                                render: function (data, type, row) {

                                    if (data != "N/A")
                                    {
                                        return data + " <img id=\""+data+"\" src=\"https://img.icons8.com/offices/30/000000/search.png\" onclick=\"$(this).reverse_check();\"/>"
                                    }
                                    else
                                    {
                                        return data;
                                    }


                                }
                            },
                            {title: "Vendor", data: 'vendor_name'},
                            {title: "Amount", data: 'check_amount'},
                            {title: "Date", data: 'post_date'}
                        ];
                    dataSrc = 'invoice_number';
                }

                let emptyInput = 0;

                inputFields.each(function () {
                    let input = $(this);
                    // console.log(input);
                    // console.log(input.val());


                    if (input.val() === null || input.val() === '') {
                        emptyInput++;
                    }
                });

                if (emptyInput === inputFields.length) {
                    alert('ERROR: No input values found. At least one search parameter is required.');
                    return;
                }


                if ($.fn.DataTable.isDataTable('#results')) {
                    $("#results-container").css('visibility', 'hidden');
                    var table1 = $('#results').DataTable();
                    table1.clear().destroy();
                }

                var formData = $(this).serialize();

                $.ajax({
                    // url: "/foo/bar",
                    url: url,
                    data: formData,
                    type: "POST",
                    success: function (queryResults) {
                        var ticketTable = $('#results').DataTable({
                            // dom: 'Bfrtip',
                            data: queryResults,
                            // processing: true,
                            // language: {
                            //     processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw></i><span class="sr-only'
                            // },
                            columns: columns,
                            order: [[0, 'asc']],
                            rowGroup: {
                                startRender: function (rows, group) {
                                    if (inputForm === 'check') {
                                        var checkAmount = rows.data().pluck("check_amount");
                                        var checkDate = rows.data().pluck("check_date");

                                        return $('<tr/>')
                                            .append('<td>' + group + '</td>')
                                            .append('<td/>')
                                            .append('<td/>')
                                            .append('<td>' + checkAmount[0] + '</td>')
                                            .append('<td>' + checkDate[0] + '</td>')

                                    } else if (inputForm === 'invoice') {
                                        var invoiceAmount = rows.data().pluck("gross_total");
                                        var invoiceDate = rows.data().pluck("post_date");
                                        var fileName = rows.data().pluck("file_name")
                                        var vendorID = rows.data().pluck("vendor_id");
                                        var invoiceNumber = rows.data().pluck("invoice_number");
                                        var foo = "";

                                        if(fileName[0] !== "")
                                        {
                                            // foo = ('<td>' + group + "<a  href= target=\"/viewPDF/"+vendorID[0]+"/"+invoiceNumber[0]+"\"/><img src=\"https://img.icons8.com/doodle/30/000000/pdf-2.png\" /></a></td>");
                                            foo = ('<td>' + group + '<img src="https://img.icons8.com/doodle/30/000000/pdf-2.png" onclick="window.open(\'/viewPDF/'+vendorID[0]+'/'+invoiceNumber[0]+'\', \'_blank\');" ></td>');
                                        }
                                        else if(fileName[0] === "")
                                        {
                                            foo = ('<td>' + group + "</td>");
                                        }

                                        return $('<tr/>')
                                            .append(foo)
                                            .append('<td/>')
                                            .append('<td/>')
                                            .append('<td>' + invoiceAmount[0] + '</td>')
                                            .append('<td>' + invoiceDate[0] + '</td>')
                                    }
                                },
                                // dataSrc: 'check_number'
                                dataSrc: dataSrc
                            },

                            select: {
                                style: 'multi',
                                selector: 'td:first-child'
                            },
                            buttons: [
                                {
                                    text: 'Select all',
                                    action: function () {
                                        ticketTable.rows().select();
                                    }
                                },
                                {
                                    text: 'Select none',
                                    action: function () {
                                        ticketTable.rows().deselect();
                                    }
                                }
                            ]
                        });

                    }
                });
                $("#results-container").css('visibility', 'visible');
            });
        });
    </script>

@endsection
