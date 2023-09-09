<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest as Request;
use App\Http\Resources\MemberResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\Resource;
use App\Models\Account_payment;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Project;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Project $project)
    {
        $report = [
            "from_date" => $request->query("from_date"),
            "to_date" => $request->query("to_date"),
            "account_count" => 0,
            "sum_sales" => 0,
        ];
        $account_acounts = $project->accounts();
        if (!is_null($request->query("from_date"))) $account_acounts->whereDate("created_at", ">=", $request->query("from_date"));
        if (!is_null($request->query("to_date"))) $account_acounts->whereDate("created_at", "<=", $request->query("to_date"));
        $report["account_count"] = $account_acounts->count();
        $report["sum_sales"] = (int)Account_payment::whereIn("account_id", (clone $account_acounts)->select('id'))->sum("price");

        $sales = explode(",", $request->query("sales", ""));

        //メンバー別
        if (in_array("member", $sales)) {
            $account_by_member = clone $account_acounts;
            $account_by_member->select('member_id');
            $account_by_member->distinct("member_id");
            $members = array_column($account_by_member->get()->toArray(), "member_id");
            foreach ($members as $member) {
                $account_cnt = clone $account_acounts;
                $account_cnt->where('member_id', $member);
                $account_price = Account_payment::whereIn("account_id", $account_cnt->select('id'));
                $report["member_sales"][] = [
                    "member" => (new MemberResource(Member::find($member)))->createArray(),
                    "count" => (int)$account_cnt->count(),
                    "price" => (int)$account_price->sum("price"),
                ];
            }
        }

        //支払い方法別
        if (in_array("payment", $sales)) {
            $account_by_payment = clone $account_acounts;
            $account_payments = Account_payment::whereIn("account_id", $account_by_payment->select('id'));
            $account_payments->select('payment_id');
            $account_payments->distinct("payment_id");
            $account_cnt = $account_payments->count();
            $payments = array_column($account_payments->get()->toArray(), "payment_id");
            foreach ($payments as $payment) {
                $account_price = Account_payment::where("payment_id", $payment);
                $report["payment_sales"][] = [
                    "payment" => (new PaymentResource(Payment::find($payment)))->createArray(),
                    "count" => (int)$account_cnt,
                    "price" => (int)$account_price->sum("price"),
                ];
            }
        }

        return Resource::success($report);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        //
    }
}