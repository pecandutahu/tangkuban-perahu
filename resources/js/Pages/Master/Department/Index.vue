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
    departments: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('departments.index'), { search: value }, {
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
});

const openModal = (department = null) => {
    isEditing.value = !!department;
    if (department) {
        form.id = department.id;
        form.code = department.code;
        form.name = department.name;
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
        form.put(route('departments.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('departments.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteData = (id) => {
    if (confirm('Yakin ingin menghapus data Departemen ini?')) {
        router.delete(route('departments.destroy', id));
    }
};
</script>

<template>
    <Head title="Master Departemen" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Master Departemen
                </h2>
                <PrimaryButton v-if="$page.props.auth.user.permissions.includes('create-master-data')" @click="openModal()">+ Tambah Departemen</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Search and Data Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <div class="w-1/3">
                            <TextInput v-model="search" type="text" class="block w-full text-sm" placeholder="Cari Kode atau Nama Departemen..." />
                        </div>
                    </div>
                    
                    <div class="p-6 text-gray-900">
                        <table class="w-full text-left text-sm text-gray-500 mb-4 border border-gray-200">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 border-b">Kode Departemen</th>
                                    <th scope="col" class="px-6 py-3 border-b">Nama Departemen</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center" v-if="$page.props.auth.user.permissions.includes('edit-master-data') || $page.props.auth.user.permissions.includes('delete-master-data')">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in departments.data" :key="item.id" class="border-b bg-white hover:bg-gray-50">
                                    <td class="px-6 py-4 font-semibold text-gray-900 border-r">{{ item.code }}</td>
                                    <td class="px-6 py-4 border-r">{{ item.name }}</td>
                                    <td class="px-6 py-4 text-center space-x-2" v-if="$page.props.auth.user.permissions.includes('edit-master-data') || $page.props.auth.user.permissions.includes('delete-master-data')">
                                        <button v-if="$page.props.auth.user.permissions.includes('edit-master-data')" @click="openModal(item)" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">Edit</button>
                                        <button v-if="$page.props.auth.user.permissions.includes('delete-master-data')" @click="deleteData(item.id)" class="text-red-600 hover:text-red-900 font-medium hover:underline">Hapus</button>
                                    </td>
                                </tr>
                                <tr v-if="departments.data.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">Tidak ada data departemen.</td>
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
                    {{ isEditing ? 'Edit Departemen' : 'Tambah Departemen Baru' }}
                </h2>

                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <InputLabel for="code" value="Kode Departemen" />
                        <TextInput id="code" type="text" v-model="form.code" class="mt-1 block w-full" autofocus />
                        <InputError :message="form.errors.code" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <InputLabel for="name" value="Nama Departemen" />
                        <TextInput id="name" type="text" v-model="form.name" class="mt-1 block w-full" />
                        <InputError :message="form.errors.name" class="mt-2" />
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
