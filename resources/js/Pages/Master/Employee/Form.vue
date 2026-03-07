<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    departments: Array,
    positions: Array,
    branches: Array,
    components: Array,
    ptkpStatuses: Array,
    employee: Object, // Empty object for create, populated object for edit
});

const isEditing = !!props.employee.id;

const form = useForm({
    nik_internal: props.employee.nik_internal || '',
    name: props.employee.name || '',
    ktp_number: props.employee.ktp_number || '',
    npwp_number: props.employee.npwp_number || '',
    ptkp_status: props.employee.ptkp_status || '',
    department_id: props.employee.department_id || '',
    position_id: props.employee.position_id || '',
    branch_id: props.employee.branch_id || '',
    employment_type: props.employee.employment_type || 'permanent',
    join_date: props.employee.join_date ? props.employee.join_date.split(' ')[0] : '', // Extract YYYY-MM-DD
    resign_date: props.employee.resign_date ? props.employee.resign_date.split(' ')[0] : '',
    is_active: props.employee.is_active ?? true,
    payment_method: props.employee.payment_method || 'bank_transfer',
    bank_name: props.employee.bank_name || '',
    bank_account: props.employee.bank_account || '',
    specific_components: props.employee.specific_components || props.employee.specificComponents || [],
});

const addSpecificComponent = () => {
    form.specific_components.push({
        payroll_component_id: '',
        amount: 0,
    });
};

const removeSpecificComponent = (index) => {
    form.specific_components.splice(index, 1);
};

