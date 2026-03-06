<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    period: Object,
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
const isImporting = ref(false);

const openImportModal = () => importModal.value = true;
const closeImportModal = () => {
    importModal.value = false;
    importFile.value = null;
};

const handleFileUpload = (e) => {
    importFile.value = e.target.files[0];
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
        alert('Gagal import: ' + (err.response?.data?.message || err.message));
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
                    <!-- Action Buttons Based on Status -->
                    <!-- CSV Import only allowed in draft -->
                    <SecondaryButton v-if="period.status === 'draft' && $page.props.auth.user.permissions.includes('edit-payroll')" @click="openImportModal">
                        Import Variable (CSV)
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
                             <p class="text-lg font-bold">{{ period.items.length }}</p>
                         </div>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6 text-gray-900 border-b overflow-x-auto">
                            <h3 class="font-bold text-lg mb-4">Daftar Karyawan (Payroll Items)</h3>
                            <table class="w-full text-left text-sm text-gray-500">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">ID Internal</th>
                                        <th scope="col" class="px-6 py-3">Nama Karyawan</th>
                                        <th scope="col" class="px-6 py-3 text-right">Total Bruto</th>
                                        <th scope="col" class="px-6 py-3 text-right">Total Potongan (Deduction)</th>
                                        <th scope="col" class="px-6 py-3 text-right">Netto Bersih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in period.items" :key="item.id" class="border-b bg-white hover:bg-gray-50">
                                        <td class="px-6 py-4">{{ item.employee?.nik_internal || '-' }}</td>
                                        <td class="px-6 py-4 font-semibold text-gray-900">{{ item.employee?.name || '-' }}</td>
                                        <td class="px-6 py-4 text-right">{{ formatCurrency(item.total_bruto) }}</td>
                                        <td class="px-6 py-4 text-right text-red-600">{{ formatCurrency(item.total_deduction) }}</td>
                                        <td class="px-6 py-4 text-right font-bold text-green-700">{{ formatCurrency(item.total_netto) }}</td>
                                    </tr>
                                    <tr v-if="period.items.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada Karyawan (Kemungkinan saat generate tdk ada pegawai aktif).</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Audit History Timeline) -->
                <div class="md:col-span-1">
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

    </AuthenticatedLayout>
</template>
