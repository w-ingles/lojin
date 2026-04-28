import { computed, ref, watch } from 'vue';

const CART_KEY = 'lojin_cart';
const items = ref(JSON.parse(localStorage.getItem(CART_KEY) || '[]'));

watch(items, (v) => localStorage.setItem(CART_KEY, JSON.stringify(v)), { deep: true });

export function useCart() {
    const total = computed(() => items.value.reduce((s, i) => s + i.price * i.qty, 0));
    const count = computed(() => items.value.reduce((s, i) => s + i.qty, 0));

    function addItem(item) {
        // item: { id, type ('ticket_batch'|'product'), name, price, qty, max? }
        const existing = items.value.find(i => i.id === item.id && i.type === item.type);
        if (existing) existing.qty += item.qty;
        else items.value.push({ ...item });
    }

    function removeItem(id, type) {
        items.value = items.value.filter(i => !(i.id === id && i.type === type));
    }

    function updateQty(id, type, qty) {
        const item = items.value.find(i => i.id === id && i.type === type);
        if (item) {
            if (qty < 1) removeItem(id, type);
            else item.qty = qty;
        }
    }

    function clear() { items.value = []; }

    return { items, total, count, addItem, removeItem, updateQty, clear };
}
