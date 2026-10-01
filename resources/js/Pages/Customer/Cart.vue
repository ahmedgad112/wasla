<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useCartStore } from '../../Stores/cartStore';
import type { SharedInertiaProps } from '../../Types';
import {
    ShoppingBag,
    Trash2,
    Plus,
    Minus,
    GraduationCap,
    CheckCircle2,
    Sparkles,
    Lock,
    LogIn,
    User,
} from '@lucide/vue';

const QUICK_NOTES = [
    'طحينة زيادة',
    'بدون شطة',
    'شطة زيادة',
    'ليمون زيادة',
    'كاتشب إضافي',
    'بدون مخلل',
    'العيش محمص',
];

const page = usePage<SharedInertiaProps>();
const auth = computed(() => page.props.auth);
const cart = useCartStore();

const subtotal = computed(() => cart.getSubtotal);
const studentDiscount = computed(() => cart.getStudentDiscountAmount);
const deliveryFee = computed(() => cart.getDeliveryFee);
const total = computed(() => cart.getTotal);
const itemCount = computed(() => cart.items.reduce((acc, i) => acc + i.quantity, 0));

const notes = ref('');

const toggleNote = (preset: string): void => {
    const parts = notes.value
        .split(/[,،]+/)
        .map((s) => s.trim())
        .filter(Boolean);
    if (parts.includes(preset)) {
        notes.value = parts.filter((p) => p !== preset).join('، ');
    } else {
        notes.value = [...parts, preset].join('، ');
    }
};

const onImageError = (e: Event): void => {
    (e.target as HTMLImageElement).src = '/images/sandwich-foul.jpg';
};
</script>

