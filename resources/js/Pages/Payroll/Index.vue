<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    periods: Object,
});

const generateModal = ref(false);

const form = useForm({
    period_type: 'monthly',
    start_date: '',
    end_date: '',
    pay_date: '',
});

const openGenerateModal = () => {
    generateModal.value = true;
};

const closeGenerateModal = () => {
    generateModal.value = false;
    form.reset();
};

const generateDraft = () => {
    window.axios.post('/payroll-periods/generate', form.data())
        .then(() => {
            closeGenerateModal();
            window.location.reload(); 
        })
        .catch((err) => {
            alert('Gagal: ' + (err.response?.data?.message || err.message));
        });
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    // Gunakan tanggal dengan format Indonesia: 17 Agustus 1945
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(dateString));
};

// Map routes, assumes the API route exists
// NOTE: for the UI to query the API from Inertia form without a dedicated web POST route,
// the user needs to ensure API routes are accessible via session-based auth (Sanctum default).
// In Breeze, web routes are preferred for forms to get validation data back via Inertia props.
// Let's create a dedicated WEB route for generating so it works seamlessly with Inertia validation.
</script>

<template>
    <Head title="Manajemen Penggajian" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Periode Penggajian
                </h2>
                <PrimaryButton v-if="$page.props.auth.user.permissions.includes('generate-payroll')" @click="openGenerateModal">
                    + Generate Draft Baru
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <table class="w-full text-left text-sm text-gray-500">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3">ID Periode</th>
                                    <th scope="col" class="px-6 py-3">Tipe</th>
                                    <th scope="col" class="px-6 py-3">Tgl Mulai - Selesai</th>
                                    <th scope="col" class="px-6 py-3">Tgl Gajian</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">Items</th>
                                    <th scope="col" class="px-6 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="period in periods.data" :key="period.id" class="border-b bg-white">
                                    <th scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">
                                        {{ period.code }}
                                    </th>
                                    <td class="px-6 py-4 capitalize">{{ period.period_type }}</td>
                                    <td class="px-6 py-4">{{ formatDate(period.start_date) }} s/d {{ formatDate(period.end_date) }}</td>
                                    <td class="px-6 py-4 font-bold">{{ formatDate(period.pay_date) }}</td>
                                    <td class="px-6 py-4">
                                        <span class="rounded bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800 capitalize">
                                            {{ period.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ period.items_count }} Karyawan
                                    </td>
                                    <td class="px-6 py-4 font-medium">
                                        <Link :href="route('payroll.show', period.id)" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                            Lihat Detail
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="periods.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        Tidak ada data periode penggajian. Silakan Generate Draft baru.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Simple Pagination controls could go here -->
                        <div class="mt-4 flex justify-end" v-if="periods.prev_page_url || periods.next_page_url">
                            <!-- Inertia links for pagination -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="generateModal" @close="closeGenerateModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Generate Baru (Draft)</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Sistem akan mengambil seluruh karyawan aktif dan menyusun draft awal gaji berdasarkan komponen default.
                </p>

                <div class="mt-6">
                    <InputLabel for="period_type" value="Tipe Periode" />
                    <select
                        id="period_type"
                        v-model="form.period_type"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="weekly">Mingguan (Weekly)</option>
                        <option value="monthly">Bulanan (Monthly)</option>
                    </select>
                    <InputError :message="form.errors.period_type" class="mt-2" />
                </div>

                <div class="mt-4 flex space-x-4">
                    <div class="w-1/2">
                        <InputLabel for="start_date" value="Start Date" />
                        <TextInput
                            id="start_date"
                            type="date"
                            v-model="form.start_date"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.start_date" class="mt-2" />
                    </div>
                    <div class="w-1/2">
                        <InputLabel for="end_date" value="End Date" />
                        <TextInput
                            id="end_date"
                            type="date"
                            v-model="form.end_date"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.end_date" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel for="pay_date" value="Payment Date (Rencana Bayar)" />
                    <TextInput
                        id="pay_date"
                        type="date"
                        v-model="form.pay_date"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.pay_date" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeGenerateModal">
                        Batal
                    </SecondaryButton>

                    <PrimaryButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="generateDraft"
                    >
                        Generate Draft
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
