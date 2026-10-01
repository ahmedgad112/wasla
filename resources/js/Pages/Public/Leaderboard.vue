<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Crown,
    Flame,
    Trophy,
    Award,
    TrendingUp,
    Users,
    ShoppingBag,
    Store,
    ChevronLeft,
} from '@lucide/vue';

interface UserRanking {
    userId: number;
    userName: string;
    badge: string;
    totalOrders: number;
    totalItems: number;
}

const props = defineProps<{
    leaderboard: {
        rankings: UserRanking[];
        kingOfBreakfast: UserRanking | null;
        totalOrdersInSystem: number;
        totalItemsInSystem: number;
    };
}>();

const rankings = computed(() => props.leaderboard?.rankings || []);
const podium = computed(() => rankings.value.slice(0, 3));
const others = computed(() => rankings.value.slice(3));
const king = computed(
    () => props.leaderboard?.kingOfBreakfast || (podium.value.length > 0 ? podium.value[0] : null),
);
</script>

<template>
        <Head title="لوحة الشرف والأكثر طلباً — جامعة برج العرب التكنولوجية" />

        <div class="px-4 py-4 space-y-6">
            <div class="text-center space-y-4 relative">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-300 text-xs font-black">
                    <Flame class="w-4 h-4 text-amber-500" />
                    <span>لوحة الشرف والنشاط التنافسية</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-black text-stone-900 dark:text-white leading-tight">
                    الأكثر طلباً في <span class="text-amber-500">جامعة برج العرب</span>
                </h1>
                <p class="text-sm text-stone-500 dark:text-stone-400 max-w-xl mx-auto leading-relaxed">
                    لوحة الشرف التنافسية لفريق إدارة التقديمات وطلاب الجامعة. تنافس وارتقِ في الترتيب بكل طلب إفطار جديد!
                </p>

                <div class="flex items-center justify-center gap-6 flex-wrap pt-2">
                    <div class="flex items-center gap-2 text-sm bg-white dark:bg-stone-900 px-4 py-2 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-sm">
                        <ShoppingBag class="w-4 h-4 text-orange-500" />
                        <span class="text-stone-500 dark:text-stone-400">إجمالي الطلبات:</span>
                        <span class="font-black text-orange-600 text-base">{{ leaderboard?.totalOrdersInSystem || 144 }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm bg-white dark:bg-stone-900 px-4 py-2 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-sm">
                        <TrendingUp class="w-4 h-4 text-emerald-500" />
                        <span class="text-stone-500 dark:text-stone-400">إجمالي الوجبات:</span>
                        <span class="font-black text-emerald-600 text-base">{{ leaderboard?.totalItemsInSystem || 384 }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm bg-white dark:bg-stone-900 px-4 py-2 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-sm">
                        <Users class="w-4 h-4 text-indigo-500" />
                        <span class="text-stone-500 dark:text-stone-400">المتنافسون:</span>
                        <span class="font-black text-indigo-600 text-base">{{ rankings.length || 5 }}</span>
                    </div>
                </div>
            </div>

            <div
                v-if="king"
                class="relative rounded-3xl overflow-hidden bg-gradient-to-br from-amber-500 via-orange-500 to-red-500 p-6 sm:p-8 text-white shadow-2xl shadow-amber-500/30"
            >
                <div class="absolute top-0 right-0 w-48 h-48 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4 blur-2xl pointer-events-none" />
                <div class="relative z-10 flex flex-col sm:flex-row items-center gap-6 text-center sm:text-right">
                    <div class="w-24 h-24 rounded-3xl bg-white/20 backdrop-blur-sm border-2 border-white/30 flex items-center justify-center shadow-xl shrink-0">
                        <Crown class="w-12 h-12 text-amber-200" />
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm font-bold text-amber-100 uppercase tracking-wider">
                            ملك الفطار الحالي في الجامعة 👑
                        </p>
                        <h2 class="text-3xl sm:text-4xl font-black text-white">{{ king.userName }}</h2>
                        <div class="flex items-center justify-center sm:justify-start gap-3 text-sm flex-wrap pt-1">
                            <span class="bg-white/20 px-3.5 py-1 rounded-full font-bold backdrop-blur-md">
                                {{ king.totalOrders }} طلب إفطار
                            </span>
                            <span class="bg-white/20 px-3.5 py-1 rounded-full font-bold backdrop-blur-md">
                                {{ king.totalItems }} وجبة مطلوبة
                            </span>
                            <span class="bg-amber-300/30 text-amber-100 px-3 py-1 rounded-full font-black text-xs">
                                {{ king.badge }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="podium.length > 0" class="space-y-4">
                <h2 class="text-lg font-black text-stone-900 dark:text-white flex items-center gap-2">
                    <Trophy class="w-5 h-5 text-amber-500" />
                    <span>المراكز الثلاثة الأولى (منصة التتويج)</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div
                        v-for="(r, i) in podium"
                        :key="r.userId"
                        class="relative p-6 rounded-3xl border transition-all text-center space-y-3"
                        :class="
                            i === 0
                                ? 'bg-gradient-to-b from-amber-50 to-orange-50/50 dark:from-amber-950/30 dark:to-stone-900 border-amber-300 dark:border-amber-600/50 shadow-xl shadow-amber-500/10 sm:-translate-y-2'
                                : 'bg-white dark:bg-stone-900 border-stone-200 dark:border-stone-800 shadow-md'
                        "
                    >
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mx-auto shadow-md font-black text-lg">
                            <div
                                v-if="i === 0"
                                class="w-full h-full rounded-2xl bg-amber-500 text-stone-950 flex items-center justify-center shadow-lg shadow-amber-500/30"
                            >
                                🥇
                            </div>
                            <div
                                v-else-if="i === 1"
                                class="w-full h-full rounded-2xl bg-stone-200 dark:bg-stone-700 text-stone-800 dark:text-stone-200 flex items-center justify-center"
                            >
                                🥈
                            </div>
                            <div
                                v-else
                                class="w-full h-full rounded-2xl bg-amber-700/30 text-amber-600 flex items-center justify-center"
                            >
                                🥉
                            </div>
                        </div>

                        <div>
                            <h3 class="font-black text-base text-stone-900 dark:text-white">{{ r.userName }}</h3>
                            <p class="text-xs text-orange-600 dark:text-orange-400 font-bold mt-0.5">{{ r.badge }}</p>
                        </div>

                        <div class="pt-3 border-t border-stone-100 dark:border-stone-800 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-stone-400 block text-[10px]">الطلبات</span>
                                <span class="font-black text-stone-800 dark:text-stone-200">{{ r.totalOrders }}</span>
                            </div>
                            <div>
                                <span class="text-stone-400 block text-[10px]">الوجبات</span>
                                <span class="font-black text-stone-800 dark:text-stone-200">{{ r.totalItems }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="others.length > 0" class="space-y-4">
                <h2 class="text-lg font-black text-stone-900 dark:text-white flex items-center gap-2">
                    <Award class="w-5 h-5 text-orange-500" />
                    <span>باقي قائمة الشرف</span>
                </h2>

                <div class="space-y-2">
                    <div
                        v-for="(r, i) in others"
                        :key="r.userId"
                        class="flex items-center justify-between p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 hover:border-orange-300 dark:hover:border-orange-500/30 transition-all shadow-sm"
                    >
                        <div class="flex items-center gap-4">
                            <span class="w-8 h-8 rounded-xl bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 flex items-center justify-center font-black text-sm">
                                #{{ i + 4 }}
                            </span>
                            <div>
                                <h3 class="font-black text-sm text-stone-900 dark:text-white">{{ r.userName }}</h3>
                                <span class="text-xs text-stone-500 dark:text-stone-400">{{ r.badge }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-6 text-xs text-left">
                            <div>
                                <span class="text-stone-400 block text-[10px]">الطلبات</span>
                                <span class="font-black text-orange-600">{{ r.totalOrders }}</span>
                            </div>
                            <div>
                                <span class="text-stone-400 block text-[10px]">الوجبات</span>
                                <span class="font-black text-stone-800 dark:text-stone-200">{{ r.totalItems }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center pt-6 space-y-4">
                <Link
                    href="/restaurants"
                    class="inline-flex items-center gap-3 px-8 py-4 rounded-2xl bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-black text-base shadow-xl shadow-orange-500/25 hover:scale-105 active:scale-95 transition-all"
                >
                    <Store class="w-5 h-5" />
                    <span>اطلب فطارك الآن وانضم للمتصدرين</span>
                    <ChevronLeft class="w-4 h-4" />
                </Link>
            </div>
        </div>
</template>
