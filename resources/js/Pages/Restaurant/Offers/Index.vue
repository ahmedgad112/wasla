<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import type { Restaurant, Offer, PaginatedResponse } from '../../../Types';
import {
    Tag,
    Plus,
    Trash2,
    Edit2,
    Percent,
    GraduationCap,
    Upload,
    Calendar,
    X,
} from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';

const props = defineProps<{
    offers: PaginatedResponse<Offer>;
    restaurant: Restaurant;
}>();

const items = computed(() => props.offers?.data || []);
const showCreateModal = ref(false);
const editingOffer = ref<Offer | null>(null);
const confirmDeleteId = ref<number | null>(null);

const createForm = useForm({
    title: '',
    description: '',
    original_price: '',
    discount_price: '',
    is_active: true,
    is_student_only: false,
    start_date: '',
    end_date: '',
    image: null as File | null,
});
const createImagePreview = ref<string | null>(null);
const createFileInput = ref<HTMLInputElement | null>(null);

const editForm = useForm({
    title: '',
    description: '',
    original_price: '',
    discount_price: '',
    is_active: true,
    is_student_only: false,
    start_date: '',
    end_date: '',
    image: null as File | null,
});
const editImagePreview = ref<string | null>(null);
const editFileInput = ref<HTMLInputElement | null>(null);

const getOfferImageUrl = (imagePath: string | null | undefined): string => {
    if (!imagePath) {
        return '/images/sandwich-foul.jpg';
    }
    if (imagePath.startsWith('http') || imagePath.startsWith('/')) {
        return imagePath;
    }
    return `/storage/${imagePath}`;
};

const onCreateImageChange = (e: Event): void => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        createForm.image = file;
        createImagePreview.value = URL.createObjectURL(file);
    }
};

const onEditImageChange = (e: Event): void => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        editForm.image = file;
        editImagePreview.value = URL.createObjectURL(file);
    }
};

const handleCreateSubmit = (): void => {
    createForm.post('/restaurant/offers', {
        forceFormData: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            createImagePreview.value = null;
        },
    });
};

const openEditModal = (offer: Offer): void => {
    editingOffer.value = offer;
    editForm.title = offer.title;
    editForm.description = offer.description || '';
    editForm.original_price = String(offer.original_price);
    editForm.discount_price = String(offer.discount_price);
    editForm.is_active = offer.is_active;
    editForm.is_student_only = offer.is_student_only || false;
    editForm.start_date = offer.start_date ? offer.start_date.split('T')[0] : '';
    editForm.end_date = offer.end_date ? offer.end_date.split('T')[0] : '';
    editForm.image = null;
    editImagePreview.value = getOfferImageUrl(offer.image);
};

const handleEditSubmit = (): void => {
    if (!editingOffer.value) {
        return;
    }

    router.post(
        `/restaurant/offers/${editingOffer.value.id}`,
        {
            _method: 'PUT',
            ...editForm.data(),
        },
        {
            forceFormData: true,
            onSuccess: () => {
                editingOffer.value = null;
                editForm.reset();
                editImagePreview.value = null;
            },
        },
    );
};

const handleToggle = (id: number): void => {
    router.post(`/restaurant/offers/${id}/toggle`);
};

const calcDiscount = (orig: string | number, disc: string | number): number => {
    const o = Number(orig);
    const d = Number(disc);
    if (o > 0 && d > 0 && o > d) {
        return Math.round(((o - d) / o) * 100);
    }
    return 0;
};

const offerDiscountPct = (offer: Offer): string | number =>
    offer.discount_percentage
        ? Number(offer.discount_percentage).toFixed(0)
        : calcDiscount(offer.original_price, offer.discount_price);

const openCreateModal = (): void => {
    createForm.reset();
    createImagePreview.value = null;
    showCreateModal.value = true;
};

const confirmDelete = (): void => {
    if (confirmDeleteId.value) {
        router.delete(`/restaurant/offers/${confirmDeleteId.value}`);
    }
    confirmDeleteId.value = null;
};

const onImgError = (e: Event): void => {
    (e.target as HTMLImageElement).src = '/images/sandwich-foul.jpg';
};
</script>

