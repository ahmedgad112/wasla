<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LogIn, Lock, Mail, ArrowRight } from '@lucide/vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = (): void => {
    form.post('/delivery/login');
};
</script>

<template>
    <div class="min-h-screen bg-stone-950 text-stone-100 flex items-center justify-center p-4 font-sans">
        <Head title="تسجيل الدخول — بوابة كباتن التوصيل" />

        <div class="max-w-md w-full space-y-8 bg-stone-900 border border-stone-800 p-8 rounded-3xl shadow-2xl">
            <div class="text-center">
                <img
                    src="/images/logo.png"
                    alt="Wasla"
                    class="mx-auto mb-4 h-16 w-16 rounded-2xl object-cover shadow-lg ring-1 ring-stone-700"
                />
                <h1 class="text-2xl font-black text-white">
                    بوابة كباتن التوصيل
                </h1>
                <p class="text-xs text-stone-400 mt-1">
                    استلم طلباتك، حدد مسارك عبر الخريطة، وأكّد استلام الكاش
                </p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-xs font-bold text-stone-300 mb-1">
                        البريد الإلكتروني للطيار
                    </label>
                    <div class="relative">
                        <Mail class="w-4 h-4 text-stone-500 absolute right-3.5 top-3.5" />
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full pr-10 pl-4 py-3 text-xs rounded-xl bg-stone-800 border border-stone-700 text-white focus:outline-none focus:border-emerald-500"
                            placeholder="driver@shabrawy.com"
                        />
                    </div>
                    <p v-if="form.errors.email" class="text-[11px] text-red-400 mt-1">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-300 mb-1">
                        كلمة المرور
                    </label>
                    <div class="relative">
                        <Lock class="w-4 h-4 text-stone-500 absolute right-3.5 top-3.5" />
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            class="w-full pr-10 pl-4 py-3 text-xs rounded-xl bg-stone-800 border border-stone-700 text-white focus:outline-none focus:border-emerald-500"
                            placeholder="••••••••"
                        />
                    </div>
                    <p v-if="form.errors.password" class="text-[11px] text-red-400 mt-1">{{ form.errors.password }}</p>
                </div>

                <div class="flex items-center justify-between text-xs text-stone-400">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="rounded text-emerald-600 focus:ring-emerald-500"
                        />
                        <span>تذكرني أثناء العمل اليومي</span>
                    </label>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-sm shadow-md transition flex items-center justify-center gap-2 disabled:opacity-60"
                >
                    <LogIn class="w-4 h-4" />
                    <span>{{ form.processing ? 'جارٍ تسجيل الدخول...' : 'دخول حساب الطيار' }}</span>
                </button>
            </form>

            <div class="text-center pt-4 border-t border-stone-800">
                <Link href="/" class="text-xs text-stone-400 hover:text-white flex items-center justify-center gap-1">
                    <ArrowRight class="w-3.5 h-3.5 rotate-180" />
                    <span>العودة للموقع الرئيسي</span>
                </Link>
            </div>
        </div>
    </div>
</template>
