<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    components: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('payroll-components.index'), { search: value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const isModalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    code: '',
    name: '',
    component_type: 'earning',
    is_variable: false,
    default_amount: 0,
    is_active: true,
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
};

const openModal = (comp = null) => {
    isEditing.value = !!comp;
    if (comp) {
        form.id = comp.id;
        form.code = comp.code;
        form.name = comp.name;
        form.component_type = comp.component_type;
        form.is_variable = !!comp.is_variable;
        form.default_amount = comp.default_amount;
        form.is_active = !!comp.is_active;
    } else {
        form.reset();
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('payroll-components.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('payroll-components.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteData = (id) => {
    if (confirm('Yakin ingin menghapus data Komponen Gaji ini?')) {
        router.delete(route('payroll-components.destroy', id));
    }
};
</script>

<template>
    <Head title="Master Komponen Gaji" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Master Komponen Gaji
                </h2>
                <PrimaryButton v-if="$page.props.auth.user.permissions.includes('create-master-data')" @click="openModal()">+ Tambah Komponen</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Search and Data Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <div class="w-1/3">
                            <TextInput v-model="search" type="text" class="block w-full text-sm" placeholder="Cari Kode atau Nama Komponen..." />
                        </div>
                    </div>

                    <div class="overflow-x-auto p-6 text-gray-900">
                        <table class="w-full text-left text-sm text-gray-500 mb-4 border border-gray-200">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 border-b">Kode</th>
                                    <th scope="col" class="px-6 py-3 border-b">Nama Komponen</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center">Tipe</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center">Variabel?</th>
                                    <th scope="col" class="px-6 py-3 border-b text-right">Default Amount</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center">Status</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center" v-if="$page.props.auth.user.permissions.includes('edit-master-data') || $page.props.auth.user.permissions.includes('delete-master-data')">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in components.data" :key="item.id" class="border-b bg-white hover:bg-gray-50">
                                    <td class="px-6 py-4 font-semibold text-gray-900 border-r">{{ item.code }}</td>
                                    <td class="px-6 py-4 border-r">{{ item.name }}</td>
                                    <td class="px-6 py-4 text-center border-r capitalize">
                                        <span :class="item.component_type === 'earning' ? 'text-green-600' : 'text-red-600'" class="font-bold">
                                            {{ item.component_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center border-r">
                                        <span v-if="item.is_variable" class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Ya</span>
                                        <span v-else class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">Tidak (Tetap)</span>
                                    </td>
                                    <td class="px-6 py-4 text-right border-r">{{ formatCurrency(item.default_amount) }}</td>
                                    <td class="px-6 py-4 text-center border-r">
                                        <span v-if="item.is_active" class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Aktif</span>
                                        <span v-else class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Nonaktif</span>
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2" v-if="$page.props.auth.user.permissions.includes('edit-master-data') || $page.props.auth.user.permissions.includes('delete-master-data')">
                                        <button v-if="$page.props.auth.user.permissions.includes('edit-master-data')" @click="openModal(item)" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">Edit</button>
                                        <button v-if="$page.props.auth.user.permissions.includes('delete-master-data')" @click="deleteData(item.id)" class="text-red-600 hover:text-red-900 font-medium hover:underline">Hapus</button>
                                    </td>
                                </tr>
                                <tr v-if="components.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">Tidak ada data komponen.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Modal -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ isEditing ? 'Edit Komponen' : 'Tambah Komponen Baru' }}
                </h2>

                <form @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <InputLabel for="code" value="Kode Komponen" />
                            <TextInput id="code" type="text" v-model="form.code" class="mt-1 block w-full" autofocus />
                            <InputError :message="form.errors.code" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <InputLabel for="name" value="Nama Komponen" />
                            <TextInput id="name" type="text" v-model="form.name" class="mt-1 block w-full" />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <InputLabel for="type" value="Sifat Komponen" />
                            <select id="type" v-model="form.component_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="earning">Penambah (Earning)</option>
                                <option value="deduction">Pengurang (Deduction)</option>
                            </select>
                            <InputError :message="form.errors.component_type" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <InputLabel for="is_variable" value="Variabel / Berubah-ubah?" />
                            <select id="is_variable" v-model="form.is_variable" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option :value="false">TIDAK (Fix/Tetap)</option>
                                <option :value="true">YA (Variabel dari Import)</option>
                            </select>
                            <InputError :message="form.errors.is_variable" class="mt-2" />
                        </div>
                    </div>

                    <div class="mb-4">
                        <InputLabel for="default_amount" value="Nominal Default (Rp)" />
                        <TextInput id="default_amount" type="number" v-model="form.default_amount" class="mt-1 block w-full" />
                        <InputError :message="form.errors.default_amount" class="mt-2" />
                    </div>

                    <div class="mb-4 mt-2">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <span class="ms-2 text-sm text-gray-600">Komponen Aktif</span>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                        <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Simpan
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
