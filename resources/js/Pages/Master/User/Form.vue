<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    userModel: Object,
    roles: Array,
    userRole: String,
    employees: Array,
});

const isEdit = !!props.userModel.id;

const form = useForm({
    name: props.userModel.name || '',
    email: props.userModel.email || '',
    password: '',
    password_confirmation: '',
    role: props.userRole || '',
    employee_id: props.userModel.employee_id || '',
});

const submit = () => {
    if (isEdit) {
        form.put(route('users.update', props.userModel.id));
    } else {
        form.post(route('users.store'));
    }
};

</script>

<template>
    <Head :title="isEdit ? 'Ubah Akun Pengguna' : 'Tambah Akun Baru'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ isEdit ? 'Edit Pengguna: ' + userModel.name : 'Pendaftaran Karyawan/User Baru' }}
                </h2>
                <Link :href="route('users.index')" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                    &larr; Kembali ke Daftar
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg p-6">
                    <!-- Warning Error Block -->
                    <div v-if="form.errors.error" class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                        <p class="font-bold">Error System</p>
                        <p>{{ form.errors.error }}</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-8">
                        
                        <!-- Block 1: Identitas User -->
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">Informasi Profil</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="name" value="Nama Lengkap Karyawan" />
                                    <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>
                                
                                <div class="col-span-1 md:col-span-2"></div> <!-- Spacer -->

                                <div>
                                    <InputLabel for="email" value="Alamat Email (Digunakan untuk Login)" />
                                    <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autocomplete="username" />
                                    <InputError class="mt-2" :message="form.errors.email" />
                                </div>
                            </div>
                        </div>

                        <!-- Block 1.5: Pemetaan Karyawan -->
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">Integrasi Data Karyawan (Opsional)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="employee_id" value="Kaitkan dengan Profil Karyawan" />
                                    <select
                                        id="employee_id"
                                        v-model="form.employee_id"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="">-- Tidak Ditautkan (Hanya untuk Admin/Root) --</option>
                                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                            {{ emp.nik_internal }} - {{ emp.name }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.employee_id" />
                                </div>
                            </div>
                        </div>

                        <!-- Block 2: Pemetaan Role -->
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">Penugasan Hak Akses Khusus</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="role" value="Jabatan Sistem (Role)" />
                                    <select
                                        id="role"
                                        v-model="form.role"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="" disabled>-- Pilih Level Akses --</option>
                                        <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.role" />
                                </div>
                            </div>
                        </div>

                        <!-- Block 3: Keamanan Sandi -->
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">Pengaturan Sandi / Password Keamanan</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="password" :value="isEdit ? 'Ubah Password (Kosongkan bila tidak ingin diganti)' : 'Password Pendaftaran'" />
                                    <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" :required="!isEdit" />
                                    <InputError class="mt-2" :message="form.errors.password" />
                                </div>

                                <div>
                                    <InputLabel for="password_confirmation" :value="isEdit ? 'Ketik Ulang Password Baru' : 'Ulangi Password Anda'" />
                                    <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" :required="!isEdit && form.password !== ''" />
                                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end gap-x-4 border-t pt-6">
                            <Link :href="route('users.index')" class="text-sm font-semibold leading-6 text-gray-900 hover:text-gray-700">Tutup Batal</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ isEdit ? 'Submit Ubah Data' : 'Daftarkan Account Sekarang' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
