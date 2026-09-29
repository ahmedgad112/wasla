<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import type { Restaurant, MenuItem, Category, PaginatedResponse } from '../../../Types';
import {
    Utensils,
    Plus,
    Edit2,
    Trash2,
    Search,
    Upload,
    Clock,
    LayoutGrid,
    Table as TableIcon,
    Camera,
    Flame,
    X,
} from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';

const props = withDefaults(
    defineProps<{
        menu_items: PaginatedResponse<MenuItem>;
        categories?: Category[];
        restaurant: Restaurant;
    }>(),
    {
        categories: () => [],
    },
);

const items = computed(() => props.menu_items?.data || []);
const search = ref('');
const selectedCat = ref('ALL');
const viewMode = ref<'grid' | 'table'>('grid');

const showCreateModal = ref(false);
const editingItem = ref<MenuItem | null>(null);
const quickImageItem = ref<MenuItem | null>(null);
const confirmDeleteId = ref<number | null>(null);

const createForm = useForm({
    name: '',
    category_id: props.categories[0]?.id ? String(props.categories[0].id) : '',
    price: '',
    discount_price: '',
    description: '',
    is_available: true,
    is_featured: false,
    preparation_time: 10,
    image: null as File | null,
});
const createImagePreview = ref<string | null>(null);
const createFileInput = ref<HTMLInputElement | null>(null);

const editForm = useForm({
    name: '',
    category_id: '',
    price: '',
    discount_price: '',
    description: '',
    is_available: true,
    is_featured: false,
    preparation_time: 10,
    image: null as File | null,
});
const editImagePreview = ref<string | null>(null);
const editFileInput = ref<HTMLInputElement | null>(null);

const quickImageForm = useForm({
    image: null as File | null,
});
const quickImagePreview = ref<string | null>(null);
const quickFileInput = ref<HTMLInputElement | null>(null);

const getItemImageUrl = (imagePath: string | null | undefined): string => {
    if (!imagePath) {
        return '/images/sandwich-foul.jpg';
    }
    if (imagePath.startsWith('http') || imagePath.startsWith('/')) {
        return imagePath;
    }
    return `/storage/${imagePath}`;
};

const onImgError = (e: Event): void => {
    (e.target as HTMLImageElement).src = '/images/sandwich-foul.jpg';
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

const onQuickImageChange = (e: Event): void => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        quickImageForm.image = file;
        quickImagePreview.value = URL.createObjectURL(file);
    }
};

const handleCreateSubmit = (): void => {
    createForm.post('/restaurant/menu', {
        forceFormData: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            createImagePreview.value = null;
        },
    });
};

const openEditModal = (item: MenuItem): void => {
    editingItem.value = item;
    editForm.name = item.name;
    editForm.category_id = String(item.category_id);
    editForm.price = String(item.price);
    editForm.discount_price = item.discount_price ? String(item.discount_price) : '';
    editForm.description = item.description || '';
    editForm.is_available = item.is_available;
    editForm.is_featured = item.is_featured;
    editForm.preparation_time = item.preparation_time || 10;
    editForm.image = null;
    editImagePreview.value = getItemImageUrl(item.image);
};

const handleEditSubmit = (): void => {
    if (!editingItem.value) {
        return;
    }

    router.post(
        `/restaurant/menu/${editingItem.value.id}`,
        {
            _method: 'PUT',
            ...editForm.data(),
        },
        {
            forceFormData: true,
            onSuccess: () => {
                editingItem.value = null;
                editForm.reset();
                editImagePreview.value = null;
            },
        },
    );
};

const openQuickImageModal = (item: MenuItem): void => {
    quickImageItem.value = item;
    quickImageForm.image = null;
    quickImagePreview.value = getItemImageUrl(item.image);
};

