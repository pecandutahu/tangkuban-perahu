<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('users.index'), { search: value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const deleteData = (id, currentUserId) => {
    if (id === 1 || id === currentUserId) {
        alert('Maaf, Anda tidak dapat menghapus akun ini.');
        return;
    }
    
    if (confirm('Yakin ingin menghapus Pengguna ini secara permanen?')) {
        router.delete(route('users.destroy', id));
    }
};
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Daftar Akun Pengguna (Users)
                </h2>
                <Link :href="route('users.create')">
                    <PrimaryButton>+ Tambah Akun Baru</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Search and Data Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <div class="w-1/3">
                            <TextInput v-model="search" type="text" class="block w-full text-sm" placeholder="Cari Nama / Email..." />
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto p-6 text-gray-900">
                        <table class="w-full text-left text-sm text-gray-500 mb-4 border border-gray-200">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 border-b">Nama</th>
                                    <th scope="col" class="px-6 py-3 border-b">Email Pendaftaran</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center">Jabatan / Role</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users.data" :key="user.id" class="border-b bg-white hover:bg-gray-50">
                                    <td class="px-6 py-4 font-semibold text-gray-900 border-r flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 mr-3 font-bold">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        {{ user.name }}
                                        <span v-if="user.id === $page.props.auth.user.id" class="ml-2 text-xs text-green-600 font-bold">(Anda)</span>
                                    </td>
                                    <td class="px-6 py-4 border-r text-gray-600">{{ user.email }}</td>
                                    <td class="px-6 py-4 border-r text-center">
                                        <span v-if="user.roles.length > 0" class="bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs font-semibold px-2.5 py-0.5 rounded">
                                            {{ user.roles[0].name }}
                                        </span>
                                        <span v-else class="text-xs text-gray-400 italic">Tanpa Role</span>
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2 whitespace-nowrap">
                                        <Link :href="route('users.edit', user.id)" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">
                                            Edit
                                        </Link>
                                        <button 
                                            v-if="user.id !== 1 && user.id !== $page.props.auth.user.id" 
                                            @click="deleteData(user.id, $page.props.auth.user.id)" 
                                            class="text-red-600 hover:text-red-900 font-medium hover:underline ml-2"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada data Akun.</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Simple Pagination -->
                        <div class="mt-4 flex justify-end" v-if="users.prev_page_url || users.next_page_url">
                            <div class="space-x-2">
                                <Link v-if="users.prev_page_url" :href="users.prev_page_url" class="px-4 py-2 border rounded bg-white hover:bg-gray-50 text-sm">Prev</Link>
                                <span class="px-4 py-2 text-sm text-gray-500">Halaman {{ users.current_page }}</span>
                                <Link v-if="users.next_page_url" :href="users.next_page_url" class="px-4 py-2 border rounded bg-white hover:bg-gray-50 text-sm">Next</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
