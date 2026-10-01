export const subscriptionPlans = [
    { value: 'MONTHLY', label: 'شهري', months: 1, periodLabel: 'شهر' },
    { value: 'QUARTERLY', label: '3 شهور', months: 3, periodLabel: '3 شهور' },
    { value: 'SEMIANNUAL', label: '6 شهور', months: 6, periodLabel: '6 شهور' },
    { value: 'YEARLY', label: 'سنة', months: 12, periodLabel: 'سنة' },
] as const;

export type SubscriptionPlan = (typeof subscriptionPlans)[number]['value'];

export function subscriptionPlanLabel(plan?: string | null): string {
    return subscriptionPlans.find((item) => item.value === plan)?.label ?? 'شهري';
}

export function subscriptionPeriodLabel(plan?: string | null): string {
    return subscriptionPlans.find((item) => item.value === plan)?.periodLabel ?? 'شهر';
}
