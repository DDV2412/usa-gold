<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function index(Request $request)
    {

        $customer_id = $request->query('id');
        $tokenApi = env('WEBFLOW_API');
    
        $customer = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->get("https://api.webflow.com/collections/".env('CUSTOMER')."/items/".$customer_id);
    
        if ($customer->successful()) {

            $frontendUrl = env('FRONTEND_URL') . '/request-gold-pack/' . $customer["items"][0]["slug"];

            $body = "<div style='margin: 0; padding: 0'>
            <table
                role='presentation'
                border='0'
                cellpadding='0'
                cellspacing='0'
                width='100%'
            >
                <tr>
                    <td style='padding: 20px 0 30px 0'>
                        <table
                            align='center'
                            border='0'
                            cellpadding='0'
                            cellspacing='0'
                            width='600'
                            style='
                                border-collapse: collapse;
                                border: 1px solid #cccccc;
                            '
                        >
                            <tr>
                                <td
                                    align='left'
                                    bgcolor='#1b5a79'
                                    style='padding: 20px; color: #fff'
                                >
                                    <h3 style='margin: 0; font-size: 20px'>
                                        Welcome To USA Gold
                                    </h3>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    bgcolor='#ffffff'
                                    style='padding: 0px 30px 0px 30px'
                                >
                                    <table
                                        border='0'
                                        cellpadding='0'
                                        cellspacing='0'
                                        width='100%'
                                        style='border-collapse: collapse'
                                    >
                                        <tr>
                                            <td
                                                style='
                                                    color: #153643;
                                                    font-family: Arial,
                                                        sans-serif;
                                                    font-size: 16px;
                                                    line-height: 24px;
                                                    padding: 20px 0 30px 0;
                                                '
                                            >
                                                <p style='font-size: 15px'>
                                                    Hi " .
                                                    $customer['items'][0]['first_name']
                                                    . ' ' .
                                                    $customer['items'][0]['last_name']
                                                    . " ,
                                                </p>
                                                <p style='font-size: 15px'>
                                                    Thank you for your USA Gold
                                                    Appraisal Kit request!
                                                </p>
                                                <p style='font-size: 15px'>
                                                    You can visit your account
                                                    to track the status of your
                                                    appraisal kit, make changes
                                                    to your account profile, or
                                                    request another appraisal
                                                    kit.
                                                </p>
                                                <p style='font-size: 15px'>
                                                    Please feel free to contact
                                                    us at any time with
                                                    questions, concerns, or if
                                                    you’d like to schedule a
                                                    FedEx pickup.
                                                </p>
                                                <p style='font-size: 15px'>
                                                    We look forward to your
                                                    business!
                                                </p>
                                                <p style='font-size: 15px'>
                                                    Click
                                                    <a
                                                        style='color: #caae5e'
                                                        href='$frontendUrl'
                                                        >here</a
                                                    >
                                                    to view your appraisal kit
                                                </p>
                                                <p style='font-size: 15px'>
                                                    Sincerely,
                                                </p>
                                                <p style='font-size: 15px'>
                                                    Our team at USA Gold
                                                </p>
                                                <div>
                                                    <img
                                                        src='https://uploads-ssl.webflow.com/64b4a87a23ffa5cf4a6ce314/64df5147fbe9e6a17c70dd01_Logo.svg'
                                                        style='width: 20%'
                                                    />
                                                    <p>(800) 337-7706</p>
                                                    <p style='color: #caae5e'>
                                                        usagold.us@gmail.com
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>";

            $headers = [
                'Content-Type' => 'text/html; charset=UTF-8',
                'From' => 'Appraisal Kit <usagold.us@gmail.com>',
            ];

            Mail::raw($body, function ($message) use ($request, $headers) {
                $message->to($customer["items"][0]["email"], $customer["items"][0]["first_name"] . ' ' . $customer["items"][0]["last_name"])
                        ->subject('Your New Request a Gold Pack')
                        ->from('usagold.us@gmail.com', 'Appraisal Kit')
                        ->setHeaders($headers);
            });

            return response()->json(['message' => 'Email sent successfully']);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Please check your input address or email'
            ], 400);
        }

    }
}

