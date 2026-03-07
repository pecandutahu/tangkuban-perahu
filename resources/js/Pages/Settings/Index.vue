<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    components: { type: Array, default: () => [] }
});

const bpjsDefaults = {
    jht_employee: 2.0,
    jp_employee: 1.0,
    kes_employee: 1.0,
    jht_company: 3.7,
    jp_company: 2.0,
    jkk_company: 0.24,
    jkm_company: 0.3,
    kes_company: 4.0,
    kes_salary_cap: 12000000,
};

const form = useForm({
    settings: {
        pph21_calculator_version: props.settings.pph21_calculator_version || 'ter_2024',
        pph21_excluded_components: props.settings.pph21_excluded_components || [],
        bpjs_rates: Object.assign({}, bpjsDefaults, props.settings.bpjs_rates || {}),
    }
});

const isSubmitting = ref(false);

const saveSettings = () => {
    isSubmitting.value = true;
    form.post('/settings', {
        preserveScroll: true,
        onSuccess: () => { isSubmitting.value = false; },
        onError:   () => { isSubmitting.value = false; }
    });
};

// Format helper for rate input labels
const pctField = (label, key, hint = '') => ({ label, key, hint });

const employeeRateFields = [
    pctField('JHT Karyawan (%)', 'jht_employee', 'Umumnya 2%'),
    pctField('JP Karyawan (%)',  'jp_employee',  'Umumnya 1%'),
    pctField('BPJS Kesehatan Karyawan (%)', 'kes_employee', 'Umumnya 1%'),
];

const companyRateFields = [
    pctField('JHT Perusahaan (%)', 'jht_company', 'Umumnya 3.7%'),
    pctField('JP Perusahaan (%)',  'jp_company',  'Umumnya 2%'),
    pctField('JKK Perusahaan (%)', 'jkk_company', 'Risiko rendah: 0.24% | Sedang: 0.54% | Tinggi: 0.89%–1.74%'),
    pctField('JKm Perusahaan (%)', 'jkm_company', 'Umumnya 0.3%'),
    pctField('BPJS Kesehatan Perusahaan (%)', 'kes_company', 'Umumnya 4%'),
];
</script>

<template>
    <Head title="Pengaturan Sistem" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pengaturan Sistem</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Notifikasi -->
                <div v-if="$page.props.flash?.message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ $page.props.flash.message }}
                </div>

                <form @submit.prevent="saveSettings" class="space-y-6">

                    <!-- ====== BAGIAN PPh 21 ====== -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-1">Konfigurasi PPh 21</h3>
                            <p class="text-sm text-gray-500 mb-5">Pengaturan regulasi perhitungan pajak penghasilan.</p>

                            <!-- Versi PPh 21 -->
                            <div class="mb-6 max-w-xl">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Versi Kalkulator PPh 21 Aktif</label>
                                <select
                                    v-model="form.settings.pph21_calculator_version"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="none">Kosong (Nonaktifkan Potongan Pajak)</option>
                                    <option value="ter_2024">Peraturan TER 2024 (Aktif saat ini)</option>
                                    <option value="regulasi_2026">Regulasi 2026 (Placeholder)</option>
                                </select>
                            </div>

                            <!-- Komponen pengecualian PPh -->
                            <div class="max-w-xl">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pengecualian dari Bruto Kena Pajak</label>
                                <p class="text-xs text-gray-500 mb-3">Komponen Deduction di bawah ini yang dicentang akan dikurangi dari bruto sebelum dikenakan pajak.</p>
                                <div class="bg-gray-50 p-4 rounded-md border border-gray-200">
                                    <div v-for="comp in components" :key="comp.id" class="flex items-center mb-2 last:mb-0">
                                        <input
                                            type="checkbox"
                                            :value="comp.id"
                                            v-model="form.settings.pph21_excluded_components"
                                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <label class="ml-3 text-sm text-gray-700 cursor-pointer">{{ comp.name }} <span class="text-gray-400">({{ comp.code }})</span></label>
                                    </div>
                                    <div v-if="components.length === 0" class="text-sm text-red-500 italic">
                                        Belum ada komponen Deduction yang terdaftar.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ====== BAGIAN BPJS ====== -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-1">Konfigurasi Tarif BPJS</h3>
                            <p class="text-sm text-gray-500 mb-5">
                                Tarif iuran BPJS dalam persen (%). Perubahan akan berlaku di sinkronisasi BPJS berikutnya.
                                <br>Tarif dasar merujuk peraturan pemerintah yang berlaku.
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                                <!-- Karyawan -->
                                <div>
                                    <h4 class="font-semibold text-sm text-gray-600 uppercase tracking-wide mb-3 border-b pb-1">Iuran Karyawan</h4>
                                    <div v-for="field in employeeRateFields" :key="field.key" class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ field.label }}</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            v-model="form.settings.bpjs_rates[field.key]"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        />
                                        <p class="text-xs text-gray-400 mt-1">{{ field.hint }}</p>
                                    </div>
                                </div>

                                <!-- Perusahaan -->
                                <div>
                                    <h4 class="font-semibold text-sm text-gray-600 uppercase tracking-wide mb-3 border-b pb-1">Iuran Perusahaan</h4>
                                    <div v-for="field in companyRateFields" :key="field.key" class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ field.label }}</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            v-model="form.settings.bpjs_rates[field.key]"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        />
                                        <p class="text-xs text-gray-400 mt-1">{{ field.hint }}</p>
                                    </div>

                                    <!-- Plafon BPJS Kes -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Plafon Gaji BPJS Kesehatan (Rp)</label>
                                        <input
                                            type="number"
                                            step="1"
                                            v-model="form.settings.bpjs_rates.kes_salary_cap"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        />
                                        <p class="text-xs text-gray-400 mt-1">Umumnya Rp 12.000.000 — batas gaji yang dijadikan basis BPJS Kesehatan.</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Simpan -->
                    <div class="flex items-center justify-end">
                        <PrimaryButton :class="{ 'opacity-25': isSubmitting }" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Menyimpan...' : 'Simpan Semua Pengaturan' }}
                        </PrimaryButton>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
