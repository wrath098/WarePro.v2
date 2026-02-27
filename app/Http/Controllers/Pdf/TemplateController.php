<?php

namespace App\Http\Controllers\Pdf;

abstract class TemplateController
{
    protected function consolidationHeader()
    {
        return '<tr style="font-size: 7px; font-weight:bold; text-align:center; background-color: #FFFFFF;">
                    <th width="590px" colspan="9">PROCUREMENT PROJECT DETAILS</th>
                    <th width="120px" colspan="3">PROJECTED TIMELINE (MM/YYYY)</th>
                    <th width="90px" colspan="2">FUNDING DETAILS</th>
                    <th width="40px" rowspan="2">ATTACHED SUPPORTING DOCUMENTS</th>
                    <th width="40px" rowspan="2">REMARKS</th>
                </tr>
                <tr style="font-size: 7px; font-weight:bold; text-align:center; background-color: #FFFFFF;">
                    <th width="50px">General Description and Objective of the Prject to be Procured</th>
                    <th width="40px">Type of the Project to be Procured</th>
                    <th width="410px" colspan="5">Quantity and Size of the Project to be Procured</th>
                    <th width="50px">Recommended Mode of Procurement</th>
                    <th width="40px">Pre-Procurement Conference, if applicable (Yes/No)</th>
                    <th width="40px">Start of Procurement Activity</th>
                    <th width="40px">End of Procurement Activity</th>
                    <th width="40px">Expected Delivery/Implementation Period</th>
                    <th width="50px">Source of Funds</th>
                    <th width="40px">Estimated Budget / Authorized Budgetary Allocation (PhP)</th>
                </tr>
                <tr style="font-size: 7px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="50px">Column 1</th>
                    <th width="40px">Column 2</th>
                    <th width="410px" colspan="5">Column 3</th>
                    <th width="50px">Column 4</th>
                    <th width="40px">Column 5</th>
                    <th width="40px">Column 6</th>
                    <th width="40px">Column 7</th>
                    <th width="40px">Column 8</th>
                    <th width="50px">Column 9</th>
                    <th width="40px">Column 10</th>
                    <th width="40px">Column 11</th>
                    <th width="40px">Column 12</th>
                </tr>
                <tr style="font-size: 7px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="50px"></th>
                    <th width="40px"></th>
                    <th width="45px">Stock No.</th>
                    <th width="30px">Qty</th>
                    <th width="35px">Unit</th>
                    <th width="250px">Descriptions</th>
                    <th width="50px">Unit Price</th>
                    <th width="50px"></th>
                    <th width="40px"></th>
                    <th width="40px"></th>
                    <th width="40px"></th>
                    <th width="40px"></th>
                    <th width="50px"></th>
                    <th width="40px"></th>
                    <th width="40px"></th>
                    <th width="40px"></th>
                </tr>';
    }

    protected function ppmpFooter()
    {
        return '
            <table>
                <thead>
                    <tr style="font-size: 11px;">
                        <th style="margin-left: 15px;" width="293px" rowspan="3">Prepared By:</th>
                        <th style="margin-left: 15px;" width="293px" rowspan="3">Reviewed By:</th>
                        <th style="margin-left: 15px;" width="293px" rowspan="3">Approved By:</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td width="100%"><br></td></tr>
                    <tr style="font-size: 11px; font-weight:bold; text-align:center;">
                        <td width="293px">AILEEN G. RAMOS</td>
                        <td width="293px">MARJORIE A. BOMOGAO</td>
                        <td width="293px">JENNIFER G. BAHOD</td>
                    </tr>
                    <tr style="font-size: 11px; text-align:center;">
                        <td width="293px">Administrative Officer IV</td>
                        <td width="293px">Supervising Administrative Officer</td>
                        <td width="293px">Provincial General Services Officer</td>
                    </tr>
                </tbody>
            </table>';
    }
}
