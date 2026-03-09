<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    employees: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('employees.index'), { search: value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const deleteData = (id) => {
    if (confirm('Yakin ingin menghapus Karyawan ini? Data terkait penggajian history mungkin akan terpengaruh jika tidak diset relasinya!')) {
        router.delete(route('employees.destroy', id));
    }
};
</script>

<template>
    <Head title="Master Karyawan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Master Karyawan (Employee)
                </h2>
                <Link :href="route('employees.create')">
                    <PrimaryButton>+ Tambah Karyawan</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Search and Data Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <div class="w-1/3">
                            <TextInput v-model="search" type="text" class="block w-full text-sm" placeholder="Cari NIK atau Nama Karyawan..." />
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto p-6 text-gray-900">
                        <table class="w-full text-left text-sm text-gray-500 mb-4 border border-gray-200">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 border-b">NIK Internal</th>
                                    <th scope="col" class="px-6 py-3 border-b">Nama Karyawan</th>
                                    <th scope="col" class="px-6 py-3 border-b">Role / Jabatan</th>
                                    <th scope="col" class="px-6 py-3 border-b">Tipe</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center">Status</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="emp in employees.data" :key="emp.id" class="border-b bg-white hover:bg-gray-50">
                                    <td class="px-6 py-4 font-semibold text-gray-900 border-r">{{ emp.nik_internal }}</td>
                                    <td class="px-6 py-4 border-r font-medium text-gray-900 whitespace-nowrap">{{ emp.name }}</td>
                                    <td class="px-6 py-4 border-r">
                                        <div class="text-sm font-semibold">{{ emp.position?.name || '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ emp.department?.name || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 border-r uppercase text-xs font-semibold">{{ emp.employment_type }}</td>
                                    <td class="px-6 py-4 text-center border-r">
                                        <span v-if="emp.is_active" class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Aktif</span>
                                        <span v-else class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Resign / Nonaktif</span>
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2 whitespace-nowrap">
                                        <Link :href="route('employees.edit', emp.id)" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">
                                            Edit
                                        </Link>
                                        <Link :href="route('employees.payroll-history', emp.id)" class="text-emerald-600 hover:text-emerald-800 font-medium hover:underline ml-2">
                                            Riwayat Payroll
                                        </Link>
                                        <button @click="deleteData(emp.id)" class="text-red-600 hover:text-red-900 font-medium hover:underline ml-2">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="employees.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada data karyawan.</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Simple Pagination controls could go here -->
                        <div class="mt-4 flex justify-end" v-if="employees.prev_page_url || employees.next_page_url">
                            <!-- Inertia links for pagination (using simplistic approach for MVP) -->
                            <div class="space-x-2">
                                <Link v-if="employees.prev_page_url" :href="employees.prev_page_url" class="px-4 py-2 border rounded bg-white hover:bg-gray-50 text-sm">Prev</Link>
                                <span class="px-4 py-2 text-sm text-gray-500">Halaman {{ employees.current_page }}</span>
                                <Link v-if="employees.next_page_url" :href="employees.next_page_url" class="px-4 py-2 border rounded bg-white hover:bg-gray-50 text-sm">Next</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
