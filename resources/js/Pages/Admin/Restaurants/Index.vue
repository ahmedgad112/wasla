<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Restaurant, PaginatedResponse } from '../../../Types';
import { Plus, Search, Eye, Pencil, ExternalLink } from '@lucide/vue';
import { resolveMediaUrl } from '../../../lib/media';

const props = defineProps<{
    restaurants: PaginatedResponse<Restaurant & { orders_count: number; delivery_drivers_count: number }>;
    filters: { search?: string; status?: string };
}>();

const items = computed(() => props.restaurants?.data || []);
const search = ref(props.filters.search || '');

const statusFilters = ['ALL', 'ACTIVE', 'INACTIVE', 'SUSPENDED'];

const handleSearch = (): void => {
    router.get(
        '/admin/restaurants',
        { search: search.value, status: props.filters.status },
        { preserveState: true },
    );
};

const handleFilterStatus = (status: string): void => {
    router.get(
        '/admin/restaurants',
        { search: search.value, status: status === 'ALL' ? '' : status },
        { preserveState: true },
    );
};

const handleToggleStatus = (id: number, currentStatus: string): void => {
    const action = currentStatus === 'ACTIVE' ? 'suspend' : 'activate';
    router.post(`/admin/restaurants/${id}/${action}`, {}, { preserveScroll: true });
};

const isStatusActive = (st: string): boolean =>
    props.filters.status === st || (!props.filters.status && st === 'ALL');

const statusLabel = (st: string): string => {
    if (st === 'ALL') {
        return 'الكل';
    }
    if (st === 'ACTIVE') {
        return 'نشط';
    }
    if (st === 'SUSPENDED') {
        return 'موقوف';
    }
    return 'غير نشط';
};

const restaurantAvatar = (restaurant: Restaurant): string | null => {
    const path = restaurant.logo || restaurant.cover_image;
    if (!path) {
        return null;
    }

    return resolveMediaUrl(path);
};
</script>

<template>
    <Head title="إدارة المطاعم الشريكة — الإدارة المركزية" />

    <div class="space-y-6">
        <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h1 class="text-xl font-black text-stone-900 dark:text-white">
                        المطاعم الشريكة المسجلة
                    </h1>
                    <p class="text-xs text-stone-400 mt-0.5">
                        إدارة عقود المطاعم، نسب العمولات، والاشتراكات الشهرية في برج العرب
                    </p>
                </div>

                <Link
                    href="/admin/restaurants/create"
                    class="px-4 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs shadow-md transition flex items-center gap-1.5 shrink-0"
                >
                    <Plus class="w-4 h-4" />
                    <span>إضافة مطعم شريك جديد</span>
                </Link>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                <form class="w-full sm:w-80 relative" @submit.prevent="handleSearch">
                    <Search class="w-4 h-4 text-stone-400 absolute right-3.5 top-3" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="ابحث بالاسم أو البريد..."
                        class="w-full pr-10 pl-4 py-2 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white"
                    >
                </form>

                <div class="flex items-center gap-2">
                    <button
                        v-for="st in statusFilters"
                        :key="st"
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                            isStatusActive(st)
                                ? 'bg-stone-900 dark:bg-white text-white dark:text-stone-900'
                                : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400',
                        ]"
                        @click="handleFilterStatus(st)"
                    >
                        {{ statusLabel(st) }}
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="border-b border-stone-200 dark:border-stone-800 text-stone-400 text-[11px] font-bold">
                            <th class="py-3 px-4">المطعم</th>
                            <th class="py-3 px-4">الهاتف والفرع</th>
                            <th class="py-3 px-4">نظام العمولة</th>
                            <th class="py-3 px-4">الطلبات</th>
                            <th class="py-3 px-4">الطيارين</th>
                            <th class="py-3 px-4">الحالة</th>
                            <th class="py-3 px-4 text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr
                            v-for="r in items"
                            :key="r.id"
                            class="hover:bg-stone-50 dark:hover:bg-stone-800/50"
                        >
                            <td class="py-4 px-4 font-bold text-stone-900 dark:text-white">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg bg-orange-100 text-xs font-bold text-orange-600 dark:bg-orange-950">
                                        <img
                                            v-if="restaurantAvatar(r)"
                                            :src="restaurantAvatar(r)!"
                                            :alt="r.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <template v-else>{{ r.name.charAt(0) }}</template>
                                    </div>
                                    <div>
                                        <span>{{ r.name }}</span>
                                        <span class="block text-[10px] font-normal text-stone-400">{{ r.slug }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-stone-600 dark:text-stone-400">
                                <span class="block font-mono">{{ r.phone }}</span>
                                <span class="block max-w-xs truncate text-[11px]">{{ r.address }}</span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="block font-bold text-stone-800 dark:text-stone-200">
                                    {{ r.commission_type === 'PERCENTAGE' ? `${r.commission_percentage}% عمولة` : 'اشتراك شهري' }}
                                </span>
                                <span v-if="Number(r.monthly_subscription_fee) > 0" class="text-[10px] text-stone-400">
                                    +{{ r.monthly_subscription_fee }} ج.م / شهر
                                </span>
                            </td>
                            <td class="py-4 px-4 font-bold text-stone-800 dark:text-stone-200">
                                {{ r.orders_count || 0 }} طلب
                            </td>
                            <td class="py-4 px-4 text-stone-600 dark:text-stone-400">
                                {{ r.delivery_drivers_count || 0 }} طيار
                            </td>
                            <td class="py-4 px-4">
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-0.5 text-[10px] font-bold',
                                        r.status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700',
                                    ]"
                                >
                                    {{ r.status === 'ACTIVE' ? 'نشط معتمد' : 'موقوف' }}
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center justify-center gap-1">
                                    <Link
                                        :href="`/admin/restaurants/${r.id}`"
                                        class="rounded-lg p-1.5 text-stone-400 transition hover:bg-orange-50 hover:text-orange-600 dark:hover:bg-orange-950/40"
                                        title="عرض التفاصيل"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                    <Link
                                        :href="`/admin/restaurants/${r.id}/edit`"
                                        class="rounded-lg p-1.5 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700 dark:hover:bg-stone-800 dark:hover:text-white"
                                        title="تعديل"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                    <a
                                        :href="`/restaurants/${r.slug}`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="rounded-lg p-1.5 text-stone-400 transition hover:bg-sky-50 hover:text-sky-600 dark:hover:bg-sky-950/40"
                                        title="فتح صفحة المطعم للعملاء"
                                    >
                                        <ExternalLink class="h-4 w-4" />
                                    </a>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-stone-200 px-2.5 py-1 text-[11px] font-bold transition hover:bg-stone-100 dark:border-stone-700 dark:hover:bg-stone-800"
                                        @click="handleToggleStatus(r.id, r.status)"
                                    >
                                        {{ r.status === 'ACTIVE' ? 'إيقاف' : 'تفعيل' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
