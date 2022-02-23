
    <div class="container-fluid">
        <div class="row" style="background-color: #000059">
            <div class="col p-5 align-content-center">
                <img class="center" src="https://eastus1-mediap.svc.ms/transform/thumbnail?provider=spo&inputFormat=png&cs=fFNQTw&docid=https%3A%2F%2Fpalladiumgroup-my.sharepoint.com%3A443%2F_api%2Fv2.0%2Fdrives%2Fb!q3-0g_Fvb0KC1It5z_Zd9qzEBNbltp9CuQONZGRmZuaw7GWXWnW-QKxJJ-0ZXhnJ%2Fitems%2F01GNNNYSAVF24YL4DHC5HJNBKRT3R66OXO%3Fversion%3DPublished&access_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJub25lIn0.eyJhdWQiOiIwMDAwMDAwMy0wMDAwLTBmZjEtY2UwMC0wMDAwMDAwMDAwMDAvcGFsbGFkaXVtZ3JvdXAtbXkuc2hhcmVwb2ludC5jb21AZTc5NDI5NzQtOTczOC00YTRhLWI2NDYtMmFiOTBmNzlkYjBmIiwiaXNzIjoiMDAwMDAwMDMtMDAwMC0wZmYxLWNlMDAtMDAwMDAwMDAwMDAwIiwibmJmIjoiMTY0NTU5NjAwMCIsImV4cCI6IjE2NDU2MTc2MDAiLCJlbmRwb2ludHVybCI6IkhBR2ZDVGdpQ2treHBKU3dMMmJ3R0xXaDRDcGVuUWtBR2svRVZtUDFuWHM9IiwiZW5kcG9pbnR1cmxMZW5ndGgiOiIxMjQiLCJpc2xvb3BiYWNrIjoiVHJ1ZSIsInZlciI6Imhhc2hlZHByb29mdG9rZW4iLCJzaXRlaWQiOiJPRE5pTkRkbVlXSXRObVptTVMwME1qWm1MVGd5WkRRdE9HSTNPV05tWmpZMVpHWTIiLCJzaWduaW5fc3RhdGUiOiJbXCJrbXNpXCJdIiwibmFtZWlkIjoiMCMuZnxtZW1iZXJzaGlwfGNoYXJsZXMuYmV0dEB0aGVwYWxsYWRpdW1ncm91cC5jb20iLCJuaWkiOiJtaWNyb3NvZnQuc2hhcmVwb2ludCIsImlzdXNlciI6InRydWUiLCJjYWNoZWtleSI6IjBoLmZ8bWVtYmVyc2hpcHwxMDAzMjAwMWM3Mzk5OWVkQGxpdmUuY29tIiwic2Vzc2lvbmlkIjoiNDI3MGIzOGUtZGJjYy00NjBhLWJmMzItZWEyNjdhZTJjYzI5IiwidHQiOiIwIiwidXNlUGVyc2lzdGVudENvb2tpZSI6IjMiLCJpcGFkZHIiOiIxNjUuOTAuMTguMTA1In0.UTNzNXNTeWU1RlhPN3NyRC95STA1bERNeHBjMmtlV3FJSk8wWnFldXhiTT0&cTag=%22c%3A%7B85B92E15-67F0-4E17-9685-519EE3EF3AEE%7D%2C1%22&encodeFailures=1&width=600&height=180&srcWidth=600&srcHeight=180" alt="logo">
            </div>
        </div>
        <div class="row">
            <div class="col pt-5 p-20 justify-content-center">
                @component('mail::message')
                <h5>Greetings {{ $partner->name }} ,</h5>
                <p>The Kenya HMIS project is working with NASCOP to improve the availability and quality of data in the
                    National Data Warehouse (NDW). This requires every facility to upload complete and up-to-date databases
                    on a monthly basis to the NDW.</p>
                <p>The purpose of this email is to share the reporting rates and selected data quality and alignment metrics
                    for the facilities that you support. Please see below a summary of the metrics for {{date('F Y',strtotime('last month'))}}:</p>

                @endcomponent
                @component('mail::table')
                <table class="table1"  role="presentation">
                    <thead>
                    <td></td>
                    <td></td>
                    <td></td>
                    </thead>
                    <tr style="background: radial-gradient(circle at bottom, black 1px, transparent 1.5px) repeat-x bottom;background-size: 5px 4px;">
                        <td style=" padding: 15px;">Number of EMR Facilities</td>
                        <td style=" padding: 15px;">{{count($facility_partner)}} Facilities</td>
                        <td style=" padding: 15px;"><a href="{{$spoturl}}">View</a></td>
                    </tr>
                    <tr style="background: radial-gradient(circle at bottom, black 1px, transparent 1.5px) repeat-x bottom;background-size: 5px 4px;">
                        <td style=" padding: 15px;">Care and Treatment Reporting Rates</td>
                        <td style=" padding: 15px;">{{$ct_rr}}%</td>
                        <td style=" padding: 15px;"><a href="{{$dwhurl}}">View</a></td>
                    </tr>
                    <tr style="background: radial-gradient(circle at bottom, black 1px, transparent 1.5px) repeat-x bottom;background-size: 5px 4px;">
                        <td style=" padding: 15px;">HTS Reporting Rates</td>
                        <td style=" padding: 15px;">{{$hts_rr}}%</td>
                        <td style=" padding: 15px;"><a href="{{$dwhurl}}">View</a></td>
                    </tr>
                    <tr style="background: radial-gradient(circle at bottom, black 1px, transparent 1.5px) repeat-x bottom;background-size: 5px 4px;">
                        <td style=" padding: 15px;">Difference (EMR value- NDW value)</td>
                        <td style=" padding: 15px;"> </td>
                        <td style=" padding: 15px;"><a href="https://palladiumgroup-my.sharepoint.com/:x:/r/personal/mary_gikura_thepalladiumgroup_com/_layouts/15/guestaccess.aspx?email=lousa.yogo%40thepalladiumgroup.com&e=4%3AP6Qi2d&at=9&CID=13149ec0-f5b1-3e05-8d39-e9f3fdf7de9c&share=EQU85MfsI1JFlw5HJHu9DkQB-iStEspiQ5aA5i4zPbU--A">View</a></td>
                    </tr>
                    <tr style="background: radial-gradient(circle at bottom, black 1px, transparent 1.5px) repeat-x bottom;background-size: 5px 4px;">
                        <td style=" padding: 15px;">% Variance*</td>
                        <td style=" padding: 15px;"></td>
                        <td style=" padding: 15px;"><a href="{{$spoturl}}">View</a></td>
                    </tr>
                </table>
                @endcomponent
                @component('mail::message')
                <small role="presentation" >*% Variance was computed as:</small>
                <img class="center" src="https://eastus1-mediap.svc.ms/transform/thumbnail?provider=spo&inputFormat=png&cs=fFNQTw&docid=https%3A%2F%2Fpalladiumgroup-my.sharepoint.com%3A443%2F_api%2Fv2.0%2Fdrives%2Fb!q3-0g_Fvb0KC1It5z_Zd9qzEBNbltp9CuQONZGRmZuaw7GWXWnW-QKxJJ-0ZXhnJ%2Fitems%2F01GNNNYSC74KBN2T6CSBHLAZ5DP4LBPH7Z%3Fversion%3DPublished&access_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJub25lIn0.eyJhdWQiOiIwMDAwMDAwMy0wMDAwLTBmZjEtY2UwMC0wMDAwMDAwMDAwMDAvcGFsbGFkaXVtZ3JvdXAtbXkuc2hhcmVwb2ludC5jb21AZTc5NDI5NzQtOTczOC00YTRhLWI2NDYtMmFiOTBmNzlkYjBmIiwiaXNzIjoiMDAwMDAwMDMtMDAwMC0wZmYxLWNlMDAtMDAwMDAwMDAwMDAwIiwibmJmIjoiMTY0NTU5NjAwMCIsImV4cCI6IjE2NDU2MTc2MDAiLCJlbmRwb2ludHVybCI6IkhBR2ZDVGdpQ2treHBKU3dMMmJ3R0xXaDRDcGVuUWtBR2svRVZtUDFuWHM9IiwiZW5kcG9pbnR1cmxMZW5ndGgiOiIxMjQiLCJpc2xvb3BiYWNrIjoiVHJ1ZSIsInZlciI6Imhhc2hlZHByb29mdG9rZW4iLCJzaXRlaWQiOiJPRE5pTkRkbVlXSXRObVptTVMwME1qWm1MVGd5WkRRdE9HSTNPV05tWmpZMVpHWTIiLCJzaWduaW5fc3RhdGUiOiJbXCJrbXNpXCJdIiwibmFtZWlkIjoiMCMuZnxtZW1iZXJzaGlwfGNoYXJsZXMuYmV0dEB0aGVwYWxsYWRpdW1ncm91cC5jb20iLCJuaWkiOiJtaWNyb3NvZnQuc2hhcmVwb2ludCIsImlzdXNlciI6InRydWUiLCJjYWNoZWtleSI6IjBoLmZ8bWVtYmVyc2hpcHwxMDAzMjAwMWM3Mzk5OWVkQGxpdmUuY29tIiwic2Vzc2lvbmlkIjoiNDI3MGIzOGUtZGJjYy00NjBhLWJmMzItZWEyNjdhZTJjYzI5IiwidHQiOiIwIiwidXNlUGVyc2lzdGVudENvb2tpZSI6IjMiLCJpcGFkZHIiOiIxNjUuOTAuMTguMTA1In0.UTNzNXNTeWU1RlhPN3NyRC95STA1bERNeHBjMmtlV3FJSk8wWnFldXhiTT0&cTag=%22c%3A%7BDD82E25F-C24F-4E90-B067-A37F16179FF9%7D%2C1%22&encodeFailures=1&width=842&height=156&srcWidth=842&srcHeight=156" alt="formular">

                @endcomponent
                @component('mail::message')
                <p>Data Alignment : - A comparison between National Data warehouse and EMR data</p>
                @endcomponent
                @component('mail::table')
                <table role="presentation">
                    <thead>
                    <td style=" padding: 15px;">Indicator Name</td>
                    <td style=" padding: 15px;">EMR Indicator Date</td>
                    <td style=" padding: 15px;">EMR Value</td>
                    <td style=" padding: 15px;">NDW Calculation</td>
                    <td style=" padding: 15px;">NDW Date</td>
                    <td style=" padding: 15px;">Difference</td>
                    <td style=" padding: 15px;">Percentage</td>
                    </thead>
                    @foreach($metrics as $metric)
                        <tr style="background: radial-gradient(circle at bottom, black 1px, transparent 1.5px) repeat-x bottom;background-size: 5px 4px;">
                            <td style="width:20%">{{ str_replace('_', ' ', $metric->name) }}</td>
                            <td align="right">{{ date('d-m-Y', strtotime($metric->metric_date)) }}</td>
                            <td align="right">{{ $metric->value }}</td>
                            <td align="right">{{ $metric->dwh_value }}</td>
                            <td align="right">{{ date('d-m-Y', strtotime($metric->dwh_metric_date)) }}</td>
                            <td align="right">{{ abs($metric->dwh_value - $metric->value) }}</td>
                            <td align="right">{{ round($metric->value == 0? 0 : abs($metric->dwh_value - $metric->value) * 100 / $metric->value , 2) }} %</td>
                        </tr>
                    @endforeach
                </table>
                @endcomponent
                @component('mail::button', ['url' => $dwhurl])
                    National Data Warehouse</a>
                @endcomponent
<p>
                <p>Kindly work with supported facilities to address any challenges they may have in uploading high quality
                    data to the NDW. Please reach out to Palladium Kenya if you have any questions.</p>
                <p>Kind Regards,</p>
                <p>The Kenya HMIS Team</p>
                </p>

            </div>
        </div>
        @component('mail::message')
            <div class="row" style="background-color: #000059">
                <div class="col p-5  text-white" style="text-align: center">
                    <p style="text-align: center">If you have any questions, feel free message us at
                        help@palladiumgroup.on.spiceworks.com.</p>
                    <p style="text-align: center">All right reserved. Update email preferences or unsubscribe.</p>
                    <p style="text-align: center">+254 717 969471</p>
                    <p style="text-align: center">Nairobi, Kenya</p>
                    <p style="text-align: center">Terms of use | Privacy Policy</p>
                </div>
            </div>
        @endcomponent
    </div>
