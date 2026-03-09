<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    period: Object,
    items: Object, // Tambahan properti paginated items
    filters: Object, // Filter pencarian
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(dateString));
};

// Variable Import Modal State
const importModal = ref(false);
const importFile = ref(null);
const fileInputRef = ref(null);
const isImporting = ref(false);
const importErrorMsg = ref('');
const importErrorsList = ref([]);

const openImportModal = () => importModal.value = true;
const closeImportModal = () => {
    importModal.value = false;
    importFile.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
    importErrorMsg.value = '';
    importErrorsList.value = [];
};

const handleFileUpload = (e) => {
    importFile.value = e.target.files[0];
    importErrorMsg.value = '';
    importErrorsList.value = [];
};

const submitImport = () => {
    if (!importFile.value) return;
    
    isImporting.value = true;
    const formData = new FormData();
    formData.append('file', importFile.value);

    window.axios.post(`/payroll-periods/${props.period.id}/import-variables`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    }).then(res => {
        alert(res.data.message || 'Import berhasil.');
        closeImportModal();
        router.reload(); // Refresh inertia page
    }).catch(err => {
        importErrorMsg.value = err.response?.data?.message || err.message || 'Terjadi kesalahan saat meng-upload data.';
        if (err.response?.data?.errors && Array.isArray(err.response.data.errors)) {
            importErrorsList.value = err.response.data.errors;
        } else {
            importErrorsList.value = [];
        }
        // Force user to re-select the file to prevent ERR_UPLOAD_FILE_CHANGED 
        // if they save changes to the same file while the modal is open.
        importFile.value = null;
        if (fileInputRef.value) fileInputRef.value.value = '';
    }).finally(() => {
        isImporting.value = false;
    });
};

// Modal Action State (Approve/Reject/Review)
const actionModal = ref(false);
const actionData = ref({
    title: '',
    actionName: '',
    endpoint: '',
    notes: '',
    isProcessing: false,
});

const openActionModal = (title, actionName, endpoint) => {
    actionData.value = {
        title, actionName, endpoint, notes: '', isProcessing: false
    };
    actionModal.value = true;
};

const closeActionModal = () => {
    actionModal.value = false;
};

const submitAction = () => {
    actionData.value.isProcessing = true;
    window.axios.post(`/payroll-periods/${props.period.id}/${actionData.value.endpoint}`, {
        notes: actionData.value.notes
    })
    .then(res => {
        alert(`Berhasil: ${actionData.value.actionName}`);
        closeActionModal();
        router.reload();
    })
    .catch(err => {
        alert('Gagal: ' + (err.response?.data?.message || err.message));
        actionData.value.isProcessing = false;
    });
};

// Detail Item Modal State
const detailModal = ref(false);
const selectedItem = ref(null);
const isRegenerating = ref(false);

const regenerateCurrentItem = () => {
    if (!selectedItem.value) return;
    
    isRegenerating.value = true;
    window.axios.post(`/payroll-periods/${props.period.id}/items/${selectedItem.value.id}/regenerate`)
        .then(res => {
            alert(res.data.message || 'Selesai sinkronisasi.');
            closeDetailModal();
            router.reload();
        })
        .catch(err => {
            alert('Gagal Regenerate: ' + (err.response?.data?.message || err.message));
        })
        .finally(() => {
            isRegenerating.value = false;
        });
};

const openDetailModal = (item) => {
    // Clone and Sort components so IMPORT sources appear first
    const sortedComponents = [...(item.components || [])].sort((a, b) => {
        if (a.source === 'IMPORT' && b.source !== 'IMPORT') return -1;
        if (a.source !== 'IMPORT' && b.source === 'IMPORT') return 1;
        if (a.component_type !== b.component_type) return a.component_type === 'earning' ? -1 : 1;
        return 0;
    });

    selectedItem.value = { ...item, components: sortedComponents };
    detailModal.value = true;
};

const closeDetailModal = () => {
    detailModal.value = false;
    selectedItem.value = null;
    isRegenerating.value = false;
};

