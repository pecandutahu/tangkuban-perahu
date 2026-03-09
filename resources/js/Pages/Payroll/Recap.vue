<script setup>
import { ref } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    period:  Object,
    summary: Object,
    grouped: Array,   // [ { name, departments: [ { name, positions: [ {...} ] } ] } ]
});

const fmt = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
const fmtDate = (d) => d ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(d)) : '-';

// Collapsible state — keluarkan per cabang dan per departemen
const openBranches = ref(new Set(props.grouped.map(b => b.name)));
const openDepts    = ref(new Set());   // defaultnya semua dept tertutup, user expand manual

const toggleBranch = (name) => openBranches.value.has(name) ? openBranches.value.delete(name) : openBranches.value.add(name);
const toggleDept   = (key)  => openDepts.value.has(key)     ? openDepts.value.delete(key)     : openDepts.value.add(key);

const exportUrl = (type) => `/payroll-periods/${props.period.id}/export-${type}`;
</script>

<template>
    <Head :title="`Rekap Payroll — ${period.code}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-start flex-wrap gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Rekap Payroll — {{ period.code }}</h2>
                    <p class="text-sm text-gray-500 mt-0.5">{{ fmtDate(period.start_date) }} s.d. {{ fmtDate(period.end_date) }}</p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <a :href="exportUrl('csv')"  class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition">⬇ Export CSV Rekap</a>
                    <a :href="exportUrl('bank')" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">🏦 Export Transfer Bank</a>
                    <Link :href="route('payroll.show', period.id)" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition">← Kembali</Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Summary Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-indigo-500">
                        <p class="text-xs text-gray-500 uppercase font-medium">Total Karyawan</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ summary.total_employees }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
                        <p class="text-xs text-gray-500 uppercase font-medium">Total Bruto</p>
                        <p class="text-xl font-bold text-green-700 mt-1">{{ fmt(summary.total_bruto) }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-red-400">
                        <p class="text-xs text-gray-500 uppercase font-medium">Total Potongan</p>
                        <p class="text-xl font-bold text-red-600 mt-1">{{ fmt(summary.total_deduction) }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
                        <p class="text-xs text-gray-500 uppercase font-medium">Total Netto</p>
                        <p class="text-xl font-bold text-blue-700 mt-1">{{ fmt(summary.total_netto) }}</p>
                    </div>
                </div>

                <!-- Hierarki: Cabang → Departemen → Jabatan -->
                <div v-for="branch in grouped" :key="branch.name" class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">

                    <!-- Header CABANG -->
                    <div
                        class="flex items-center justify-between px-6 py-4 bg-gray-800 text-white cursor-pointer select-none"
                        @click="toggleBranch(branch.name)"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-lg font-bold">🏢 {{ branch.name }}</span>
                            <span class="text-xs bg-gray-600 text-gray-200 px-2 py-0.5 rounded-full">{{ branch.employee_count }} karyawan</span>
                        </div>
                        <div class="flex items-center gap-6 text-sm">
                            <span class="text-green-300">Bruto: {{ fmt(branch.total_bruto) }}</span>
                            <span class="text-red-300">Pot: {{ fmt(branch.total_deduction) }}</span>
                            <span class="font-bold text-blue-200">Netto: {{ fmt(branch.total_netto) }}</span>
                            <span class="ml-2 text-gray-400">{{ openBranches.has(branch.name) ? '▲' : '▼' }}</span>
                        </div>
                    </div>

                    <!-- DEPARTEMEN loop (collapsible per cabang) -->
                    <template v-if="openBranches.has(branch.name)">
                        <div v-for="dept in branch.departments" :key="dept.name" class="border-t border-gray-100">

                            <!-- Header DEPARTEMEN -->
                            <div
                                class="flex items-center justify-between px-8 py-3 bg-indigo-50 cursor-pointer select-none hover:bg-indigo-100 transition"
                                @click="toggleDept(branch.name + dept.name)"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="font-semibold text-indigo-800">📂 {{ dept.name }}</span>
                                    <span class="text-xs text-indigo-500">{{ dept.employee_count }} karyawan</span>
                                </div>
                                <div class="flex items-center gap-6 text-sm">
                                    <span class="text-green-600">{{ fmt(dept.total_bruto) }}</span>
                                    <span class="text-red-500">{{ fmt(dept.total_deduction) }}</span>
                                    <span class="font-semibold text-blue-700">{{ fmt(dept.total_netto) }}</span>
                                    <span class="text-gray-400 text-xs">{{ openDepts.has(branch.name + dept.name) ? '▲' : '▼' }}</span>
                                </div>
                            </div>

                            <!-- Tabel JABATAN (collapsible per departemen) -->
                            <template v-if="openDepts.has(branch.name + dept.name)">
                                <table class="w-full text-sm text-left text-gray-600">
                                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase border-t border-b">
                                        <tr>
                                            <th class="pl-14 pr-6 py-2">Jabatan</th>
                                            <th class="px-6 py-2 text-center">Karyawan</th>
                                            <th class="px-6 py-2 text-right">Bruto</th>
                                            <th class="px-6 py-2 text-right">Potongan</th>
                                            <th class="px-6 py-2 text-right font-semibold">Netto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="pos in dept.positions" :key="pos.name" class="border-t hover:bg-gray-50">
                                            <td class="pl-14 pr-6 py-2.5 font-medium text-gray-700">{{ pos.name }}</td>
                                            <td class="px-6 py-2.5 text-center text-gray-500">{{ pos.employee_count }}</td>
                                            <td class="px-6 py-2.5 text-right text-green-600">{{ fmt(pos.total_bruto) }}</td>
                                            <td class="px-6 py-2.5 text-right text-red-500">{{ fmt(pos.total_deduction) }}</td>
                                            <td class="px-6 py-2.5 text-right font-semibold text-blue-700">{{ fmt(pos.total_netto) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </template>

                        </div>
                    </template>

                </div>

                <div v-if="!grouped.length" class="bg-white rounded-xl shadow-sm p-12 text-center text-gray-400 italic">
                    Tidak ada data payroll untuk periode ini.
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
