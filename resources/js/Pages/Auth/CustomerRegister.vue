<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UserPlus, Mail, Lock, Phone, User as UserIcon, Eye, EyeOff, Check } from '@lucide/vue';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const hasMinLength = computed(() => form.password.length >= 8);
const hasUppercase = computed(() => /[A-Z]/.test(form.password));
const hasNumber = computed(() => /\d/.test(form.password));
const hasSymbol = computed(() => /[^A-Za-z0-9]/.test(form.password));

const passwordChecks = computed(() => [
    { id: 'length', label: '8 أحرف على الأقل', met: hasMinLength.value },
    { id: 'upper', label: 'حرف كبير (Capital)', met: hasUppercase.value },
    { id: 'number', label: 'رقم', met: hasNumber.value },
    { id: 'symbol', label: 'رمز مثل @ # !', met: hasSymbol.value },
]);

const strengthScore = computed(() => passwordChecks.value.filter((check) => check.met).length);

const strengthLabel = computed(() => {
    if (form.password.length === 0) {
        return '';
    }
    if (strengthScore.value <= 1) {
        return 'ضعيفة';
    }
    if (strengthScore.value === 2) {
        return 'متوسطة';
    }
    if (strengthScore.value === 3) {
        return 'جيدة';
    }

    return 'قوية';
});

const strengthBarClass = computed(() => {
    if (strengthScore.value <= 1) {
        return 'bg-red-500';
    }
    if (strengthScore.value === 2) {
        return 'bg-amber-500';
    }
    if (strengthScore.value === 3) {
        return 'bg-lime-500';
    }

    return 'bg-emerald-500';
});

const passwordIsStrong = computed(() => strengthScore.value === 4);
const passwordsMatch = computed(
    () => form.password_confirmation.length > 0 && form.password === form.password_confirmation,
);

const submit = (): void => {
    if (!passwordIsStrong.value || form.password !== form.password_confirmation) {
        return;
    }

    form.post('/register');
};
</script>

<template>
        <Head title="إنشاء حساب جديد" />

        <div class="flex items-center justify-center px-4 py-6">
            <div class="max-w-md w-full space-y-8 bg-white dark:bg-stone-900 p-8 rounded-3xl border border-stone-200 dark:border-stone-800 shadow-xl">
                <div class="text-center">
                    <img
                        src="/images/logo.png"
                        alt="Wasla"
                        class="mx-auto mb-4 h-24 w-24 rounded-3xl object-cover shadow-md ring-1 ring-stone-200 dark:ring-stone-700"
                    />
                    <h1 class="text-2xl font-black text-stone-900 dark:text-white">
                        إنشاء حسابك
                    </h1>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-2">
                        انضم لمجتمع Wasla في برج العرب واستمتع بخصومات حصرية
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                            الاسم بالكامل
                        </label>
                        <div class="relative">
                            <UserIcon class="w-4 h-4 text-stone-400 absolute right-3.5 top-3.5" />
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full pr-10 pl-4 py-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                                placeholder="أحمد محمد"
                            />
                        </div>
                        <p v-if="form.errors.name" class="text-[11px] text-red-500 mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                            البريد الإلكتروني
                        </label>
                        <div class="relative">
                            <Mail class="w-4 h-4 text-stone-400 absolute right-3.5 top-3.5" />
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full pr-10 pl-4 py-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                                placeholder="ahmed@gmail.com"
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-[11px] text-red-500 mt-1">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                            رقم الهاتف المحمول
                        </label>
                        <div class="relative">
                            <Phone class="w-4 h-4 text-stone-400 absolute right-3.5 top-3.5" />
                            <input
                                v-model="form.phone"
                                type="tel"
                                class="w-full pr-10 pl-4 py-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                                placeholder="010xxxxxxxx"
                            />
                        </div>
                        <p v-if="form.errors.phone" class="text-[11px] text-red-500 mt-1">{{ form.errors.phone }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                            كلمة المرور
                        </label>
                        <div class="relative">
                            <Lock class="w-4 h-4 text-stone-400 absolute right-3.5 top-3.5" />
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                class="w-full px-10 py-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                                dir="ltr"
                                placeholder="اكتب كلمة مرور قوية"
                            />
                            <button
                                type="button"
                                class="absolute left-3.5 top-3 text-stone-400 hover:text-stone-700 dark:hover:text-stone-200"
                                :aria-label="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>

                        <div v-if="form.password.length > 0" class="mt-2 space-y-1.5">
                            <div class="flex items-center justify-between text-[10px] font-bold">
                                <span class="text-stone-500">قوة كلمة المرور</span>
                                <span
                                    :class="{
                                        'text-red-500': strengthScore <= 1,
                                        'text-amber-600': strengthScore === 2,
                                        'text-lime-600': strengthScore === 3,
                                        'text-emerald-600': strengthScore === 4,
                                    }"
                                >
                                    {{ strengthLabel }}
                                </span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-stone-200 dark:bg-stone-800">
                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="strengthBarClass"
                                    :style="{ width: `${(strengthScore / 4) * 100}%` }"
                                />
                            </div>
                            <ul class="grid grid-cols-2 gap-x-2 gap-y-1 pt-1">
                                <li
                                    v-for="check in passwordChecks"
                                    :key="check.id"
                                    class="flex items-center gap-1 text-[10px] font-semibold"
                                    :class="check.met ? 'text-emerald-600' : 'text-stone-400'"
                                >
                                    <Check class="h-3 w-3 shrink-0" />
                                    {{ check.label }}
                                </li>
                            </ul>
                        </div>
                        <p v-if="form.errors.password" class="text-[11px] text-red-500 mt-1">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                            تأكيد كلمة المرور
                        </label>
                        <div class="relative">
                            <Lock class="w-4 h-4 text-stone-400 absolute right-3.5 top-3.5" />
                            <input
                                v-model="form.password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                class="w-full px-10 py-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                                dir="ltr"
                                placeholder="أكد كلمة المرور"
                            />
                            <button
                                type="button"
                                class="absolute left-3.5 top-3 text-stone-400 hover:text-stone-700 dark:hover:text-stone-200"
                                :aria-label="showPasswordConfirmation ? 'إخفاء تأكيد كلمة المرور' : 'إظهار تأكيد كلمة المرور'"
                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                            >
                                <EyeOff v-if="showPasswordConfirmation" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p
                            v-if="form.password_confirmation.length > 0 && !passwordsMatch"
                            class="text-[11px] text-red-500 mt-1"
                        >
                            التأكيد مش مطابق لكلمة المرور
                        </p>
                        <p v-else-if="passwordsMatch" class="text-[11px] font-semibold text-emerald-600 mt-1">
                            التأكيد مطابق
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || !passwordIsStrong || !passwordsMatch"
                        class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white font-bold text-sm shadow-md transition flex items-center justify-center gap-2 disabled:opacity-60"
                    >
                        <UserPlus class="w-4 h-4" />
                        <span>{{ form.processing ? 'جارٍ إنشاء الحساب...' : 'إنشاء الحساب الآن' }}</span>
                    </button>
                </form>

                <div class="text-center pt-4 border-t border-stone-100 dark:border-stone-800 text-xs text-stone-500 dark:text-stone-400">
                    <span>لديك حساب بالفعل؟ </span>
                    <Link href="/login" class="text-orange-600 font-bold hover:underline">
                        سجل دخولك هنا
                    </Link>
                </div>
            </div>
        </div>
</template>
