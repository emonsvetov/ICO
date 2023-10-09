@extends('layouts.email')

@section('content')

    <table cellspacing="0" cellpadding="0" style="font-family:Helvetica, Arial; border-left:solid 1px;
                    border-right:solid 1px; border-top:solid 1px;
                    border-bottom:solid 1px;
                    border-color:#bfbaba;">
        <tbody>
        <tr>
            <td style="background-color:white; font-size: 14px;">
                <div style="margin-left:30px; margin-right:30px; margin-top: 15px; margin-bottom: 15px;
                            font-size:14px;">
                    {!! $content !!}
                </div>
            </td>
        </tr>
        </tbody>
    </table>
@endsection
