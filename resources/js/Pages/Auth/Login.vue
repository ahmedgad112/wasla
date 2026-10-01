<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LogIn, Mail, Lock } from '@lucide/vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = (): void => {
    form
        .transform((data) => ({
            ...data,
            remember: Boolean(data.remember),
        }))
        .post('/login');
};
</script>

<template>
        <Head title="تسجيل الدخول" />

        <div class="flex items-center justify-center px-4 py-8">
            <div class="max-w-md w-full space-y-8 bg-white dark:bg-stone-900 p-8 rounded-3xl border border-stone-200 dark:border-stone-800 shadow-xl">
                <div class="text-center">
                    <img
                        src="/images/logo.png"
                        alt="Wasla"
                        class="mx-auto mb-4 h-24 w-24 rounded-3xl object-cover shadow-md ring-1 ring-stone-200 dark:ring-stone-700"
                    />
                    <h1 class="text-2xl font-black text-stone-900 dark:text-white">
                        مرحباً بك في Wasla
                    </h1>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-2">
                        سجل دخولك بالإيميل أو الهاتف، وسيتم فتح لوحة التحكم الخاصة بحسابك
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                            البريد الإلكتروني أو رقم الهاتف
                        </label>
                        <div class="relative">
                            <Mail class="w-4 h-4 text-stone-400 absolute right-3.5 top-3.5" />
                            <input
                                v-model="form.email"
                                type="text"
                                required
                                class="w-full pr-10 pl-4 py-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                                placeholder="your-email@example.com أو 010xxxxxxxx"
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-[11px] text-red-500 mt-1">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                            كلمة المرور
                        </label>
                        <div class="relative">
                            <Lock class="w-4 h-4 text-stone-400 absolute right-3.5 top-3.5" />
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                class="w-full pr-10 pl-4 py-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                                placeholder="••••••••"
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-[11px] text-red-500 mt-1">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <label class="flex cursor-pointer items-start gap-2 text-stone-600 dark:text-stone-400">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                name="remember"
                                class="mt-0.5 rounded text-orange-600 focus:ring-orange-500"
                            />
                            <span class="leading-tight">
                                <span class="block font-semibold text-stone-700 dark:text-stone-300">
                                    تذكرني على هذا الجهاز
                                </span>
                                <span class="block text-[10px] font-medium text-stone-400">
                                    Keep me logged in
                                </span>
                            </span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white font-bold text-sm shadow-md transition flex items-center justify-center gap-2 disabled:opacity-60"
                    >
                        <LogIn class="w-4 h-4" />
                        <span>{{ form.processing ? 'جارٍ تسجيل الدخول...' : 'تسجيل الدخول' }}</span>
                    </button>
                </form>

                <div class="text-center pt-4 border-t border-stone-100 dark:border-stone-800 text-xs text-stone-500 dark:text-stone-400">
                    <span>عميل جديد؟ </span>
                    <Link href="/register" class="text-orange-600 font-bold hover:underline">
                        أنشئ حسابك
                    </Link>
                </div>
            </div>
        </div>
</template>
