<?php

namespace App\Http\Controllers\Pdf;

abstract class TemplateController
{
    protected function consolidationHeader()
    {
        return '<tr style="font-size: 10px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                <th width="40px" rowspan="3">Old Stock. No</th>
                <th width="45px" rowspan="3">New Stock. No</th>
                <th width="190px" rowspan="3">Item Description</th>
                <th width="45px" rowspan="3">Unit of Measure</th>
                <th width="45px" rowspan="3">Price</th>
                <th width="40px" rowspan="3">Total Qantity</th>
                <th width="60px" rowspan="3">Total Amount</th>
                <th width="414px" colspan="12">SCHEDULE/MILESTONE OF ACTIVITIES</th>
            </tr>
            <tr style="font-size: 8px; font-weight:bold; background-color: #EEEEEE;">
                <th width="82px" colspan="2" style="text-align:center;">JAN</th>
                <th width="25px" rowspan="2" style="text-align:center;">FEB</th>
                <th width="25px" rowspan="2" style="text-align:center;" >MAR</th>
                <th width="25px" rowspan="2" style="text-align:center;">APR</th>
                <th width="82px" colspan="2" style="text-align:center;" >MAY</th>
                <th width="25px" rowspan="2" style="text-align:center;">JUN</th>
                <th width="25px" rowspan="2" style="text-align:center;">JUL</th>
                <th width="25px" rowspan="2" style="text-align:center;">AUG</th>
                <th width="25px" rowspan="2" style="text-align:center;">SEP</th>
                <th width="25px" rowspan="2" style="text-align:center;">OCT</th>
                <th width="25px" rowspan="2" style="text-align:center;">NOV</th>
                <th width="25px" rowspan="2" style="text-align:center;">DEC</th>
            </tr>
            <tr style="font-size: 8px; font-weight: bold; background-color: #EEEEEE;">
                <th width="32px" style="text-align: center;">QTY</th>
                <th width="50px" style="text-align: center;">AMT</th>
                <th width="32px" style="text-align: center;">QTY</th>
                <th width="50px" style="text-align: center;">AMT</th>
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
