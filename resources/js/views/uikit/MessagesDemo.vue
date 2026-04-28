<template>
    <div class="grid">
        <div class="col-12 lg:col-6">
            <div class="card">
                <h5>Toast</h5>
                <Button @click="showSuccess()" label="Success" class="mr-2" severity="success" />
                <Button @click="showInfo()" label="Info" class="mr-2" severity="info" />
                <Button @click="showWarn()" label="Warn" class="mr-2" severity="warning" />
                <Button @click="showError()" label="Error" class="mr-2" severity="danger" />
            </div>
        </div>

        <div class="col-12 lg:col-6">
            <div class="card">
                <h5>Messages</h5>
                <Button label="Success" @click="addMessage('success')" class="mr-2" severity="success" />
                <Button label="Info" @click="addMessage('info')" class="mr-2" severity="info" />
                <Button label="Warn" @click="addMessage('warn')" class="mr-2" severity="warning" />
                <Button label="Error" @click="addMessage('error')" class="mr-2" severity="danger" />

                <transition-group name="p-message" tag="div">
                    <Message v-for="msg of message" :severity="msg.severity" :key="msg.content">{{ msg.content }}</Message>
                </transition-group>
            </div>
        </div>

        <div class="col-12 lg:col-8">
            <div class="card">
                <h5>Inline</h5>
                <div class="field grid">
                    <label for="username1" class="col-fixed w-9rem">Username</label>
                    <div class="col">
                        <InputText id="username1" v-model="username" :required="true" invalid class="mr-2"></InputText>
                        <InlineMessage>Username is required</InlineMessage>
                    </div>
                </div>
                <div class="field grid">
                    <label for="email" class="col-fixed w-9rem">Email</label>
                    <div class="col">
                        <InputText id="email" v-model="email" :required="true" invalid class="mr-2"></InputText>
                        <InlineMessage />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 lg:col-4">
            <div class="card">
                <h5>Help Text</h5>
                <div class="field p-fluid">
                    <label for="username2">Username</label>
                    <InputText id="username2" type="username" class="p-error" aria-describedby="username-help" />
                    <small id="username-help" class="p-error">Enter your username to reset your password.</small>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            message: [],
            username: null,
            email: null,
            count: 0,
        };
    },
    methods: {
        addMessage(type) {
            if (type === 'success') {
                this.message = [{ severity: 'success', detail: 'Success Message', content: 'Message sent', id: this.count++ }];
            } else if (type === 'info') {
                this.message = [{ severity: 'info', detail: 'Info Message', content: 'PrimeVue rocks', id: this.count++ }];
            } else if (type === 'warn') {
                this.message = [{ severity: 'warn', detail: 'Warn Message', content: 'There are unsaved changes', id: this.count++ }];
            } else if (type === 'error') {
                this.message = [{ severity: 'error', detail: 'Error Message', content: 'Validation failed', id: this.count++ }];
            }
        },
        showSuccess() {
            this.$toast.add({ severity: 'success', summary: 'Success Message', detail: 'Message sent', life: 3000 });
        },
        showInfo() {
            this.$toast.add({ severity: 'info', summary: 'Info Message', detail: 'PrimeVue rocks', life: 3000 });
        },
        showWarn() {
            this.$toast.add({ severity: 'warn', summary: 'Warn Message', detail: 'There are unsaved messages', life: 3000 });
        },
        showError() {
            this.$toast.add({ severity: 'error', summary: 'Error Message', detail: 'Validation failed', life: 3000 });
        },
    },
};
</script>

<style lang="scss" scoped>
.field > label {
    width: 125px;
}
.p-inputtext {
    margin-right: 0.5rem;
}
::v-deep(.p-button) {
    min-width: 8em;
}
::v-deep(.p-message) {
    margin-left: 0.25em;
}
</style>
