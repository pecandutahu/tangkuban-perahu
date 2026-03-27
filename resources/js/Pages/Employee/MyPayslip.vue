<script setup>
import { Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    employee: Object,
    history:  Object,   // paginated PayrollItem list
    error: String,      // if not linked
});

const fmt = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
const fmtDate = (d) => d ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(d)) : '-';

const statusBadge = (s) => ({
    draft:    'bg-gray-100 text-gray-600',
    reviewed: 'bg-yellow-100 text-yellow-700',
    approved: 'bg-blue-100 text-blue-700',
    paid:     'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-600',
    closed:   'bg-purple-100 text-purple-700',
}[s] ?? 'bg-gray-100 text-gray-500');
</script>

<template>
    <Head title="Slip Gaji Saya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Slip Gaji Saya</h2>
                    <p class="text-sm text-gray-500 mt-0.5" v-if="employee">
                        {{ employee.name }} — {{ employee.position?.name ?? '-' }} | {{ employee.branch?.name ?? '-' }}
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <!-- If Not Linked -->
                <div v-if="error" class="bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm">
                    <p class="text-red-700 font-medium">{{ error }}</p>
                </div> <!-- End Not Linked -->

                <!-- If Linked -->
                <div v-else class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase border-b">
                                <tr>
                                    <th class="px-6 py-3">Periode</th>
                                    <th class="px-6 py-3">Tanggal Gajian</th>
                                    <th class="px-6 py-3 text-right">Bruto</th>
                                    <th class="px-6 py-3 text-right">Potongan</th>
                                    <th class="px-6 py-3 text-right font-semibold">Netto</th>
                                    <th class="px-6 py-3 text-center">Status</th>
                                    <th class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in history.data" :key="item.id" class="border-t hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-800">
                                        {{ item.period?.code ?? '-' }}
                                        <span class="block text-xs text-gray-400">
                                            {{ fmtDate(item.period?.start_date) }} — {{ fmtDate(item.period?.end_date) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3">{{ fmtDate(item.period?.pay_date) }}</td>
                                    <td class="px-6 py-3 text-right text-green-700">{{ fmt(item.total_bruto) }}</td>
                                    <td class="px-6 py-3 text-right text-red-600">{{ fmt(item.total_deduction) }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-blue-700">{{ fmt(item.total_netto) }}</td>
                                    <td class="px-6 py-3 text-center">
                                        <span :class="['px-2 py-0.5 rounded-full text-xs font-medium capitalize', statusBadge(item.period?.status)]">
                                            {{ item.period?.status ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        <a :href="route('payroll.slip', item.id)"
                                           target="_blank"
                                           class="inline-flex items-center justify-center px-4 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-300 rounded text-xs font-bold hover:bg-emerald-100 transition shadow-sm">
                                            ⬇ Cetak / Download PDF
                                        </a>
                                    </td>
                                </tr>
                                <tr v-if="!history.data.length" class="border-t">
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-400 italic">
                                        Anda belum memiliki riwayat payroll yang disetujui.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="history.last_page > 1" class="px-6 py-4 border-t flex gap-2 flex-wrap justify-end">
                        <template v-for="link in history.links" :key="link.label">
                            <Link v-if="link.url"
                                  :href="link.url"
                                  v-html="link.label"
                                  :class="['px-3 py-1 rounded text-sm border', link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50']" />
                            <span v-else
                                  v-html="link.label"
                                  class="px-3 py-1 rounded text-sm border bg-gray-50 text-gray-400 cursor-not-allowed" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