const submit = () => {
    if (isEditing) {
        form.put(route('employees.update', props.employee.id));
    } else {
        form.post(route('employees.store'));
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Karyawan' : 'Tambah Karyawan'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('employees.index')" class="text-gray-500 hover:text-gray-700 font-bold">
                    &larr; Kembali
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ isEditing ? 'Edit Karyawan: ' + employee.name : 'Tulis Karyawan Baru' }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg p-8">
                    <form @submit.prevent="submit">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            
                            <!-- Kolom Kiri: Profil Dasar -->
                            <div>
                                <h3 class="font-bold text-gray-900 border-b pb-2 mb-4">Profil Pegawai</h3>
                                
                                <div class="mb-4">
                                    <InputLabel for="nik_internal" value="NIK Internal / ID Pegawai" />
                                    <TextInput id="nik_internal" type="text" v-model="form.nik_internal" class="mt-1 block w-full bg-gray-50" required />
                                    <InputError :message="form.errors.nik_internal" class="mt-2" />
                                </div>

                                <div class="mb-4">
                                    <InputLabel for="name" value="Nama Lengkap" />
                                    <TextInput id="name" type="text" v-model="form.name" class="mt-1 block w-full bg-yellow-50" required />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="mb-4">
                                        <InputLabel for="ktp_number" value="No KTP" />
                                        <TextInput id="ktp_number" type="text" v-model="form.ktp_number" class="mt-1 block w-full" />
                                        <InputError :message="form.errors.ktp_number" class="mt-2" />
                                    </div>
                                    <div class="mb-4">
                                        <InputLabel for="npwp_number" value="No NPWP" />
                                        <TextInput id="npwp_number" type="text" v-model="form.npwp_number" class="mt-1 block w-full" />
                                        <InputError :message="form.errors.npwp_number" class="mt-2" />
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <InputLabel for="ptkp_status" value="Status PTKP (Pajak)" />
                                    <select id="ptkp_status" v-model="form.ptkp_status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="">-- Kosongkan / Default --</option>
                                        <option v-for="ptkp in ptkpStatuses" :key="ptkp.code" :value="ptkp.code">{{ ptkp.code }} - {{ ptkp.description }}</option>
                                    </select>
                                    <InputError :message="form.errors.ptkp_status" class="mt-2" />
                                </div>

                                <h3 class="font-bold text-gray-900 border-b pb-2 mt-8 mb-4">Status Ketenagakerjaan</h3>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="mb-4">
                                        <InputLabel for="join_date" value="Tanggal Bergabung" />
                                        <TextInput id="join_date" type="date" v-model="form.join_date" class="mt-1 block w-full" required />
                                        <InputError :message="form.errors.join_date" class="mt-2" />
                                    </div>
                                    <div class="mb-4">
                                        <InputLabel for="resign_date" value="Tanggal Resign" />
                                        <TextInput id="resign_date" type="date" v-model="form.resign_date" class="mt-1 block w-full" />
                                        <InputError :message="form.errors.resign_date" class="mt-2" />
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <label class="flex items-center mt-2 cursor-pointer bg-gray-50 p-3 rounded border">
                                        <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500 w-5 h-5" />
                                        <span class="ms-3 font-semibold text-gray-700">Karyawan Aktif Bekerja (Dihitung Payroll)</span>
                                    </label>
                                    <InputError :message="form.errors.is_active" class="mt-2" />
                                </div>

                            </div>

                            <!-- Kolom Kanan: Penempatan & Pembayaran -->
                            <div>
                                <h3 class="font-bold text-gray-900 border-b pb-2 mb-4">Informasi Penempatan</h3>

                                <div class="mb-4">
                                    <InputLabel for="employment_type" value="Tipe Kontrak Pekerjaan" />
                                    <select id="employment_type" v-model="form.employment_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="permanent">Karyawan Tetap (Permanent)</option>
                                        <option value="contract">PKWT (Contract)</option>
                                        <option value="freelance">Supir Harian / BHL (Freelance)</option>
                                        <option value="outsource">Alhadaya (Outsource)</option>
                                    </select>
                                    <InputError :message="form.errors.employment_type" class="mt-2" />
                                </div>

                                <div class="mb-4">
                                    <InputLabel for="branch_id" value="Pilih Cabang (Branch)" />
                                    <select id="branch_id" v-model="form.branch_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="">-- Kosongkan / Opsional --</option>
                                        <option v-for="br in branches" :key="br.id" :value="br.id">{{ br.name }} ({{br.code}})</option>
                                    </select>
                                    <InputError :message="form.errors.branch_id" class="mt-2" />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="mb-4">
                                        <InputLabel for="department_id" value="Pilih Departemen" />
                                        <select id="department_id" v-model="form.department_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                            <option value="">-- Opsional --</option>
                                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                                        </select>
                                        <InputError :message="form.errors.department_id" class="mt-2" />
                                    </div>
                                    <div class="mb-4">
                                        <InputLabel for="position_id" value="Pilih Jabatan" />
                                        <select id="position_id" v-model="form.position_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                            <option value="">-- Opsional --</option>
                                            <option v-for="pos in positions" :key="pos.id" :value="pos.id">{{ pos.name }}</option>
                                        </select>
                                        <InputError :message="form.errors.position_id" class="mt-2" />
                                    </div>
                                </div>


                                <h3 class="font-bold text-gray-900 border-b pb-2 mt-8 mb-4">Informasi Rekening / Gaji</h3>

                                <div class="mb-4">
                                    <InputLabel for="payment_method" value="Metode Pembayaran Gaji" />
                                    <select id="payment_method" v-model="form.payment_method" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="bank_transfer">Transfer Bank (Bank Transfer)</option>
                                        <option value="cash">Uang Tunai (Cash)</option>
                                    </select>
                                    <InputError :message="form.errors.payment_method" class="mt-2" />
                                </div>

                                <div v-if="form.payment_method === 'bank_transfer'" class="grid grid-cols-2 gap-4 p-4 border rounded-md bg-blue-50 border-blue-100">
                                    <div class="mb-2">
                                        <InputLabel for="bank_name" value="Nama Bank" />
                                        <TextInput id="bank_name" type="text" v-model="form.bank_name" class="mt-1 block w-full" placeholder="Contoh: BCA, Mandiri" />
                                        <InputError :message="form.errors.bank_name" class="mt-2" />
                                    </div>
                                    <div class="mb-2">
                                        <InputLabel for="bank_account" value="Nomor Rekening" />
                                        <TextInput id="bank_account" type="text" v-model="form.bank_account" class="mt-1 block w-full" />
                                        <InputError :message="form.errors.bank_account" class="mt-2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bagian Tunjangan & Potongan Khusus -->
                        <div class="mt-10 border-t pt-8">
                            <h3 class="font-bold text-gray-900 border-b pb-2 mb-4">Tunjangan & Potongan Khusus (Personal Allowance)</h3>
                            <p class="text-sm text-gray-500 mb-6">
                                Anda bisa mendaftarkan komponen gaji khusus yang nominalnya hanya berlaku dan <strong>menimpa (override)</strong> tarif bawaan untuk karyawan ini saja. Misalnya: Tunjangan Kinerja atau Tunjangan Khusus tertentu.
                            </p>
                            
                            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-200 sm:rounded-lg">
                                <table class="w-full text-left text-sm text-gray-600">
                                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                        <tr>
                                            <th class="px-6 py-3 font-semibold">Pilih Komponen Tunjangan / Potongan</th>
                                            <th class="px-6 py-3 font-semibold">Nominal Dasar Pegawai (Rp)</th>
                                            <th class="px-6 py-3 font-semibold text-center w-24">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr v-for="(comp, index) in form.specific_components" :key="index" class="bg-white">
                                            <td class="px-6 py-4">
                                                <select v-model="comp.payroll_component_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                                    <option value="" disabled>-- Pilih Komponen --</option>
                                                    <option v-for="c in components" :key="c.id" :value="c.id">
                                                        [{{ c.component_type === 'earning' ? '+' : '-' }}] {{ c.code }} - {{ c.name }}
                                                    </option>
                                                </select>
                                                <InputError :message="form.errors[`specific_components.${index}.payroll_component_id`]" class="mt-2" />
                                            </td>
                                            <td class="px-6 py-4">
                                                <TextInput type="number" v-model="comp.amount" class="block w-full text-right" min="0" required />
                                                <InputError :message="form.errors[`specific_components.${index}.amount`]" class="mt-2" />
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <button type="button" @click="removeSpecificComponent(index)" class="text-red-600 hover:text-red-900 font-bold p-2 bg-red-50 rounded-md ring-1 ring-red-200">Hapus</button>
                                            </td>
                                        </tr>
                                        <tr v-if="form.specific_components.length === 0">
                                            <td colspan="3" class="px-6 py-6 text-center text-gray-500 italic bg-gray-50">Belum ada rincian komponen yang terdaftar khusus untuknya.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-4 flex justify-start">
                                <button type="button" @click="addSpecificComponent" class="inline-flex items-center px-4 py-2 bg-indigo-50 border border-indigo-200 border-dashed rounded-md font-semibold text-xs text-indigo-700 hover:bg-indigo-100 uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    + Tambah Rincian
                                </button>
                            </div>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="mt-10 flex justify-end border-t pt-6">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ isEditing ? 'Simpan Perubahan Data' : 'Buat Data Pegawai Baru' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
