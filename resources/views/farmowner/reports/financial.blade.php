@extends('farmowner.layouts.app')

@section('title', 'Financial Report')
@section('header', 'Financial Report')
@section('subheader', $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y'))

@section('header-actions')
<a href="{{ route('reports.export', ['type' => 'financial']) }}?start_date={{ $startDate->format('Y-m-d') }}&end_date={{ $endDate->format('Y-m-d') }}" 
   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">📥 Export CSV</a>
@endsection

@section('content')
<!-- Date Filter -->
<div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm text-gray-600 mb-1">Period</label>
            <select name="period" class="px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
                @foreach($periodOptions as $value => $label)
                    <option value="{{ $value }}" {{ $normalizedPeriod === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Start Date</label>
            <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                class="px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">End Date</label>
            <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                class="px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Apply</button>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 border-l-4 border-green-600">
        <p class="text-gray-300 text-sm">Total Income</p>
        <p class="text-3xl font-bold text-green-600">₱{{ number_format($totalIncome, 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 border-l-4 border-red-600">
        <p class="text-gray-300 text-sm">Total Expenses</p>
        <p class="text-3xl font-bold text-red-600">₱{{ number_format($totalExpenses, 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 border-l-4 {{ $netProfit >= 0 ? 'border-blue-600' : 'border-orange-600' }}">
        <p class="text-gray-300 text-sm">Net Profit</p>
        <p class="text-3xl font-bold {{ $netProfit >= 0 ? 'text-blue-600' : 'text-orange-600' }}">
            {{ $netProfit >= 0 ? '+' : '' }}₱{{ number_format($netProfit, 2) }}
        </p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 border-l-4 border-indigo-600">
        <p class="text-gray-300 text-sm">Period</p>
        <p class="text-3xl font-bold text-indigo-400">{{ $periodOptions[$normalizedPeriod] ?? 'Monthly' }}</p>
    </div>
</div>

<!-- Auto-Updating Trend Chart -->
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h3 class="font-semibold text-lg">Income vs Expenses Trend</h3>
            <p class="text-xs text-gray-400">Auto-refreshes every 20 seconds to reflect latest sales and expense entries.</p>
        </div>
        <div class="flex gap-2" id="periodQuickSwitch">
            @foreach($periodOptions as $value => $label)
                <a
                    href="{{ route('reports.financial', ['period' => $value]) }}"
                    class="px-3 py-1.5 rounded text-xs font-semibold border {{ $normalizedPeriod === $value ? 'bg-orange-600 border-orange-500 text-white' : 'bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="flex items-center gap-4 text-xs mb-2">
        <span class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-green-500"></span> Income</span>
        <span class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-red-500"></span> Expenses</span>
        <span class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-blue-500"></span> Net</span>
    </div>
    <div class="relative h-72 w-full">
        <canvas id="financeLineChart" class="w-full h-full"></canvas>
    </div>
</div>

<!-- Income & Expense Breakdown -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Income by Source -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg">
        <div class="p-6 border-b border-gray-600">
            <h3 class="font-semibold text-lg text-green-600">📈 Income by Source</h3>
        </div>
        <div class="p-6">
            @forelse($income as $item)
            <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                <span class="text-gray-300">{{ ucfirst(str_replace('_', ' ', $item->category)) }}</span>
                <span class="font-semibold text-green-600">₱{{ number_format($item->total, 2) }}</span>
            </div>
            @empty
            <p class="text-gray-400 text-center py-4">No income recorded</p>
            @endforelse
        </div>
    </div>

    <!-- Expenses by Category -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg">
        <div class="p-6 border-b border-gray-600">
            <h3 class="font-semibold text-lg text-red-600">📉 Expenses by Category</h3>
        </div>
        <div class="p-6">
            @forelse($expenses as $item)
            <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                <span class="text-gray-300">{{ ucfirst($item->category) }}</span>
                <span class="font-semibold text-red-600">₱{{ number_format($item->total, 2) }}</span>
            </div>
            @empty
            <p class="text-gray-400 text-center py-4">No expenses recorded</p>
            @endforelse
        </div>
    </div>
</div>

<script>
    const initialSeries = @json($series);
    const selectedPeriod = @json($normalizedPeriod);
    const selectedStartDate = @json($startDate->format('Y-m-d'));
    const selectedEndDate = @json($endDate->format('Y-m-d'));
    const chartCanvas = document.getElementById('financeLineChart');
    let currentSeries = initialSeries;

    function formatMoney(value) {
        const number = Number(value || 0);
        return `PHP ${number.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function drawFinanceChart(series) {
        if (!chartCanvas || !series) return;

        const labels = Array.isArray(series.labels) ? series.labels : [];
        const income = Array.isArray(series.income) ? series.income : [];
        const expenses = Array.isArray(series.expenses) ? series.expenses : [];
        const net = Array.isArray(series.net) ? series.net : [];

        const parent = chartCanvas.parentElement;
        const width = Math.max(640, parent?.clientWidth || 640);
        const height = Math.max(280, parent?.clientHeight || 280);
        chartCanvas.width = width;
        chartCanvas.height = height;

        const ctx = chartCanvas.getContext('2d');
        if (!ctx) return;

        ctx.clearRect(0, 0, width, height);

        const padding = { top: 20, right: 14, bottom: 38, left: 62 };
        const chartW = width - padding.left - padding.right;
        const chartH = height - padding.top - padding.bottom;

        const allValues = [...income, ...expenses, ...net].map((v) => Number(v || 0));
        const maxValue = Math.max(1, ...allValues);
        const minValue = Math.min(0, ...allValues);
        const valueRange = Math.max(1, maxValue - minValue);

        // axes
        ctx.strokeStyle = 'rgba(148,163,184,0.45)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(padding.left, padding.top);
        ctx.lineTo(padding.left, height - padding.bottom);
        ctx.lineTo(width - padding.right, height - padding.bottom);
        ctx.stroke();

        // y ticks
        ctx.fillStyle = 'rgba(203,213,225,0.85)';
        ctx.font = '11px sans-serif';
        const yTicks = 5;
        for (let i = 0; i <= yTicks; i++) {
            const ratio = i / yTicks;
            const value = maxValue - (valueRange * ratio);
            const y = padding.top + (chartH * ratio);

            ctx.strokeStyle = 'rgba(71,85,105,0.35)';
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(width - padding.right, y);
            ctx.stroke();

            ctx.fillText(`₱${Math.round(value).toLocaleString()}`, 6, y + 3);
        }

        const safeLen = Math.max(labels.length, income.length, expenses.length, net.length, 1);
        const xStep = safeLen > 1 ? (chartW / (safeLen - 1)) : chartW;

        function yForValue(v) {
            return padding.top + ((maxValue - Number(v || 0)) / valueRange) * chartH;
        }

        function drawSeries(values, color) {
            if (!Array.isArray(values) || values.length === 0) return;

            ctx.strokeStyle = color;
            ctx.lineWidth = 2.2;
            ctx.beginPath();
            values.forEach((value, index) => {
                const x = padding.left + (xStep * index);
                const y = yForValue(value);
                if (index === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            });
            ctx.stroke();

            values.forEach((value, index) => {
                const x = padding.left + (xStep * index);
                const y = yForValue(value);
                ctx.fillStyle = color;
                ctx.beginPath();
                ctx.arc(x, y, 2.8, 0, Math.PI * 2);
                ctx.fill();
            });
        }

        drawSeries(income, '#22c55e');
        drawSeries(expenses, '#ef4444');
        drawSeries(net, '#3b82f6');

        // x labels (reduce density for readability)
        const maxLabels = 8;
        const labelStep = Math.max(1, Math.ceil(safeLen / maxLabels));
        ctx.fillStyle = 'rgba(203,213,225,0.8)';
        ctx.font = '11px sans-serif';
        labels.forEach((label, index) => {
            if (index % labelStep !== 0 && index !== labels.length - 1) return;
            const x = padding.left + (xStep * index);
            ctx.fillText(String(label), x - 14, height - 14);
        });
    }

    async function refreshFinanceSeries() {
        try {
            const query = new URLSearchParams({
                period: selectedPeriod,
                start_date: selectedStartDate,
                end_date: selectedEndDate,
            });

            const response = await fetch(`{{ route('reports.financial.series') }}?${query.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) return;
            const payload = await response.json();
            if (!payload || !payload.series) return;
            currentSeries = payload.series;
            drawFinanceChart(currentSeries);
        } catch (_) {
            // Silent failure for auto-refresh keeps UX stable.
        }
    }

    drawFinanceChart(currentSeries);
    window.addEventListener('resize', () => drawFinanceChart(currentSeries));
    setInterval(refreshFinanceSeries, 20000);
</script>
@endsection
