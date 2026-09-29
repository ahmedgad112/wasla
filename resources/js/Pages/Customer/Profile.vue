<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';
import type { Customer } from '../../Types';
import {
    User,
    MapPin,
    Plus,
    Trash2,
    GraduationCap,
    Upload,
    CheckCircle2,
    Clock,
    AlertCircle,
    LogOut,
} from '@lucide/vue';
import ConfirmModal from '../../Components/ConfirmModal.vue';

const props = defineProps<{
    customer: Customer;
}>();

const user = computed(() => props.customer.user);
const addresses = computed(() => props.customer.addresses || []);

const profileForm = useForm({
    name: user.value?.name || '',
    phone: user.value?.phone || '',
});

const submitProfile = (): void => {
    profileForm.put('/customer/profile');
};

const showAddAddress = ref(false);
const confirmDeleteAddressId = ref<number | null>(null);

const addressForm = useForm({
    label: 'سكن الطلاب',
    address: '',
    is_default: false,
});

const submitAddress = (): void => {
    addressForm.post('/customer/profile/address', {
        onSuccess: () => {
            showAddAddress.value = false;
            addressForm.reset();
        },
    });
};

const studentForm = useForm<{
    university_name: string;
    student_id_image: File | null;
}>({
    university_name: props.customer?.university_name || 'جامعة برج العرب التكنولوجية',
    student_id_image: null,
});

const submitStudent = (): void => {
    studentForm.post('/customer/profile/student-verification', {
        forceFormData: true,
    });
};

const onStudentFileChange = (e: Event): void => {
    const input = e.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        studentForm.student_id_image = input.files[0];
    }
};

const confirmDeleteAddress = (): void => {
    if (confirmDeleteAddressId.value) {
        router.delete(`/customer/profile/address/${confirmDeleteAddressId.value}`);
    }
    confirmDeleteAddressId.value = null;
};
</script>