const handleQuickImageSubmit = (): void => {
    if (!quickImageItem.value || !quickImageForm.image) {
        return;
    }

    router.post(
        `/restaurant/menu/${quickImageItem.value.id}`,
        {
            _method: 'PUT',
            name: quickImageItem.value.name,
            category_id: quickImageItem.value.category_id,
            price: quickImageItem.value.price,
            discount_price: quickImageItem.value.discount_price || '',
            description: quickImageItem.value.description || '',
            is_available: quickImageItem.value.is_available,
            is_featured: quickImageItem.value.is_featured,
            preparation_time: quickImageItem.value.preparation_time || 10,
            image: quickImageForm.image,
        },
        {
            forceFormData: true,
            onSuccess: () => {
                quickImageItem.value = null;
                quickImageForm.reset();
                quickImagePreview.value = null;
            },
        },
    );
};

const handleToggleAvailable = (id: number): void => {
    router.post(`/restaurant/menu/${id}/toggle-availability`);
};

const filteredItems = computed(() =>
    items.value.filter((item) => {
        const matchesSearch =
            item.name.toLowerCase().includes(search.value.toLowerCase()) ||
            (item.description && item.description.toLowerCase().includes(search.value.toLowerCase()));
        const matchesCat = selectedCat.value === 'ALL' || item.category_id === Number(selectedCat.value);
        return matchesSearch && matchesCat;
    }),
);

const openCreateModal = (): void => {
    createForm.reset();
    createImagePreview.value = null;
    showCreateModal.value = true;
};

const confirmDelete = (): void => {
    if (confirmDeleteId.value) {
        router.delete(`/restaurant/menu/${confirmDeleteId.value}`);
    }
    confirmDeleteId.value = null;
};

const basePrice = (item: MenuItem): number =>
    Number(item.discount_price && item.discount_price > 0 ? item.discount_price : item.price);

const hasDiscount = (item: MenuItem): boolean =>
    !!(item.discount_price && Number(item.discount_price) < Number(item.price));

const categoryCount = (catId: number): number => items.value.filter((i) => i.category_id === catId).length;
</script>

