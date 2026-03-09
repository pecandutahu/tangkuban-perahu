<script setup>
import { Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    stats:           Object,
    employeesByBranch: Array,
    employeesByDept:   Array,
    lastPaidPeriod:    Object,
    lastPaidSummary:   Object,
    activePeriods:     Array,
    payrollTrend:      Array,
});

const fmt = (val) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);

const fmtDate = (d) =>
    d ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(d)) : '-';

const statusBadge = (s) => ({
    draft:    'bg-gray-100 text-gray-600',
    reviewed: 'bg-yellow-100 text-yellow-700',
    approved: 'bg-blue-100 text-blue-700',
    paid:     'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-600',
    closed:   'bg-purple-100 text-purple-700',
}[s] ?? 'bg-gray-100 text-gray-500');

const maxBranch = Math.max(...(props.employeesByBranch.map(b => b.count) || [1]), 1);
const maxDept   = Math.max(...(props.employeesByDept.map(d => d.count)   || [1]), 1);
const maxNetto  = Math.max(...(props.payrollTrend.map(t => Number(t.netto)) || [1]), 1);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- ─── Stat Cards ──────────────────────────────────────────── -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-indigo-500">
                        <p class="text-xs text-gray-400 uppercase font-medium">Karyawan Aktif</p>
                        <p class="text-3xl font-bold text-indigo-700 mt-1">{{ stats.total_employees }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ stats.inactive_employees }} nonaktif</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-emerald-500">
                        <p class="text-xs text-gray-400 uppercase font-medium">Cabang</p>
                        <p class="text-3xl font-bold text-emerald-700 mt-1">{{ stats.total_branches }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
                        <p class="text-xs text-gray-400 uppercase font-medium">Departemen</p>
                        <p class="text-3xl font-bold text-blue-700 mt-1">{{ stats.total_departments }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-amber-500">
                        <p class="text-xs text-gray-400 uppercase font-medium">Jabatan</p>
                        <p class="text-3xl font-bold text-amber-700 mt-1">{{ stats.total_positions }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-rose-500 cursor-pointer hover:shadow-md transition"
                         @click="$inertia.visit(route('payroll.index'))">
                        <p class="text-xs text-gray-400 uppercase font-medium">Periode Aktif</p>
                        <p class="text-3xl font-bold text-rose-600 mt-1">{{ activePeriods.length }}</p>
                        <p class="text-xs text-indigo-500 mt-1 hover:underline">Lihat semua →</p>
                    </div>
                </div>

                <!-- ─── Row 2: Payroll Terakhir + Periode Aktif ──────────────── -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Ringkasan Gaji Terakhir Dibayar -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                            <h3 class="font-semibold text-gray-700">Payroll Terakhir Dibayar</h3>
                            <Link v-if="lastPaidPeriod" :href="route('payroll.show', lastPaidPeriod.id)"
                                  class="text-xs text-indigo-500 hover:underline">Detail →</Link>
                        </div>
                        <div v-if="lastPaidPeriod && lastPaidSummary" class="p-6 space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-semibold text-gray-700">{{ lastPaidPeriod.code }}</span>
                                <span class="text-xs text-gray-400">Bayar: {{ fmtDate(lastPaidPeriod.pay_date) }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-green-50 rounded-lg p-3 text-center">
                                    <p class="text-xs text-gray-400">Total Bruto</p>
                                    <p class="text-sm font-bold text-green-700">{{ fmt(lastPaidSummary.bruto) }}</p>
                                </div>
                                <div class="bg-red-50 rounded-lg p-3 text-center">
                                    <p class="text-xs text-gray-400">Total Potongan</p>
                                    <p class="text-sm font-bold text-red-600">{{ fmt(lastPaidSummary.deduction) }}</p>
                                </div>
                                <div class="bg-blue-50 rounded-lg p-3 text-center col-span-2">
                                    <p class="text-xs text-gray-400">Total Netto (Dibayarkan)</p>
                                    <p class="text-xl font-bold text-blue-700">{{ fmt(lastPaidSummary.netto) }}</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">{{ lastPaidSummary.employees }} karyawan</p>
                        </div>
                        <div v-else class="p-10 text-center text-gray-400 italic text-sm">
                            Belum ada payroll yang dibayar.
                        </div>
                    </div>

                    <!-- Periode Aktif (draft/review/approved) -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                            <h3 class="font-semibold text-gray-700">Periode Berjalan</h3>
                            <Link :href="route('payroll.index')" class="text-xs text-indigo-500 hover:underline">Semua →</Link>
                        </div>
                        <div v-if="activePeriods.length" class="divide-y">
                            <div v-for="p in activePeriods" :key="p.id"
                                 class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 transition">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ p.code }}</p>
                                    <p class="text-xs text-gray-400">{{ fmtDate(p.start_date) }} — {{ fmtDate(p.end_date) }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-gray-400">{{ p.items_count }} karyawan</span>
                                    <span :class="['px-2 py-0.5 rounded-full text-xs font-medium capitalize', statusBadge(p.status)]">
                                        {{ p.status }}
                                    </span>
                                    <Link :href="route('payroll.show', p.id)" class="text-xs text-indigo-500 hover:underline">→</Link>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-10 text-center text-gray-400 italic text-sm">
                            Tidak ada periode aktif saat ini.
                        </div>
                    </div>
                </div>

                <!-- ─── Row 3: Bar Karyawan per Cabang & Departemen ──────────── -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Karyawan per Cabang -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-700 mb-4">Karyawan per Cabang (Top 5)</h3>
                        <div class="space-y-3">
                            <div v-for="b in employeesByBranch" :key="b.name">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium text-gray-700 truncate">{{ b.name }}</span>
                                    <span class="text-gray-500 ml-2 shrink-0">{{ b.count }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2.5">
                                    <div class="bg-indigo-500 h-2.5 rounded-full transition-all duration-500"
                                         :style="{ width: Math.round((b.count / maxBranch) * 100) + '%' }"></div>
                                </div>
                            </div>
                            <p v-if="!employeesByBranch.length" class="text-sm text-gray-400 italic">Belum ada data.</p>
                        </div>
                    </div>

                    <!-- Karyawan per Departemen -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-700 mb-4">Karyawan per Departemen (Top 5)</h3>
                        <div class="space-y-3">
                            <div v-for="d in employeesByDept" :key="d.name">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium text-gray-700 truncate">{{ d.name }}</span>
                                    <span class="text-gray-500 ml-2 shrink-0">{{ d.count }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2.5">
                                    <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500"
                                         :style="{ width: Math.round((d.count / maxDept) * 100) + '%' }"></div>
                                </div>
                            </div>
                            <p v-if="!employeesByDept.length" class="text-sm text-gray-400 italic">Belum ada data.</p>
                        </div>
                    </div>
                </div>

                <!-- ─── Row 4: Tren Netto 6 Periode Terakhir ─────────────────── -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-700 mb-4">Tren Total Gaji Netto (6 Periode Terakhir)</h3>
                    <div v-if="payrollTrend.length" class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600">
                            <thead class="text-xs text-gray-500 uppercase border-b">
                                <tr>
                                    <th class="py-2 pr-6">Periode</th>
                                    <th class="py-2 pr-6">Tanggal Bayar</th>
                                    <th class="py-2 pr-6 text-center">Karyawan</th>
                                    <th class="py-2">Total Netto</th>
                                    <th class="py-2 w-1/3">Bar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="t in payrollTrend" :key="t.code" class="border-t hover:bg-gray-50">
                                    <td class="py-2.5 pr-6 font-medium text-gray-800">{{ t.code }}</td>
                                    <td class="py-2.5 pr-6 text-gray-500">{{ fmtDate(t.pay_date) }}</td>
                                    <td class="py-2.5 pr-6 text-center">{{ t.employees }}</td>
                                    <td class="py-2.5 pr-4 font-semibold text-blue-700">{{ fmt(t.netto) }}</td>
                                    <td class="py-2.5">
                                        <div class="w-full bg-gray-100 rounded-full h-3">
                                            <div class="bg-blue-500 h-3 rounded-full"
                                                 :style="{ width: Math.round((Number(t.netto) / maxNetto) * 100) + '%' }"></div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-sm text-gray-400 italic">Belum ada periode yang selesai dibayar.</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
