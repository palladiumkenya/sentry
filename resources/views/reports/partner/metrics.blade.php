<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{--    <title>{{ $facility->name }}</title>--}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css"
          integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <style>
        .center {
            display: block;
            margin-left: auto;
            margin-right: auto;
            width: 25%;
        }

        .p-20 {
            padding-left: 20%;
            padding-right: 20%;
            font-size: 18px;
        }

        table {
            width: 100%;
            border-spacing: 0;
        }

        th {
            text-align: left;
        }

        tr {
            background: radial-gradient(circle at bottom, black 1px, transparent 1.5px) repeat-x bottom;
            background-size: 5px 4px;
        }

        .table1 td:first-child {
            font-weight: bold;
        }

        td {
            padding: 15px;
            /*border-bottom:1px dotted #aaa;*/
            /*background: radial-gradient(circle at bottom, black 1px, transparent 1.5px) repeat-x bottom;
            background-size: 5px 4px;*/
        }

        .cmjx-highlight {
            border: 2px solid #DD4A68;
            padding: 8px;
            margin-right: 4px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row" style="background-color: #000059">
        <div class="col p-5 align-content-center">
            <img class="center" src="img.png">
        </div>
    </div>
    <div class="row">
        <div class="col pt-5 p-20 justify-content-center">
            {{--                <h3 class="mt-4">{{ $facility->name }}</h3>--}}
            <h5>Greetings HJF Kisumu West ,</h5>
            <p>The Kenya HMIS project is working with NASCOP to improve the availability and quality of data in the
                National Data Warehouse (NDW). This requires every facility to upload complete and up-to-date databases
                on a monthly basis to the NDW.</p>
            <p>The purpose of this email is to share the reporting rates and selected data quality and alignment metrics
                for the facilities that you support. Please see below a summary of the metrics for January 2022:</p>

            <table class="table1">
                <thead>
                <td></td>
                <td></td>
                <td></td>
                </thead>
                <tr>
                    <td>Number of EMR Facilities</td>
                    <td>3 Facilities</td>
                    <td><a href="#">View</a></td>
                </tr>
                <tr>
                    <td>Care and Treatment Reporting Rates</td>
                    <td>60%</td>
                    <td><a href="#">View</a></td>
                </tr>
                <tr>
                    <td>HTS Reporting Rates</td>
                    <td>50%</td>
                    <td><a href="#">View</a></td>
                </tr>
                <tr>
                    <td>Difference (EMR value- NDW value)</td>
                    <td>55%</td>
                    <td><a href="#">View</a></td>
                </tr>
                <tr>
                    <td>% Variance*</td>
                    <td>55%</td>
                    <td><a href="#">View</a></td>
                </tr>
            </table>

            <small>*% Variance was computed as =</small>
            <small style="text-align:center">
                \[ \Biggl( {Reported value - Verified value \over Verified value}\Biggr) * 100\]
            </small>
            <br>
            <p>Data Alignment : - A comparison between National Data warehouse and EMR data</p>
            <table>
                <thead>
                <td>Indicator Name</td>
                <td>EMR Indicator Date</td>
                <td>EMR Value</td>
                <td>NDW Calculation</td>
                <td>NDW Date</td>
                <td>Difference</td>
                <td>Percentage</td>
                </thead>
                <tr>
                    <td>HTS Tested POS</td>
                    <td>29/06/2021</td>
                    <td>10,454</td>
                    <td>10,000</td>
                    <td>30/06/2021</td>
                    <td>454</td>
                    <td>44%</td>
                </tr>
                <tr>
                    <td>HTS Tested POS</td>
                    <td>29/06/2021</td>
                    <td>10,454</td>
                    <td>10,000</td>
                    <td>30/06/2021</td>
                    <td>454</td>
                    <td>44%</td>
                </tr>
                <tr>
                    <td>HTS Tested POS</td>
                    <td>29/06/2021</td>
                    <td>10,454</td>
                    <td>10,000</td>
                    <td>30/06/2021</td>
                    <td>454</td>
                    <td>44%</td>
                </tr>
                <tr>
                    <td>HTS Tested POS</td>
                    <td>29/06/2021</td>
                    <td>10,454</td>
                    <td>10,000</td>
                    <td>30/06/2021</td>
                    <td>454</td>
                    <td>44%</td>
                </tr>
                <tr>
                    <td>HTS Tested POS</td>
                    <td>29/06/2021</td>
                    <td>10,454</td>
                    <td>10,000</td>
                    <td>30/06/2021</td>
                    <td>454</td>
                    <td>44%</td>
                </tr>
                <tr>
                    <td>HTS Tested POS</td>
                    <td>29/06/2021</td>
                    <td>10,454</td>
                    <td>10,000</td>
                    <td>30/06/2021</td>
                    <td>454</td>
                    <td>44%</td>
                </tr>
            </table>
            <br>
            <button class="btn btn-dark btn-block">National Data Warehouse</button>
            <br>

            <p>Kindly work with supported facilities to address any challenges they may have in uploading high quality
                data to the NDW. Please reach out to Palladium Kenya if you have any questions.</p>
            <p>Kind Regards,</p>
            <p>The Kenya HMIS Team</p>
            {{--            <h4 class="mt-4 mb-3">Indicator Metrics</h4>--}}
            {{--            <table class="table table-bordered table-sm">--}}
            {{--                <thead>--}}
            {{--                <th>Name</th>--}}
            {{--                <th>Description</th>--}}
            {{--                <th>EMR Date</th>--}}
            {{--                <th>EMR Value</th>--}}
            {{--                <th>DWH Value</th>--}}
            {{--                <th>DWH Date</th>--}}
            {{--                <th>Diff</th>--}}
            {{--                </thead>--}}
            {{--                <tbody>--}}
            {{--                --}}{{--                        @foreach($metrics as $metric)--}}
            {{--                --}}{{--                        <tr>--}}
            {{--                --}}{{--                            <td width="20%">{{ str_replace('_', ' ', $metric->name) }}</td>--}}
            {{--                --}}{{--                            <td width="30%">{{ $descriptions[$metric->name] }}</td>--}}
            {{--                --}}{{--                            <td align="right">{{ $metric->metric_date->format('d M Y') }}</td>--}}
            {{--                --}}{{--                            <td align="right">{{ $metric->value }}</td>--}}
            {{--                --}}{{--                            <td align="right">{{ $metric->dwh_value }}</td>--}}
            {{--                --}}{{--                            <td align="right">{{ $metric->dwh_metric_date->format('d M Y') }}</td>--}}
            {{--                --}}{{--                            <td align="right">{{ abs($metric->dwh_value - $metric->value) }}</td>--}}
            {{--                --}}{{--                        </tr>--}}
            {{--                --}}{{--                        @endforeach--}}
            {{--                </tbody>--}}
            {{--            </table>--}}
            {{--            <h4 class="mt-4 mb-3">Upload History</h4>--}}
            {{--            <div id="upload-history-chart"></div>--}}
            {{--                <a href="{{ $url }}" style="color: #0000ee;" class="mt-4">{{ $url }}</a>--}}
        </div>
    </div>
    <div class="row" style="background-color: #000059">
        <div class="col p-5 text-center text-white">
            <p>If you have any questions, feel free message us at
                help@palladiumgroup.on.spiceworks.com.</p>
            <p>All right reserved. Update email preferences or unsubscribe.</p>
            <p>+254 717 969471</p>
            <p>Nairobi, Kenya</p>
            <p>Terms of use | Privacy Policy</p>
        </div>
    </div>
</div>
<script>
    const months = {};
    const data = {}
</script>
</body>
</html>
