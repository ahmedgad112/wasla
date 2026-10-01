import { defineStore } from 'pinia';
import type { MenuItem, MenuItemAddon, Restaurant } from '../Types';

export interface CartItem {
    id: string;
    menuItem: MenuItem;
    quantity: number;
    selectedOptions: {
        id?: number;
        optionName: string;
        valueName: string;
        price: number;
    }[];
    selectedAddons: MenuItemAddon[];
    notes?: string;
    unitPrice: number;
    totalPrice: number;
}

interface CartState {
    restaurant: Restaurant | null;
    items: CartItem[];
    studentDiscountApplied: boolean;
    studentDiscountPercentage: number;
    isCartOpen: boolean;
}

export const useCartStore = defineStore('cart', {
    state: (): CartState => ({
        restaurant: null,
        items: [],
        studentDiscountApplied: false,
        studentDiscountPercentage: 0,
        isCartOpen: false,
    }),

    getters: {
        getSubtotal: (state): number =>
            state.items.reduce((sum, item) => sum + item.totalPrice, 0),

        getStudentDiscountAmount(): number {
            if (!this.studentDiscountApplied || this.studentDiscountPercentage <= 0) {
                return 0;
            }

            return Number(((this.getSubtotal * this.studentDiscountPercentage) / 100).toFixed(2));
        },

        getDeliveryFee: (state): number => {
            if (state.items.length === 0 || !state.restaurant) {
                return 0;
            }

            if (state.restaurant.delivery_provider === 'PICKUP') {
                return 0;
            }

            return Number(state.restaurant.delivery_fee) || 0;
        },

        getTotal(): number {
            return Math.max(0, this.getSubtotal - this.getStudentDiscountAmount + this.getDeliveryFee);
        },

        getItemCount: (state): number =>
            state.items.reduce((count, item) => count + item.quantity, 0),
    },

    actions: {
        setIsCartOpen(isCartOpen: boolean): void {
            this.isCartOpen = isCartOpen;
        },

        openCart(): void {
            this.isCartOpen = true;
        },

        closeCart(): void {
            this.isCartOpen = false;
        },

        addItem(
            item: MenuItem,
            restaurant: Restaurant,
            quantity = 1,
            selectedOptions: { id?: number; optionName: string; valueName: string; price: number }[] = [],
            selectedAddons: MenuItemAddon[] = [],
            notes = '',
        ): boolean {
            if (this.restaurant && this.restaurant.id !== restaurant.id) {
                return false;
            }

            const optionsPrice = selectedOptions.reduce((acc, opt) => acc + (Number(opt.price) || 0), 0);
            const addonsPrice = selectedAddons.reduce((acc, add) => acc + (Number(add.price) || 0), 0);
            const basePrice = Number(item.effective_price ?? item.discount_price ?? item.price);
            const unitPrice = basePrice + optionsPrice + addonsPrice;

            const optionsKey = selectedOptions
                .map((o) => `${o.optionName}:${o.valueName}`)
                .sort()
                .join('|');
            const addonsKey = selectedAddons
                .map((a) => a.id)
                .sort()
                .join('|');
            const itemKey = `${item.id}_${optionsKey}_${addonsKey}_${notes.trim()}`;

            const existingIndex = this.items.findIndex((i) => i.id === itemKey);

            if (existingIndex > -1) {
                const existingItem = this.items[existingIndex];
                const newQty = existingItem.quantity + quantity;
                this.items[existingIndex] = {
                    ...existingItem,
                    quantity: newQty,
                    totalPrice: unitPrice * newQty,
                };
            } else {
                this.items.push({
                    id: itemKey,
                    menuItem: item,
                    quantity,
                    selectedOptions,
                    selectedAddons,
                    notes,
                    unitPrice,
                    totalPrice: unitPrice * quantity,
                });
            }

            this.restaurant = restaurant;
            this.studentDiscountPercentage = Number(restaurant.student_discount_percentage) || 0;

            return true;
        },

        removeItem(itemId: string): void {
            this.items = this.items.filter((i) => i.id !== itemId);
            if (this.items.length === 0) {
                this.restaurant = null;
            }
        },

        updateQuantity(itemId: string, quantity: number): void {
            if (quantity <= 0) {
                this.removeItem(itemId);
                return;
            }

            this.items = this.items.map((item) => {
                if (item.id === itemId) {
                    return {
                        ...item,
                        quantity,
                        totalPrice: item.unitPrice * quantity,
                    };
                }

                return item;
            });
        },

        clearCart(): void {
            this.restaurant = null;
            this.items = [];
            this.studentDiscountApplied = false;
            this.studentDiscountPercentage = 0;
        },

        setStudentDiscount(percentage: number): void {
            this.studentDiscountApplied = percentage > 0;
            this.studentDiscountPercentage = percentage;
        },
    },

    persist: {
        key: 'fatrna-cart-storage',
        pick: ['restaurant', 'items', 'studentDiscountApplied', 'studentDiscountPercentage'],
    },
});