// Regenerate All Logic
const isRegeneratingAll = ref(false);
const confirmRegenerateAll = () => {
    if (confirm("Anda yakin ingin mensinkronisasi ulang SELURUH 300+ Karyawan dengan template Data Master terbaru?\n\nCATATAN AMAN: Data lembur atau tambahan ekstra yang berasal dari Import CSV sebelumnya TIDAK AKAN HILANG dan akan dikalkulasi ulang.\nTunggu hingga proses tuntas.")) {
        isRegeneratingAll.value = true;
        window.axios.post(`/payroll-periods/${props.period.id}/regenerate-all`)
            .then(res => {
                alert(res.data.message || 'Berhasil mensinkronisasi semua data karyawan.');
                router.reload();
            })
            .catch(err => {
                alert('Gagal Regenerate All: ' + (err.response?.data?.message || err.message));
            })
            .finally(() => {
                isRegeneratingAll.value = false;
            });
    }
};

// Search & Sorting Logic
const searchFilter = ref(props.filters?.search || '');
const sortFilter = ref(props.filters?.sort || '');
let searchTimeout;

const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(`/payroll-periods/${props.period.id}`, { 
            search: searchFilter.value,
            sort: sortFilter.value
        }, { preserveState: true, replace: true });
    }, 500); // 500ms debounce
};
</script>

