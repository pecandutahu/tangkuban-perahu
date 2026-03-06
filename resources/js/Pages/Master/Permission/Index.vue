<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    permissions: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('permissions.index'), { search: value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const deleteData = (id, name) => {
    const corePerms = ['view-master-data', 'create-master-data', 'edit-master-data', 'delete-master-data'];
    if (corePerms.includes(name)) {
        alert('Maaf, Kunci Inti Master Data dilarang untuk dihapus.');
        return;
    }
    
    if (confirm('Yakin ingin menghapus Kunci Akses ini? Jika programmer sudah memasangnya di kodingan, fitur terkait akan terbuka tanpa kunci!')) {
        router.delete(route('permissions.destroy', id));
    }
};

const formatPermissionName = (name) => {
    return name.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};
</script>

<template>
    <Head title="Manajemen Kunci Sistem" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Daftar Kunci Hak Akses (Permissions Tag)
                </h2>
                <Link :href="route('permissions.create')">
                    <PrimaryButton>+ Tambah Tag Kunci</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Data Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50 mb-2">
                        <div class="w-1/3">
                            <TextInput v-model="search" type="text" class="block w-full text-sm" placeholder="Cari Kode Kunci..." />
                        </div>
                        <div class="text-xs text-gray-500 max-w-xs text-right italic">
                            Semua kunci akses ini bisa dihubungkan ke Jabatan (Role) tertentu di menu Hak Akses Jabatan.
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto p-6 text-gray-900 pt-0">
                        <!-- Warning Error Block -->
                        <div v-if="$page.props.errors && $page.props.errors.error" class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                            <p class="font-bold">Peringatan Penghapusan!</p>
                            <p>{{ $page.props.errors.error }}</p>
                        </div>

                        <table class="w-full text-left text-sm text-gray-500 mb-4 border border-gray-200">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 border-b">ID</th>
                                    <th scope="col" class="px-6 py-3 border-b">Label Akses</th>
                                    <th scope="col" class="px-6 py-3 border-b">Sandi Spesifik (System Key)</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="permission in permissions.data" :key="permission.id" class="border-b bg-white hover:bg-gray-50">
                                    <td class="px-6 py-4 font-semibold text-gray-900 border-r">{{ permission.id }}</td>
                                    <td class="px-6 py-4 border-r text-gray-700 font-medium">{{ formatPermissionName(permission.name) }}</td>
                                    <td class="px-6 py-4 border-r">
                                        <code class="bg-indigo-50 text-indigo-800 px-2 py-1 rounded text-xs font-mono font-bold">{{ permission.name }}</code>
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2 whitespace-nowrap">
                                        <Link :href="route('permissions.edit', permission.id)" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">
                                            Edit Sandi
                                        </Link>
                                        <button 
                                            v-if="!['view-master-data', 'create-master-data', 'edit-master-data', 'delete-master-data'].includes(permission.name)" 
                                            @click="deleteData(permission.id, permission.name)" 
                                            class="text-red-600 hover:text-red-900 font-medium hover:underline ml-2"
                                        >
                                            Hapus
                                        </button>
                                        <span v-else class="text-gray-400 text-xs italic ml-2">Dikunci</span>
                                    </td>
                                </tr>
                                <tr v-if="permissions.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada data tag Kunci.</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Simple Pagination -->
                        <div class="mt-4 flex justify-end" v-if="permissions.prev_page_url || permissions.next_page_url">
                            <div class="space-x-2">
                                <Link v-if="permissions.prev_page_url" :href="permissions.prev_page_url" class="px-4 py-2 border rounded bg-white hover:bg-gray-50 text-sm">Prev</Link>
                                <span class="px-4 py-2 text-sm text-gray-500">Halaman {{ permissions.current_page }}</span>
                                <Link v-if="permissions.next_page_url" :href="permissions.next_page_url" class="px-4 py-2 border rounded bg-white hover:bg-gray-50 text-sm">Next</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
