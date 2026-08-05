@extends('layouts.admin')

@section('title', 'Reports System')
@section('page_title', 'Reports System')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <!-- Sales Reports Panel -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <div class="flex items-center space-x-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                <i class="bi bi-file-earmark-bar-graph-fill text-lg"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-950">Sales & Revenue Reports</h3>
                <p class="text-[10px] text-slate-400 font-medium mt-0.5">Export revenue journals and client transaction records.</p>
            </div>
        </div>

        <form action="" method="GET" id="sales-form" class="space-y-4">
            <!-- Filter Type -->
            <div>
                <label for="sales_filter" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Reporting Window</label>
                <select name="filter_type" id="sales_filter" onchange="toggleSalesDateFields()"
                    class="block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white cursor-pointer font-semibold">
                    <option value="daily">Daily Report (Today)</option>
                    <option value="weekly">Weekly Report (This Week)</option>
                    <option value="monthly">Monthly Report (This Month)</option>
                    <option value="annual">Annual Report (This Year)</option>
                    <option value="custom">Custom Date Range...</option>
                </select>
            </div>

            <!-- Date range picker (Custom only) -->
            <div id="sales_custom_dates" class="grid grid-cols-2 gap-4" style="display: none;">
                <div>
                    <label for="sales_start" class="block text-[10px] font-semibold text-slate-500">Start Date</label>
                    <input type="date" name="start_date" id="sales_start"
                        class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 transition-all focus:bg-white">
                </div>
                <div>
                    <label for="sales_end" class="block text-[10px] font-semibold text-slate-500">End Date</label>
                    <input type="date" name="end_date" id="sales_end"
                        class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 transition-all focus:bg-white">
                </div>
            </div>

            <!-- Export Buttons -->
            <div class="pt-6 border-t border-slate-100 grid grid-cols-2 gap-4">
                <button type="button" onclick="submitReport('sales', 'excel')"
                    class="flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100/80 hover:-translate-y-0.5 transition-all border border-emerald-100 shadow-2xs hover:shadow-xs cursor-pointer">
                    <i class="bi bi-file-earmark-excel-fill text-sm"></i>
                    <span>Export Excel</span>
                </button>
                <button type="button" onclick="submitReport('sales', 'pdf')"
                    class="flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100/80 hover:-translate-y-0.5 transition-all border border-rose-100 shadow-2xs hover:shadow-xs cursor-pointer">
                    <i class="bi bi-file-earmark-pdf-fill text-sm"></i>
                    <span>Export PDF</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Inventory Reports Panel -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <div class="flex items-center space-x-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100">
                <i class="bi bi-archive-fill text-lg"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-950">Inventory Transaction Logs</h3>
                <p class="text-[10px] text-slate-400 font-medium mt-0.5">Export stock ledger logs, intake lists and FIFO status audits.</p>
            </div>
        </div>

        <form action="" method="GET" id="inventory-form" class="space-y-4">
            <!-- Filter Type -->
            <div>
                <label for="inv_filter" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Reporting Window</label>
                <select name="filter_type" id="inv_filter" onchange="toggleInvDateFields()"
                    class="block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white cursor-pointer font-semibold">
                    <option value="daily">Daily Logs (Today)</option>
                    <option value="weekly">Weekly Logs (This Week)</option>
                    <option value="monthly">Monthly Logs (This Month)</option>
                    <option value="annual">Annual Logs (This Year)</option>
                    <option value="custom">Custom Date Range...</option>
                </select>
            </div>

            <!-- Date range picker (Custom only) -->
            <div id="inv_custom_dates" class="grid grid-cols-2 gap-4" style="display: none;">
                <div>
                    <label for="inv_start" class="block text-[10px] font-semibold text-slate-500">Start Date</label>
                    <input type="date" name="start_date" id="inv_start"
                        class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 transition-all focus:bg-white">
                </div>
                <div>
                    <label for="inv_end" class="block text-[10px] font-semibold text-slate-500">End Date</label>
                    <input type="date" name="end_date" id="inv_end"
                        class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 transition-all focus:bg-white">
                </div>
            </div>

            <!-- Export Buttons -->
            <div class="pt-6 border-t border-slate-100 grid grid-cols-2 gap-4">
                <button type="button" onclick="submitReport('inventory', 'excel')"
                    class="flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100/80 hover:-translate-y-0.5 transition-all border border-emerald-100 shadow-2xs hover:shadow-xs cursor-pointer">
                    <i class="bi bi-file-earmark-excel-fill text-sm"></i>
                    <span>Export Excel</span>
                </button>
                <button type="button" onclick="submitReport('inventory', 'pdf')"
                    class="flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100/80 hover:-translate-y-0.5 transition-all border border-rose-100 shadow-2xs hover:shadow-xs cursor-pointer">
                    <i class="bi bi-file-earmark-pdf-fill text-sm"></i>
                    <span>Export PDF</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleSalesDateFields() {
        const select = document.getElementById('sales_filter');
        const container = document.getElementById('sales_custom_dates');
        container.style.display = (select.value === 'custom') ? 'grid' : 'none';
    }

    function toggleInvDateFields() {
        const select = document.getElementById('inv_filter');
        const container = document.getElementById('inv_custom_dates');
        container.style.display = (select.value === 'custom') ? 'grid' : 'none';
    }

    function submitReport(type, format) {
        const form = document.getElementById(type + '-form');
        let route = '';

        if (type === 'sales') {
            route = (format === 'excel') ? "{{ route('admin.reports.salesExcel') }}" : "{{ route('admin.reports.salesPdf') }}";
        } else {
            route = (format === 'excel') ? "{{ route('admin.reports.inventoryExcel') }}" : "{{ route('admin.reports.inventoryPdf') }}";
        }

        form.action = route;
        form.submit();
    }
</script>
@endsection