<template>
    <Head :title="`Payroll - ${period.code}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Detail Periode: {{ period.code }} 
                    <span class="ml-2 rounded px-2.5 py-0.5 text-sm font-semibold text-blue-800 bg-blue-100 capitalize">
                        {{ period.status }}
                    </span>
                </h2>
                
                <div class="space-x-2">
                    <!-- Tombol Rekap Payroll -->
                    <Link :href="route('payroll.recap', period.id)"
                          class="inline-flex items-center px-4 py-2 bg-emerald-50 text-emerald-700 border border-emerald-300 text-sm font-medium rounded-md hover:bg-emerald-100 transition">
                        📊 Rekap Periode
                    </Link>

                    <!-- Action Buttons Based on Status -->
                    <!-- CSV Import only allowed in draft -->
                    <SecondaryButton v-if="period.status === 'draft' && $page.props.auth.user.permissions.includes('edit-payroll')" @click="openImportModal">
                        Import Variable (CSV)
                    </SecondaryButton>

                    <SecondaryButton 
                        v-if="period.status === 'draft' && $page.props.auth.user.permissions.includes('generate-payroll')" 
                        @click="confirmRegenerateAll" 
                        class="text-indigo-600 border-indigo-300 hover:bg-indigo-50"
                        :disabled="isRegeneratingAll"
                        :class="{'opacity-50 cursor-wait': isRegeneratingAll}"
                    >
                        {{ isRegeneratingAll ? '⏳ Menyinkronkan 300+ Data...' : '♻️ Sync Master (Regenerate All)' }}
                    </SecondaryButton>

                    <PrimaryButton v-if="period.status === 'draft' && $page.props.auth.user.permissions.includes('generate-payroll')" @click="openActionModal('Kirim untuk Review', 'Kirim Review', 'mark-as-reviewed')">
                        Kirim Review
                    </PrimaryButton>

                    <!-- REJECT BUTTONS -->
                    <SecondaryButton v-if="period.status === 'pending-approval' && $page.props.auth.user.permissions.includes('approve-payroll')" @click="openActionModal('Kembalikan ke Draft (Revisi)', 'Revisi Draft', 'reject-to-draft')" class="bg-yellow-50 hover:bg-yellow-100 text-yellow-700 border-yellow-300">
                        Revisi (Ke Draft)
                    </SecondaryButton>

                    <SecondaryButton v-if="period.status === 'pending-approval' && $page.props.auth.user.permissions.includes('approve-payroll')" @click="openActionModal('Tolak Permanen (Void)', 'Reject Gaji', 'mark-as-rejected')" class="bg-red-50 hover:bg-red-100 text-red-700 border-red-300">
                        Void / Reject
                    </SecondaryButton>
                    <!-- END REJECT BUTTONS -->

                    <PrimaryButton v-if="period.status === 'pending-approval' && $page.props.auth.user.permissions.includes('approve-payroll')" @click="openActionModal('Setujui Penggajian', 'Approve Payroll', 'mark-as-approved')" class="bg-green-600 hover:bg-green-500 text-white">
                        Approve Payroll
                    </PrimaryButton>

                    <PrimaryButton v-if="period.status === 'approved' && $page.props.auth.user.permissions.includes('approve-payroll')" @click="openActionModal('Tandai Sebagai Dibayar', 'Tandai Dibayar', 'mark-as-paid')" class="bg-orange-600 hover:bg-orange-500 text-white">
                        Tandai Dibayar
                    </PrimaryButton>

                    <PrimaryButton v-if="period.status === 'paid' && $page.props.auth.user.roles.includes('HR Admin')" @click="openActionModal('Tutup Pembukuan', 'Close Period', 'mark-as-closed')" class="bg-gray-800 hover:bg-gray-700 text-white">
                        Close Period
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Left Column (General Info & Items) -->
                <div class="md:col-span-2 space-y-6">
                    <!-- Info Section -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg py-4 px-6 flex flex-wrap gap-8">
                         <div>
                             <p class="text-sm text-gray-500 font-semibold mb-1">Tipe</p>
                             <p class="text-lg font-bold capitalize">{{ period.period_type }}</p>
                         </div>
                         <div>
                             <p class="text-sm text-gray-500 font-semibold mb-1">Rentang Tanggal</p>
                             <p class="text-lg font-bold">{{ formatDate(period.start_date) }} s/d {{ formatDate(period.end_date) }}</p>
                         </div>
                         <div>
                             <p class="text-sm text-gray-500 font-semibold mb-1">Tanggal Gajian</p>
                             <p class="text-lg font-bold text-indigo-700">{{ formatDate(period.pay_date) }}</p>
                         </div>
                         <div>
                             <p class="text-sm text-gray-500 font-semibold mb-1">Total Karyawan</p>
                             <p class="text-lg font-bold">{{ items.total || period.items?.length || 0 }}</p>
                         </div>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6 text-gray-900 border-b overflow-x-auto">
                            <!-- Header & Search -->
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                                <h3 class="font-bold text-lg">Daftar Karyawan (Payroll Items)</h3>
                                
                                <div class="flex flex-col md:flex-row md:items-center gap-3">
                                    <!-- Sort Dropdown -->
                                    <div class="w-full md:w-64">
                                        <select 
                                            v-model="sortFilter" 
                                            @change="performSearch"
                                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                        >
                                            <option value="">Sort Berdasar NIK Karyawan</option>
                                            <option value="tinggi_earning">Nominal Tambahan Terbesar</option>
                                            <option value="tinggi_deduction">Nominal Potongan Terbesar</option>
                                        </select>
                                    </div>
                                    <!-- Search Input -->
                                    <div class="w-full md:w-64">
                                        <TextInput 
                                            type="text" 
                                            v-model="searchFilter" 
                                            @input="performSearch"
                                            class="block w-full text-sm" 
                                            placeholder="Cari Nama / NIK Karyawan..." 
                                        />
                                    </div>
                                </div>
                            </div>
                            
                            <table class="w-full text-left text-sm text-gray-600 mb-4">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">ID Internal</th>
                                        <th scope="col" class="px-6 py-3">Nama Karyawan</th>
                                        <th scope="col" class="px-6 py-3 text-right">Total Bruto</th>
                                        <th scope="col" class="px-6 py-3 text-right">Total Potongan (Deduction)</th>
                                        <th scope="col" class="px-6 py-3 text-right">Netto Bersih</th>
                                        <th scope="col" class="px-6 py-3 text-center border-l">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in items.data" :key="item.id" class="border-b bg-white hover:bg-gray-50">
                                        <td class="px-6 py-4">{{ item.employee?.nik_internal || '-' }}</td>
                                        <td class="px-6 py-4 font-semibold text-gray-900">{{ item.employee?.name || '-' }}</td>
                                        <td class="px-6 py-4 text-right">
                                            {{ formatCurrency(item.total_bruto) }}
                                            <div v-if="item.import_earning_total > 0" class="text-[10px] text-orange-600 mt-1" title="Termasuk Import Extra Earning">+ {{ formatCurrency(item.import_earning_total) }} (CSV)</div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-red-600">
                                            {{ formatCurrency(item.total_deduction) }}
                                            <div v-if="item.import_deduction_total > 0" class="text-[10px] text-orange-600 mt-1" title="Termasuk Import Extra Deduction">+ {{ formatCurrency(item.import_deduction_total) }} (CSV)</div>
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold text-green-700">{{ formatCurrency(item.total_netto) }}</td>
                                        <td class="px-6 py-4 text-center border-l">
                                            <button @click="openDetailModal(item)" class="text-indigo-600 hover:text-indigo-900 text-sm font-semibold underline decoration-indigo-300 decoration-dotted underline-offset-4">Lihat Rincian</button>
                                        </td>
                                    </tr>
                                    <tr v-if="items.data.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">Belum ada Karyawan atau Tidak Ditemukan.</td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div class="mt-4 flex flex-wrap gap-1 justify-center" v-if="items.links && items.links.length > 3">
                                <template v-for="(link, key) in items.links" :key="key">
                                    <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded bg-gray-50" v-html="link.label" />
                                    <a v-else
                                        class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 focus:border-indigo-500 focus:text-indigo-500"
                                        :class="{ 'bg-indigo-600 text-white border-indigo-600 hover:bg-indigo-700 hover:text-white pointer-events-none': link.active }"
                                        :href="link.url" v-html="link.label" />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Audit History Timeline) -->
                <div class="md:col-span-1" >
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-bold text-lg mb-6 text-gray-900 border-b pb-2">Jejak Rekam (Audit Trail)</h3>
                        
                        <ol class="relative border-l border-gray-200 ml-3" v-if="period.auditLogs && period.auditLogs.length > 0">                  
                            <li class="mb-8 ml-6" v-for="log in period.auditLogs" :key="log.id">
                                <span class="absolute flex items-center justify-center w-6 h-6 bg-indigo-100 rounded-full -left-3 ring-8 ring-white">
                                    <svg class="w-2.5 h-2.5 text-indigo-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </span>
                                <h3 class="flex items-center mb-1 text-sm font-semibold text-gray-900">
                                    {{ log.user ? log.user.name : 'Sistem' }}
                                    <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase"
                                        :class="{'bg-green-100 text-green-800': log.after_data?.status === 'approved', 'bg-red-100 text-red-800': log.after_data?.status === 'rejected', 'bg-yellow-100 text-yellow-800': log.after_data?.status === 'draft', 'bg-blue-100 text-blue-800': !['approved','rejected','draft'].includes(log.after_data?.status)}"
                                    >
                                        {{ log.after_data?.status || '?' }}
                                    </span>
                                </h3>
                                <time class="block mb-2 text-xs font-normal leading-none text-gray-400">
                                    {{ new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(log.created_at)) }}
                                </time>
                                <div v-if="log.after_data?.notes" class="mt-2 p-3 bg-gray-50 rounded-md border border-gray-100 text-sm italic text-gray-600 whitespace-pre-line shadow-inner">
                                    "{{ log.after_data.notes }}"
                                </div>
                            </li>
                        </ol>
                        <div v-else class="text-sm text-gray-500 italic text-center py-4">Belum ada jejak rekam persetujuan.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <Modal :show="importModal" @close="closeImportModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Import CSV (Variabel Payroll)</h2>
                <p class="mt-1 text-sm text-gray-600 mb-4">
                    Unggah file CSV dengan kolom header: <code>nik_internal</code>, <code>nama_karyawan</code>, <code>component_code</code>, <code>amount</code>. Kolom ini akan menimpa/menambahkan nilai komponen (Misal: UJ, Lembur, Kasbon) pada periode draft ini dan Sistem akan menghitung ulang otomatis.
                </p>

                <div class="mt-4 space-y-4">
                    <!-- Error Notification Banner -->
                    <div v-if="importErrorMsg" class="p-4 bg-red-50 border-l-4 border-red-500 rounded-md shadow-sm">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-bold text-red-800">{{ importErrorMsg }}</h3>
                                <div class="mt-2 text-sm text-red-700" v-if="importErrorsList.length">
                                    <ul role="list" class="list-disc pl-5 space-y-1">
                                        <li v-for="(errItem, idx) in importErrorsList" :key="idx">{{ errItem }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-blue-50 text-blue-800 rounded-md border border-blue-200">
                        <p class="text-sm font-semibold mb-2">Ingin proses isi lebih cepat?</p>
                        <p class="text-xs mb-3 text-blue-700">Unduh Format Template CSV di bawah. Dokumen sudah terisi otomatis dengan NIK seluruh karyawan di daftar periode ini. Anda tinggal set nominalnya!</p>
                        <a :href="`/payroll-periods/${period.id}/import-template`" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13V4M7 14H5a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4a1 1 0 0 0-1-1h-2m-1-5-4 5-4-5m9 8h.01"/>
                            </svg>
                            Unduh Template CSV Rekomendasi
                        </a>
                    </div>

                    <div>
                        <InputLabel value="Upload CSV yang Teredit:" class="mb-2 font-bold" />
                        <input 
                            type="file" 
                            accept=".csv"
                            ref="fileInputRef"
                            @change="handleFileUpload" 
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" 
                        />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeImportModal">Batal</SecondaryButton>
                    <PrimaryButton 
                        class="ms-3" 
                        :disabled="!importFile || isImporting" 
                        :class="{'opacity-50': !importFile || isImporting}"
                        @click="submitImport"
                    >
                        {{ isImporting ? 'Mengupload...' : 'Upload & Hitung' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Modal Action (Komentar Persetujuan / Penolakan) -->
        <Modal :show="actionModal" @close="closeActionModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">{{ actionData.title }}</h2>
                <p class="mt-1 text-sm text-gray-600 mb-4">
                    Silakan isi catatan ekstra (opsional) sebelum melanjutkan aksi ini. Catatan ini akan direkam permanen dalam riwayat <b>Audit Trail</b>.
                </p>

                <div class="mt-4">
                    <InputLabel for="notes" value="Catatan / Komentar (Opsional):" />
                    <textarea 
                        id="notes" 
                        v-model="actionData.notes" 
                        rows="3"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        placeholder="Ketikan alasan atau catatan di sini... (Misal: Uang makan kepotong kebanyakan)"
                    ></textarea>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeActionModal">Batal</SecondaryButton>
                    <PrimaryButton 
                        class="ms-3" 
                        :disabled="actionData.isProcessing" 
                        :class="{'opacity-50': actionData.isProcessing}"
                        @click="submitAction"
                    >
                        {{ actionData.isProcessing ? 'Memproses...' : 'Proses Aksi' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Detail Rincian Gaji Modal -->
        <Modal :show="detailModal" @close="closeDetailModal" maxWidth="2xl">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 border-b pb-3">Rincian Komponen Gaji: <span class="text-indigo-700">{{ selectedItem?.employee?.name || '-' }}</span> ({{ selectedItem?.employee?.nik_internal || '-' }})</h2>
                
                <div v-if="selectedItem">
                    <div class="grid grid-cols-3 gap-4 mb-5 p-4 rounded-lg bg-gray-50 border border-gray-100 shadow-inner">
                        <div>
                            <span class="text-gray-500 block text-sm uppercase font-bold tracking-wider mb-1">Total Pendapatan (Bruto)</span>
                            <span class="font-bold text-xl text-gray-900">{{ formatCurrency(selectedItem.total_bruto) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-sm uppercase font-bold tracking-wider mb-1">Total Potongan (Deduction)</span>
                            <span class="font-bold text-xl text-red-600">{{ formatCurrency(selectedItem.total_deduction) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-sm uppercase font-bold tracking-wider mb-1">Total Pendapatan (Netto)</span>
                            <span class="font-bold text-xl text-green-600">{{ formatCurrency(selectedItem.total_netto) }}</span>
                        </div>
                    </div>

                    <h4 class="font-bold text-md mb-3 text-gray-800">Daftar Komponen:</h4>
                    <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
                        <table class="w-full text-left text-sm text-gray-600 bg-white">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700 border-b">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Tipe</th>
                                    <th class="px-4 py-3 font-semibold">Kode</th>
                                    <th class="px-4 py-3 font-semibold">Nama Komponen</th>
                                    <th class="px-4 py-3 text-right font-semibold">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="comp in selectedItem.components" :key="comp.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-[10px] font-bold uppercase rounded-md"
                                            :class="comp.component_type === 'earning' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                            {{ comp.component_type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ comp.component_code }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ comp.component_name }}
                                        <span v-if="comp.source === 'IMPORT'" class="ml-2 px-1.5 py-0.5 rounded text-[9px] font-bold bg-orange-100 text-orange-800 uppercase border border-orange-200" title="Berasal dari Import CSV">
                                            {{ comp.component_type === 'earning' ? 'ADD EARNING' : 'ADD DEDUCTION' }} (CSV)
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold" :class="comp.component_type === 'earning' ? 'text-green-700' : 'text-red-600'">
                                        {{ formatCurrency(comp.amount) }}
                                    </td>
                                </tr>
                                <tr v-if="!selectedItem.components || selectedItem.components.length === 0">
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-400 italic bg-gray-50">Tidak ada rincian komponen yang tercatat di Sistem.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton 
                        v-if="period.status === 'draft'"
                        @click="regenerateCurrentItem" 
                        class="text-indigo-600 border-indigo-200 hover:bg-indigo-50"
                        :disabled="isRegenerating"
                    >
                        {{ isRegenerating ? 'Menyinkronkan...' : '♻️ Refresh Data Master (Regenerate)' }}
                    </SecondaryButton>
                    <PrimaryButton @click="closeDetailModal" class="bg-indigo-600 hover:bg-indigo-700">Tutup Rincian</PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
