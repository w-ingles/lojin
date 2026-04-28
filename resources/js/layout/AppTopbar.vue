<template>
    <div class="layout-topbar clearfix">
        <button class="layout-topbar-logo p-link" @click="goDashboard">
            <img id="layout-topbar-logo" alt="babylon-layout" src="/layout/images/logo-white.png" />
        </button>

        <button class="layout-menu-button p-link" @click="onMenuButtonClick">
            <i class="pi pi-bars"></i>
        </button>

        <button id="topbar-menu-button" class="p-link" @click="onTopbarMenuButtonClick">
            <i class="pi pi-ellipsis-v"></i>
        </button>

        <ul :class="topbarItemsClass">
            <li v-if="profileMode === 'popup' || horizontal" :class="['user-profile', { 'active-topmenuitem': activeTopbarItem === 'profile' }]" @click="$emit('topbar-item-click', { originalEvent: $event, item: 'profile' })">
                <button class="p-link">
                    <img alt="avatar" src="/layout/images/avatar.png" />
                    <span class="topbar-item-name">{{ userName }}</span>
                </button>
                <transition name="layout-submenu-container">
                    <ul class="fadeInDown" v-show="activeTopbarItem === 'profile'">
                        <li role="menuitem">
                            <button class="p-link" @click="logout">
                                <i class="pi pi-sign-out"></i>
                                <span>Sair</span>
                            </button>
                        </li>
                    </ul>
                </transition>
            </li>

            <li :class="[{ 'active-topmenuitem': activeTopbarItem === 'settings' }]" @click="$emit('topbar-item-click', { originalEvent: $event, item: 'settings' })">
                <button class="p-link">
                    <i class="topbar-icon pi pi-calendar"></i>
                    <span class="topbar-item-name">Notifications</span>
                </button>

                <transition name="layout-submenu-container">
                    <ul class="fadeInDown" v-show="activeTopbarItem === 'settings'">
                        <li role="menuitem">
                            <button class="p-link">
                                <i class="pi pi-tags"></i>
                                <span>Pending tasks</span>
                                <span class="topbar-submenuitem-badge">6</span>
                            </button>
                        </li>
                        <li role="menuitem">
                            <button class="p-link">
                                <i class="pi pi-calendar-plus"></i>
                                <span>Meeting today at 3pm</span>
                            </button>
                        </li>
                        <li role="menuitem">
                            <button class="p-link">
                                <i class="pi pi-download"></i>
                                <span>Download</span>
                            </button>
                        </li>
                        <li role="menuitem">
                            <button class="p-link">
                                <i class="pi pi-lock"></i>
                                <span>Book flight</span>
                            </button>
                        </li>
                    </ul>
                </transition>
            </li>

            <li :class="[{ 'active-topmenuitem': activeTopbarItem === 'messages' }]" @click="$emit('topbar-item-click', { originalEvent: $event, item: 'messages' })">
                <button class="p-link">
                    <i class="topbar-icon pi pi-inbox"></i>
                    <span class="topbar-item-name">Messages</span>
                    <span class="topbar-badge">8</span>
                </button>

                <transition name="layout-submenu-container">
                    <ul class="fadeInDown" v-show="activeTopbarItem === 'messages'">
                        <li role="menuitem">
                            <button class="topbar-message p-link">
                                <img src="/layout/images/avatar-john.png" alt="babylon-layout" />
                                <span>Give me a call</span>
                            </button>
                        </li>
                        <li role="menuitem">
                            <button class="topbar-message p-link">
                                <img src="/layout/images/avatar-julia.png" alt="babylon-layout" />
                                <span>Reports attached</span>
                            </button>
                        </li>
                        <li role="menuitem">
                            <button class="topbar-message p-link">
                                <img src="/layout/images/avatar-kevin.png" alt="babylon-layout" />
                                <span>About your invoice</span>
                            </button>
                        </li>
                        <li role="menuitem">
                            <button class="topbar-message p-link">
                                <img src="/layout/images/avatar-julia.png" alt="babylon-layout" />
                                <span>Meeting today</span>
                            </button>
                        </li>
                        <li role="menuitem">
                            <button class="topbar-message p-link">
                                <img src="/layout/images/avatar.png" alt="babylon-layout" />
                                <span>Out of office</span>
                            </button>
                        </li>
                    </ul>
                </transition>
            </li>

            <li :class="[{ 'active-topmenuitem': activeTopbarItem === 'notifications' }]" @click="$emit('topbar-item-click', { originalEvent: $event, item: 'notifications' })">
                <button class="p-link">
                    <i class="topbar-icon pi pi-cog"></i>
                    <span class="topbar-item-name">Settings</span>
                </button>

                <transition name="layout-submenu-container">
                    <ul class="fadeInDown" v-show="activeTopbarItem === 'notifications'">
                        <li role="menuitem">
                            <button class="p-link">
                                <i class="pi pi-pencil"></i>
                                <span>Change Theme</span>
                                <span class="topbar-submenuitem-badge">4</span>
                            </button>
                        </li>
                        <li role="menuitem">
                            <button class="p-link">
                                <i class="pi pi-star"></i>
                                <span>Favorites</span>
                            </button>
                        </li>
                        <li role="menuitem">
                            <button class="p-link">
                                <i class="pi pi-lock"></i>
                                <span>Lock Screen</span>
                                <span class="topbar-submenuitem-badge">2</span>
                            </button>
                        </li>
                        <li role="menuitem">
                            <button class="p-link">
                                <i class="pi pi-image"></i>
                                <span>Wallpaper</span>
                            </button>
                        </li>
                    </ul>
                </transition>
            </li>
        </ul>
    </div>
</template>

<script>
import { useAuth } from '@/composables/useAuth';

export default {
    props: {
        topbarMenuActive: Boolean,
        activeTopbarItem: String,
        profileMode: String,
        horizontal: Boolean,
    },
    setup() {
        const { user, logout: authLogout } = useAuth();
        return { user, authLogout };
    },
    computed: {
        userName() { return this.user?.name ?? 'Usuário'; },
        topbarItemsClass() {
            return ['topbar-menu fadeInDown', { 'topbar-menu-visible': this.topbarMenuActive }];
        },
    },
    methods: {
        onMenuButtonClick(event) { this.$emit('menubutton-click', event); },
        onTopbarMenuButtonClick(event) { this.$emit('topbar-menubutton-click', event); },
        goDashboard() { this.$router.push('/'); },
        async logout() {
            await this.authLogout();
            this.$router.push('/login');
        },
    },
};
</script>