<template>
        <Head title="سلة التسوق" />

        <div v-if="cart.items.length === 0 || !cart.restaurant" class="px-4 py-16 text-center space-y-6">
            <div class="w-24 h-24 rounded-3xl bg-orange-100 dark:bg-orange-950/40 flex items-center justify-center mx-auto shadow-inner">
                <ShoppingBag class="w-12 h-12 text-orange-500" />
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-stone-900 dark:text-white">عربة التسوق فارغة حالياً</h1>
            <p class="text-sm text-stone-500 max-w-md mx-auto">لم تقم بإضافة أي وجبة. تصفح مطاعم جامعة برج العرب واختر وجبتك!</p>
            <Link
                href="/restaurants"
                class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl bg-orange-500 hover:bg-orange-600 text-white font-black text-sm shadow-lg shadow-orange-500/25 transition-all active:scale-95"
            >
                <span>استعراض المطاعم</span>
            </Link>
        </div>

        <div v-else class="px-4 py-4 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-stone-900 dark:text-white flex items-center gap-3">
                        <span>عربة التسوق</span>
                        <span class="text-sm px-3 py-1 rounded-full bg-orange-500/10 text-orange-600 font-bold">{{ itemCount }} أصناف</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-1">طلبك من {{ cart.restaurant.name }}</p>
                </div>
                <button
                    type="button"
                    class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-stone-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
                    @click="cart.clearCart()"
                >
                    <Trash2 class="w-4 h-4" /><span>تفريغ السلة</span>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <div class="lg:col-span-7 bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 shadow-sm overflow-hidden divide-y divide-stone-100 dark:divide-stone-800">
                    <div class="p-4 bg-stone-50 text-xs font-black text-stone-600 dark:text-stone-300 grid grid-cols-12 gap-2">
                        <span class="col-span-6">المنتج</span>
                        <span class="col-span-3 text-center">الكمية</span>
                        <span class="col-span-3 text-left">الإجمالي</span>
                    </div>
                    <div v-for="item in cart.items" :key="item.id" class="p-4 space-y-2">
                        <div class="grid grid-cols-12 gap-2 items-center">
                            <div class="col-span-6 flex items-center gap-3">
                                <div class="relative w-14 h-14 rounded-2xl overflow-hidden bg-stone-100 shrink-0">
                                    <img
                                        :src="item.menuItem.image || '/images/sandwich-foul.jpg'"
                                        :alt="item.menuItem.name"
                                        class="w-full h-full object-cover"
                                        @error="onImageError"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-extrabold text-xs sm:text-sm text-stone-900 dark:text-stone-100 truncate">{{ item.menuItem.name }}</h3>
                                    <span class="text-[11px] text-orange-600 font-semibold">{{ item.unitPrice > 0 ? `${item.unitPrice} ج.م` : 'مجاني' }}</span>
                                    <div v-if="item.selectedOptions.length > 0" class="text-[10px] text-stone-400 mt-0.5">
                                        {{ item.selectedOptions.map((o) => o.valueName).join('، ') }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-3 flex items-center justify-center gap-1 bg-stone-100 dark:bg-stone-800 rounded-xl p-1">
                                <button type="button" class="w-6 h-6 flex items-center justify-center text-stone-600 hover:text-orange-600" @click="cart.updateQuantity(item.id, item.quantity - 1)">
                                    <Minus class="w-3 h-3" />
                                </button>
                                <span class="px-1 text-xs font-black text-stone-800 dark:text-stone-100">{{ item.quantity }}</span>
                                <button type="button" class="w-6 h-6 flex items-center justify-center text-stone-600 hover:text-orange-600" @click="cart.updateQuantity(item.id, item.quantity + 1)">
                                    <Plus class="w-3 h-3" />
                                </button>
                            </div>
                            <div class="col-span-3 flex items-center justify-end gap-2 text-left">
                                <span class="font-black text-xs sm:text-sm text-stone-900 dark:text-stone-100">{{ item.unitPrice > 0 ? `${item.totalPrice} ج.م` : '—' }}</span>
                                <button type="button" class="p-1.5 text-stone-400 hover:text-red-500 rounded-lg hover:bg-stone-100 dark:hover:bg-stone-800" @click="cart.removeItem(item.id)">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                        <div v-if="item.notes" class="text-[10px] text-stone-500 bg-orange-50/50 dark:bg-stone-800/50 px-3 py-1.5 rounded-xl border border-orange-100 dark:border-stone-700">
                            <Sparkles class="w-3 h-3 inline text-orange-400 ml-1" />{{ item.notes }}
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5 bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-6 shadow-sm space-y-6">
                    <h2 class="text-lg font-black text-stone-900 dark:text-white pb-3 border-b border-stone-100 dark:border-stone-800">تأكيد وبيانات الطلب</h2>

                    <div
                        v-if="!auth?.user"
                        class="p-4 rounded-2xl bg-gradient-to-br from-amber-50 to-orange-50 dark:from-stone-800 dark:to-orange-950/30 border border-orange-200 space-y-3 text-center"
                    >
                        <div class="w-11 h-11 rounded-full bg-orange-500/10 text-orange-600 flex items-center justify-center mx-auto"><Lock class="w-5 h-5" /></div>
                        <p class="text-sm font-black text-stone-900 dark:text-white">يلزم تسجيل الدخول لتأكيد الطلب</p>
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <Link href="/login" class="py-2.5 px-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-black text-xs flex items-center justify-center gap-1.5">
                                <LogIn class="w-3.5 h-3.5" /><span>تسجيل الدخول</span>
                            </Link>
                            <Link href="/register" class="py-2.5 px-3 rounded-xl bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-700 dark:text-stone-300 font-bold text-xs flex items-center justify-center gap-1">
                                <User class="w-3.5 h-3.5" /><span>إنشاء حساب</span>
                            </Link>
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex items-center gap-3 p-3.5 rounded-2xl bg-stone-50 dark:bg-stone-800/90 border border-stone-200 dark:border-stone-700/80"
                    >
                        <div class="w-10 h-10 rounded-xl bg-orange-500 text-white flex items-center justify-center font-bold text-sm">{{ auth.user.name.charAt(0) }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-black text-stone-900 dark:text-white truncate">{{ auth.user.name }}</div>
                            <div class="text-[11px] text-stone-500">{{ auth.user.phone || auth.user.email }}</div>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">حساب مسجل ✓</span>
                    </div>

                    <div class="space-y-3 p-4 rounded-2xl bg-stone-50 dark:bg-stone-800/60 border border-stone-200/80 dark:border-stone-700/80">
                        <label class="text-xs font-black text-stone-800 dark:text-stone-200 flex items-center gap-1.5">
                            <Sparkles class="w-4 h-4 text-orange-500" /><span>ملاحظات (اختياري):</span>
                        </label>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="preset in QUICK_NOTES"
                                :key="preset"
                                type="button"
                                class="text-xs px-3 py-1.5 rounded-full font-bold transition-all border"
                                :class="
                                    notes.includes(preset)
                                        ? 'bg-orange-500 text-white border-orange-500'
                                        : 'bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-300 border-stone-200 dark:border-stone-700 hover:border-orange-300'
                                "
                                @click="toggleNote(preset)"
                            >
                                {{ notes.includes(preset) ? '✓ ' : '+ ' }}{{ preset }}
                            </button>
                        </div>
                        <textarea
                            v-model="notes"
                            rows="2"
                            placeholder="ملاحظات إضافية..."
                            class="w-full text-xs p-3 rounded-xl border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-900 dark:text-stone-100 focus:ring-2 focus:ring-orange-500 outline-none"
                        />
                    </div>

                    <div
                        v-if="cart.studentDiscountApplied"
                        class="flex items-center justify-between text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 p-3 rounded-2xl border border-emerald-200 dark:border-emerald-800"
                    >
                        <span class="flex items-center gap-1.5"><GraduationCap class="w-4 h-4 text-emerald-600" /><span>خصم الطلاب الجامعي:</span></span>
                        <span class="bg-emerald-600 text-white text-[10px] px-2 py-0.5 rounded-full font-black">مفعّل تلقائياً 🎓</span>
                    </div>

                    <div class="space-y-2 pt-2 border-t border-stone-100 dark:border-stone-800 text-xs">
                        <div class="flex justify-between text-stone-500">
                            <span>المجموع الفرعي:</span><span class="font-bold text-stone-800 dark:text-stone-200">{{ subtotal.toFixed(2) }} ج.م</span>
                        </div>
                        <div v-if="studentDiscount > 0" class="flex justify-between text-emerald-600">
                            <span>خصم الطلاب ({{ cart.studentDiscountPercentage }}%):</span><span class="font-bold">-{{ studentDiscount.toFixed(2) }} ج.م</span>
                        </div>
                        <div class="flex justify-between text-stone-500">
                            <span>رسوم التوصيل:</span><span class="font-bold text-emerald-600">{{ deliveryFee > 0 ? `${deliveryFee} ج.م` : 'مجاناً' }}</span>
                        </div>
                        <div class="flex justify-between text-base font-black text-stone-900 dark:text-white pt-2 border-t border-stone-100 dark:border-stone-800">
                            <span>الإجمالي الكلي:</span><span class="text-orange-600 dark:text-orange-400">{{ total.toFixed(2) }} ج.م</span>
                        </div>
                    </div>

                    <Link
                        v-if="!auth?.user"
                        href="/login"
                        class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 text-white font-black text-base shadow-xl shadow-orange-500/25 flex items-center justify-center gap-2 transition-all"
                    >
                        <Lock class="w-5 h-5" /><span>سجل دخولك لتأكيد الطلب</span>
                    </Link>
                    <Link
                        v-else
                        href="/checkout"
                        class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-orange-500 via-amber-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white font-black text-base shadow-xl shadow-orange-500/25 flex items-center justify-center gap-2 transition-all active:scale-98"
                    >
                        <CheckCircle2 class="w-5 h-5" /><span>المتابعة لتحديد الموقع بالخريطة وإتمام الطلب 🚀</span>
                    </Link>
                    <p class="text-[11px] text-center text-stone-400">تحديد دقيق لموقع الاستلام بالـ GPS أو المقر الجامعي مع حساب رسوم التوصيل بالكيلومتر</p>
                </div>
            </div>
        </div>
</template>
