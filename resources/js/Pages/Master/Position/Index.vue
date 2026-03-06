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
    positions: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('positions.index'), { search: value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const isModalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    name: '',
});

const openModal = (position = null) => {
    isEditing.value = !!position;
    if (position) {
        form.id = position.id;
        form.name = position.name;
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
        form.put(route('positions.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('positions.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteData = (id) => {
    if (confirm('Yakin ingin menghapus data Jabatan ini?')) {
        router.delete(route('positions.destroy', id));
    }
};
</script>

<template>
    <Head title="Master Jabatan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Master Jabatan (Position)
                </h2>
                <PrimaryButton v-if="$page.props.auth.user.permissions.includes('create-master-data')" @click="openModal()">+ Tambah Jabatan</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Search and Data Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <div class="w-1/3">
                            <TextInput v-model="search" type="text" class="block w-full text-sm" placeholder="Cari Jabatan..." />
                        </div>
                    </div>
                    
                    <div class="p-6 text-gray-900">
                        <table class="w-full text-left text-sm text-gray-500 mb-4 border border-gray-200">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 border-b">ID</th>
                                    <th scope="col" class="px-6 py-3 border-b">Nama Jabatan</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center" v-if="$page.props.auth.user.permissions.includes('edit-master-data') || $page.props.auth.user.permissions.includes('delete-master-data')">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in positions.data" :key="item.id" class="border-b bg-white hover:bg-gray-50">
                                    <td class="px-6 py-4 font-semibold text-gray-900 border-r">{{ item.id }}</td>
                                    <td class="px-6 py-4 border-r">{{ item.name }}</td>
                                    <td class="px-6 py-4 text-center space-x-2" v-if="$page.props.auth.user.permissions.includes('edit-master-data') || $page.props.auth.user.permissions.includes('delete-master-data')">
                                        <button v-if="$page.props.auth.user.permissions.includes('edit-master-data')" @click="openModal(item)" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">Edit</button>
                                        <button v-if="$page.props.auth.user.permissions.includes('delete-master-data')" @click="deleteData(item.id)" class="text-red-600 hover:text-red-900 font-medium hover:underline">Hapus</button>
                                    </td>
                                </tr>
                                <tr v-if="positions.data.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">Tidak ada data jabatan.</td>
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
                    {{ isEditing ? 'Edit Jabatan' : 'Tambah Jabatan Baru' }}
                </h2>

                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <InputLabel for="name" value="Nama Jabatan" />
                        <TextInput id="name" type="text" v-model="form.name" class="mt-1 block w-full" autofocus />
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
