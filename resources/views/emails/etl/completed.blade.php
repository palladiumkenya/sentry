
    <div class="container-fluid">
        <div class="row" style="background-color: #000059">
            <div class="col p-5 align-content-center">
                <img class="center" src="https://lh3.googleusercontent.com/hLsj2td6VF7ijpRkfDpudfIJYbpTdX5_L9MhuyFn7_Dfh1TuCandHp9ySCGpskjZiu_fOQt4IORXYErtofzaYgbETaoxHogQVwFUTIym0uahfVFFpfl6giAqS3Idv0tx6ZuJ3_z73_vntuGBTUmKtV-18ah0CVWR4iFJAzGMqpH3PLpBp-6rFwecq9MM2kFzxuUbZ7pYxDSXN9WOI_qgGKVKL0iWa5-c04q8FlleAmIFleEcomIQ65rAskfBwkDjTegW_l7Gb2egCigkl-PLyxpgyVwiJnvfbPm_UKSonjBRBwqcTOHwd58Bxgt2l4bTCJwvo68YbaRbaE93Wydt1N2K4Tl1CPhbauxEaKFlFrs6HQxoqP62pM0xRKcyT5EtJYAyy-kw_RR5DPsbCNuARdOafMQHZyUmGC2iNaPukiu6zorD7nvJqhHRqxeLuShMU-GxSmaQpyJsY-diYxwdD3tuM9QYe7xjHGuAqWZ4GJXlCSYV9TQ2L9ffP4HYK8D5rvx9TyszqgQO-X5335DdUIohtx36qXl2pPvURC93isBLmMCjEsoO02izAtmEgiWQVmQgQEhkNQ5xfe6ph4NNVQPJjBdP7kzyg7-WT9S0jCno70zRyT9YiUSvWQFgGtxYpEO-Hz9b6V3NPa3qPpwYsIUUg8nDy5bB1HSf59y7hPps_shaCLnd2VmNNU_bGt6AuVNeDqIbRkXZ0Bg0N9MXrRN_=w600-h180-no?authuser=0" alt="logo">
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
                        <td style=" padding: 15px;">Stale Databases</td>
                        <td style=" padding: 15px;"> </td>
                        <td style=" padding: 15px;"><a href="https://palladiumgroup-my.sharepoint.com/:x:/r/personal/mary_gikura_thepalladiumgroup_com/_layouts/15/guestaccess.aspx?email=lousa.yogo%40thepalladiumgroup.com&e=4%3AP6Qi2d&at=9&CID=13149ec0-f5b1-3e05-8d39-e9f3fdf7de9c&share=EQU85MfsI1JFlw5HJHu9DkQB-iStEspiQ5aA5i4zPbU--A">View</a></td>
                    </tr>
                    <tr style="background: radial-gradient(circle at bottom, black 1px, transparent 1.5px) repeat-x bottom;background-size: 5px 4px;">
                        <td style=" padding: 15px;">Number of facilities with incomplete uploads</td>
                        <td style=" padding: 15px;"></td>
                        <td style=" padding: 15px;"><a href="{{$spoturl}}">View</a></td>
                    </tr>
                </table>
                @endcomponent
                @component('mail::message')
                <small role="presentation" >*% Variance was computed as:</small>
                <img class="center" src="https://lh3.googleusercontent.com/q-wFxwrbH9rYRLY_55HjL_ANrue4IFXPH2gj03kRkDqZp0eYNhfAlvjPseqdnAiFvVYdE9U4g7nKVt8yx9F57f-T72tBLJ9h7TTj30Fm_DlKfnyQijIC9y2ju0tgBsRrtY3QEziEiAS_R3Hm4rFTFv-ayMJ8GZg700UHmnjIEUbzzTcqizdB8ZoxfoILpgVjAILvwr--fsGjsIVXAaoS2HoIVDu4qeOCX5kXYkxCMFcjUNuvFRGYV1i19JSqxvhLFHMWe-TKqcAAhtmK-Diza9UIpT0FTHkFxeCab_iWNPM583L1MIFOfDx1bNkDoD1IDMLlBuZiZJC1SEvPAwsAx-4kPolnnLhOk7JZiJw6cfCCzgX-xZRzjeizCCbkRbIlkcn9i5q2-5Kmbn65b7aQdqY6Gv-cC5vfPUMWu3ioA52w_8L8PsgtSgTKxqUz5fi_Vf2XrPYvFlnBQHTqvGRVf7osb9cOZKta1X3ZWW6ba-XX27ROCW98r0q83GXgM405ym-eefd1ZPGsUPhck3h4ariFrXDIyKei3efkN2B01ToZw8E9_iJkMohDcfLeYDZjt4X90HLnZuv9SxF9Scbu8EZRi0A5t1IMD-kZ6ZEli4TZRhs_1qmLXM_N46nATZefEiLhgQN2hOGThJ54_yeci3T5wbkthhvgL7QurCxxQBGk1e_32fftgTj8Xz4zLVD-p0643DBX_xYZ9VOCYfET38hp=w842-h156-no?authuser=0" alt="formular">

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
                    <td style=" padding: 15px;">Difference (EMR value- NDW value)</td>
                    <td style=" padding: 15px;">% Variance*</td>
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