<template>
    <Head title="إدارة المنيو — بوابة المطعم" />

    <div class="space-y-6">
        <div class="bg-white/80 dark:bg-stone-900/80 backdrop-blur-xl rounded-3xl border border-orange-100/80 dark:border-stone-800 p-6 shadow-xl shadow-orange-500/5">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-orange-100/60 dark:border-stone-800">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl sm:text-2xl font-black text-stone-900 dark:text-white">
                            أصناف وقائمة الطعام
                        </h1>
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-orange-100 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400">
                            {{ items.length }} صنف متاح
                        </span>
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                        عدّل صور الوجبات، الأسعار، العروض، وتحكم في توفر الأصناف في مطبخك بنقرة زر
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center bg-stone-100 dark:bg-stone-800 p-1 rounded-2xl border border-stone-200 dark:border-stone-700">
                        <button
                            :class="[
                                'p-2 rounded-xl transition flex items-center gap-1 text-xs font-bold',
                                viewMode === 'grid'
                                    ? 'bg-white dark:bg-stone-700 text-orange-600 shadow-xs'
                                    : 'text-stone-500 hover:text-stone-700 dark:hover:text-stone-300',
                            ]"
                            title="عرض شبكي (كروت مع صور)"
                            @click="viewMode = 'grid'"
                        >
                            <LayoutGrid class="w-4 h-4" />
                            <span class="hidden sm:inline">كروت</span>
                        </button>
                        <button
                            :class="[
                                'p-2 rounded-xl transition flex items-center gap-1 text-xs font-bold',
                                viewMode === 'table'
                                    ? 'bg-white dark:bg-stone-700 text-orange-600 shadow-xs'
                                    : 'text-stone-500 hover:text-stone-700 dark:hover:text-stone-300',
                            ]"
                            title="عرض جدول مضغوط"
                            @click="viewMode = 'table'"
                        >
                            <TableIcon class="w-4 h-4" />
                            <span class="hidden sm:inline">جدول</span>
                        </button>
                    </div>

                    <button
                        class="px-4 py-3 rounded-2xl bg-gradient-to-r from-orange-600 via-amber-500 to-orange-500 hover:from-orange-700 hover:to-amber-600 text-white font-black text-xs shadow-lg shadow-orange-500/25 transition-all duration-200 flex items-center gap-2 shrink-0 hover:scale-102"
                        @click="openCreateModal"
                    >
                        <Plus class="w-4 h-4" />
                        <span>إضافة صنف جديد</span>
                    </button>
                </div>
            </div>

            <div class="pt-6 flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <Search class="w-4 h-4 text-stone-400 absolute right-4 top-3.5" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="ابحث باسم الصنف أو المكونات أو الوصف..."
                        class="w-full pr-11 pl-4 py-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800/70 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition-all"
                    />
                </div>

                <div class="flex items-center gap-2 overflow-x-auto pb-1 custom-scrollbar">
                    <button
                        :class="[
                            'px-4 py-2.5 rounded-2xl text-xs font-black transition-all shrink-0 border',
                            selectedCat === 'ALL'
                                ? 'bg-gradient-to-r from-orange-600 to-amber-500 text-white border-transparent shadow-md shadow-orange-500/20'
                                : 'bg-stone-50 dark:bg-stone-800/70 text-stone-600 dark:text-stone-300 border-stone-200 dark:border-stone-700 hover:border-orange-300',
                        ]"
                        @click="selectedCat = 'ALL'"
                    >
                        الكل ({{ items.length }})
                    </button>
                    <button
                        v-for="c in categories"
                        :key="c.id"
                        :class="[
                            'px-4 py-2.5 rounded-2xl text-xs font-black transition-all shrink-0 border',
                            selectedCat === String(c.id)
                                ? 'bg-gradient-to-r from-orange-600 to-amber-500 text-white border-transparent shadow-md shadow-orange-500/20'
                                : 'bg-stone-50 dark:bg-stone-800/70 text-stone-600 dark:text-stone-300 border-stone-200 dark:border-stone-700 hover:border-orange-300',
                        ]"
                        @click="selectedCat = String(c.id)"
                    >
                        {{ c.name }} ({{ categoryCount(c.id) }})
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="filteredItems.length === 0"
            class="bg-white/80 dark:bg-stone-900/80 backdrop-blur-xl rounded-3xl border border-orange-100/80 dark:border-stone-800 p-12 text-center shadow-xl shadow-orange-500/5"
        >
            <div class="w-16 h-16 rounded-3xl bg-orange-100 dark:bg-stone-800 flex items-center justify-center text-orange-500 mx-auto mb-4">
                <Utensils class="w-8 h-8" />
            </div>
            <h3 class="text-base font-black text-stone-900 dark:text-white mb-1">لم يتم العثور على أي أصناف</h3>
            <p class="text-xs text-stone-400 max-w-sm mx-auto mb-4">
                جرب البحث بكلمات أخرى أو اختر تصنيفاً مختلفاً، أو أضف وجبات جديدة إلى منيو المطعم.
            </p>
            <button
                class="px-5 py-2.5 rounded-2xl bg-orange-600 text-white font-black text-xs shadow-md hover:bg-orange-700 transition"
                @click="showCreateModal = true"
            >
                إضافة صنف الآن
            </button>
        </div>

        <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <div
                v-for="item in filteredItems"
                :key="item.id"
                :class="[
                    'group relative flex flex-col bg-white dark:bg-stone-900 rounded-3xl overflow-hidden border transition-all duration-300 hover:shadow-2xl hover:shadow-orange-500/10 hover:-translate-y-1',
                    item.is_available
                        ? 'border-orange-100/80 dark:border-stone-800'
                        : 'border-red-200 dark:border-red-950/60 opacity-80',
                ]"
            >
                <div class="relative w-full h-44 overflow-hidden bg-stone-100 dark:bg-stone-950">
                    <img
                        :src="getItemImageUrl(item.image)"
                        :alt="item.name"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        @error="onImgError"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent pointer-events-none" />

                    <span
                        v-if="item.category?.name"
                        class="absolute top-3 right-3 px-3 py-1 rounded-full text-[10px] font-black bg-stone-950/80 text-orange-400 backdrop-blur-md border border-stone-200 shadow-md"
                    >
                        {{ item.category.name }}
                    </span>

                    <span
                        v-if="item.is_featured"
                        class="absolute top-3 left-3 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-md flex items-center gap-1"
                    >
                        <Flame class="w-3 h-3" />
                        مميز
                    </span>

                    <button
                        class="absolute bottom-3 left-3 px-2.5 py-1.5 rounded-xl bg-white/90 dark:bg-stone-800/90 text-stone-800 dark:text-stone-200 text-[10px] font-black backdrop-blur-md shadow-md hover:bg-orange-600 hover:text-white transition flex items-center gap-1"
                        title="تغيير صورة الطبق"
                        @click="openQuickImageModal(item)"
                    >
                        <Camera class="w-3.5 h-3.5" />
                        <span>تعديل الصورة</span>
                    </button>

                    <div class="absolute bottom-3 right-3 flex items-baseline gap-1.5 bg-stone-950/85 backdrop-blur-md px-2.5 py-1 rounded-xl border border-stone-200 text-white">
                        <span class="text-sm font-black text-amber-400">{{ basePrice(item) }} ج.م</span>
                        <span v-if="hasDiscount(item)" class="text-[10px] text-stone-400 line-through">{{ item.price }} ج.م</span>
                    </div>
                </div>

                <div class="flex-1 p-5 flex flex-col justify-between gap-4">
                    <div class="space-y-1.5">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-base font-black text-stone-900 dark:text-white leading-snug group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">
                                {{ item.name }}
                            </h3>
                        </div>
                        <p v-if="item.description" class="text-xs text-stone-500 dark:text-stone-400 line-clamp-2 leading-relaxed">
                            {{ item.description }}
                        </p>
                        <p v-else class="text-[11px] text-stone-400 italic">بدون وصف إضافي</p>

                        <div v-if="item.preparation_time" class="flex items-center gap-1 text-[11px] font-bold text-stone-400 pt-1">
                            <Clock class="w-3.5 h-3.5 text-orange-500" />
                            <span>تجهيز: {{ item.preparation_time }} دقيقة</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-stone-100 dark:border-stone-800 flex items-center justify-between gap-2">
                        <button
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-black transition flex items-center gap-1.5',
                                item.is_available
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100'
                                    : 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300 hover:bg-red-100',
                            ]"
                            @click="handleToggleAvailable(item.id)"
                        >
                            <span :class="['w-2 h-2 rounded-full', item.is_available ? 'bg-emerald-500' : 'bg-red-500']" />
                            <span>{{ item.is_available ? 'متوفر' : 'غير متوفر' }}</span>
                        </button>

                        <div class="flex items-center gap-1">
                            <button
                                class="p-2 rounded-xl bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 hover:bg-orange-50 hover:text-orange-600 dark:hover:bg-orange-950/40 dark:hover:text-orange-400 transition"
                                title="تعديل الصنف والسعر"
                                @click="openEditModal(item)"
                            >
                                <Edit2 class="w-3.5 h-3.5" />
                            </button>
                            <button
                                class="p-2 rounded-xl bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400 transition"
                                title="حذف الصنف"
                                @click="confirmDeleteId = item.id"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else
            class="bg-white/80 dark:bg-stone-900/80 backdrop-blur-xl rounded-3xl border border-orange-100/80 dark:border-stone-800 overflow-hidden shadow-xl shadow-orange-500/5"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="border-b border-stone-200 dark:border-stone-800 text-stone-400 text-[11px] font-bold bg-stone-50/50 dark:bg-stone-800/30">
                            <th class="py-4 px-6">الصنف والصورة</th>
                            <th class="py-4 px-4">التصنيف</th>
                            <th class="py-4 px-4">السعر</th>
                            <th class="py-4 px-4">الخصم</th>
                            <th class="py-4 px-4">حالة التوفر</th>
                            <th class="py-4 px-6 text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800 font-bold">
                        <tr
                            v-for="item in filteredItems"
                            :key="item.id"
                            class="hover:bg-orange-50/30 dark:hover:bg-stone-800/50 transition"
                        >
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="relative group w-12 h-12 rounded-xl overflow-hidden bg-stone-100 dark:bg-stone-800 shrink-0 border border-stone-200 dark:border-stone-700">
                                        <img
                                            :src="getItemImageUrl(item.image)"
                                            :alt="item.name"
                                            class="w-full h-full object-cover"
                                            @error="onImgError"
                                        />
                                        <button
                                            class="absolute inset-0 bg-black/60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition"
                                            title="تغيير الصورة"
                                            @click="openQuickImageModal(item)"
                                        >
                                            <Camera class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-black text-stone-900 dark:text-white text-sm">{{ item.name }}</span>
                                            <span
                                                v-if="item.is_featured"
                                                class="text-[10px] bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 px-2 py-0.5 rounded-full font-bold"
                                            >مميز</span>
                                        </div>
                                        <p class="text-[11px] text-stone-400 truncate max-w-xs font-normal">{{ item.description }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-stone-600 dark:text-stone-300">
                                {{ item.category?.name || '—' }}
                            </td>
                            <td class="py-4 px-4 font-black text-stone-900 dark:text-white">
                                {{ item.price }} ج.م
                            </td>
                            <td class="py-4 px-4 text-emerald-600 font-black">
                                {{ item.discount_price ? `${item.discount_price} ج.م` : '—' }}
                            </td>
                            <td class="py-4 px-4">
                                <button
                                    :class="[
                                        'px-3 py-1 rounded-full text-[10px] font-black transition',
                                        item.is_available
                                            ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300'
                                            : 'bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300',
                                    ]"
                                    @click="handleToggleAvailable(item.id)"
                                >
                                    {{ item.is_available ? 'متوفر بالمطبخ' : 'غير متاح' }}
                                </button>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        class="p-2 rounded-xl text-stone-500 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-stone-800 transition"
                                        title="تعديل الصنف"
                                        @click="openEditModal(item)"
                                    >
                                        <Edit2 class="w-4 h-4" />
                                    </button>
                                    <button
                                        class="p-2 rounded-xl text-stone-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-stone-800 transition"
                                        title="حذف الصنف"
                                        @click="confirmDeleteId = item.id"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-stone-950/70 backdrop-blur-sm transition-opacity" @click="showCreateModal = false" />
        <div class="relative bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 w-full max-w-xl p-6 sm:p-8 shadow-2xl z-10 animate-fade-in max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h2 class="text-lg font-black text-stone-900 dark:text-white">إضافة صنف ووجبة جديدة</h2>
                    <p class="text-xs text-stone-400 mt-0.5">ارفع صورة مميزة للصنف وحدد السعر والتصنيف</p>
                </div>
                <button class="p-2 text-stone-400 hover:text-stone-600 dark:hover:text-white rounded-xl" @click="showCreateModal = false">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form class="space-y-4" @submit.prevent="handleCreateSubmit">
                <div>
                    <label class="block text-xs font-black mb-1.5">صورة الطبق / الوجبة</label>
                    <input ref="createFileInput" type="file" accept="image/*" class="hidden" @change="onCreateImageChange" />

                    <div v-if="createImagePreview" class="relative w-full h-40 rounded-2xl overflow-hidden border-2 border-orange-500/50 group bg-stone-100 dark:bg-stone-800">
                        <img :src="createImagePreview" alt="Preview" class="w-full h-full object-cover" />
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-3">
                            <button type="button" class="px-3 py-1.5 bg-orange-600 text-white rounded-xl text-xs font-black" @click="createFileInput?.click()">تغيير الصورة</button>
                            <button type="button" class="px-3 py-1.5 bg-red-600 text-white rounded-xl text-xs font-black" @click="createForm.image = null; createImagePreview = null">إزالة</button>
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
                        <span class="text-xs font-black text-stone-800 dark:text-stone-200">اضغط لرفع صورة الصنف من جهازك</span>
                        <span class="text-[10px] text-stone-400">PNG, JPG, WEBP بحد أقصى 10MB</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black mb-1.5">اسم الصنف أو الوجبة *</label>
                    <input
                        v-model="createForm.name"
                        type="text"
                        required
                        placeholder="مثال: ساندوتش فلافل محشية سوبر ميكس"
                        class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black mb-1.5">التصنيف *</label>
                        <select
                            v-model="createForm.category_id"
                            class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                        >
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-black mb-1.5">وقت التجهيز المتوقع (بالدقائق)</label>
                        <input
                            v-model.number="createForm.preparation_time"
                            type="number"
                            min="1"
                            class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black mb-1.5">السعر الأصلي (ج.م) *</label>
                        <input
                            v-model="createForm.price"
                            type="number"
                            step="0.5"
                            min="0"
                            required
                            placeholder="مثال: 35"
                            class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-black mb-1.5">سعر الخصم / العرض (اختياري)</label>
                        <input
                            v-model="createForm.discount_price"
                            type="number"
                            step="0.5"
                            min="0"
                            placeholder="مثال: 30"
                            class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black mb-1.5">الوصف والمكونات</label>
                    <textarea
                        v-model="createForm.description"
                        rows="2"
                        placeholder="اكتب تفاصيل ومكونات الوجبة لجذب الزبائن..."
                        class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500 resize-none"
                    />
                </div>

                <div class="flex items-center gap-6 p-3 rounded-2xl bg-stone-50 dark:bg-stone-800/50 border border-stone-200 dark:border-stone-700">
                    <label class="flex items-center gap-2 text-xs font-bold cursor-pointer">
                        <input v-model="createForm.is_available" type="checkbox" class="w-4 h-4 rounded text-orange-600 focus:ring-orange-500" />
                        <span>متاح للطلب الآن</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs font-bold cursor-pointer">
                        <input v-model="createForm.is_featured" type="checkbox" class="w-4 h-4 rounded text-orange-600 focus:ring-orange-500" />
                        <span>صنف مميز في واجهة المطعم</span>
                    </label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="flex-1 py-3.5 rounded-2xl bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-700 hover:to-amber-600 text-white font-black text-xs shadow-lg shadow-orange-500/25 transition disabled:opacity-50"
                    >
                        {{ createForm.processing ? 'جاري الحفظ...' : 'حفظ الصنف' }}
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

    <!-- Edit Modal -->
    <div v-if="editingItem" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-stone-950/70 backdrop-blur-sm transition-opacity" @click="editingItem = null" />
        <div class="relative bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 w-full max-w-xl p-6 sm:p-8 shadow-2xl z-10 animate-fade-in max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h2 class="text-lg font-black text-stone-900 dark:text-white">تعديل بيانات الصنف والصورة</h2>
                    <p class="text-xs text-stone-400 mt-0.5">{{ editingItem.name }}</p>
                </div>
                <button class="p-2 text-stone-400 hover:text-stone-600 dark:hover:text-white rounded-xl" @click="editingItem = null">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form class="space-y-4" @submit.prevent="handleEditSubmit">
                <div>
                    <label class="block text-xs font-black mb-1.5">صورة الصنف</label>
                    <input ref="editFileInput" type="file" accept="image/*" class="hidden" @change="onEditImageChange" />

                    <div class="relative w-full h-44 rounded-2xl overflow-hidden border-2 border-orange-500/40 group bg-stone-100 dark:bg-stone-800">
                        <img :src="editImagePreview || getItemImageUrl(editingItem.image)" alt="Item" class="w-full h-full object-cover" />
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
                    <label class="block text-xs font-black mb-1.5">اسم الصنف *</label>
                    <input
                        v-model="editForm.name"
                        type="text"
                        required
                        class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black mb-1.5">التصنيف *</label>
                        <select
                            v-model="editForm.category_id"
                            class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                        >
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-black mb-1.5">وقت التجهيز (دقائق)</label>
                        <input
                            v-model.number="editForm.preparation_time"
                            type="number"
                            min="1"
                            class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black mb-1.5">السعر الأصلي (ج.م) *</label>
                        <input
                            v-model="editForm.price"
                            type="number"
                            step="0.5"
                            min="0"
                            required
                            class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-black mb-1.5">سعر الخصم (ج.م)</label>
                        <input
                            v-model="editForm.discount_price"
                            type="number"
                            step="0.5"
                            min="0"
                            placeholder="اختياري"
                            class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black mb-1.5">الوصف والمكونات</label>
                    <textarea
                        v-model="editForm.description"
                        rows="2"
                        class="w-full p-3 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 focus:outline-none focus:border-orange-500 resize-none"
                    />
                </div>

                <div class="flex items-center gap-6 p-3 rounded-2xl bg-stone-50 dark:bg-stone-800/50 border border-stone-200 dark:border-stone-700">
                    <label class="flex items-center gap-2 text-xs font-bold cursor-pointer">
                        <input v-model="editForm.is_available" type="checkbox" class="w-4 h-4 rounded text-orange-600 focus:ring-orange-500" />
                        <span>متاح للطلب</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs font-bold cursor-pointer">
                        <input v-model="editForm.is_featured" type="checkbox" class="w-4 h-4 rounded text-orange-600 focus:ring-orange-500" />
                        <span>صنف مميز</span>
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
                        @click="editingItem = null"
                    >
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Image Modal -->
    <div v-if="quickImageItem" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-stone-950/70 backdrop-blur-sm transition-opacity" @click="quickImageItem = null" />
        <div class="relative bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 w-full max-w-md p-6 sm:p-8 shadow-2xl z-10 animate-fade-in">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h2 class="text-base font-black text-stone-900 dark:text-white">تغيير صورة الصنف</h2>
                    <p class="text-xs text-stone-400 mt-0.5">{{ quickImageItem.name }}</p>
                </div>
                <button class="p-2 text-stone-400 hover:text-stone-600 dark:hover:text-white rounded-xl" @click="quickImageItem = null">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form class="space-y-4" @submit.prevent="handleQuickImageSubmit">
                <input ref="quickFileInput" type="file" accept="image/*" class="hidden" @change="onQuickImageChange" />

                <div
                    class="relative w-full h-48 rounded-2xl overflow-hidden border-2 border-dashed border-orange-400 hover:border-orange-600 bg-stone-100 dark:bg-stone-800 cursor-pointer group flex items-center justify-center"
                    @click="quickFileInput?.click()"
                >
                    <img :src="quickImagePreview || getItemImageUrl(quickImageItem.image)" alt="Item" class="w-full h-full object-cover" />
                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition flex flex-col items-center justify-center gap-2 text-white p-4">
                        <Upload class="w-6 h-6" />
                        <span class="text-xs font-black">اضغط لاختيار صورة من جهازك</span>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="!quickImageForm.image || quickImageForm.processing"
                        class="flex-1 py-3.5 rounded-2xl bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-700 hover:to-amber-600 text-white font-black text-xs shadow-lg shadow-orange-500/25 transition disabled:opacity-40"
                    >
                        {{ quickImageForm.processing ? 'جاري رفع الصورة...' : 'حفظ الصورة الجديدة' }}
                    </button>
                    <button
                        type="button"
                        class="py-3.5 px-5 rounded-2xl border border-stone-200 dark:border-stone-700 text-xs font-black hover:bg-stone-100 dark:hover:bg-stone-800 transition"
                        @click="quickImageItem = null"
                    >
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    <ConfirmModal
        :is-open="confirmDeleteId !== null"
        message="هل أنت متأكد من حذف هذا الصنف من المنيو؟ لن تتمكن من استعادته."
        @confirm="confirmDelete"
        @cancel="confirmDeleteId = null"
    />
</template>
