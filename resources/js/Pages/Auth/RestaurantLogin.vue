<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Store, LogIn, Lock, Mail, ArrowRight } from '@lucide/vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = (): void => {
    form.post('/restaurant/login');
};
</script>

<template>
    <div class="relative flex min-h-dvh items-center justify-center overflow-hidden bg-stone-50 px-4 py-10 font-sans text-stone-900">
        <Head title="دخول المطعم" />

        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,.12),transparent_40%),radial-gradient(circle_at_bottom_left,rgba(245,158,11,.1),transparent_35%)]" />

        <div class="relative w-full max-w-md space-y-6">
            <div class="text-center">
                <Link href="/" class="mb-5 inline-flex items-center gap-2.5">
                    <img
                        src="/images/logo.png"
                        alt="Wasla"
                        class="h-12 w-12 rounded-2xl object-cover shadow-lg ring-1 ring-stone-200"
                    />
                    <span class="text-lg font-black text-stone-900">Wasla</span>
                </Link>
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-100 text-orange-600">
                    <Store class="h-6 w-6" />
                </div>
                <h1 class="text-2xl font-black text-stone-900">دخول المطعم</h1>
                <p class="mt-1 text-sm text-stone-500">إدارة الطلبات والمنيو والكباتن من مكان واحد</p>
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-stone-600">البريد الإلكتروني</label>
                        <div class="relative">
                            <Mail class="absolute right-3.5 top-3.5 h-4 w-4 text-stone-400" />
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full rounded-xl border border-stone-200 bg-stone-50 py-3 pr-10 pl-4 text-sm text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none"
                                placeholder="owner@example.com"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1 text-[11px] text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-stone-600">كلمة المرور</label>
                        <div class="relative">
                            <Lock class="absolute right-3.5 top-3.5 h-4 w-4 text-stone-400" />
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                class="w-full rounded-xl border border-stone-200 bg-stone-50 py-3 pr-10 pl-4 text-sm text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none"
                                placeholder="••••••••"
                            />
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-[11px] text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2 text-xs text-stone-500">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="rounded text-orange-600 focus:ring-orange-500"
                        />
                        <span>تذكرني</span>
                    </label>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 py-3.5 text-sm font-black text-white shadow-md shadow-orange-500/25 transition hover:from-orange-600 hover:to-amber-600 disabled:opacity-60"
                    >
                        <LogIn class="h-4 w-4" />
                        <span>{{ form.processing ? 'جارٍ الدخول...' : 'دخول' }}</span>
                    </button>
                </form>
            </div>

            <div class="text-center">
                <Link href="/" class="inline-flex items-center gap-1 text-xs font-bold text-stone-400 transition hover:text-orange-600">
                    <ArrowRight class="h-3.5 w-3.5 rotate-180" />
                    العودة للموقع
                </Link>
            </div>
        </div>
    </div>
</template>
