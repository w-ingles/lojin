<template>
    <div>
        <div class="flex align-items-center gap-2 mb-4">
            <Button icon="pi pi-arrow-left" class="p-button-text" @click="$router.back()" />
            <h3 class="m-0">Meu Carrinho</h3>
        </div>

        <div v-if="items.length === 0" class="card text-center py-6">
            <i class="pi pi-shopping-cart text-6xl text-color-secondary mb-4 block"></i>
            <h4 class="text-color-secondary mb-3">Seu carrinho está vazio</h4>
            <router-link :to="`/c/${$route.params.slug}`">
                <Button label="Ver Eventos" icon="pi pi-calendar" />
            </router-link>
        </div>

        <div v-else class="grid">
            <div class="col-12 lg:col-8">
                <div class="card p-0">
                    <div v-for="(item, idx) in items" :key="`${item.type}-${item.id}`">
                        <div class="flex align-items-center gap-3 p-3">
                            <div class="flex align-items-center justify-content-center border-round"
                                style="width:50px;height:50px;background:var(--surface-ground);flex-shrink:0">
                                <i :class="item.type === 'ticket_batch' ? 'pi pi-ticket' : 'pi pi-tag'"
                                    style="font-size:1.4rem;color:#1976d2"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold">{{ item.name }}</div>
                                <div class="text-color-secondary text-sm">
                                    {{ item.type === 'ticket_batch' ? 'Ingresso' : 'Produto' }} · R$ {{ Number(item.price).toFixed(2) }} / un.
                                </div>
                            </div>
                            <div class="flex align-items-center gap-2">
                                <Button icon="pi pi-minus" class="p-button-text p-button-sm" @click="updateQty(item.id, item.type, item.qty - 1)" />
                                <span class="font-bold w-2rem text-center">{{ item.qty }}</span>
                                <Button icon="pi pi-plus" class="p-button-text p-button-sm" @click="updateQty(item.id, item.type, item.qty + 1)" />
                            </div>
                            <span class="font-bold" style="color:#2e7d32;min-width:80px;text-align:right">
                                R$ {{ (item.price * item.qty).toFixed(2) }}
                            </span>
                            <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm"
                                @click="removeItem(item.id, item.type)" />
                        </div>
                        <Divider v-if="idx < items.length - 1" class="m-0" />
                    </div>
                </div>
            </div>

            <div class="col-12 lg:col-4">
                <div class="card" style="position:sticky;top:80px">
                    <h5>Resumo</h5>
                    <div class="flex justify-content-between mb-2 text-color-secondary text-sm">
                        <span>{{ count }} {{ count === 1 ? 'item' : 'itens' }}</span>
                        <span>R$ {{ total.toFixed(2) }}</span>
                    </div>
                    <Divider />
                    <div class="flex justify-content-between font-bold text-lg mb-4">
                        <span>Total</span>
                        <span style="color:#2e7d32">R$ {{ total.toFixed(2) }}</span>
                    </div>
                    <router-link :to="`/c/${$route.params.slug}/checkout`">
                        <Button label="Finalizar Compra" icon="pi pi-check" class="p-button-success w-full" />
                    </router-link>
                    <router-link :to="`/c/${$route.params.slug}`">
                        <Button label="Continuar comprando" icon="pi pi-arrow-left" class="p-button-text w-full mt-2" />
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useCart } from '@/composables/useCart';
const { items, total, count, removeItem, updateQty } = useCart();
</script>
