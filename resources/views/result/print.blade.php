      
<table class="table table-bordered">
    <tr> <img src="img/coa"></tr>
    <tr style="text-align:center"><td><b>MINISTRY OF HEALTH</b></td></tr>
    <tr style="text-align:center"><td><b>NATIONAL PUBLIC HEALTH LABORATORY SERVICES (NPHLS)</b></td></tr>
    <tr style="text-align:center"><td><b>NATIONAL HIV REFERENCE LABORATORY (NHRL)</b></td></tr>
    <tr style="text-align:center"><td><b>P.O Box 20750 - 00202, NAIROBI, nhrlpt@nphls.or.ke</b></td></tr>
    <tr style="text-align:center"><td><b>NATIONAL HIV SEROLOGY PROFICIENCY TESTING SCHEME</b></td></tr>
    <tr style="text-align:center"><td><b>Final Report</b></td></tr>
    <tr class="col-md-12">
        <td class="col-md-3"><b>Round</b></td>
        <td class="col-md-3">{{$data['round_name']}}</td>
        <td class="col-md-3"><b>County</b></td>
        <td class="col-md-3">{{$data['county']}}</td>
    </tr>
     <tr>
        <td class="col-md-3"><b>Tester ID</b></td>
        <td class="col-md-3">{{$data['tester_id']}}</td>
        <td class="col-md-3"><b>Sub County</b></td>
        <td class="col-md-3">{{$data['sub_county']}}</td>
    </tr>
     <tr>
        <td class="col-md-3"><b>Tester Name</b></td>
        <td class="col-md-3">{{$data['user_name']}}</td>
        <td class="col-md-3"><b>Facility</b></td>
        <td class="col-md-3">{{$data['facility']}}</td>
    </tr>
     <tr>
        <td class="col-md-3"><b>Program</b></td>
        <td class="col-md-3">{{$data['program']}}</td>
        <td class="col-md-3"><b>Facility MFL</b></td>
        <td class="col-md-3">{{$data['mfl']}}</td>
    </tr>
    <tr class="text-center"> <td>RE: Profeciency Testing Results</td></tr>
    <tr class="text-center"> <td>NHRL acknowledges receipt of your Proficiency Testing results for Round {{$data['round_name']}}</td></tr>
    <tr class="text-center"> <td>Your result is <b> <i>{{$data['feedback']}}</i></b></td></tr>
    <tr class="text-center"> 
        <td style ="border-radius"></td>
        <td style ="border-radius">Your Results</td>
        <td style ="border-radius">NHRL Results</td>
    </tr>
    <tr>
        <th>PT Sample ID</th>
        <th>Determine</th>
        <th>First Response</th>
        <th>Final Result</th>
        <th>Expected Result</th>
    </tr>                                        
    <tr>
        <td>{{$data['sample_1']}}</td>
        <td>{{$data['pt_panel_1_kit1_results']}}</td>
        <td>{{$data['pt_panel_1_kit2_results']}}</td>
        <td>{{$data['pt_panel_1_final_results']}}</td>
        <td class="text-uppercase">{{$data['expected_result_1']}}</td>
    </tr>
    <tr>
        <td>{{$data['sample_2']}}</td>
        <td>{{$data['pt_panel_2_kit1_results']}}</td>
        <td>{{$data['pt_panel_2_kit2_results']}}</td>
        <td>{{$data['pt_panel_2_final_results']}}</td>
        <td class ="text-uppercase">{{$data['expected_result_2']}}</td>
    </tr>
    <tr>
        <td>{{$data['sample_3']}}</td>
        <td>{{$data['pt_panel_3_kit1_results']}}</td>
        <td>{{$data['pt_panel_3_kit2_results']}}</td>
        <td>{{$data['pt_panel_3_final_results']}}</td>
        <td class="text-uppercase">{{$data['expected_result_3']}}</td>
    </tr>
    <tr>
        <td>{{$data['sample_4']}}</td>
        <td>{{$data['pt_panel_4_kit1_results']}}</td>
        <td>{{$data['pt_panel_4_kit2_results']}}</td>
        <td>{{$data['pt_panel_4_final_results']}}</td>
        <td class="text-uppercase">{{$data['expected_result_4']}}</td>
    </tr>
    <tr>
        <td>{{$data['sample_5']}}</td>
        <td>{{$data['pt_panel_5_kit1_results']}}</td>
        <td>{{$data['pt_panel_5_kit2_results']}}</td>
        <td>{{$data['pt_panel_5_final_results']}}</td>
        <td class="text-uppercase">{{$data['expected_result_5']}}</td>
    </tr>
    <tr>
        <td>{{$data['sample_6']}}</td>
        <td>{{$data['pt_panel_6_kit1_results']}}</td>
        <td>{{$data['pt_panel_6_kit2_results']}}</td>
        <td>{{$data['pt_panel_6_final_results']}}</td>
        <td class="text-uppercase">{{$data['expected_result_6']}}</td>
    </tr>
    <tr>
        <td><b>Date Authorized</b></td>
        <td> Head of Virology</td>
    </tr>
    <tr>
        <td>Thank you for your participation</td>
    </tr>
    <tr>
        <td>PT Coordinator.</td>
    </tr>
</table>   