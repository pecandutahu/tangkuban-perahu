<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    roles: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('roles.index'), { search: value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const deleteData = (id, name) => {
    if (name === 'HR Admin') {
        alert('Maaf, Role HR Admin (Super Admin) terkunci di sistem dan tidak dapat dihapus.');
        return;
    }
    
    if (confirm('Yakin ingin menghapus Peran (Role) ini? Akses user yang terkait mungkin akan hilang!')) {
        router.delete(route('roles.destroy', id));
    }
};
</script>

<template>
    <Head title="Manajemen Peran" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Daftar Manajemen Peran (Roles)
                </h2>
                <Link :href="route('roles.create')">
                    <PrimaryButton>+ Tambah Role Baru</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Search and Data Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <div class="w-1/3">
                            <TextInput v-model="search" type="text" class="block w-full text-sm" placeholder="Cari Nama Role..." />
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto p-6 text-gray-900">
                        <table class="w-full text-left text-sm text-gray-500 mb-4 border border-gray-200">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 border-b">ID</th>
                                    <th scope="col" class="px-6 py-3 border-b">Nama Role</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center">Jumlah Pemakai</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="role in roles.data" :key="role.id" class="border-b bg-white hover:bg-gray-50">
                                    <td class="px-6 py-4 font-semibold text-gray-900 border-r">{{ role.id }}</td>
                                    <td class="px-6 py-4 border-r font-medium text-indigo-600">{{ role.name }}</td>
                                    <td class="px-6 py-4 border-r text-center">
                                        <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                            {{ role.users_count }} Akun
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2 whitespace-nowrap">
                                        <!-- HR Admin locked from basic edit/delete (optional strict mode) -->
                                        <Link :href="route('roles.edit', role.id)" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">
                                            Ubah Akses
                                        </Link>
                                        <button v-if="role.name !== 'HR Admin'" @click="deleteData(role.id, role.name)" class="text-red-600 hover:text-red-900 font-medium hover:underline ml-2">
                                            Hapus
                                        </button>
                                        <span v-else class="text-gray-400 text-xs italic ml-2">Terkunci</span>
                                    </td>
                                </tr>
                                <tr v-if="roles.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada data Role.</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Simple Pagination -->
                        <div class="mt-4 flex justify-end" v-if="roles.prev_page_url || roles.next_page_url">
                            <div class="space-x-2">
                                <Link v-if="roles.prev_page_url" :href="roles.prev_page_url" class="px-4 py-2 border rounded bg-white hover:bg-gray-50 text-sm">Prev</Link>
                                <span class="px-4 py-2 text-sm text-gray-500">Halaman {{ roles.current_page }}</span>
                                <Link v-if="roles.next_page_url" :href="roles.next_page_url" class="px-4 py-2 border rounded bg-white hover:bg-gray-50 text-sm">Next</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
