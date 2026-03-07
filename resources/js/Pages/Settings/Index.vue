<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    settings: {
        pph21_calculator_version: props.settings.pph21_calculator_version || 'ter_2024',
    }
});

const isSubmitting = ref(false);

const saveSettings = () => {
    isSubmitting.value = true;
    form.post('/settings', {
        preserveScroll: true,
        onSuccess: () => {
            alert('Pengaturan PPh 21 berhasil diperbarui.');
            isSubmitting.value = false;
        },
        onError: () => {
            isSubmitting.value = false;
        }
    });
};
</script>

<template>
    <Head title="Pengaturan Sistem" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pengaturan Sistem</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Notifikasi -->
                <div v-if="$page.props.flash?.message" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ $page.props.flash.message }}</span>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2">Konfigurasi Penggajian & Pajak</h3>
                        
                        <form @submit.prevent="saveSettings">
                            <!-- Versi PPh 21 -->
                            <div class="mb-6 max-w-xl">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Versi Kalkulator PPh 21 Aktif
                                </label>
                                <p class="text-sm text-gray-500 mb-3">Pilih regulasi rumus perhitungan pajak penghasilan PPh 21 yang akan digunakan sistem saat me-mproses Slip Gaji (Generate Payroll).</p>
                                
                                <select 
                                    v-model="form.settings.pph21_calculator_version"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="none">Kosong (Nonaktifkan Potongan Pajak)</option>
                                    <option value="ter_2024">Peraturan TER 2024 (Aktif saat ini)</option>
                                    <option value="regulasi_2026">Regulasi 2026 (Placeholder / Uji Coba Pendatang)</option>
                                </select>
                                <InputError :message="form.errors['settings.pph21_calculator_version']" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end pt-4 border-t border-gray-100">
                                <PrimaryButton :class="{ 'opacity-25': isSubmitting }" :disabled="isSubmitting">
                                    {{ isSubmitting ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>
