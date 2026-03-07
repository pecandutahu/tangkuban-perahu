<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    statuses: {
        type: Array,
        default: () => []
    }
});

const isModalOpen = ref(false);
const modalMode = ref('add'); // 'add' or 'edit'

const form = useForm({
    id: null,
    code: '',
    amount: '',
    description: ''
});

const formDelete = useForm({});

const openModal = (mode, status = null) => {
    modalMode.value = mode;
    if (mode === 'edit' && status) {
        form.id = status.id;
        form.code = status.code;
        form.amount = status.amount;
        form.description = status.description || '';
    } else {
        form.reset();
        form.id = null;
    }
    form.clearErrors();
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const submitForm = () => {
    if (modalMode.value === 'add') {
        form.post(route('ptkp-statuses.store'), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.put(route('ptkp-statuses.update', form.id), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteStatus = (statusId) => {
    if (confirm('Yakin ingin menghapus kode status ini? Aksi ini akan mempengaruhi Generate Payroll!')) {
        formDelete.delete(route('ptkp-statuses.destroy', statusId));
    }
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
};
</script>

<template>
    <Head title="Master Data PTKP" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Master Data Status PTKP</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="$page.props.flash?.message" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ $page.props.flash.message }}</span>
                </div>
                
                <div v-if="$page.props.errors?.message" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ $page.props.errors.message }}</span>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-6 border-b pb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">Daftar Status PTKP</h3>
                                <p class="text-sm text-gray-500">Nilai rujukan Penghasilan Tidak Kena Pajak per Tahun berdasarkan Status Kawin & Tanggungan.</p>
                            </div>
                            <PrimaryButton @click="openModal('add')">
                                + Tambah Status Baru
                            </PrimaryButton>
                        </div>

                        <div class="overflow-x-auto relative shadow-sm sm:rounded-lg border border-gray-200">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th scope="col" class="py-3 px-6">Kode PTKP</th>
                                        <th scope="col" class="py-3 px-6 text-right">Nilai PTKP (Rp) / Tahun</th>
                                        <th scope="col" class="py-3 px-6">Keterangan</th>
                                        <th scope="col" class="py-3 px-6 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="status in statuses" :key="status.id" class="bg-white border-b hover:bg-gray-50">
                                        <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap">
                                            <span class="bg-indigo-100 text-indigo-800 text-sm font-semibold mr-2 px-2.5 py-0.5 rounded">{{ status.code }}</span>
                                        </td>
                                        <td class="py-4 px-6 text-right font-medium">
                                            {{ formatRupiah(status.amount) }}
                                        </td>
                                        <td class="py-4 px-6">
                                            {{ status.description || '-' }}
                                        </td>
                                        <td class="py-4 px-6 text-center space-x-2">
                                            <button @click="openModal('edit', status)" class="text-blue-600 hover:text-blue-900 font-medium">Edit</button>
                                            <button v-if="status.code !== 'TK/0'" @click="deleteStatus(status.id)" class="text-red-600 hover:text-red-900 font-medium ml-2">Hapus</button>
                                        </td>
                                    </tr>
                                    <tr v-if="statuses.length === 0">
                                        <td colspan="4" class="py-4 px-6 text-center text-gray-500">Tidak ada data PTKP ditemukan. Jalankan Migrasi Seeder.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Dialog -->
                <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">
                            {{ modalMode === 'add' ? 'Tambah Status PTKP' : 'Edit Status PTKP' }}
                        </h2>

                        <form @submit.prevent="submitForm">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kode Status PTKP <span class="text-red-500">*</span></label>
                                <input v-model="form.code" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: TK/0" required>
                                <p class="text-xs text-gray-500 mt-1">Gunakan kode standar pajak, max 20 karakter.</p>
                                <InputError :message="form.errors.code" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nominal PTKP (Per Tahun) <span class="text-red-500">*</span></label>
                                <input v-model="form.amount" type="number" min="0" step="1000" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="54000000" required>
                                <InputError :message="form.errors.amount" class="mt-2" />
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan Singkat</label>
                                <input v-model="form.description" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: Tidak Kawin Tanpa Tanggungan">
                                <InputError :message="form.errors.description" class="mt-2" />
                            </div>

                            <div class="flex justify-end space-x-3 pt-4 border-t">
                                <SecondaryButton @click="closeModal" type="button">Batal</SecondaryButton>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Simpan
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Modal>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
