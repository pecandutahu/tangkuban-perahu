<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

const isScrolled = ref(false);

onMounted(() => {
    window.addEventListener('scroll', () => {
        isScrolled.value = window.scrollY > 20;
    });
});
</script>

<template>
    <Head title="Welcome to Sobat Gaji" />
    
    <div class="min-h-screen bg-white text-gray-900 font-sans antialiased selection:bg-indigo-500 selection:text-white">
        
        <!-- Navigation -->
        <nav :class="['fixed w-full z-50 transition-all duration-300', isScrolled ? 'bg-white/90 backdrop-blur-md shadow-sm border-b border-gray-100' : 'bg-transparent']">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <div class="flex-shrink-0 flex items-center gap-3">
                        <!-- Logo -->
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white font-bold text-xl shadow-md shadow-indigo-200">
                            S
                        </div>
                        <span class="font-bold text-2xl tracking-tight text-gray-900">
                            Sobat<span class="text-indigo-600">Gaji</span>
                        </span>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <template v-if="canLogin">
                            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-sm font-semibold text-gray-600 hover:text-indigo-600 transition-colors">
                                Dashboard
                            </Link>
                            <template v-else>
                                <Link :href="route('login')" class="text-sm font-semibold text-gray-600 hover:text-indigo-600 transition-colors">
                                    Log in
                                </Link>
                                <Link v-if="canRegister" :href="route('register')" class="inline-flex items-center justify-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
                                    Get Started
                                </Link>
                            </template>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative pt-32 pb-24 sm:pt-40 sm:pb-32 overflow-hidden bg-indigo-50/40 border-b border-indigo-100/50">
            <!-- Lighter Decorative background elements -->
            <div class="absolute inset-0 z-0">
                <div class="absolute top-0 left-1/2 w-[800px] h-[800px] -translate-x-1/2 -translate-y-1/2 opacity-10 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-indigo-300 via-transparent to-transparent rounded-full blur-3xl"></div>
                <div class="absolute top-1/4 right-0 w-[600px] h-[600px] translate-x-1/3 opacity-10 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-blue-300 via-transparent to-transparent rounded-full blur-3xl"></div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight mb-8">
                    <span class="block text-gray-900">Pusat Pengelolaan</span>
                    <span class="block text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 pb-2">
                        SDM & Penggajian
                    </span>
                </h1>
                <p class="mt-4 max-w-2xl mx-auto text-lg md:text-xl text-gray-500 leading-relaxed">
                    Sistem HRIS terpadu yang menyederhanakan perhitungan payroll, BPJS, dan PPh 21 dengan akurasi tinggi dan keamanan terjamin.
                </p>
                <div class="mt-10 flex justify-center gap-4">
                    <Link :href="route('login')" class="inline-flex items-center justify-center px-8 py-4 text-base font-medium rounded-xl text-white bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 shadow-lg shadow-indigo-200 transition-all transform hover:-translate-y-1">
                        Mulai Sekarang
                        <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="py-24 bg-white border-y border-gray-50 relative z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-indigo-600 font-semibold tracking-wide uppercase text-sm">Fitur Unggulan</h2>
                    <p class="mt-2 text-3xl font-extrabold text-gray-900 sm:text-4xl">Satu Platform Untuk Semua</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Feature 1 -->
                    <div class="relative group bg-white p-8 rounded-2xl transition-all duration-300 hover:shadow-lg hover:-translate-y-1 ring-1 ring-gray-100">
                        <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center mb-6 text-indigo-500 group-hover:scale-110 group-hover:rotate-3 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-gray-900">Penggajian Otomatis</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">Kalkulasi gaji dengan cepat, termasuk lembur, tunjangan, dan potongan yang dapat disesuaikan.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="relative group bg-white p-8 rounded-2xl transition-all duration-300 hover:shadow-lg hover:-translate-y-1 ring-1 ring-gray-100">
                        <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center mb-6 text-emerald-500 group-hover:scale-110 group-hover:rotate-3 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-gray-900">BPJS & PPh21 Terintegrasi</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">Perhitungan BPJS Kesehatan, Ketenagakerjaan, dan pajak PPh 21 sesuai dengan regulasi terbaru.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="relative group bg-white p-8 rounded-2xl transition-all duration-300 hover:shadow-lg hover:-translate-y-1 ring-1 ring-gray-100">
                        <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center mb-6 text-amber-500 group-hover:scale-110 group-hover:rotate-3 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-gray-900">Manajemen Karyawan</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">Kelola data personalia, struktur organisasi, jabatan, dan riwayat karyawan dalam satu basis data.</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="relative group bg-white p-8 rounded-2xl transition-all duration-300 hover:shadow-lg hover:-translate-y-1 ring-1 ring-gray-100">
                        <div class="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center mb-6 text-rose-500 group-hover:scale-110 group-hover:rotate-3 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-gray-900">Portal Slip Gaji</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">Akses mandiri bagi karyawan untuk mengunduh slip gaji dan melihat riwayat penggajian kapan saja.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section - Made bright and light -->
        <div class="relative py-20 bg-indigo-50 overflow-hidden relative z-20">
            <div class="absolute inset-0 opacity-40 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Ccircle%20cx%3D%222%22%20cy%3D%222%22%20r%3D%222%22%20fill%3D%22%236366f1%22%20fill-opacity%3D%220.2%22%2F%3E%3C%2Fsvg%3E')]"></div>
            <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-extrabold text-indigo-900 sm:text-4xl">
                    Tingkatkan Efisiensi HR Anda
                </h2>
                <p class="mt-4 text-xl text-indigo-700 max-w-2xl mx-auto font-light">
                    Bergabunglah bersama kami dan transformasikan sistem HR perusahaan Anda menjadi lebih profesional, akurat, dan andal.
                </p>
                <div class="mt-10 flex justify-center gap-4">
                    <Link :href="route('login')" class="bg-indigo-600 text-white px-8 py-4 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg hover:shadow-xl hover:-translate-y-1">
                        Mulai Kelola Payroll
                    </Link>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-white py-12 border-t border-gray-100 relative z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white font-bold text-sm">
                        S
                    </div>
                    <span class="font-bold text-xl text-gray-900">
                        Sobat<span class="text-indigo-600">Gaji</span>
                    </span>
                </div>
                <div class="text-sm text-gray-500 text-center md:text-left">
                    &copy; {{ new Date().getFullYear() }} Sobat Gaji. All rights reserved. <br class="md:hidden">
                </div>
            </div>
        </footer>

    </div>
</template>
