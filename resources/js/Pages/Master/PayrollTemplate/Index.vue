<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    templates: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('templates.index'), { search: value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const deleteData = (id) => {
    if (confirm('Yakin ingin menghapus Template Gaji ini?')) {
        router.delete(route('templates.destroy', id));
    }
};
</script>

<template>
    <Head title="Master Template Gaji" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Master Template Gaji
                </h2>
                <Link :href="route('templates.create')">
                    <PrimaryButton>+ Tambah Template</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Search and Data Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <div class="w-1/3">
                            <TextInput v-model="search" type="text" class="block w-full text-sm" placeholder="Cari Nama Template..." />
                        </div>
                    </div>
                    
                    <div class="p-6 text-gray-900">
                        <table class="w-full text-left text-sm text-gray-500 mb-4 border border-gray-200">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 border-b">Nama Template</th>
                                    <th scope="col" class="px-6 py-3 border-b">Tipe Karyawan</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center">Jumlah Komponen</th>
                                    <th scope="col" class="px-6 py-3 border-b text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in templates.data" :key="item.id" class="border-b bg-white hover:bg-gray-50">
                                    <td class="px-6 py-4 font-semibold text-gray-900 border-r">{{ item.name }}</td>
                                    <td class="px-6 py-4 border-r uppercase">{{ item.employment_type }}</td>
                                    <td class="px-6 py-4 text-center border-r">
                                        <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                            {{ item.components_count }} Item
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2">
                                        <Link :href="route('templates.edit', item.id)" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">
                                            Edit
                                        </Link>
                                        <button @click="deleteData(item.id)" class="text-red-600 hover:text-red-900 font-medium hover:underline ml-2">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="templates.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada data template.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