<template>
    <Head title="العروض والخصومات — بوابة المطعم" />

    <div class="space-y-6">
        <div class="bg-white/80 dark:bg-stone-900/80 backdrop-blur-xl rounded-3xl border border-orange-100/80 dark:border-stone-800 p-6 sm:p-8 shadow-xl shadow-orange-500/5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl sm:text-2xl font-black text-stone-900 dark:text-white">
                            عروض وخصومات المطعم
                        </h1>
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-sm">
                            {{ items.length }} عرض متاح
                        </span>
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1.5">
                        أضف عروضاً ترويجية حصرية وصوراً جذابة مع إمكانية تخصيص عروض لطلاب الجامعات المعتمدين 🎓
                    </p>
                </div>

                <button
                    class="px-5 py-3 rounded-2xl bg-gradient-to-r from-orange-600 via-amber-500 to-orange-500 hover:from-orange-700 hover:to-amber-600 text-white font-black text-xs shadow-lg shadow-orange-500/25 transition flex items-center gap-2 shrink-0 hover:scale-102"
                    @click="openCreateModal"
                >
                    <Plus class="w-4 h-4" />
                    <span>إنشاء عرض جديد</span>
                </button>
            </div>
        </div>

        <div
            v-if="items.length === 0"
            class="bg-white/80 dark:bg-stone-900/80 backdrop-blur-xl rounded-3xl border border-orange-100/80 dark:border-stone-800 p-12 text-center shadow-xl shadow-orange-500/5"
        >
            <div class="w-16 h-16 rounded-3xl bg-orange-100 dark:bg-stone-800 flex items-center justify-center text-orange-500 mx-auto mb-4">
                <Tag class="w-8 h-8" />
            </div>
            <h3 class="text-base font-black text-stone-900 dark:text-white mb-1">لا توجد عروض ترويجية بعد</h3>
            <p class="text-xs text-stone-400 max-w-sm mx-auto mb-4">
                العروض والخصومات تزيد من مبيعات مطعمك وتظهر لآلاف الطلاب والزبائن على الصفحة الرئيسية.
            </p>
            <button
                class="px-5 py-2.5 rounded-2xl bg-orange-600 text-white font-black text-xs shadow-md hover:bg-orange-700 transition"
                @click="showCreateModal = true"
            >
                إنشاء أول عرض الآن
            </button>
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="offer in items"
                :key="offer.id"
                :class="[
                    'group relative flex flex-col bg-white dark:bg-stone-900 rounded-3xl overflow-hidden border transition-all duration-300 hover:shadow-2xl hover:shadow-orange-500/10 hover:-translate-y-1',
                    offer.is_active
                        ? 'border-orange-100/80 dark:border-stone-800'
                        : 'border-stone-200 dark:border-stone-800 opacity-75',
                ]"
            >
                <div class="relative w-full h-48 overflow-hidden bg-stone-100 dark:bg-stone-950">
                    <img
                        :src="getOfferImageUrl(offer.image)"
                        :alt="offer.title"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        @error="onImgError"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-transparent pointer-events-none" />

                    <div class="absolute top-3 right-3 px-3 py-1 rounded-2xl bg-gradient-to-r from-red-600 to-rose-500 text-white font-black text-xs shadow-lg flex items-center gap-1">
                        <Percent class="w-3.5 h-3.5" />
                        <span>خصم {{ offerDiscountPct(offer) }}%</span>
                    </div>

                    <div
                        v-if="offer.is_student_only"
                        class="absolute top-3 left-3 px-2.5 py-1 rounded-2xl bg-blue-600/90 backdrop-blur-md text-white font-black text-[10px] shadow-lg flex items-center gap-1 border border-blue-400/30"
                    >
                        <GraduationCap class="w-3.5 h-3.5" />
                        <span>حصري للطلاب 🎓</span>
                    </div>

                    <div class="absolute bottom-3 right-3 flex items-baseline gap-2 bg-stone-950/85 backdrop-blur-md px-3 py-1 rounded-2xl border border-stone-200 text-white">
                        <span class="text-base font-black text-amber-400">{{ offer.discount_price }} ج.م</span>
                        <span class="text-xs text-stone-400 line-through">{{ offer.original_price }} ج.م</span>
                    </div>
                </div>

                <div class="flex-1 p-5 flex flex-col justify-between gap-4">
                    <div class="space-y-2">
                        <h3 class="text-base font-black text-stone-900 dark:text-white leading-snug group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">
                            {{ offer.title }}
                        </h3>
                        <p v-if="offer.description" class="text-xs text-stone-500 dark:text-stone-400 line-clamp-2 leading-relaxed">
                            {{ offer.description }}
                        </p>

                        <div v-if="offer.start_date" class="flex items-center gap-1.5 text-[11px] font-bold text-stone-400 pt-1">
                            <Calendar class="w-3.5 h-3.5 text-orange-500" />
                            <span>
                                صالح حتى: {{ offer.end_date ? new Date(offer.end_date).toLocaleDateString('ar-EG') : 'غير محدد' }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-stone-100 dark:border-stone-800 flex items-center justify-between gap-2">
                        <button
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-black transition flex items-center gap-1.5',
                                offer.is_active
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100'
                                    : 'bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-400 hover:bg-stone-200',
                            ]"
                            @click="handleToggle(offer.id)"
                        >
                            <span :class="['w-2 h-2 rounded-full', offer.is_active ? 'bg-emerald-500' : 'bg-stone-400']" />
                            <span>{{ offer.is_active ? 'العرض نشط' : 'متوقف' }}</span>
                        </button>

                        <div class="flex items-center gap-1">
                            <button
                                class="p-2 rounded-xl bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 hover:bg-orange-50 hover:text-orange-600 dark:hover:bg-orange-950/40 dark:hover:text-orange-400 transition"
                                title="تعديل العرض والصورة"
                                @click="openEditModal(offer)"
                            >
                                <Edit2 class="w-3.5 h-3.5" />
                            </button>
                            <button
                                class="p-2 rounded-xl bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400 transition"
                                title="حذف العرض"
                                @click="confirmDeleteId = offer.id"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-stone-950/70 backdrop-blur-sm transition-opacity" @click="showCreateModal = false" />
        <div class="relative bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 w-full max-w-xl p-6 sm:p-8 shadow-2xl z-10 animate-fade-in max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h2 class="text-lg font-black text-stone-900 dark:text-white">
                        إنشاء عرض ترويجي جديد
                    </h2>
                    <p class="text-xs text-stone-400 mt-0.5">ارفع صورة مميزة وحدد السعر قبل وبعد الخصم</p>
                </div>
                <button class="p-2 text-stone-400 hover:text-stone-600 dark:hover:text-white rounded-xl" @click="showCreateModal = false">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form class="space-y-4" @submit.prevent="handleCreateSubmit">
                <div>
                    <label class="block text-xs font-black mb-1.5">صورة العرض الترويجي (بانر جذاب)</label>
                    <input ref="createFileInput" type="file" accept="image/*" class="hidden" @change="onCreateImageChange" />

                    <div v-if="createImagePreview" class="relative w-full h-44 rounded-2xl overflow-hidden border-2 border-orange-500/50 group bg-stone-100 dark:bg-stone-800">
                        <img :src="createImagePreview" alt="Preview" class="w-full h-full object-cover" />
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-3">
                            <button type="button" class="px-3 py-1.5 bg-orange-600 text-white rounded-xl text-xs font-black" @click="createFileInput?.click()">
                                تغيير الصورة
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 bg-red-600 text-white rounded-xl text-xs font-black"
                                @click="createForm.image = null; createImagePreview = null"
                            >
                                إزالة
                            </button>
                        </div>
                    </div>
                    <div
                        v-else
                        class="w-full h-36 rounded-2xl border-2 border-dashed border-orange-300 dark:border-stone-700 hover:border-orange-500 bg-orange-50/30 dark:bg-stone-800/50 flex flex-col items-center justify-center gap-2 cursor-pointer transition p-4 text-center"
                        @click="createFileInput?.click()"
                    >
                        <div class="w-10 h-10 rounded-2xl bg-orange-100 dark:bg-stone-700 text-orange-600 dark:text-orange-400 flex items-center justify-center">
                            <Upload class="w-5 h-5" />
                        </div>
                        <span class="text-xs font-black text-stone-800 dark:text-stone-200">اضغط لرفع صورة العرض الترويجي</span>
                        <span class="text-[10px] text-stone-400">PNG, JPG, WEBP بحد أقصى 10MB</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black mb-1.5">عنوان العرض *</label>
                    <input
                        v-model="createForm.title"
                        type="text"
                        required
                        placeholder="مثال: عرض الغداء الجامعي — 3 ساندوتشات + كانز هدية"
                        class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black mb-1.5">السعر الأصلي (ج.م) *</label>
                        <input
                            v-model="createForm.original_price"
                            type="number"
                            step="0.5"
                            min="0"
                            required
                            placeholder="مثال: 60"
                            class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-black mb-1.5">سعر العرض بعد الخصم (ج.م) *</label>
                        <input
                            v-model="createForm.discount_price"
                            type="number"
                            step="0.5"
                            min="0"
                            required
                            placeholder="مثال: 45"
                            class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                        />
                    </div>
                </div>

                <div
                    v-if="createForm.original_price && createForm.discount_price"
                    class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 flex items-center justify-between text-xs font-black text-amber-900 dark:text-amber-200"
                >
                    <span>نسبة التخفيض للزبون:</span>
                    <span class="text-amber-600 dark:text-amber-400 font-black">
                        خصم {{ calcDiscount(createForm.original_price, createForm.discount_price) }}% (توفير {{ (Number(createForm.original_price) - Number(createForm.discount_price)).toFixed(1) }} ج.م)
                    </span>
                </div>

                <div>
                    <label class="block text-xs font-black mb-1.5">الوصف التفصيلي للعرض</label>
                    <textarea
                        v-model="createForm.description"
                        rows="2"
                        placeholder="اكتب تفاصيل العرض وما يشمله لجذب العملاء..."
                        class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500 resize-none"
                    />
                </div>

                <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/60 space-y-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            v-model="createForm.is_student_only"
                            type="checkbox"
                            class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500"
                        />
                        <div>
                            <span class="text-xs font-black text-blue-950 dark:text-blue-200 flex items-center gap-1.5">
                                <GraduationCap class="w-4 h-4 text-blue-600" />
                                عرض مخصص للطلاب فقط (كارنيه جامعي معتمد)
                            </span>
                            <p class="text-[11px] text-blue-700 dark:text-blue-300 mt-0.5">
                                لن يظهر هذا الخصم إلا للطلاب الذين تم رفع بطاقتهم واعتمادها من الإدارة.
                            </p>
                        </div>
                    </label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="flex-1 py-3.5 rounded-2xl bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-700 hover:to-amber-600 text-white font-black text-xs shadow-lg shadow-orange-500/25 transition disabled:opacity-50"
                    >
                        {{ createForm.processing ? 'جاري النشر...' : 'نشر العرض' }}
                    </button>
                    <button
                        type="button"
                        class="py-3.5 px-6 rounded-2xl border border-stone-200 dark:border-stone-700 text-xs font-black hover:bg-stone-100 dark:hover:bg-stone-800 transition"
                        @click="showCreateModal = false"
                    >
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div v-if="editingOffer" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-stone-950/70 backdrop-blur-sm transition-opacity" @click="editingOffer = null" />
        <div class="relative bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 w-full max-w-xl p-6 sm:p-8 shadow-2xl z-10 animate-fade-in max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h2 class="text-lg font-black text-stone-900 dark:text-white">
                        تعديل العرض والصورة
                    </h2>
                    <p class="text-xs text-stone-400 mt-0.5">{{ editingOffer.title }}</p>
                </div>
                <button class="p-2 text-stone-400 hover:text-stone-600 dark:hover:text-white rounded-xl" @click="editingOffer = null">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form class="space-y-4" @submit.prevent="handleEditSubmit">
                <div>
                    <label class="block text-xs font-black mb-1.5">صورة العرض الترويجي</label>
                    <input ref="editFileInput" type="file" accept="image/*" class="hidden" @change="onEditImageChange" />

                    <div class="relative w-full h-44 rounded-2xl overflow-hidden border-2 border-orange-500/40 group bg-stone-100 dark:bg-stone-800">
                        <img
                            :src="editImagePreview || getOfferImageUrl(editingOffer.image)"
                            alt="Offer"
                            class="w-full h-full object-cover"
                        />
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-3">
                            <button
                                type="button"
                                class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-black flex items-center gap-1.5 shadow-lg"
                                @click="editFileInput?.click()"
                            >
                                <Upload class="w-3.5 h-3.5" />
                                <span>اختيار صورة جديدة</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black mb-1.5">عنوان العرض *</label>
                    <input
                        v-model="editForm.title"
                        type="text"
                        required
                        class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black mb-1.5">السعر الأصلي (ج.م) *</label>
                        <input
                            v-model="editForm.original_price"
                            type="number"
                            step="0.5"
                            min="0"
                            required
                            class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-black mb-1.5">سعر العرض بعد الخصم (ج.م) *</label>
                        <input
                            v-model="editForm.discount_price"
                            type="number"
                            step="0.5"
                            min="0"
                            required
                            class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black mb-1.5">الوصف التفصيلي للعرض</label>
                    <textarea
                        v-model="editForm.description"
                        rows="2"
                        class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500 resize-none"
                    />
                </div>

                <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/60">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            v-model="editForm.is_student_only"
                            type="checkbox"
                            class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500"
                        />
                        <div>
                            <span class="text-xs font-black text-blue-950 dark:text-blue-200 flex items-center gap-1.5">
                                <GraduationCap class="w-4 h-4 text-blue-600" />
                                عرض مخصص للطلاب فقط (كارنيه جامعي معتمد)
                            </span>
                        </div>
                    </label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="editForm.processing"
                        class="flex-1 py-3.5 rounded-2xl bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-700 hover:to-amber-600 text-white font-black text-xs shadow-lg shadow-orange-500/25 transition disabled:opacity-50"
                    >
                        {{ editForm.processing ? 'جاري الحفظ...' : 'حفظ التغييرات' }}
                    </button>
                    <button
                        type="button"
                        class="py-3.5 px-6 rounded-2xl border border-stone-200 dark:border-stone-700 text-xs font-black hover:bg-stone-100 dark:hover:bg-stone-800 transition"
                        @click="editingOffer = null"
                    >
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    <ConfirmModal
        :is-open="confirmDeleteId !== null"
        message="هل أنت متأكد من حذف هذا العرض الترويجي؟ لن تتمكن من استعادته."
        @confirm="confirmDelete"
        @cancel="confirmDeleteId = null"
    />
</template>
