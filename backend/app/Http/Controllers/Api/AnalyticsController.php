<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get dashboard overview
     */
    public function dashboard(): JsonResponse
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        return response()->json([
            'contacts' => [
                'total' => Contact::count(),
                'new_today' => Contact::whereDate('created_at', $today)->count(),
                'new_this_month' => Contact::where('created_at', '>=', $thisMonth)->count(),
            ],
            'leads' => $this->getLeadStats(),
            'messages' => [
                'total_sent' => Message::where('direction', 'outbound')->count(),
                'sent_today' => Message::where('direction', 'outbound')
                    ->whereDate('created_at', $today)->count(),
                'delivered' => Message::where('status', 'delivered')->count(),
                'read' => Message::where('status', 'read')->count(),
                'failed' => Message::where('status', 'failed')->count(),
            ],
            'campaigns' => [
                'total' => Campaign::count(),
                'active' => Campaign::where('status', 'running')->count(),
                'completed_this_month' => Campaign::where('status', 'completed')
                    ->where('completed_at', '>=', $thisMonth)->count(),
            ],
        ]);
    }

    /**
     * Get lead funnel statistics
     */
    public function leadFunnel(): JsonResponse
    {
        return response()->json([
            'funnel' => $this->getLeadStats(),
            'conversion_rate' => $this->calculateConversionRate(),
        ]);
    }

    /**
     * Get message delivery statistics
     */
    public function messageStats(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $startDate = now()->subDays($days)->startOfDay();

        $dailyStats = Message::where('direction', 'outbound')
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered'),
                DB::raw('SUM(CASE WHEN status = "read" THEN 1 ELSE 0 END) as `read`'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'daily_stats' => $dailyStats,
            'summary' => [
                'total_sent' => $dailyStats->sum('total'),
                'avg_delivery_rate' => $this->calculateAvgDeliveryRate($dailyStats),
                'avg_read_rate' => $this->calculateAvgReadRate($dailyStats),
            ],
        ]);
    }

    /**
     * Get campaign performance
     */
    public function campaignPerformance(Request $request): JsonResponse
    {
        $campaigns = Campaign::where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit($request->input('limit', 10))
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'total_recipients' => $campaign->total_recipients,
                    'sent_count' => $campaign->sent_count,
                    'delivered_count' => $campaign->delivered_count,
                    'read_count' => $campaign->read_count,
                    'failed_count' => $campaign->failed_count,
                    'delivery_rate' => $campaign->sent_count > 0
                        ? round(($campaign->delivered_count / $campaign->sent_count) * 100, 2)
                        : 0,
                    'read_rate' => $campaign->delivered_count > 0
                        ? round(($campaign->read_count / $campaign->delivered_count) * 100, 2)
                        : 0,
                    'completed_at' => $campaign->completed_at,
                ];
            });

        return response()->json(['campaigns' => $campaigns]);
    }

    /**
     * Get vehicle interest distribution
     */
    public function vehicleInterest(): JsonResponse
    {
        $distribution = Contact::whereNotNull('vehicle_interest')
            ->select('vehicle_interest', DB::raw('COUNT(*) as count'))
            ->groupBy('vehicle_interest')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json(['distribution' => $distribution]);
    }

    /**
     * Get lead source breakdown
     */
    public function leadSources(): JsonResponse
    {
        $sources = Contact::whereNotNull('source')
            ->select('source', DB::raw('COUNT(*) as count'))
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        return response()->json(['sources' => $sources]);
    }

    private function getLeadStats(): array
    {
        return Contact::select('lead_status', DB::raw('COUNT(*) as count'))
            ->groupBy('lead_status')
            ->pluck('count', 'lead_status')
            ->toArray();
    }

    private function calculateConversionRate(): float
    {
        $total = Contact::count();
        $won = Contact::where('lead_status', 'closed_won')->count();

        return $total > 0 ? round(($won / $total) * 100, 2) : 0;
    }

    private function calculateAvgDeliveryRate($dailyStats): float
    {
        $totalSent = $dailyStats->sum('total');
        $totalDelivered = $dailyStats->sum('delivered');

        return $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 2) : 0;
    }

    private function calculateAvgReadRate($dailyStats): float
    {
        $totalDelivered = $dailyStats->sum('delivered');
        $totalRead = $dailyStats->sum('read');

        return $totalDelivered > 0 ? round(($totalRead / $totalDelivered) * 100, 2) : 0;
    }
}