<template>
    <CustomerLayout title="الملف الشخصي والعناوين" :customer="customer">
        <Head title="الملف الشخصي والعناوين" />

        <div class="space-y-8">
            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <h2 class="text-base font-black text-stone-900 dark:text-white mb-4 flex items-center gap-2">
                    <User class="w-4 h-4 text-orange-500" />
                    <span>البيانات الأساسية</span>
                </h2>

                <form class="space-y-4 max-w-lg" @submit.prevent="submitProfile">
                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                            الاسم بالكامل
                        </label>
                        <input
                            v-model="profileForm.name"
                            type="text"
                            required
                            class="w-full p-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                        />
                        <p v-if="profileForm.errors.name" class="text-[11px] text-red-500 mt-1">{{ profileForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                            رقم الهاتف (للتواصل مع الكابتن)
                        </label>
                        <input
                            v-model="profileForm.phone"
                            type="tel"
                            class="w-full p-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                        />
                        <p v-if="profileForm.errors.phone" class="text-[11px] text-red-500 mt-1">{{ profileForm.errors.phone }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="profileForm.processing"
                        class="py-2.5 px-6 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs shadow transition disabled:opacity-60"
                    >
                        {{ profileForm.processing ? 'جارٍ الحفظ...' : 'تحديث البيانات' }}
                    </button>
                </form>
            </div>

            <div id="student-id" class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                        <GraduationCap class="w-5 h-5 text-orange-500" />
                        <span>توثيق هوية الطالب الجامعي لخصومات المطاعم</span>
                    </h2>

                    <span
                        v-if="customer.student_status === 'APPROVED'"
                        class="px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 text-xs font-bold flex items-center gap-1"
                    >
                        <CheckCircle2 class="w-3.5 h-3.5" /> كارنيه موثق (الخصم مفعل)
                    </span>
                    <span
                        v-else-if="customer.student_status === 'PENDING'"
                        class="px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 text-xs font-bold flex items-center gap-1"
                    >
                        <Clock class="w-3.5 h-3.5" /> قيد مراجعة الإدارة
                    </span>
                    <span
                        v-else-if="customer.student_status === 'REJECTED'"
                        class="px-3 py-1 rounded-full bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 text-xs font-bold flex items-center gap-1"
                    >
                        <AlertCircle class="w-3.5 h-3.5" /> تم رفض الكارنيه (يرجى إعادة الرفع)
                    </span>
                </div>

                <div
                    v-if="customer.student_status === 'APPROVED'"
                    class="p-5 rounded-2xl bg-emerald-50 dark:bg-stone-800/80 border border-emerald-200 dark:border-stone-700 text-xs text-stone-700 dark:text-stone-300 space-y-2"
                >
                    <p class="font-bold text-sm text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
                        <CheckCircle2 class="w-4 h-4" />
                        <span>تم تأكيد هويتك الطلابية بنجاح من قبل إدارة المنصة!</span>
                    </p>
                    <p><strong>الجامعة المسجلة:</strong> {{ customer.university_name || 'جامعة معتمدة' }}</p>
                    <p class="text-stone-500 dark:text-stone-400">
                        يتم تطبيق الخصومات الطلابية والعروض الحصرية للطلاب تلقائياً على كل طلب تقوم بتقديمه من مطاعم المنصة.
                    </p>
                </div>
                <form v-else class="space-y-4 max-w-lg" @submit.prevent="submitStudent">
                    <div class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/40 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                        <p class="font-bold">🎓 كيف تحصل على خصم الطلاب والعروض الحصرية؟</p>
                        <p class="text-[11px] leading-relaxed">
                            ارفع صورة واضحة لكارنيه كليتك أو جامعتك (سواء جامعة برج العرب التكنولوجية، الجامعة اليابانية، جامعة سنجور، أو أي جامعة مصرية). فور قبول الكارنيه من إدارة الموقع يتم تفعيل الخصم مباشرة.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                            الجامعة المقيد بها
                        </label>
                        <select
                            v-model="studentForm.university_name"
                            class="w-full p-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                        >
                            <optgroup label="جامعات برج العرب">
                                <option value="جامعة برج العرب التكنولوجية (BATU)">جامعة برج العرب التكنولوجية (BATU)</option>
                                <option value="الجامعة المصرية اليابانية للعلوم والتكنولوجيا (E-JUST)">الجامعة المصرية اليابانية للعلوم والتكنولوجيا (E-JUST)</option>
                                <option value="جامعة سنجور الدولية (Senghor University)">جامعة سنجور الدولية (Senghor University)</option>
                            </optgroup>
                            <optgroup label="جامعات ومعاهد أخرى">
                                <option value="جامعة الإسكندرية">جامعة الإسكندرية</option>
                                <option value="جامعة مطروح">جامعة مطروح</option>
                                <option value="الأكاديمية العربية للعلوم والتكنولوجيا (AASTMT)">الأكاديمية العربية للعلوم والتكنولوجيا (AASTMT)</option>
                                <option value="جامعة فاروس (PUA)">جامعة فاروس (PUA)</option>
                                <option value="جامعة العلمين الدولية (AIU)">جامعة العلمين الدولية (AIU)</option>
                                <option value="المعهد العالي للهندسة والتكنولوجيا ببرج العرب">المعهد العالي للهندسة والتكنولوجيا ببرج العرب</option>
                                <option value="جامعة / معهد آخر في مصر">جامعة / معهد آخر في مصر</option>
                            </optgroup>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                            صورة كارنيه الجامعة (JPG أو PNG أو PDF)
                        </label>
                        <input
                            type="file"
                            required
                            accept="image/*,.pdf"
                            class="w-full p-2.5 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-orange-600 file:text-white cursor-pointer"
                            @change="onStudentFileChange"
                        />
                        <p v-if="studentForm.errors.student_id_image" class="text-[11px] text-red-500 mt-1">
                            {{ studentForm.errors.student_id_image }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="studentForm.processing"
                        class="py-2.5 px-6 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 text-white font-bold text-xs shadow transition disabled:opacity-60 flex items-center gap-1.5 cursor-pointer"
                    >
                        <Upload class="w-3.5 h-3.5" />
                        <span>{{ studentForm.processing ? 'جارٍ رفع الكارنيه للإدارة...' : 'إرسال الكارنيه للمراجعة والتفعيل' }}</span>
                    </button>
                </form>
            </div>

            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                        <MapPin class="w-4 h-4 text-orange-500" />
                        <span>عناوين التوصيل المحفوظة</span>
                    </h2>

                    <button
                        type="button"
                        class="px-3.5 py-1.5 rounded-xl bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 text-xs font-bold transition flex items-center gap-1"
                        @click="showAddAddress = !showAddAddress"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        <span>إضافة عنوان جديد</span>
                    </button>
                </div>

                <form
                    v-if="showAddAddress"
                    class="p-4 rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 space-y-3 max-w-lg"
                    @submit.prevent="submitAddress"
                >
                    <div>
                        <label class="block text-xs font-bold mb-1">اسم العنوان / الوصف المختصر</label>
                        <input
                            v-model="addressForm.label"
                            type="text"
                            required
                            placeholder="مثال: سكن الطلاب، شقة الإسكندرية، المكتب..."
                            class="w-full p-2.5 text-xs rounded-xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">العنوان التفصيلي</label>
                        <textarea
                            v-model="addressForm.address"
                            required
                            rows="2"
                            placeholder="الحي، الشارع، رقم العمارة، الدور..."
                            class="w-full p-2.5 text-xs rounded-xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="is_def"
                            v-model="addressForm.is_default"
                            type="checkbox"
                            class="rounded text-orange-600 focus:ring-orange-500"
                        />
                        <label for="is_def" class="text-xs cursor-pointer">جعله العنوان الافتراضي</label>
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button
                            type="submit"
                            :disabled="addressForm.processing"
                            class="py-2 px-4 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs"
                        >
                            حفظ العنوان
                        </button>
                        <button
                            type="button"
                            class="py-2 px-4 rounded-xl border border-stone-300 dark:border-stone-700 text-xs font-bold"
                            @click="showAddAddress = false"
                        >
                            إلغاء
                        </button>
                    </div>
                </form>

                <p v-if="addresses.length === 0" class="text-xs text-stone-400 py-4">
                    لم تقم بإضافة أي عنوان بعد. أضف عنوان سكنك لتسهيل عملية الطلب بنقرة واحدة.
                </p>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="addr in addresses"
                        :key="addr.id"
                        class="p-4 rounded-2xl bg-stone-50 dark:bg-stone-800/60 border border-stone-200 dark:border-stone-700 flex items-start justify-between gap-3"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-xs text-stone-900 dark:text-white">{{ addr.label }}</span>
                                <span
                                    v-if="addr.is_default"
                                    class="text-[10px] bg-orange-100 dark:bg-orange-950 text-orange-600 font-bold px-1.5 py-0.5 rounded"
                                >
                                    افتراضي
                                </span>
                            </div>
                            <p class="text-xs text-stone-500 leading-relaxed">{{ addr.address }}</p>
                        </div>
                        <button
                            type="button"
                            class="text-stone-400 hover:text-red-500 p-1 transition"
                            title="حذف"
                            @click="confirmDeleteAddressId = addr.id"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-black text-red-600 transition hover:bg-red-100 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400 dark:hover:bg-red-950/50"
                >
                    <LogOut class="h-4 w-4" />
                    <span>تسجيل الخروج</span>
                </Link>
            </div>
        </div>

        <ConfirmModal
            :is-open="confirmDeleteAddressId !== null"
            message="هل أنت متأكد من حذف هذا العنوان؟ لن تتمكن من استعادته."
            @confirm="confirmDeleteAddress"
            @cancel="confirmDeleteAddressId = null"
        />
    </CustomerLayout>
</template>
