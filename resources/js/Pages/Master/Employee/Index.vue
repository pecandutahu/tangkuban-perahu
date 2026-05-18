<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    employees: Object,
    filters: Object,
});

const page = usePage();

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

// ─── Import Excel ────────────────────────────────────────────────
const showImportModal  = ref(false);
const importFile       = ref(null);
const isDragging       = ref(false);
const isUploading      = ref(false);
const serverErrors     = ref([]);

// Flash data dibaca dari HandleInertiaRequests middleware
const importResults   = computed(() => page.props.flash?.importResults ?? null);
const flashSuccess    = computed(() => page.props.flash?.success ?? null);
const flashError      = computed(() => page.props.flash?.error ?? null);
const importFileError = computed(() => page.props.errors?.file ?? null);

const fileInput = ref(null);

function openImportModal() {
    importFile.value   = null;
    serverErrors.value = [];
    showImportModal.value = true;
}

function closeImportModal() {
    if (isUploading.value) return;
    showImportModal.value = false;
    importFile.value   = null;
    serverErrors.value = [];
}

function onFileChange(e) {
    importFile.value   = e.target.files[0] ?? null;
    serverErrors.value = [];
}

function onDrop(e) {
    isDragging.value   = false;
    importFile.value   = e.dataTransfer.files[0] ?? null;
    serverErrors.value = [];
}

function onDragOver() { isDragging.value = true; }
function onDragLeave() { isDragging.value = false; }

function clearFile() {
    importFile.value   = null;
    serverErrors.value = [];
    if (fileInput.value) fileInput.value.value = '';
}

function submitImport() {
    if (!importFile.value) return;
    isUploading.value  = true;
    serverErrors.value = [];

    const formData = new FormData();
    formData.append('file', importFile.value);

    router.post(route('employees.import'), formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Tutup modal — hasil import tampil di banner halaman
            showImportModal.value = false;
        },
        onError: (errors) => {
            // Tampilkan error validasi di dalam modal
            serverErrors.value = Object.values(errors);
        },
        onFinish: () => {
            isUploading.value  = false;
            importFile.value   = null;
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}

// Format bytes
function formatSize(bytes) {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1024 / 1024).toFixed(1) + ' MB';
}
</script>

