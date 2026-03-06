<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Services\PayrollStatusTransitionService;
use Illuminate\Http\Request;

class PayrollStatusController extends Controller
{
    protected $service;

    public function __construct(PayrollStatusTransitionService $service)
    {
        $this->service = $service;
    }

    public function markAsReviewed(Request $request, $id)
    {
        try {
            $userId = $request->user() ? $request->user()->id : 1;
            $period = $this->service->markAsReviewed($id, $userId, $request->notes);
            return response()->json(['success' => true, 'data' => $period]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function markAsApproved(Request $request, $id)
    {
        try {
            $approverId = $request->user() ? $request->user()->id : 1;
            $period = $this->service->markAsApproved($id, $approverId, $request->notes);
            return response()->json(['success' => true, 'data' => $period]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // --- REJECT OPSI ---
    public function rejectToDraft(Request $request, $id)
    {
        try {
            $approverId = $request->user() ? $request->user()->id : 1;
            $period = $this->service->rejectToDraft($id, $approverId, $request->notes);
            return response()->json(['success' => true, 'data' => $period]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function markAsRejected(Request $request, $id)
    {
        try {
            $approverId = $request->user() ? $request->user()->id : 1;
            $period = $this->service->markAsRejected($id, $approverId, $request->notes);
            return response()->json(['success' => true, 'data' => $period]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    // -------------------

    public function markAsPaid(Request $request, $id)
    {
        try {
            $userId = $request->user() ? $request->user()->id : 1;
            $period = $this->service->markAsPaid($id, $userId, $request->notes);
            return response()->json(['success' => true, 'data' => $period]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function markAsClosed(Request $request, $id)
    {
        try {
            $userId = $request->user() ? $request->user()->id : 1;
            $period = $this->service->markAsClosed($id, $userId, $request->notes);
            return response()->json(['success' => true, 'data' => $period]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