<template>
    <Head title="Master Karyawan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Master Karyawan (Employee)
                </h2>
                <div class="flex items-center gap-2">
                    <!-- Download Template -->
                    <a
                        :href="route('employees.import-template')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 border border-emerald-600 text-emerald-700 text-sm font-medium rounded-md hover:bg-emerald-50 transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                        </svg>
                        Template Excel
                    </a>

                    <!-- Upload Excel -->
                    <button
                        @click="openImportModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2 border border-blue-600 text-blue-700 text-sm font-medium rounded-md hover:bg-blue-50 transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 8l5-5m0 0l5 5m-5-5v12" />
                        </svg>
                        Import Excel
                    </button>

                    <!-- Tambah Manual -->
                    <Link :href="route('employees.create')">
                        <PrimaryButton>+ Tambah Karyawan</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-4">

                <!-- ── Import Results Banner ── -->
                <div
                    v-if="importResults"
                    class="rounded-xl border overflow-hidden"
                    :class="importResults.errors > 0 ? 'border-amber-300 bg-amber-50' : 'border-green-300 bg-green-50'"
                >
                    <div class="px-5 py-4 flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-sm" :class="importResults.errors > 0 ? 'text-amber-800' : 'text-green-800'">
                                Import selesai:
                                <span class="text-green-700">{{ importResults.success }} baris berhasil</span>
                                <span v-if="importResults.errors > 0" class="ml-2 text-red-600">{{ importResults.errors }} baris gagal</span>
                            </p>
                        </div>
                    </div>
                    <div v-if="importResults.rows.length" class="overflow-x-auto border-t border-gray-200">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-100 text-gray-600 uppercase">
                                <tr>
                                    <th class="px-4 py-2 text-left">Baris</th>
                                    <th class="px-4 py-2 text-left">NIK</th>
                                    <th class="px-4 py-2 text-left">Nama</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="r in importResults.rows"
                                    :key="r.row"
                                    class="border-t"
                                    :class="r.status === 'success' ? 'bg-white' : 'bg-red-50'"
                                >
                                    <td class="px-4 py-2 text-center font-mono">{{ r.row }}</td>
                                    <td class="px-4 py-2 font-mono">{{ r.nik }}</td>
                                    <td class="px-4 py-2">{{ r.name }}</td>
                                    <td class="px-4 py-2">
                                        <span
                                            class="px-2 py-0.5 rounded text-xs font-semibold"
                                            :class="r.status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                        >
                                            {{ r.status === 'success' ? '✓ Sukses' : '✗ Gagal' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600">{{ r.reason }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── Search and Data Table ── -->
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

                        <!-- Pagination -->
                        <div class="mt-4 flex justify-end" v-if="employees.prev_page_url || employees.next_page_url">
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

        <!-- ═══════════════════════════════════════════════
             MODAL IMPORT EXCEL
        ════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showImportModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @click.self="closeImportModal"
                >
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

                    <!-- Dialog -->
                    <div class="relative z-10 w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
                        <!-- Header -->
                        <div class="px-6 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/20 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 8l5-5m0 0l5 5m-5-5v12" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-bold text-lg leading-tight">Import Karyawan</h3>
                                    <p class="text-blue-100 text-xs mt-0.5">Upload file Excel (.xlsx / .xls / .csv)</p>
                                </div>
                            </div>
                            <button
                                @click="closeImportModal"
                                class="text-white/70 hover:text-white transition p-1"
                                :disabled="isUploading"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-6 space-y-4">
                            <!-- Info template -->
                            <div class="flex items-start gap-3 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z" />
                                </svg>
                                <span>
                                    Gunakan format template yang benar.
                                    <a :href="route('employees.import-template')" class="font-semibold underline hover:text-blue-900">Download template</a>.
                                </span>
                            </div>

                            <!-- Error message -->
                            <p v-if="importError" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-2">
                                {{ importError }}
                            </p>

                            <!-- Drop zone -->
                            <div
                                class="relative border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
                                :class="isDragging
                                    ? 'border-blue-500 bg-blue-50'
                                    : importFile
                                        ? 'border-green-400 bg-green-50'
                                        : 'border-gray-300 bg-gray-50 hover:border-gray-400'"
                                @dragover.prevent="onDragOver"
                                @dragleave="onDragLeave"
                                @drop.prevent="onDrop"
                                @click="fileInput?.click()"
                            >
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    class="hidden"
                                    @change="onFileChange"
                                />

                                <!-- File selected state -->
                                <div v-if="importFile" class="space-y-2">
                                    <div class="flex justify-center">
                                        <div class="p-3 bg-green-100 rounded-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="text-sm font-semibold text-green-700 truncate max-w-xs mx-auto">{{ importFile.name }}</p>
                                    <p class="text-xs text-gray-500">{{ formatSize(importFile.size) }}</p>
                                    <button
                                        type="button"
                                        @click.stop="clearFile"
                                        class="text-xs text-red-500 hover:text-red-700 underline mt-1"
                                    >
                                        Hapus file
                                    </button>
                                </div>

                                <!-- Empty state -->
                                <div v-else class="space-y-3">
                                    <div class="flex justify-center">
                                        <div class="p-3 bg-gray-200 rounded-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Drag & drop file ke sini</p>
                                        <p class="text-xs text-gray-400 mt-0.5">atau klik untuk memilih file</p>
                                    </div>
                                    <p class="text-xs text-gray-400">.xlsx, .xls, .csv — Maks. 5 MB</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-gray-50 border-t flex items-center justify-end gap-3">
                            <button
                                @click="closeImportModal"
                                :disabled="isUploading"
                                class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-100 transition disabled:opacity-50"
                            >
                                Batal
                            </button>
                            <button
                                @click="submitImport"
                                :disabled="!importFile || isUploading"
                                class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <svg v-if="isUploading" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                <span>{{ isUploading ? 'Mengupload...' : 'Upload & Import' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease;
}
.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95);
}
</style>
